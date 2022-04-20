<?php
defined('BASEPATH') or exit('No direct script access allowed');
$check =  __dir__ ;
$str= preg_replace('/\W\w+\s*(\W*)$/', '$1', $check);
$str.'/twilio-web/src/Twilio/autoload.php';
use Twilio\Rest\Client;
use Twilio\Jwt\ClientToken;
use Twilio\TwiML\VoiceResponse;
use Carbon\Carbon;
class Call_logs extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('call_logs_model');
    }

    public function calculate_duration(){
        $posted_data = $this->input->post();
        $start_time = strtotime($posted_data['start_time']);
        $end_time = strtotime($posted_data['end_time']);
        $duration = $end_time - $start_time;
        if($duration < 0){
            echo '00:00:00';
        }else{
            $seconds = $duration;
            $H = floor($seconds / 3600);
            $i = ($seconds / 60) % 60;
            $s = $seconds % 60;
            echo sprintf("%02d:%02d:%02d", $H, $i, $s);
            //echo gmdate("H:i:s", $duration);
        }
        die();
    }

    /* List all call_logs */
    public function index()
    {
        //if (!has_permission('call_logs', '', 'access')) {
            //access_denied('call_logs');
        //}

        if ($this->input->is_ajax_request()) {
            $this->app->get_table_data(module_views_path('call_logs', 'table'));
        }
        $data['switch_grid'] = false;

        if ($this->session->userdata('cl_grid_view') == 'true') {
            $data['switch_grid'] = true;
        }

        $this->load->model('staff_model');
        $this->load->model('clients_model');
        $this->load->model('leads_model');

        $data['staffs'] = $this->staff_model->get();
        $data['leads'] = $this->leads_model->get();
        $data['clcustomers'] = $this->clients_model->get();
        $data['rel_types'] = $this->call_logs_model->get_rel_types();
        $data['cl_filter_status'] = [
            ['id' => '1', 'name' => 'Complete'],
            ['id' => '2', 'name' => 'Incomplete'],
        ];

        $data['title']     = _l('call_logs_tracking');
        //for modal data
        $data['bulk_sms_modal_title']     = _l('cl_bulk_sms_modal_title');
        $data['call_directions'] = $this->call_logs_model->get_call_directions();
        $data['owner']         = $this->staff_model->get(get_staff_user_id());
        $data['staff']         = $this->staff_model->get();
        //print_r($data); exit;

        if(isset($_GET['type']) && $_GET['type'] == 'visit'){
            $data['request_type'] = 'visit';
            $data['title'] = _l('visit_logs_tracking');
        }

        $this->app_scripts->add('mindmap-js','modules/call_logs/assets/js/call_logs.js');
        $this->load->view('manage', $data);
    }

    /* Prepare the table function to display the records in table format. */
    public function table($clientid = '')
    {
        $data['clientid'] = $clientid;
        $this->app->get_table_data(module_views_path('call_logs', 'table'), $data);
    }

    /* Prepare the table function to display the records in table format. */
    public function table_visit($clientid = '')
    {
        $data['clientid'] = $clientid;
        $this->app->get_table_data(module_views_path('call_logs', 'table_visit'), $data);
    }

    /* Get the data ready for grid view. */
    public function grid()
    {
        echo $this->load->view('call_logs/grid', [], true);
    }

    /* Make a relationship with client and customer tables. */
    public function call_log_relations($clientid, $customer_type)
    {
        $data['clientid'] = $clientid;
        $data['customer_type'] = $customer_type;

        $this->app->get_table_data(module_views_path('call_logs', 'call_log_relations'), $data);
    }

    /**
     * Task ajax large preview request modal
     * @param  mixed $id
     * @return mixed
     */
    public function get_call_log_data($id)
    {
        $call_log = $this->call_logs_model->get($id);

        if (!$call_log) {
            header('HTTP/1.0 404 Not Found');
            echo 'Call Log not found';
            die();
        }

        $data['rel_type'] = $this->call_logs_model->get_rel_types($call_log->rel_type);
        $data['call_direction'] = $this->call_logs_model->get_call_directions($call_log->call_direction);

        $data['call_log']  = $call_log;

        $html =  $this->load->view('view_call_log_template', $data, true);
        echo $html;
    }

    /* Call log function to handle create, view, edit views. */
    public function visit_log($id = '')
    {
        $POSTS_ARRAY = $this->input->post();
        $POSTS_ARRAY['opt_event_type'] = 'call';
        $POSTS_ARRAY['call_start_time'] = $POSTS_ARRAY['meeting_start_time'];
        $POSTS_ARRAY['call_end_time'] = $POSTS_ARRAY['meeting_end_time'];
        $POSTS_ARRAY['call_duration'] = $POSTS_ARRAY['meeting_duration'];
        $POSTS_ARRAY['is_completed']    = 1;
        $POSTS_ARRAY['is_important']    = 1;
        $POSTS_ARRAY['twilio_sms_response'] = 'n/a';
        

        if($this->input->post('has_task') == 1){
            $this->save_call_log_as_task($this->input->post(),'visit');

            //UNSETTING VARIABLES THAT NEEDS TO BE SAVED IN TASK
            if(isset($POSTS_ARRAY['has_task']) && $POSTS_ARRAY['has_task'] == 1){
                unset($POSTS_ARRAY['has_task']);
                unset($POSTS_ARRAY['name']);
                unset($POSTS_ARRAY['description']);
            }
        }

        unset($POSTS_ARRAY['meeting_end_time']);
        unset($POSTS_ARRAY['has_task']);

        if ($this->input->post()) {
            $this->load->model('misc_model');
            
            if ($id == '') {
                if (!has_permission('call_logs', '', 'create')) {
                    access_denied('call_logs');
                }
                $id = $this->call_logs_model->add($POSTS_ARRAY);
                if ($id) {
                    if($this->input->post('has_follow_up') == 1 && $this->input->post('is_completed') == 0) {
                        $params = [
                            'notify_by_email' => 1,
                            'date' => $this->input->post('follow_up_schedule'),
                            'description' => $this->input->post('call_summary'),
                            'rel_type' => $this->input->post('customer_type'),
                            'rel_id' => $this->input->post('clientid'),
                            'staff' => ((int)$this->input->post('call_with_staffid') > 0)?$this->input->post('call_with_staffid') :$this->input->post('staffid') ,
                        ];
                        $success = $this->misc_model->add_reminder($params, $this->input->post('clientid'));
                    }
                    set_alert('success', _l('added_successfully', _l('call_log')));
                    redirect(admin_url('call_logs?type=visit'));
                }
            } else {
                if (!has_permission('call_logs', '', 'edit')) {
                    access_denied('call_logs');
                }
                $success = $this->call_logs_model->update($this->input->post(), $id);
                if ($success) {
                    set_alert('success', _l('updated_successfully', _l('call_log')));
                }
                redirect($_SERVER['HTTP_REFERER']);
            }
        }
        if ($id == '') {
            $title = _l('add_new', _l('call_log_lowercase'));
        } else {
            $data['call_log']        = $this->call_logs_model->get($id);
            $data['cl_rel_type']        = $this->call_logs_model->get_rel_types($data['call_log']->rel_type);
            $title = _l('edit', _l('call_log_lowercase'));
        }

        $data['owner']         = $this->staff_model->get(get_staff_user_id());
        //$data['staff']         = $this->staff_model->get('',["staffid <> " => get_staff_user_id()]);
        $data['staff']         = $this->staff_model->get();
        $data['members'] = $this->staff_model->get('', ['is_not_staff' => 0, 'active'=>1]);
        $data['rel_types'] = $this->call_logs_model->get_rel_types();
        $data['call_directions'] = $this->call_logs_model->get_call_directions();

        $data['title']                 = $title;

        $this->load->view('visit_log', $data);
    }

    /* Call log function to handle create, view, edit views. */
    public function call_log($id = '')
    {
        $POSTS_ARRAY = $this->input->post();

        if($this->input->post('has_task') == 1){
            $task_id = $this->save_call_log_as_task($this->input->post(),'call');
            $POSTS_ARRAY['task_id'] = $task_id;

            //UNSETTING VARIABLES THAT NEEDS TO BE SAVED IN TASK
            if(isset($POSTS_ARRAY['has_task']) && $POSTS_ARRAY['has_task'] == 1){
                unset($POSTS_ARRAY['has_task']);
                unset($POSTS_ARRAY['name']);
                unset($POSTS_ARRAY['description']);
            }
        }

        if(isset($POSTS_ARRAY['meeting_end_time'])){
            unset($POSTS_ARRAY['meeting_end_time']);
        }

        unset($POSTS_ARRAY['has_task']);

        if ($this->input->post()) {
            $this->load->model('misc_model');
            
            if ($id == '') {
                if (!has_permission('call_logs', '', 'create')) {
                    access_denied('call_logs');
                }
                $id = $this->call_logs_model->add($POSTS_ARRAY);
                if ($id) {
                    if($this->input->post('has_follow_up') == 1 && $this->input->post('is_completed') == 0) {
                        $params = [
                            'notify_by_email' => 1,
                            'date' => $this->input->post('follow_up_schedule'),
                            'description' => $this->input->post('call_summary'),
                            'rel_type' => $this->input->post('customer_type'),
                            'rel_id' => $this->input->post('clientid'),
                            'staff' => ((int)$this->input->post('call_with_staffid') > 0)?$this->input->post('call_with_staffid') :$this->input->post('staffid') ,
                        ];
                        $success = $this->misc_model->add_reminder($params, $this->input->post('clientid'));
                    }
                    set_alert('success', _l('added_successfully', _l('call_log')));
                    redirect(admin_url('call_logs'));
                }
            } else {
                if (!has_permission('call_logs', '', 'edit')) {
                    access_denied('call_logs');
                }
                $success = $this->call_logs_model->update($this->input->post(), $id);
                if ($success) {
                    set_alert('success', _l('updated_successfully', _l('call_log')));
                }
                redirect($_SERVER['HTTP_REFERER']);
            }
        }
        if ($id == '') {
            $title = _l('add_new', _l('call_log_lowercase'));
        } else {
            $data['call_log']        = $this->call_logs_model->get($id);
            $data['cl_rel_type']        = $this->call_logs_model->get_rel_types($data['call_log']->rel_type);
            $title = _l('edit', _l('call_log_lowercase'));
        }

        $data['owner']         = $this->staff_model->get(get_staff_user_id());
        //$data['staff']         = $this->staff_model->get('',["staffid <> " => get_staff_user_id()]);
        $data['staff']         = $this->staff_model->get();
        $data['members'] = $this->staff_model->get('', ['is_not_staff' => 0, 'active'=>1]);
        $data['rel_types'] = $this->call_logs_model->get_rel_types();
        $data['call_directions'] = $this->call_logs_model->get_call_directions();

        $data['title']                 = $title;

        $this->load->view('call_log', $data);
    }

    /** */  
    public function save_call_log_as_task($post,$type){
        $data = [];

        if($type == 'call'){
            $data['startdate']             = substr($post['call_start_time'],0,10);
            $data['duedate']               = substr($post['call_end_time'],0,10);
        }else if($type == 'visit'){
            $data['startdate']             = substr($post['meeting_start_time'],0,10);
            $data['duedate']               = substr($post['meeting_start_time'],0,10);
        }

        $data['name']                  = $post['call_purpose'];
        $data['description']           = html_purify($post['call_summary'],false);
        $data['rel_type']              = $post['rel_type'];

        $insert_id = $this->tasks_model->add($data);
        return $insert_id;
    }
    
    /* Call log function to handle preview views. */
    public function preview($id = 0)
    {
        //if (!has_permission('call_logs', '', 'view')) {
            //access_denied('call_logs');
        //}
        $data['call_log']        = $this->call_logs_model->get($id);

        if (!$data['call_log']) {
            blank_page(_l('cl_not_found'), 'danger');
        }

        $data['rel_types'] = $this->call_logs_model->get_rel_types();
        $data['call_directions'] = $this->call_logs_model->get_call_directions();
        $data['owner']         = $this->staff_model->get(get_staff_user_id());
        $data['staff']         = $this->staff_model->get('',["staffid <> " => get_staff_user_id()]);
        $data['members'] = $this->staff_model->get('', ['is_not_staff' => 0, 'active'=>1]);
        $data['cl_rel_type']        = $this->call_logs_model->get_rel_types($data['call_log']->rel_type);

        $data['title']                 = _l('preview_call_log');

        $this->load->view('preview', $data);
    }

    /* Delete from database */
    public function delete($id)
    {
        if (!has_permission('call_logs', '', 'delete')) {
            access_denied('call_logs');
        }
        if (!$id) {
            redirect(admin_url('call_logs'));
        }
        $response = $this->call_logs_model->delete($id);
        if ($response == true) {
            set_alert('success', _l('deleted', _l('call_log')));
        } else {
            set_alert('warning', _l('problem_deleting', _l('call_log_lowercase')));
        }
        redirect(admin_url('call_logs'));
    }

    /* get contact data from customer id */
    public function get_contact()
    {
        $posted_data = $this->input->post();
        if($posted_data){
            $query = "SELECT * FROM ".db_prefix()."contacts WHERE userid = ".$posted_data['clientid'];
            $query = $this->db->query($query);
            $result = $query->result_array();
            $i = 0;
            foreach ($result as $res) {
                $result[$i]['name'] = $res['email'].' - '.$res['firstname'].' '.$res['lastname'];
                $i++;
            }
            die(json_encode($result));
        }
        die;
    }

    /* Get the relationship of Types. */
    public function get_relation_data()
    {
        if ($this->input->post()) {
            $type = $this->input->post('type');
            $data = get_relation_data_for_cl($type);
            if ($this->input->post('rel_id')) {
                $rel_id = $this->input->post('rel_id');
            } else {
                $rel_id = '';
            }

            $relOptions = init_relation_options($data, $type, $rel_id);
            echo json_encode($relOptions);
            die;
        }
    }
    /* Prepare Data for the Overview tab/graphs. */
    public function overview($staffid = ''){
        $now = Carbon::now();
        if($staffid == ''){
            $staffid = get_staff_user_id();
        }
        $weekStartDate = $now->startOfWeek()->format('Y-m-d');
        $weekEndDate = $now->endOfWeek()->format('Y-m-d');

        $start_of_month = Carbon::now()->startOfMonth()->format('Y-m-d');
        $end_of_month = Carbon::now()->endOfMonth()->format('Y-m-d');

        $data['daily_count']        = $this->call_logs_model->count_inbound_outbound_calls(Carbon::now()->format("Y-m-d"), Carbon::now()->format("Y-m-d"), $staffid);
        $data['week_count']         = $this->call_logs_model->count_inbound_outbound_calls($weekStartDate, $weekEndDate, $staffid);
        $data['month_count']        = $this->call_logs_model->count_inbound_outbound_calls($start_of_month, $end_of_month, $staffid);

        $data['daily_sms']          = $this->call_logs_model->count_all_sms(Carbon::now()->format("Y-m-d"), Carbon::now()->format("Y-m-d"), $staffid);
        $data['week_sms']          = $this->call_logs_model->count_all_sms($weekStartDate, $weekEndDate, $staffid);
        $data['month_sms']          = $this->call_logs_model->count_all_sms($start_of_month, $end_of_month, $staffid);

        $data['weekly_chart_Date']  = json_encode($this->call_logs_model->get_inbound_outbound_report($weekStartDate, $weekEndDate, $staffid));
        $data['monthly_chart_Date'] = json_encode($this->call_logs_model->get_inbound_outbound_report($start_of_month, $end_of_month, $staffid));

        $this->load->model('staff_model');
        $data['staffs'] = $this->staff_model->get();
        $data['staffid'] = $staffid;

        $this->load->view('gantt', $data);
    }

    /* Switch functionality between list and grid view. */
    public function switch_grid($set = 0, $manual = false)
    {
        if ($set == 1) {
            $set = 'false';
        } else {
            $set = 'true';
        }

        $this->session->set_userdata([
            'cl_grid_view' => $set,
        ]);
        if ($manual == false) {
            redirect($_SERVER['HTTP_REFERER']);
        }
    }

    /*
     * manage types section
     */
    public function cl_types()
    {
        if (!is_admin()) {
            access_denied('Call logs Type');
        }
        if ($this->input->is_ajax_request()) {
            $this->app->get_table_data(module_views_path('call_logs', 'call_types/cl_types_table'));
        }
        $data['title'] = _l('call_log_type');
        $this->load->view('call_types/cl_types_manage', $data);
    }

    public function cl_type()
    {
        if (!is_admin() && get_option('staff_members_create_inline_cl_types') == '0') {
            access_denied('call_logs');
        }
        if ($this->input->post()) {
            if (!$this->input->post('id')) {
                $id = $this->call_logs_model->add_cl_type($this->input->post());
                echo json_encode([
                    'success' => $id ? true : false,
                    'message' => $id ? _l('added_successfully', _l('cl_type')) : '',
                    'id'      => $id,
                    'name'    => $this->input->post('name'),
                ]);
            } else {
                $data = $this->input->post();
                $id   = $data['id'];
                unset($data['id']);
                $success = $this->call_logs_model->update_cl_type($data, $id);
                $message = _l('updated_successfully', _l('cl_type'));
                echo json_encode(['success' => $success, 'message' => $message]);
            }
        }
    }

    public function delete_type($id)
    {
        if (!$id) {
            redirect(admin_url('call_logs'));
        }
        $response = $this->call_logs_model->delete_cl_type($id);
        if (is_array($response) && isset($response['referenced'])) {
            set_alert('warning', _l('is_referenced', _l('call_log_type')));
        } elseif ($response == true) {
            set_alert('success', _l('deleted', _l('call_log_type')));
        } else {
            set_alert('warning', _l('problem_deleting', _l('call_log_type')));
        }
        redirect(admin_url('call_logs/cl_types'));
    }

    /*
     * end manager types section
     */


    /*
     * manage call directions
     */
    public function call_directions()
    {
        if (!is_admin()) {
            access_denied('Call Type');
        }
        if ($this->input->is_ajax_request()) {
            $this->app->get_table_data(module_views_path('call_logs', 'call_types/call_direction_table'));
        }
        $data['title'] = _l('call_log_direction');
        $this->load->view('call_types/call_direction_manage', $data);
    }

    public function call_direction()
    {
        if (!is_admin() && get_option('staff_members_create_inline_call_direction') == '0') {
            access_denied('call_logs');
        }
        if ($this->input->post()) {
            if (!$this->input->post('id')) {
                $id = $this->call_logs_model->add_call_direction($this->input->post());
                echo json_encode([
                    'success' => $id ? true : false,
                    'message' => $id ? _l('added_successfully', _l('call_log_direction')) : '',
                    'id'      => $id,
                    'name'    => $this->input->post('name'),
                ]);
            } else {
                $data = $this->input->post();
                $id   = $data['id'];
                unset($data['id']);
                $success = $this->call_logs_model->update_call_direction($data, $id);
                $message = _l('updated_successfully', _l('call_log_direction'));
                echo json_encode(['success' => $success, 'message' => $message]);
            }
        }
    }


    public function delete_call_direction($id)
    {
        if (!$id) {
            redirect(admin_url('call_logs'));
        }
        $response = $this->call_logs_model->delete_call_direction($id);
        if (is_array($response) && isset($response['referenced'])) {
            set_alert('warning', _l('is_referenced', _l('call_log_direction')));
        } elseif ($response == true) {
            set_alert('success', _l('deleted', _l('call_log_direction')));
        } else {
            set_alert('warning', _l('problem_deleting', _l('call_log_direction')));
        }
        redirect(admin_url('call_logs/call_directions'));
    }
    public function check_call()
    {
       // print_r($this->input->post()); exit;
        if(isset($_POST['userphone']))
        {
         $this->load->helper('call_logs_helper');
         $result = twilio_setting(); 
             // print_r($result); exit;
            // $result['twilio_number'] = ' +12054190964';
            // Where to make a voice call (your cell phone?)
         $to_number = $_POST['userphone']; 
            //$to_number = '+91 88607 67651'; 
         $client = new Client($result['account_sid'], $result['auth_token']);
            //$client = new Client('ACfef2526c96f877698cb713d27dffe799', 'b0498d2d3c5c717267e09767e2a7e89e');
         $call = $client->account->calls->create(
            $to_number,
            $result['twilio_number'],
            [
                "method" => "GET",
                /*"statusCallback" => "http://localhost/shopini_crm/testing.php",*/
                "statusCallback" => "http://localhost/prontoinvoices/testing.php",
                "statusCallbackEvent" => ["initiated","ringing","answered","complete"],
                "statusCallbackMethod" => "POST",
                "url" => "http://demo.twilio.com/docs/voice.xml"
            ]
        );
            //print_r($call); exit;
         if($call->sid)
         {
            echo  'ok';
        }
        else
        {
            echo 'no';
        }
        exit;
    }
    $this->load->view('checkcall');
}
public function get_lead_info()
{
    $this->load->model('Leads_model');
    $leadid = $_POST['lead_id'];
    $results = $this->Leads_model->get($leadid);
    echo $results->phonenumber;
}

