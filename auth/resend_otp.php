<?php
// auth/resend_otp.php
// ------------------------------------------------------------
// Controller to regenerate and resend Email Verification OTP
// ------------------------------------------------------------

require_once '../includes/auth.php';
require_once '../config/db_connect.php';
require_once '../includes/mail_helper.php';

// Redirect if already logged in
if (is_logged_in()) {
    $role = get_user_role();
    if ($role === 'admin') {
        header("Location: ../admin/dashboard.php");
    } elseif ($role === 'coordinator') {
        header("Location: ../coordinator/dashboard.php");
    } else {
        header("Location: ../student/dashboard.php");
    }
    exit;
}

// Redirect if no email is set in session for verification
if (empty($_SESSION['otp_email'])) {
    header("Location: login.php");
    exit;
}

$email = $_SESSION['otp_email'];

// Rate limiting: Maximum 3 resends per hour
if (!isset($_SESSION['resend_timestamps'])) {
    $_SESSION['resend_timestamps'] = [];
}

// Clean up timestamps older than 1 hour (3600 seconds)
$current_time = time();
$_SESSION['resend_timestamps'] = array_filter($_SESSION['resend_timestamps'], function($timestamp) use ($current_time) {
    return ($current_time - $timestamp) < 3600;
});

// Check if rate limit reached
if (count($_SESSION['resend_timestamps']) >= 3) {
    $timestamps = $_SESSION['resend_timestamps'];
    sort($timestamps);
    $oldest_resend = $timestamps[0];
    $wait_seconds = 3600 - ($current_time - $oldest_resend);
    $wait_minutes = ceil($wait_seconds / 60);
    
    header("Location: verify_otp.php?error=" . urlencode("Maximum of 3 email resends per hour reached. Please try again after " . $wait_minutes . " minute(s)."));
    exit;
}

// Query user to ensure they exist and are not verified
$sql = "SELECT id, full_name, is_verified, otp_code, otp_expiry, otp_attempts FROM users WHERE email = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $email);
$stmt->execute();
$res = $stmt->get_result();

if ($res && $res->num_rows > 0) {
    $user = $res->fetch_assoc();

    if ($user['is_verified'] == 1) {
        // Already verified, clear verification session and send to login
        unset($_SESSION['otp_email']);
        header("Location: login.php?verify=already");
        exit;
    }

    // Lockout protection: block resends if currently locked (5 or more failed attempts and within 10 minutes)
    $db_attempts = (int)$user['otp_attempts'];
    $db_expiry = strtotime($user['otp_expiry']);
    if ($db_attempts >= 5 && time() <= $db_expiry) {
        $lock_time_left = $db_expiry - time();
        $minutes = ceil($lock_time_left / 60);
        header("Location: verify_otp.php?error=" . urlencode("Verification is locked due to too many failed attempts. Please wait " . $minutes . " minute(s) before trying again."));
        exit;
    }

    // Generate new OTP and set expiry (5 minutes from now)
    $new_otp_code = str_pad(random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
    $new_otp_expiry = date('Y-m-d H:i:s', strtotime('+5 minutes'));

    // Update database (resetting otp_attempts to 0 since we regenerated a new code)
    $update_sql = "UPDATE users SET otp_code = ?, otp_expiry = ?, otp_attempts = 0 WHERE id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("ssi", $new_otp_code, $new_otp_expiry, $user['id']);

    if ($update_stmt->execute()) {
        // Send email
        if (send_otp_email($email, $user['full_name'], $new_otp_code)) {
            // Success: Add resend timestamp to session
            $_SESSION['resend_timestamps'][] = time();
            header("Location: verify_otp.php?resend=success");
            exit;
        } else {
            header("Location: verify_otp.php?error=mail_fail");
            exit;
        }
    } else {
        header("Location: verify_otp.php?error=db_fail");
        exit;
    }
} else {
    // Session email is invalid, clear it and redirect to login
    unset($_SESSION['otp_email']);
    header("Location: login.php");
    exit;
}
