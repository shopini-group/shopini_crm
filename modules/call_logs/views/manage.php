<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$isGridView = 0;
if ($this->session->has_userdata('cl_grid_view') && $this->session->userdata('cl_grid_view') == 'true') {
    $isGridView = 1;
}
?>
<?php init_head(); ?>
<?php
$customer_type = '';
$clientid = '';
if(isset($call_log) || ($this->input->get('clientid') && $this->input->get('customer_type'))){
    if($this->input->get('clientid')){
        $clientid = $this->input->get('clientid');
        $customer_type = $this->input->get('customer_type');
    } else {
        $clientid = $call_log->clientid;
        $customer_type = $call_log->customer_type;
    }
}

$rel_type = '';
$rel_id = '';
if(isset($call_log) || ($this->input->get('rel_id') && $this->input->get('rel_type'))){
    if($this->input->get('rel_id')){
        $rel_id = $this->input->get('rel_id');
        $rel_type = $this->input->get('rel_type');
    } else {
        $rel_id = $call_log->rel_id;
        $rel_type = (isset($cl_rel_type))?$cl_rel_type->key:$call_log->rel_type;
    }
}

$contactid = '';
if(isset($call_log)){
   $contactid = $call_log->contactid;
}

?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body">
                        <div class="_buttons">
                            <?php if(has_permission('call_logs','','create')){
                                if(isset($request_type) && $request_type == 'visit'){?>
                                    <a href="<?php echo admin_url('call_logs/visit_log'); ?>" class="btn btn-info pull-left display-block mright5"><i class="fa fa-phone menu-icon"></i> 
                                    <?php echo _l('new_visit_log'); ?></a>
                                <?php }else{?>
                                    <a href="<?php echo admin_url('call_logs/call_log'); ?>" class="btn btn-info pull-left display-block mright5"><i class="fa fa-phone menu-icon"></i> 
                                    <?php echo _l('new_call_log'); ?></a>
                                    <a href="#send_bulk_sms_modal" class="btn btn-default" data-toggle="modal">
                                <i class="fa fa-envelope"></i>
                                <?php echo _l('cl_bulk_sms_modal_title'); ?>
                            </a>
                            <a href="<?php echo admin_url('call_logs/overview'); ?>" data-toggle="tooltip" title="<?php echo _l('cl_gantt_overview'); ?>" class="btn btn-default"><i class="fa fa-bar-chart" aria-hidden="true"></i> <?php echo _l('cl_overview'); ?></a>

                            <a href="<?php echo admin_url('call_logs/switch_grid/'.$switch_grid); ?>" class="btn btn-default hidden-xs">
                                <?php if($switch_grid == 1){ echo _l('cl_switch_to_list_view');}else{echo _l('cl_switch_to_grid_view');}; ?>
                            </a>
                            <?php }
                            } ?>

                            <div class="visible-xs">
                                <div class="clearfix"></div>
                            </div>
                        </div>
                        <div class="clearfix"></div>
                        <hr class="hr-panel-heading" />

                        <div class="clearfix mtop20"></div>
                        <div class="row" id="call-logs-table">
                            <?php if($isGridView ==0){ ?>
                                <div class="col-md-12">
                                    <div class="row">
                                        <div class="col-md-12">
                                            <p class="bold"><?php echo _l('filter_by'); ?></p>
                                        </div>
                                        <div class="col-md-2 cl-filter-column">
                                            <?php echo render_select('view_assigned',$staffs,array('staffid',array('firstname','lastname')),'','',array('data-width'=>'100%','data-none-selected-text'=>_l('cl_filter_staff')),array(),'no-mbot'); ?>
                                        </div>
                                        <?php if(!isset($request_type) && $request_type != 'visit'){?>
                                        <div class="col-md-2 cl-filter-column">
                                            <?php echo render_select('view_by_rel_type',$rel_types,array('id',array('name')),'','',array('data-width'=>'100%','data-none-selected-text'=>_l('cl_type')),array(),'no-mbot'); ?>
                                        </div>
                                        <?php }?>
                                        <div class="col-md-2 cl-filter-column">
                                            <?php echo render_select('view_by_lead',$leads,array('id',array('name')),'','',array('data-width'=>'100%','data-none-selected-text'=>_l('cl_lead')),array(),'no-mbot'); ?>
                                        </div>
                                        <div class="col-md-2 cl-filter-column">
                                            <?php echo render_select('view_by_customer',$clcustomers,array('userid',array('company')),'','',array('data-width'=>'100%','data-none-selected-text'=>_l('cl_customer')),array(),'no-mbot'); ?>
                                        </div>
                                        <?php if(!isset($request_type) && $request_type != 'visit'){?>
                                        <div class="col-md-2 cl-filter-column">
                                            <?php echo render_select('view_by_status',$cl_filter_status,array('id',array('name')),'','',array('data-width'=>'100%','data-none-selected-text'=>_l('cl_filter_status')),array(),'no-mbot'); ?>
                                        </div>
                                        <?php }?>
                                        <!-- <div class="col-md-2 cl-filter-column">
                                            <a href="#send_sms_modal" class="btn btn-block btn-default" data-toggle="modal">
                                                <i class="fa fa-envelope"></i>
                                                Send SMS
                                            </a>
                                        </div> -->
                                    </div>
                                </div>
                                <div class="clearfix"></div>
                                <hr class="hr-panel-heading" />
                            <?php } ?>

                            <div class="col-md-12">
                                <?php if($this->session->has_userdata('cl_grid_view') && $this->session->userdata('cl_grid_view') == 'true') { ?>
                                    <div class="grid-tab" id="grid-tab">
                                        <div class="row">
                                            <div id="cl-grid-view" class="container-fluid">

                                            </div>
                                        </div>
                                    </div>
                                <?php } else {
                                    if(isset($request_type) && $request_type == 'visit'){
                                        render_datatable(array(
                                            _l('cl_type'),
                                            _l('cl_purpose_of_call'),
                                            _l('cl_visitor'),
                                            _l('cl_contact'),
                                            _l('cl_meeting_start_time'),
                                            _l('cl_meeting_duration'),
                                        ),'call_logs');
                                    } else{ render_datatable(array(
                                        _l('cl_type'),
                                        _l('cl_purpose_of_call'),
                                        _l('cl_task_name'),
                                        _l('cl_caller'),
                                        _l('cl_contact'),
                                        _l('cl_start_time'),
                                        _l('cl_end_time'),
                                        _l('cl_duration'),
                                        _l('cl_call_follow_up'),
                                        _l('cl_is_important'),
                                        _l('cl_is_completed'),
                                        _l('cl_opt_event_type'),
                                        _l('cl_twilio_sms_response'),
                                    ),'call_logs'); ?>
                                <?php }
                            } ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Call Log Modal-->
