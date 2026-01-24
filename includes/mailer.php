<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Load Composer's autoloader
require_once 'vendor/autoload.php';
require_once 'config.php'; // Configuration file for SMTP credentials

/**
 * Sends an OTP email to the user
 *
 * @param string $recipientEmail
 * @param string $otp
 * @return bool
 */
function sendOTPEmail(string $recipientEmail, string $otp): bool
{
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_EMAIL;
        $mail->Password   = SMTP_PASSWORD;
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;

        // Add timeout settings for better reliability
        $mail->Timeout = 15;
        $mail->SMTPKeepAlive = true;

        // Recipients
        $mail->setFrom(SMTP_EMAIL, SMTP_NAME);
        $mail->addAddress($recipientEmail);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Your One-Time Password (OTP)';
        $mail->Body = "
            <p style='font-size: 16px;'>Hello,</p>
            <p style='font-size: 16px;'>Please use the following code to complete your login:</p>
        
            <div style='
                margin: 20px auto;
                padding: 15px 20px;
                background-color: #f4f4f4;
                border: 1px dashed #ccc;
                width: fit-content;
                font-size: 28px;
                font-weight: bold;
                letter-spacing: 6px;
                text-align: center;
                border-radius: 8px;
            '>
                {$otp}
            </div>
        
            <p style='font-size: 14px; color: #555;'>This code will expire in 5 minutes.</p>
            <p style='font-size: 14px; color: #555;'>If you didn't request this, please ignore this email.</p>
        ";

        $mail->send();
        error_log("OTP Email sent successfully to: " . $recipientEmail);
        return true;
    } catch (Exception $e) {
        error_log("Mailer error - Email: {$recipientEmail}, Error: " . $mail->ErrorInfo);
        return false;
    }
}


/**
 *
 * Send email
 * 
 * @param string $to Recipient email
 * @param string $subject Email subject
 * @param string $body HTML content of the email
 * @return bool True if sent successfully, false otherwise
 */
function sendEmail(string $to, string $subject, string $body): bool
{
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_EMAIL;
        $mail->Password   = SMTP_PASSWORD;
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;

        // Add timeout settings for better reliability
        $mail->Timeout = 15;
        $mail->SMTPKeepAlive = true;

        // Recipients
        $mail->setFrom(SMTP_EMAIL, SMTP_NAME);
        $mail->addAddress($to);

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;

        $mail->send();
        error_log("Email sent successfully to: " . $to . " | Subject: " . $subject);
        return true;
    } catch (Exception $e) {
        error_log("Mailer error - Email: {$to}, Subject: {$subject}, Error: " . $mail->ErrorInfo);
        return false;
    }
}
