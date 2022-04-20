<?php

defined('BASEPATH') or exit('No direct script access allowed');

$check =  __dir__ ;
$str= preg_replace('/\W\w+\s*(\W*)$/', '$1', $check);
$str.'/twilio-web/src/Twilio/autoload.php';
/*use Twilio\TwiML\TwiML;*/
use Twilio\TwiML\VoiceResponse;

class Twilio_request extends CI_Controller {

	public function __construct()
    {
        parent::__construct();
    }

    public function new_call()
    {
        $response = new VoiceResponse();
        $callerIdNumber = get_option('sms_twilio_phone_number');
        $dial = $response->dial(null, ['callerId'=>$callerIdNumber]);
        $phoneNumberToDial = isset($_GET['phoneNumber']) ? $_GET['phoneNumber'] : '';

        if (isset($phoneNumberToDial)) {
            $dial->number($phoneNumberToDial);
        } else {
            $dial->client('support_agent');
        }
        header('Content-Type: text/xml');
        echo $response;
}
}