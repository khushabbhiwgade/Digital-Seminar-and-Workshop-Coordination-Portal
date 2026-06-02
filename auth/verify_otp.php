<?php
// auth/verify_otp.php
$base_path = '../';
$page_title = 'Verify Email - Campus Connect';
$active_page = 'verify';

require_once $base_path . 'includes/auth.php';
require_once $base_path . 'config/db_connect.php';

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
$error_msg = "";
$success_msg = "";
$is_locked = false;

// 1. Query user to verify lockout and credentials status
$sql = "SELECT id, otp_code, otp_expiry, otp_attempts FROM users WHERE email = ? AND is_verified = 0";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $email);
$stmt->execute();
$res = $stmt->get_result();

if ($res && $res->num_rows > 0) {
    $user = $res->fetch_assoc();
    $db_attempts = (int)$user['otp_attempts'];
    $db_expiry = strtotime($user['otp_expiry']);
    
    // Check if verification is currently locked (5 or more failed attempts and within 10 minutes)
    if ($db_attempts >= 5 && time() <= $db_expiry) {
        $is_locked = true;
        $lock_time_left = $db_expiry - time();
        $minutes = ceil($lock_time_left / 60);
        $error_msg = "Verification locked due to 5 consecutive failed attempts. Please wait " . $minutes . " minute(s) before trying again.";
    }
} else {
    // Already verified or account does not exist
    unset($_SESSION['otp_email']);
    header("Location: login.php?verify=already");
    exit;
}

// Check for resend and other redirection query alerts
if (isset($_GET['resend']) && $_GET['resend'] === 'success') {
    $success_msg = "A new verification code has been sent to your email address.";
}
if (isset($_GET['error']) && $_GET['error'] === 'mail_fail') {
    $error_msg = "Account created, but we failed to send the verification email. Please try to resend OTP using the link below.";
}

// 2. Process OTP submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($is_locked) {
        $error_msg = "Verification is locked. Please wait for the lockout to expire.";
    } else {
        $otp_entered = trim($_POST['otp_code'] ?? '');

        if (empty($otp_entered)) {
            $error_msg = "Please enter the 6-digit verification code.";
        } elseif (strlen($otp_entered) !== 6 || !is_numeric($otp_entered)) {
            $error_msg = "The verification code must be exactly 6 digits.";
        } else {
            if ($user['otp_code'] === $otp_entered) {
                $expiry_time = strtotime($user['otp_expiry']);
                $current_time = time();

                if ($current_time <= $expiry_time) {
                    // Successful verification: activate account, reset attempts, clear OTP fields
                    $update_sql = "UPDATE users SET is_verified = 1, otp_code = NULL, otp_expiry = NULL, otp_attempts = 0 WHERE id = ?";
                    $update_stmt = $conn->prepare($update_sql);
                    $update_stmt->bind_param("i", $user['id']);

                    if ($update_stmt->execute()) {
                        unset($_SESSION['otp_email']);
                        header("Location: login.php?verify=success");
                        exit;
                    } else {
                        $error_msg = "Activation failed. Please try again later.";
                    }
                } else {
                    $error_msg = "The verification code has expired. Please request a new code.";
                }
            } else {
                // Incorrect OTP entered - increment attempts
                $new_attempts = $db_attempts + 1;

                if ($new_attempts >= 5) {
                    // Set lockout for 10 minutes (expires 10 minutes from now)
                    $lock_expiry = date('Y-m-d H:i:s', strtotime('+10 minutes'));
                    $update_lock_sql = "UPDATE users SET otp_attempts = ?, otp_code = 'LOCKED', otp_expiry = ? WHERE id = ?";
                    $update_lock_stmt = $conn->prepare($update_lock_sql);
                    $update_lock_stmt->bind_param("isi", $new_attempts, $lock_expiry, $user['id']);
                    $update_lock_stmt->execute();

                    $is_locked = true;
                    $error_msg = "Too many failed attempts. Verification locked for 10 minutes. Please wait before trying again.";
                } else {
                    // Update attempts count
                    $update_attempts_sql = "UPDATE users SET otp_attempts = ? WHERE id = ?";
                    $update_attempts_stmt = $conn->prepare($update_attempts_sql);
                    $update_attempts_stmt->bind_param("ii", $new_attempts, $user['id']);
                    $update_attempts_stmt->execute();

                    $attempts_left = 5 - $new_attempts;
                    $error_msg = "Incorrect verification code. You have " . $attempts_left . " attempt(s) remaining before lockout.";
                }
            }
        }
    }
}

include $base_path . 'includes/header.php';
include $base_path . 'includes/navbar.php';
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card shadow-lg border-0 rounded-3 overflow-hidden">
                <div class="card-header bg-dark text-center text-white py-4" style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); border-bottom: 2px solid #eab308;">
                    <i class="fa-solid fa-envelope-circle-check text-warning fs-1 mb-2"></i>
                    <h3 class="fw-bold mb-0">Verify Your Email</h3>
                    <p class="text-muted small mb-0 mt-1">We sent a 6-digit code to <strong><?php echo htmlspecialchars($email); ?></strong></p>
                </div>
                <div class="card-body p-4 p-md-5 bg-white">
                    <?php if (!empty($success_msg)): ?>
                        <div class="alert alert-success alert-dismissible fade show border-start border-4 border-success" role="alert">
                            <i class="fa-solid fa-circle-check me-2"></i><?php echo htmlspecialchars($success_msg); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($error_msg)): ?>
                        <div class="alert alert-danger alert-dismissible fade show border-start border-4 border-danger" role="alert">
                            <i class="fa-solid fa-triangle-exclamation me-2"></i><?php echo $error_msg; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="verify_otp.php" novalidate>
                        <div class="mb-4">
                            <label for="otp_code" class="form-label fw-semibold">Enter 6-Digit Verification Code</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted"><i class="fa-solid fa-key"></i></span>
                                <input type="text" class="form-control text-center fw-bold fs-4 letter-spacing-lg" id="otp_code" name="otp_code" placeholder="000000" maxlength="6" pattern="\d{6}" required <?php echo $is_locked ? 'disabled' : 'autofocus'; ?> autocomplete="one-time-code">
                            </div>
                            <div class="form-text text-muted small text-center mt-2">The code expires in 5 minutes. Check your spam folder if you do not see it.</div>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-warning text-dark fw-bold btn-lg" <?php echo $is_locked ? 'disabled' : ''; ?>>
                                <i class="fa-solid fa-shield-check me-2"></i>Verify Account
                            </button>
                        </div>
                    </form>
                </div>
                <div class="card-footer bg-light text-center py-3">
                    <span class="text-muted small">Did not receive the code? <a href="resend_otp.php" class="text-decoration-none fw-semibold"><i class="fa-solid fa-rotate-right me-1"></i>Resend Code</a></span>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.letter-spacing-lg {
    letter-spacing: 4px;
}
</style>

<?php
include $base_path . 'includes/footer.php';
?>
