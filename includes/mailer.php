<?php
require_once __DIR__ . '/../config/mail.php';
require_once __DIR__ . '/../PHPMailer/src/Exception.php';
require_once __DIR__ . '/../PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function send_otp_email($toEmail, $toName, $otp, &$errorOut = null) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = MAIL_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = MAIL_USERNAME;
        $mail->Password   = MAIL_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = MAIL_PORT;

        $mail->setFrom(MAIL_FROM_EMAIL, MAIL_FROM_NAME);
        $mail->addAddress($toEmail, $toName);

        $mail->isHTML(true);
        $mail->Subject = 'Your Tuition Finder Password Reset Code';
        $mail->Body    = "<p>Hi " . htmlspecialchars($toName) . ",</p>
            <p>Your password reset verification code is:</p>
            <h2 style='letter-spacing:4px;'>{$otp}</h2>
            <p>This code expires in 10 minutes. If you didn't request this, you can ignore this email.</p>";
        $mail->AltBody = "Your Tuition Finder password reset code is: {$otp} (expires in 10 minutes)";

        $mail->send();
        return true;
    } catch (Exception $e) {
        $errorOut = $mail->ErrorInfo;
        return false;
    }
}