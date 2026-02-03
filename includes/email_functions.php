<?php
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

require __DIR__ . '/../vendor/autoload.php';

function sendEmail($to, $name, $subject, $body)
{
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'bansiparmar345@gmail.com';  // Your Gmail
        $mail->Password = 'gbyq tddp ufxk kcqs';  // App password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = 465;
        $mail->SMTPDebug = 2;  // Enable verbose debug output
        $mail->Debugoutput = function ($str, $level) {
            error_log("PHPMailer: $str");
        };

        // Recipients
        $mail->setFrom('bansiparmar345@gmail.com', 'Bansi Patel');
        $mail->addAddress($to, $name);

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $body;
        $mail->AltBody = strip_tags($body);

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}
