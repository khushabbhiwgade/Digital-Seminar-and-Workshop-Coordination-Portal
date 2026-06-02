<?php
// includes/mail_helper.php
// ------------------------------------------------------------
// Email Sending Helper Function using PHPMailer with Log Fallback
// ------------------------------------------------------------

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/PHPMailer/Exception.php';
require_once __DIR__ . '/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/SMTP.php';
require_once __DIR__ . '/../config/mail_config.php';

/**
 * Sends a modern HTML verification email with a 6-digit OTP code to the user.
 * Falls back to local text logs if SMTP fails or is unconfigured.
 * 
 * @param string $to_email Recipient email address
 * @param string $to_name Recipient full name
 * @param string $otp_code 6-digit OTP code
 * @return bool True if mail was sent or logged successfully, False otherwise
 */
function send_otp_email($to_email, $to_name, $otp_code) {
    // Generate beautiful responsive HTML body matching the portal's design
    $subject = "Verify Your Account";
    $html_body = "
    <!DOCTYPE html>
    <html lang='en'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Verify Your Account</title>
        <style>
            body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; background-color: #f8fafc; color: #334155; margin: 0; padding: 0; }
            .container { max-width: 600px; margin: 40px auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); border: 1px solid #e2e8f0; }
            .header { background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); color: #ffffff; text-align: center; padding: 35px 20px; border-bottom: 4px solid #eab308; }
            .header h1 { margin: 0; font-size: 24px; font-weight: bold; letter-spacing: -0.5px; }
            .content { padding: 40px 30px; line-height: 1.6; font-size: 15px; }
            .otp-container { background-color: #f1f5f9; border-radius: 6px; padding: 18px; text-align: center; margin: 25px 0; border: 1px dashed #cbd5e1; }
            .otp-code { font-family: 'Courier New', Courier, monospace; font-size: 34px; font-weight: bold; letter-spacing: 5px; color: #1e293b; margin: 0; }
            .expiry-text { font-size: 13px; color: #64748b; margin-top: 6px; font-style: italic; }
            .footer { background-color: #f8fafc; text-align: center; padding: 20px 30px; font-size: 12px; color: #64748b; border-top: 1px solid #e2e8f0; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>Digital Seminar & Workshop Coordination Portal</h1>
            </div>
            <div class='content'>
                <p>Hello " . htmlspecialchars($to_name) . ",</p>
                <p>Your verification code is:</p>
                <div class='otp-container'>
                    <div class='otp-code'>" . htmlspecialchars($otp_code) . "</div>
                    <div class='expiry-text'>This OTP expires in 5 minutes.</div>
                </div>
                <p>If you did not create an account, ignore this email.</p>
                <p>Regards,<br><strong>Digital Seminar & Workshop Coordination Portal</strong></p>
            </div>
            <div class='footer'>
                <p>This is an automated system security email. Please do not reply directly.</p>
            </div>
        </div>
    </body>
    </html>";

    $plain_text = "Hello " . $to_name . ",\n\nYour verification code is:\n\n" . $otp_code . "\n\nThis OTP expires in 5 minutes.\n\nIf you did not create an account, ignore this email.\n\nRegards,\nDigital Seminar & Workshop Coordination Portal";

    try {
        // Guard check: If SMTP Host or User/Pass are unconfigured or contain placeholder defaults, skip SMTP and force log fallback immediately
        if (empty(SMTP_USER) || empty(SMTP_PASS) || SMTP_USER === 'your-gmail@gmail.com' || SMTP_PASS === 'abcd efgh ijkl mnop') {
            throw new Exception("SMTP server credentials are not fully configured in config/mail_config.php.");
        }

        $mail = new PHPMailer(true);

        // Server Settings
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;
        $mail->Port       = SMTP_PORT;

        if (!empty(SMTP_ENCRYPTION)) {
            $mail->SMTPSecure = (SMTP_ENCRYPTION === 'ssl') ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
        }

        // Recipients
        $mail->setFrom(MAIL_FROM_EMAIL, MAIL_FROM_NAME);
        $mail->addAddress($to_email, $to_name);

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $html_body;
        $mail->AltBody = $plain_text;

        $mail->send();
        return true;

    } catch (Exception $e) {
        // SMTP sending failed or credentials not present
        if (defined('MAIL_LOG_FALLBACK') && MAIL_LOG_FALLBACK) {
            $log_dir = dirname(MAIL_LOG_PATH);
            if (!is_dir($log_dir)) {
                mkdir($log_dir, 0777, true);
            }
            
            $log_entry = sprintf(
                "[%s] To: %s (%s) | OTP Code: %s | Subject: %s | Expiry: 5 Mins | SMTP Status: Offline (%s)\n",
                date('Y-m-d H:i:s'),
                $to_email,
                $to_name,
                $otp_code,
                $subject,
                $e->getMessage()
            );
            
            return file_put_contents(MAIL_LOG_PATH, $log_entry, FILE_APPEND) !== false;
        }
        return false;
    }
}

/**
 * Sends a modern HTML certificate email notifying the user that their certificate is ready.
 * Falls back to local text logs if SMTP fails or is unconfigured.
 * 
 * @param string $to_email Recipient email address
 * @param string $to_name Recipient full name
 * @param string $workshop_name Name of the completed workshop
 * @param string $download_link Download URL for the certificate PDF
 * @param string $verification_link Verification URL for the certificate
 * @return bool True if mail was sent or logged successfully, False otherwise
 */
function send_certificate_email($to_email, $to_name, $workshop_name, $download_link, $verification_link) {
    $subject = "Your Workshop Certificate is Ready";
    $html_body = "
    <!DOCTYPE html>
    <html lang='en'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Your Certificate is Ready</title>
        <style>
            body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; background-color: #f8fafc; color: #334155; margin: 0; padding: 0; }
            .container { max-width: 600px; margin: 40px auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); border: 1px solid #e2e8f0; }
            .header { background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); color: #ffffff; text-align: center; padding: 35px 20px; border-bottom: 4px solid #eab308; }
            .header h1 { margin: 0; font-size: 24px; font-weight: bold; letter-spacing: -0.5px; }
            .content { padding: 40px 30px; line-height: 1.6; font-size: 15px; }
            .btn-group { text-align: center; margin: 30px 0; }
            .btn { display: inline-block; padding: 12px 24px; color: #1e293b !important; background-color: #eab308; text-decoration: none; border-radius: 6px; font-weight: bold; font-size: 15px; margin: 0 10px; }
            .btn-secondary { background-color: #e2e8f0; color: #334155 !important; }
            .footer { background-color: #f8fafc; text-align: center; padding: 20px 30px; font-size: 12px; color: #64748b; border-top: 1px solid #e2e8f0; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>Digital Seminar & Workshop Coordination Portal</h1>
            </div>
            <div class='content'>
                <p>Hello " . htmlspecialchars($to_name) . ",</p>
                <p>Congratulations! Your certificate of completion for the workshop <strong>" . htmlspecialchars($workshop_name) . "</strong> is ready.</p>
                <p>You can download your PDF certificate and verify its authenticity using the links below:</p>
                <div class='btn-group'>
                    <a href='" . htmlspecialchars($download_link) . "' class='btn'>Download Certificate</a>
                    <a href='" . htmlspecialchars($verification_link) . "' class='btn btn-secondary'>Verify Certificate</a>
                </div>
                <p>If you have any questions, please contact the coordinator desk.</p>
                <p>Regards,<br><strong>Digital Seminar & Workshop Coordination Portal</strong></p>
            </div>
            <div class='footer'>
                <p>This is an automated system email. Please do not reply directly.</p>
            </div>
        </div>
    </body>
    </html>";

    $plain_text = "Hello " . $to_name . ",\n\nCongratulations! Your certificate of completion for the workshop \"" . $workshop_name . "\" is ready.\n\nDownload Link: " . $download_link . "\nVerification Link: " . $verification_link . "\n\nRegards,\nDigital Seminar & Workshop Coordination Portal";

    try {
        if (empty(SMTP_USER) || empty(SMTP_PASS) || SMTP_USER === 'your-gmail@gmail.com' || SMTP_PASS === 'abcd efgh ijkl mnop') {
            throw new Exception("SMTP server credentials are not fully configured in config/mail_config.php.");
        }

        $mail = new PHPMailer(true);

        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;
        $mail->Port       = SMTP_PORT;

        if (!empty(SMTP_ENCRYPTION)) {
            $mail->SMTPSecure = (SMTP_ENCRYPTION === 'ssl') ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
        }

        $mail->setFrom(MAIL_FROM_EMAIL, MAIL_FROM_NAME);
        $mail->addAddress($to_email, $to_name);

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $html_body;
        $mail->AltBody = $plain_text;

        $mail->send();
        return true;

    } catch (Exception $e) {
        if (defined('MAIL_LOG_FALLBACK') && MAIL_LOG_FALLBACK) {
            $log_dir = dirname(MAIL_LOG_PATH);
            if (!is_dir($log_dir)) {
                mkdir($log_dir, 0777, true);
            }
            
            $log_entry = sprintf(
                "[%s] To: %s (%s) | Subject: %s | Workshop: %s | Download: %s | Verify: %s | SMTP Status: Offline (%s)\n",
                date('Y-m-d H:i:s'),
                $to_email,
                $to_name,
                $subject,
                $workshop_name,
                $download_link,
                $verification_link,
                $e->getMessage()
            );
            
            return file_put_contents(MAIL_LOG_PATH, $log_entry, FILE_APPEND) !== false;
        }
        return false;
    }
}
?>
