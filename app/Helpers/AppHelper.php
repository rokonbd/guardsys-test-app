<?php
use Twilio\Rest\Client;

if (! function_exists('sent_sms_for_new_user')) {
    function sent_sms_for_new_user($toNumber) {
        $sid = config('sms.TWILIO_ACCOUNT_SID');
        $token = config('sms.TWILIO_AUTH_TOKEN');
        $formNumber = config('sms.TWILIO_FORM_NUMBER');
        $twilio = new Client($sid, $token);

        $message = $twilio->messages
                        ->create($toNumber,
                            [
                                "body" => "Your acoount has been opened in guardsys-test-app", 
                                "from" => $formNumber
                            ]
                        );
        return $message;
    }
}

if (! function_exists('sent_sms_for_delete_user')) {
    function sent_sms_for_delete_user($toNumber) {
        $sid = config('sms.TWILIO_ACCOUNT_SID');
        $token = config('sms.TWILIO_AUTH_TOKEN');
        $formNumber = config('sms.TWILIO_FORM_NUMBER');
        $twilio = new Client($sid, $token);

        $message = $twilio->messages
                        ->create($toNumber,
                            [
                                "body" => "Your acoount has been suspended in guardsys-test-app", 
                                "from" => $formNumber
                            ]
                        );
        return $message;
    }
}