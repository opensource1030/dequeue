<?php

namespace App\Helpers;

use Mail;

class EmailHelper {


//    function sendEmail($to, $subject, $msg, $user_id = 0, $user_flag = 1) {
    function sendEmail($from, $to, $subject, $msg, $user_id = 0, $user_flag = 1) {

        $data = [
            'to'        => $to,
            'from'      => $from,
            'subject'   => $subject
        ];

        Mail::send(['raw' => $msg], [], function ($message) use ($data) {
            $message->to($data['to'])
                ->subject(['$data->subject']);
//                ->from($data['from']);
        });

//        if(!$mail->Send()) {
//            //echo "Mailer Error: " . $mail->ErrorInfo;
//            logEmail($user_id, $message, $subject, $to, 0, $user_flag);
//        } else {
//            //echo "Message sent!";
//            logEmail($user_id,$message, $subject, $to, 1, $user_flag);
//        }
    }
}