<div class="modal fade call_log-modal" id="call_log-modal" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel">
    <div class="modal-dialog modal-lg">
        <div class="modal-content data">

        </div>
    </div>
</div>

<!-- Send SMS Modal -->
<!-- <div class="modal fade" id="send_sms_modal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="loader" style="width: 100%;
                height: 100%;
                position: absolute;
                left: 0;
                top: 0;
                display: none;
                background: #ffffffa1;"></div>
                <div class="form-content">
                    <?php echo form_open_multipart($this->uri->uri_string().'/SendSMS/send', array('id'=>'form_sms')) ;?>
                    <div class="form-group">
                        <label for="">Phone 1 </label>
                        <input type="text" name="phone_number[]" value="" class="form-control">
                    </div>

                    <div class="form-group">
                        <label for="">Phone 1 </label>
                        <input type="text" name="phone_number[]" value="" class="form-control">
                    </div>

                    <div class="form-group">
                        <label for="message">Message</label>
                        <textarea name="message" id="message" rows="4" class="form-control"></textarea>
                    </div>

                    <button class="btn btn-primary">Send</button>
                </form>
            </div>
            <div class="report-content"></div>
        </div>
    </div>
</div>
</div> -->

<div class="modal fade" id="send_bulk_sms_modal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title"><?= _l('cl_bulk_sms_modal_title') ?></h3>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="margin-top: -40px;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
              <div class="row">
                <?php echo form_open_multipart($this->uri->uri_string().'/call_log',array('id'=>'bulk_sms-form')) ;?>
                <div class="col-md-6">
                    <div class="panel_s">
                        <div class="panel-body">
                            <h4 class="no-margin">Add new</h4>
                            <hr class="hr-panel-heading">
                            <div class="form-group select-placeholder">
                                <input type="hidden" name="opt_event_type" value="bulk sms">
                                <label for="customer_type" class="control-label"> <?php echo _l('cl_related'); ?></label>
                                <select name="customer_type" id="customer_type" class="selectpicker" data-width="100%" data-none-selected-text="<?php echo _l('dropdown_non_selected_tex'); ?>">
                                    <option value=""></option>
                                    <option value="lead" <?php if((isset($call_log) && $call_log->customer_type == 'lead') || $this->input->get('customer_type')){if($customer_type == 'lead'){echo 'selected';}} ?>><?php echo _l('cl_lead'); ?></option>
                                    <option value="customer" <?php if((isset($call_log) &&  $call_log->customer_type == 'customer') || $this->input->get('customer_type')){if($customer_type == 'customer'){echo 'selected';}} ?>><?php echo _l('cl_customer'); ?></option>
                                </select>
                            </div>
                            <div class="form-group select-placeholder<?php if($clientid == ''){echo ' hide';} ?> " id="clientid_wrapper">
                                <label for="clientid"><span class="clientid_label"></span></label>
                                <div id="clientid_select">
                                    <select name="clientid" id="clientid" class="ajax-search" data-width="100%" data-live-search="true" data-none-selected-text="<?php echo _l('dropdown_non_selected_tex'); ?>" onchange="myFunction()" multiple>
                                        <?php if($clientid != '' && $customer_type != ''){
                                            $rel_data = get_relation_data($customer_type,$clientid);
                                            $rel_val = get_relation_values($rel_data,$customer_type);
                                            echo '<option value="'.$rel_val['id'].'" selected>'.$rel_val['name'].'</option>';
                                        } ?>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group select-placeholder <?php if($contactid == ''){echo ' hide';} ?> " id="contactid_wrapper">
                                <label for="contactid"><span class="contactid_label">Contact</span></label>
                                <div id="contactid_select">
                                    <select name="contactid" id="contactid" class="ajax-search" data-width="100%" data-live-search="true" data-none-selected-text="<?php echo _l('dropdown_non_selected_tex'); ?>" onchange="myFunction2()" multiple>
                                        <?php
                                        if($contactid != ''){
                                            echo '<option value="'.$call_log->contactid.'" selected>'.$call_log->contact_name.' - '.$call_log->contact_email.'</option>';
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>

                            <?php
                            $selected = (isset($call_log) ? $call_log->rel_type : '');
                            if(is_admin() || get_option('staff_members_create_inline_cl_types') == '1'){
                                echo render_select_with_input_group('rel_type',$rel_types,array('id','name'),'cl_type',$selected,'<a href="#" onclick="new_cl_type();return false;"><i class="fa fa-plus"></i></a>');
                            } else {
                                echo render_select('rel_type',$rel_types,array('id','name'),'cl_type',$selected);
                            } ?>
                            <div class="form-group select-placeholder<?php if($rel_type != 'proposal' && $rel_type != 'estimate'){echo ' hide';} ?> " id="rel_id_wrapper">
                                <label for="rel_id"><span class="rel_id_label"></span></label>
                                <div id="rel_id_select">
                                    <select name="rel_id" id="rel_id" class="ajax-search" data-width="100%" data-live-search="true" data-none-selected-text="<?php echo _l('dropdown_non_selected_tex'); ?>">
                                        <?php if($rel_id != '' && $rel_type != ''){
                                            $rel_data = get_relation_data($rel_type,$rel_id);
                                            $rel_val = get_relation_values($rel_data,$rel_type);
                                            echo '<option value="'.$rel_val['id'].'" selected>'.$rel_val['name'].'</option>';
                                        } ?>
                                    </select>
                                </div>
                            </div>
                            <?php
                            $selected = (isset($call_log) ? $call_log->call_direction : '');
                            if(is_admin() || get_option('staff_members_create_inline_call_direction') == '1'){
                                echo render_select_with_input_group('call_direction',$call_directions,array('id','name'),'sms_log_direction',$selected,'<a href="#" onclick="new_call_direction();return false;"><i class="fa fa-plus"></i></a>');
                            } else {
                                echo render_select('call_direction',$call_directions,array('id','name'),'sms_log_direction',$selected);
                            }
                            ?>

                            <?php $value = (isset($call_log) ? $call_log->call_purpose : ''); ?>
                            <?php echo render_input('call_purpose','sms_purpose',$value); ?>

                            <?php $value = (isset($call_log) ? $call_log->call_summary : ''); ?>
                            <?php echo render_textarea('call_summary','sms_log_add_edit_call_summary',$value,array('rows'=>4),array()); ?>
                            <div class="form-group follow_up_wrapper" app-field-wrapper="has_follow_up">
                                <div class="">
                                    <span><?php echo _l('cl_follow_up_requried'); ?></span>
                                    <div class="radio radio-primary radio-inline">
                                        <input type="radio" value="1" id="has_follow_1" name="has_follow_up" <?php if(isset($call_log) && $call_log->has_follow_up == 1){echo 'checked';} ?>>
                                        <label for="has_follow_1"><?php echo _l('cl_follow_up_yes'); ?></label>
                                    </div>
                                    <div class="radio radio-primary radio-inline">
                                        <input type="radio" value="0" id="has_follow_0" name="has_follow_up" <?php if(isset($call_log) && $call_log->has_follow_up == 0){echo 'checked';}else if(!isset($call_log)){echo'checked';} ?>>
                                        <label for="has_follow_0"><?php echo _l('cl_follow_up_no'); ?></label>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group followup-schedule <?php if(!isset($call_log) || $call_log->has_follow_up == 0){echo 'hide';}?>">
                                <?php $value = ( (isset($call_log) && $call_log->follow_up_schedule!='') ? _d($call_log->follow_up_schedule) : _d(date('Y-m-d H:i'))) ?>
                                <?php echo render_datetime_input('follow_up_schedule','cl_follow_up_schedule',$value, ['readonly' => 'readonly']); ?>
                            </div>  
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="panel_s">
                        <div class="panel-body ">
                            <div class="row">
                                <div class="col-lg-6">
                                   <h4 class="no-margin "><?php echo _l('advanced_options'); ?></h4>
                               </div>
                           </div>
                           <hr class="hr-panel-heading" />
                           <div class="row">

                            <div id="sms-input">
                                <div class="col-md-12">
                                    <?php echo render_textarea('sms_content','write_your_sms_here','',array(),array(),'','form-control'); ?>
                                    <span id="rchars_limit">160</span>/<span id="user_entered">0</span>
                                    <div class="row">
                                     <div class="col-lg-8">
                                        <input type="hidden" name="twilio_sms_response" value="n/a">
                                        <p id="sms_message" style="display: none;"></p>
                                    </div>
                                    <div class="col-lg-4">
                                        <div class="text-right">
                                         <button type="button" class="btn btn-sm btn-success" id="sms-send-btn" onclick="sendsmsnew()"><span><i class="fa fa-envelope" style="padding-right: 3px;"></i></span>Send</button>
                                     </div>

                                 </div>
                             </div>

                         </div>
                     </div>

                     <div class="col-md-12 start-calendar">
                        <?php $value = (isset($call_log) ? _d($call_log->call_start_time) : _d(date('Y-m-d H:i'))) ?>
                        <?php echo render_datetime_input('call_start_time','cl_sms_start_time',$value); ?>
                    </div>
                    <div class="col-md-12">
                        <?php $value = (isset($call_log) ? _d($call_log->call_end_time) : _d(date('Y-m-d H:i'))) ?>
                        <?php echo render_datetime_input('call_end_time','cl_sms_end_time',$value); ?>
                    </div>
                    <div class="col-md-12" id="cl_call_duration_div" style="display: none;">
                        <?php $value = (isset($call_log) ? $call_log->call_duration : '') ?>
                        <?php echo render_input('call_duration','cl_call_duration',$value, 'text', ["readonly" => "readonly"]); ?>
                    </div>
                    <div class="col-md-12">
                        <?php echo render_input('staffid','', get_staff_user_id(), 'hidden'); ?>
                        <?php echo render_input('staff_email','cl_call_owner', $owner->firstname.' '.$owner->lastname, 'text', ['disabled' => 'disabled']); ?>
                        <?php
                        $i = 0;
                        $selected = '';
                        foreach($staff as $member){
                            if($member['staffid'] == get_staff_user_id()) {continue;}
                            if(isset($call_log)){
                                if($call_log->call_with_staffid == $member['staffid']) {
                                    $selected = $member['staffid'];
                                }
                            }
                            $i++;
                        }
                        echo render_select('call_with_staffid',$staff,array('staffid',array('firstname','lastname')),'cl_call_with_staff',$selected);
                        ?>
                    </div>

                    <div class="col-md-12">
                        <div class="">
                            <span><?php echo _l('cl_call_log_completed'); ?></span>
                            <div class="radio radio-primary radio-inline">
                                <input type="radio" value="1" id="is_completed_1" name="is_completed" <?php if(isset($call_log) && $call_log->is_completed == 1){echo 'checked';} ?>>
                                <label for="is_completed_1"><?php echo _l('cl_follow_up_yes'); ?></label>
                            </div>
                            <div class="radio radio-primary radio-inline">
                                <input type="radio" value="0" id="is_completed_0" name="is_completed" <?php if(isset($call_log) && $call_log->is_completed == 0){echo 'checked';}else if(!isset($call_log)){echo'checked';} ?>>
                                <label for="is_completed_0"><?php echo _l('cl_follow_up_no'); ?></label>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-12">&nbsp;</div>
                    <div class="col-md-12">
                        <div class="">
                            <span><?php echo _l('cl_call_log_important'); ?>&nbsp;&nbsp;</span>
                            <div class="radio radio-primary radio-inline">
                                <input type="radio" value="1" id="is_important_1" name="is_important" <?php if(isset($call_log) && $call_log->is_important == 1){echo 'checked';} ?>>
                                <label for="is_important_1"><?php echo _l('cl_follow_up_yes'); ?></label>
                            </div>
                            <div class="radio radio-primary radio-inline">
                                <input type="radio" value="0" id="is_important_0" name="is_important" <?php if(isset($call_log) && $call_log->is_important == 0){echo 'checked';}else if(!isset($call_log)){echo'checked';} ?>>
                                <label for="is_important_0"><?php echo _l('cl_follow_up_no'); ?></label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <input type="hidden" name="lead_id" id="lead_id">
    <input type="hidden" id="customer_id">
    <?php echo form_close(); ?>
</div>
<div class="btn-bottom-pusher"></div>
</div>

<div class="modal-footer">
    <button type="button" class="btn btn-info save-cl"><?php echo _l('submit'); ?></button>
    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
</div>
</div>
</div>
</div>
<div class="modal fade lead-modal in" id="customer-info-modal" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-lg">
    <div class="modal-content data">
        <div class="modal-header">
         <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
         <h4 class="modal-title">Profile</h4>
     </div>
     <div class="modal-body">
         <div class="ribbon success"><span>Customer</span></div>
         <div class="row">
           <div class="col-md-12">
               <!-- Tab panes -->
               <div class="tab-content">
                  <!-- from leads modal -->
                  <div role="tabpanel" class="tab-pane active" id="tab_lead_profile">
                   <div class="lead-wrapper">
                    <div class="row">
                      <div class="lead-view" id="leadViewWrapper">
                       <div class="col-md-4 col-xs-12 lead-information-col">
                        <div class="lead-info-heading">
                         <h4 class="no-margin font-medium-xs bold">Lead Information</h4>
                     </div>
                     <p class="text-muted lead-field-heading no-mtop">Name</p>
                     <p class="bold font-medium-xs lead-name">Abdul Hanan</p>
                     <p class="text-muted lead-field-heading">Position</p>
                     <p class="bold font-medium-xs">-</p>
                     <p class="text-muted lead-field-heading">Email Address</p>
                     <p class="bold font-medium-xs"><a href="mailto:theabdulhasvsnan@gmail.com">theabdulhasvsnan@gmail.com</a></p>
                     <p class="text-muted lead-field-heading">Website</p>
                     <p class="bold font-medium-xs">-</p>
                     <p class="text-muted lead-field-heading">Phone</p>
                     <p class="bold font-medium-xs"><a href="tel:03006724741">03006724741</a></p>
                     <p class="text-muted lead-field-heading">Company</p>
                     <p class="bold font-medium-xs">Ideoversity</p>
                     <p class="text-muted lead-field-heading">Address</p>
                     <p class="bold font-medium-xs">ARFA Tower, Office # 8, Level # 8، Ferozepur Road, lahore, 54000</p>
                     <p class="text-muted lead-field-heading">City</p>
                     <p class="bold font-medium-xs">Lahore</p>
                     <p class="text-muted lead-field-heading">State</p>
                     <p class="bold font-medium-xs">-</p>
                     <p class="text-muted lead-field-heading">Country</p>
                     <p class="bold font-medium-xs">Pakistan</p>
                     <p class="text-muted lead-field-heading">Zip Code</p>
                     <p class="bold font-medium-xs">54000</p>
                 </div>
                 <div class="col-md-4 col-xs-12 lead-information-col">
                    <div class="lead-info-heading">
                     <h4 class="no-margin font-medium-xs bold">General Information</h4>
                 </div>
                 <p class="text-muted lead-field-heading no-mtop">Status</p>
                 <p class="bold font-medium-xs mbot15">Аллочка</p>
                 <p class="text-muted lead-field-heading">Source</p>
                 <p class="bold font-medium-xs mbot15">Facebook</p>
                 <p class="text-muted lead-field-heading">Default Language</p>
                 <p class="bold font-medium-xs mbot15">System Default</p>
                 <p class="text-muted lead-field-heading">Assigned</p>
                 <p class="bold font-medium-xs mbot15">Simply Admin</p>
                 <p class="text-muted lead-field-heading">Tags</p>
                 <p class="bold font-medium-xs mbot10">
                 -            </p>
                 <p class="text-muted lead-field-heading">Created</p>
                 <p class="bold font-medium-xs"><span class="text-has-action" data-toggle="tooltip" data-title="2020-11-07 10:14:45">2 months ago</span></p>
                 <p class="text-muted lead-field-heading">Last Contact</p>
                 <p class="bold font-medium-xs"><span class="text-has-action" data-toggle="tooltip" data-title="2020-12-02 12:25:00">a month ago</span></p>
                 <p class="text-muted lead-field-heading">Public</p>
                 <p class="bold font-medium-xs mbot15">
                 No            </p>
             </div>
             <div class="col-md-4 col-xs-12 lead-information-col">
             </div>
             <div class="clearfix"></div>
             <div class="col-md-12">
                <p class="text-muted lead-field-heading">Description</p>
                <p class="bold font-medium-xs">-</p>
            </div>
        </div>
    </div>
</div>
</div>
</div>
</div>
</div>
</div>
</div>
</div>
</div>
<?php $this->load->view('call_types/type.php'); ?>
<?php $this->load->view('call_types/call_direction.php'); ?>
<?php init_tail(); ?>
<?php if($contactid != ''): ?>
    <script type="text/javascript">
        $(function(){
           init_ajax_search('contactid', $('#contactid'), {clientid: $('#clientid').val()}, admin_url+'call_logs/get_contact');
       })
   </script>
<?php endif ?>
<script>
    var userphone = [];
    var _lnth = 13;
    $(function(){
        var request_type = '<?php echo isset($request_type) ? $request_type : ''; ?>';

            var TblServerParams = {
            "assigned": "[name='view_assigned']",
            "view_by_rel_type": "[name='view_by_rel_type']",
            "view_by_lead": "[name='view_by_lead']",
            "view_by_customer": "[name='view_by_customer']",
            "view_by_status": "[name='view_by_status']",
            };        
        if(<?php echo $isGridView ?> == 0) {
            var extra_query_param = '';
            if(request_type != ''){
                var path = 'table_visit?type=visit';
            }else{
                var path = 'table';
            }
            var tAPI = initDataTable('.table-call_logs', admin_url+'call_logs/'+path, [], [2, 3], TblServerParams,[4, 'desc']);
            

            
            $.each(TblServerParams, function(i, obj) {
                $('select' + obj).on('change', function() {
                    $('table.table-call_logs').DataTable().ajax.reload()
                    .columns.adjust()
                    .responsive.recalc();
                });
            });
        }else{
            loadGridView();

            $(document).off().on('click','a.paginate',function(e){
                e.preventDefault();
                console.log("$(this)", $(this).data('ci-pagination-page'))
                var pageno = $(this).data('ci-pagination-page');
                var formData = {
                    search: $("input#search").val(),
                    start: (pageno-1),
                    length: _lnth,
                    draw: 1
                }
                gridViewDataCall(formData, function (resposne) {
                    $('div#grid-tab').html(resposne)
                })
            });
        }
    });

    // Send SMS
    $('#form_sms').on('submit', function (e) {
        e.preventDefault();
        let $form = $(this);
        let check = false;

        // If phone number checked
        $form.find('[name="phone_number[]"]').each(function () {
            if ($(this).val() != '') {
                check = true;
                return true;
            }
        });

        if (check === false) {
            alert('Please, check at least one phone number');
        }

        // Check if message is empty
        if ($('#message').val() === '') {
            $('#message').parents('.form-group').addClass('has-error');
            check = false;
        }

        // Stop if check is false
        if (!check) {
            return false;
        }

        // Send Post
        $('.loader').show();
        $.post($form.attr('action'), $form.serialize(), function (data) {
            let json = JSON.parse(data);
            if (json.success === false) {
                alert('Error! Please make sure that provided data is valid');
            }
            let html = `<h3 class="text-success">Sent: ${json.sent.length}</h3>`;
            html += `<h3 class="text-danger">Errors: ${json.errors.length}</h3>`;
            $('.report-content').html(html);
            $('.form-content').hide();
            $('.loader').fadeOut();
        });
    });
    function myFunction2(){
        var contactid =$('#contactid option:selected').val();
        if(contactid != ''){
            $('#contact_id').val(contactid);  
            $.ajax({ 
             url: admin_url+'call_logs/get_contact_info',
             type: 'POST',
             data:$('#bulk_sms-form').serialize(),   
             success: function (result) {
               if(!isNaN(result))
                userphone.push(result);
        }
    });
        }
    }
    function myFunction()
    {
        var leadid =$('#clientid option:selected').val();
        if(leadid!='')
        {
            var _customer_type = $('#customer_type');
            if(_customer_type.val() == 'lead'){
                $('#contactid_wrapper').addClass('hide');
                $('#lead_id').val(leadid)    
                $.ajax({ 
                    url: admin_url+'call_logs/get_lead_info',
                    type: 'POST',
                    data:$('#bulk_sms-form').serialize(),   
                    success: function (result) {
                        if(!isNaN(result))
                            userphone.push(result);
                    }
                });
            }
            if(_customer_type.val() == 'customer'){
              $('#customer_id').val($('#clientid').val());
              $('#contactid_wrapper').removeClass('hide');
              init_ajax_search('contactid', $('#contactid'),{clientid: $('#customer_id').val()}, admin_url+'call_logs/get_contact');
          }

      }
  }
  var _clientid = $('#clientid'),
  _customer_type = $('#customer_type'),
  _clientid_wrapper = $('#clientid_wrapper'),
  data = {};

  var _rel_id = $('#rel_id'),
  _rel_type = $('#rel_type'),
  _rel_id_wrapper = $('#rel_id_wrapper');

  $(function(){
    $('body').on('click','button.save-cl', function() {
        $( "#call_end_time" ).trigger('blur');
        $('form#bulk_sms-form').submit();
    });

    $('body').on('change','#clientid', function() {
        initRelIdCntrl();
    });
    validate_call_log_form();
    $('.clientid_label').html(_customer_type.find('option:selected').text());
    _customer_type.on('change', function() {    
        userphone = [];
        var clonedSelect = _clientid.html('').clone();
        _clientid.selectpicker('destroy').remove();
        _clientid = clonedSelect;
        $('#clientid_select').append(clonedSelect);
        call_log_clientid_select();
        _rel_id.trigger('change');
        if($(this).val() != ''){
            _clientid_wrapper.removeClass('hide');
        } else {
            _clientid_wrapper.addClass('hide');
        }
        $('.clientid_label').html(_customer_type.find('option:selected').text());

        initRelIdCntrl();
    });
    call_log_clientid_select();

    <?php if(!isset($call_log) && $clientid != ''){ ?>
        _clientid.change();
    <?php } ?>

    $('.rel_id_label').html(_rel_type.find('option:selected').text());
    _rel_type.on('change', function() {

        var clonedSelect = _rel_id.html('').clone();
        _rel_id.selectpicker('destroy').remove();
        _rel_id = clonedSelect;
        $('#rel_id_select').append(clonedSelect);
        call_log_rel_id_select();
        if($(this).val() == '1' || $(this).val() == '2'){

            _rel_id_wrapper.removeClass('hide');
        } else {
            _rel_id_wrapper.addClass('hide');
        }
        $('.rel_id_label').html(_rel_type.find('option:selected').text());
    });
    call_log_rel_id_select();
    <?php if(!isset($call_log) && $rel_id != ''){ ?>
        _rel_id.change();
    <?php } ?>

    $( "input[type='radio'][name='has_follow_up']" ).change(function() {
        if($('input[type="radio"][name="has_follow_up"]:checked').val() == 1){
            $('div.followup-schedule').removeClass('hide');
        }else{
            $('div.followup-schedule').addClass('hide');
        }
    });

    $( "#call_start_time" ).blur(function() {
            calculate_duration($( this ).val(), $('#call_end_time').val());
        });
    $( "#call_end_time" ).blur(function() {
            calculate_duration($('#call_start_time').val(), $( this ).val());
        });
});
  function calculate_duration(start_time, end_time){
    $.ajax({ 
      url: admin_url+'call_logs/calculate_duration',
      type: 'POST',
      data: {
        start_time: start_time,
        end_time: end_time
    },
    success: function (result) {
        $("#call_duration").val(result)
    }
});
}
function initRelIdCntrl() {
    var clonedSelect = _rel_id.html('').clone();
    _rel_id.selectpicker('destroy').remove();
    _rel_id = clonedSelect;
    $('#rel_id_select').append(clonedSelect);
    call_log_rel_id_select();
    if(_rel_type.find('option:selected').val() != ''){
        _rel_id_wrapper.removeClass('hide');
    } else {
        _rel_id_wrapper.addClass('hide');
    }
    $('.rel_id_label').html(_rel_type.find('option:selected').text());
}
function validate_call_log_form(){
    $( "#call_end_time" ).trigger('blur');
    appValidateForm($('#bulk_sms-form'), {
        customer_type: 'required',
        clientid : 'required',
        rel_type : 'required',
        rel_id : {
            required: {
                depends: function() {
                    return (rel_type == '1' || rel_type == '2')?true:false;
                }
            }
        },
        call_direction : 'required',
        userphone: 'required',
        sms_content: 'required',
        call_purpose : 'required',
        call_summary : 'required',
        staffid : 'required',
        call_start_time: 'required',
        call_end_time: 'required',
        follow_up_schedule : {
            required: {
                depends: function() {
                    return ($("input[name='has_follow_up']:checked").val() == '1')?true:false;
                }
            }
        },
    });
}
function call_log_clientid_select(){
    var serverData = {};
    serverData.clientid = _clientid.val();
    data.type = _customer_type.val();
    init_ajax_search(_customer_type.val(),_clientid,serverData);
}

function call_log_rel_id_select(){
    var serverData = {};
    serverData.rel_type = $('#customer_type').children("option:selected"). val();
    serverData.rel_id = _clientid.val();
    var cl_rel_type= '';

    if(_rel_type.val() ==1){
        cl_rel_type = 'proposal';
    }else if(_rel_type.val() == 2){
        cl_rel_type = 'estimate';
    }else{
        cl_rel_type = _rel_type.val();
    }

    data.type = cl_rel_type;
    init_ajax_search(cl_rel_type,_rel_id,serverData, admin_url + 'call_logs/get_relation_data');
}

function convertTime(sec) {
    var hours = Math.floor(sec/3600);
    (hours >= 1) ? sec = sec - (hours*3600) : hours = '00';
    var min = Math.floor(sec/60);
    (min >= 1) ? sec = sec - (min*60) : min = '00';
    (sec < 1) ? sec='00' : void 0;
    (min.toString().length == 1) ? min = '0'+min : void 0;
    (sec.toString().length == 1) ? sec = '0'+sec : void 0;
    return hours+':'+min+':'+sec;
}
function sendsmsnew()
{
    console.log(userphone);
    var smscontent = document.getElementById('sms_content').value;
    if(userphone.length == 0)
    {
        alert('please select any from customer/lead');
    }
    else if(smscontent==''){
        alert('please enter your message');   
    }
    else
    {
     $.ajax({ 
      url: admin_url+'call_logs/SendSMS/send',
      type: 'POST',
      data:{message: smscontent, phone_number: userphone},
      beforeSend: function(xhr){
        $("#sms_content").prop('readonly', true);
        $("#sms-send-btn").attr('disabled',true);
        $('#sms_message').html('<p style="color:green;">The message is sending.'+'<img src="'+site_url+'modules/call_logs/callingimage.gif" width="100px">');
        $('#sms_message').show();
    },
    success: function (result) {
        var resposne = JSON.parse(result);
        if(resposne.errors.length == 0)
        {
            $("#sms_content").prop('readonly', true);
            $("input[name='twilio_sms_response']").val('Sent');
            $('#sms_message').html('<p style="color:green;">The message sent.');
            $("#sms-send-btn").attr('disabled',true);
            $('#sms_message').show();
            document.getElementById("call_start_time").readOnly = false;
            var dt = new Date();
            var time = dt.getHours() + ":" + dt.getMinutes() + ":" + dt.getSeconds();
            var month = dt.getMonth()+1;
            var day = dt.getDate();
            var fdate = dt.getFullYear() + '-' +
            ((''+month).length<2 ? '0' : '') + month + '-' +
            ((''+day).length<2 ? '0' : '') + day;
            var fulldate = fdate + ' '+ time;
            $('#call_start_time').val(fulldate)
        }
        else
        {
            $("input[name='twilio_sms_response']").val('Failed');
            $("#sms_content").prop('readonly', false);
            $("#sms-send-btn").attr('disabled',false);
            $('#sms_message').html('<p style="color:red;">'+resposne.errors[0].message+'!</p>');
            $('#sms_message').show();
        }
    }
});
 }
}
function loadCustomerProfile(e) {
    var id = $(e).attr('data-id');
    $.ajax({ 
      url: admin_url+'call_logs/get_customer_data',
      type: 'POST',
      data: {contactid: id},
      success: function (result) {
        $("#leadViewWrapper").html(result)
        $("#customer-info-modal").modal('show');
    }
});
}
$(document).ready(function(){
    var maxLength = 160;
    var enterdChar = 0;
    $('#sms_content').keyup(function(e) {
      var textlen = maxLength - $(this).val().length;
      var enteredTextLen = enterdChar + $(this).val().length
      $('#rchars_limit').text(textlen);
      $('#user_entered').text(enteredTextLen);
      if(textlen <= 0){
        $(this).css('color','red');
        $('#rchars_limit').text(0);
    }else if(textlen > 0){
        $(this).css('color','black');
        $('#rchars_limit').text(textlen);

    }
    console.log(textlen)

});
});
</script>
</body>
</html>