public function get_contact_info()
{
    $this->load->model('Clients_model');
    $contactid = $_POST['contactid'];
    $results = $this->Clients_model->get_contact($contactid);
    echo $results->phonenumber;
}
public function newToken()
{
    $result = twilio_setting(); 
    //print_r($result); exit;
    $client = new ClientToken($result['account_sid'], $result['auth_token']);
       // print_r($client); exit;
    /*$forPage = $request->input('forPage');*/
        //$applicationSid = config('services.twilio')[$result['account_sid']];
    $client->allowClientOutgoing('APd60421a62b83f992b681405d4387f0b2');

        /*if ($forPage === route('dashboard', [], false)) {
            $client->allowClientIncoming('support_agent');
        } else {
            $client->allowClientIncoming('customer');
        }*/

        $token = $client->generateToken();
        echo json_encode(['token' => $token]);
    }
    public function newCall()
    {
     $result = twilio_setting(); 
     $response = new VoiceResponse();
     $callerIdNumber =  $result['twilio_number'];

     $dial = $response->dial(null, ['callerId'=>$callerIdNumber]);
     $phoneNumberToDial = $this->input->post('phoneNumber');

     if (isset($phoneNumberToDial)) {
        $dial->number($phoneNumberToDial);
    } else {
        $dial->client('support_agent');
    }

    return $response;
}
public function check_sms()
{
   print_r($this->input->post()); exit;
   if(isset($_POST['userphone']))
   {
     $this->load->helper('call_logs_helper');
     $result = twilio_setting(); 
             // print_r($result); exit;
            // $result['twilio_number'] = ' +12054190964';
            // Where to make a voice call (your cell phone?)
     $to_number = $_POST['userphone']; 
            //$to_number = '+91 88607 67651'; 
     $client = new Client($result['account_sid'], $result['auth_token']);
            //$client = new Client('ACfef2526c96f877698cb713d27dffe799', 'b0498d2d3c5c717267e09767e2a7e89e');
     $call = $client->account->calls->create(
        $to_number,
        $result['twilio_number'],
        [
            "method" => "GET",
            /*"statusCallback" => "http://localhost/shopini_crm/testing.php",*/
            "statusCallback" => "http://localhost/prontoinvoices/testing.php",
            "statusCallbackEvent" => ["initiated","ringing","answered","complete"],
            "statusCallbackMethod" => "POST",
            "url" => "http://demo.twilio.com/docs/voice.xml"
        ]
    );
            //print_r($call); exit;
     if($call->sid)
     {
        echo  'ok';
    }
    else
    {
        echo 'no';
    }
    exit;
}
$this->load->view('checkcall');
}
public function udpate_sms_response($id='')
{
    if($this->input->post()){
        $post_data['twilio_sms_response'] = $this->input->post('SmsStatus');
        return $this->call_logs_model->udpate_sms_response($post_data, $id);
    }else{
        echo "403 forbidden access!";
    }
}
public function get_customer_data()
{
    $html = '';
    $this->load->model('Clients_model');
    $result = $this->Clients_model->get_contact($this->input->post('contactid'));
    $html .= ' <div class="col-md-4 col-xs-12 lead-information-col"><div class="lead-info-heading"><h4 class="no-margin font-medium-xs bold">Customer Information</h4></div>';
    $html .= '<p class="text-muted lead-field-heading no-mtop">Name</p>';
    $html .= '<p class="bold font-medium-xs lead-name">'.$result->firstname.' '.$result->lastname.'</p>';
    $html .= '<p class="text-muted lead-field-heading">Position</p>';
    $html .= '<p class="bold font-medium-xs">-</p><p class="text-muted lead-field-heading">Email Address</p>';
    $html .= '<p class="bold font-medium-xs"><a href="mailto:'.$result->email.'">'.$result->email.'</a></p>';
    $html .= '<p class="text-muted lead-field-heading">Website</p>';
    $html .= '<p class="bold font-medium-xs">-</p>';
    $html .= '<p class="text-muted lead-field-heading">Phone</p>';
    $html .= '<p class="bold font-medium-xs"><a href="tel:'.$result->phonenumber.'">'.$result->phonenumber.'</a></p>';
    $html .= '<p class="text-muted lead-field-heading">Company</p>';
    $html .= ' <p class="bold font-medium-xs">-</p>';
    $html .= '<p class="text-muted lead-field-heading">Address</p>';
    $html .= '<p class="bold font-medium-xs">-</p>';
    $html .= '<p class="text-muted lead-field-heading">City</p>';
    $html .= '<p class="bold font-medium-xs">-</p>';
    $html .= '<p class="text-muted lead-field-heading">State</p>';
    $html .= '<p class="bold font-medium-xs">-</p>';
    $html .= '<p class="text-muted lead-field-heading">Country</p>';
    $html .= '<p class="bold font-medium-xs">-</p>';
    $html .= '<p class="text-muted lead-field-heading">Zip Code</p>';
    $html .= '<p class="bold font-medium-xs">-</p></div>';
    echo $html;
}

}
