<?php
// config/mail_config.php
// ------------------------------------------------------------
// SMTP and PHPMailer Configuration Settings
// ------------------------------------------------------------

define('SMTP_HOST', 'smtp.gmail.com');           // Gmail SMTP server host
define('SMTP_PORT', 587);                        // Port 587 (TLS encryption)
define('SMTP_USER', 'your-gmail@gmail.com');     // Replace with your Google Account email address
define('SMTP_PASS', 'abcd efgh ijkl mnop');     // Replace with your 16-character Google App Password (no spaces)
define('SMTP_ENCRYPTION', 'tls');                // Use TLS encryption


define('MAIL_FROM_EMAIL', 'no-reply@campusconnect.edu');
define('MAIL_FROM_NAME', 'Campus Connect Portal');

// Local text log fallback for debugging when SMTP settings are not configured or fail
define('MAIL_LOG_FALLBACK', true);
define('MAIL_LOG_PATH', dirname(__DIR__) . '/scratch/email_logs.txt');
