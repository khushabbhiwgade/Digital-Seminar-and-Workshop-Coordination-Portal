<?php
// auth/login.php
$base_path = '../';
$page_title = 'Portal Login - Campus Connect';
$active_page = 'login';

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

$error_msg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($email) || empty($password)) {
        $error_msg = "Please enter both email and password.";
    } else {
        // Find user by email
        $sql = "SELECT id, email, password, full_name, role, is_verified FROM users WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows > 0) {
            $user = $result->fetch_assoc();
            
            // Check if password column is set and verified
            if ($user['password'] !== null && password_verify($password, $user['password'])) {
                // Check if account is verified
                if (isset($user['is_verified']) && $user['is_verified'] == 0) {
                    $_SESSION['otp_email'] = $user['email'];
                    $error_msg = "Please verify your email before logging in. <a href='verify_otp.php' class='alert-link fw-semibold text-decoration-underline'>Verify here</a>";
                } else {
                    // Prevent session fixation
                    session_regenerate_id(true);

                    // Set session variables
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['user_name'] = $user['full_name'];
                    $_SESSION['role'] = $user['role'];

                    // Handle post-login redirection URL if available
                    $redirect = $_SESSION['redirect_url'] ?? '';
                    unset($_SESSION['redirect_url']);

                    if (!empty($redirect)) {
                        header("Location: " . $redirect);
                    } else {
                        // Default redirection by role
                        if ($user['role'] === 'admin') {
                            header("Location: ../admin/dashboard.php");
                        } elseif ($user['role'] === 'coordinator') {
                            header("Location: ../coordinator/dashboard.php");
                        } else {
                            header("Location: ../student/dashboard.php");
                        }
                    }
                    exit;
                }
            } else {
                $error_msg = "Invalid email or password.";
            }
        } else {
            $error_msg = "Invalid email or password.";
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
                    <i class="fa-solid fa-graduation-cap text-warning fs-1 mb-2"></i>
                    <h3 class="fw-bold mb-0">Campus Connect</h3>
                    <p class="text-muted small mb-0 mt-1">Digital Seminar Portal Login</p>
                </div>
                <div class="card-body p-4 p-md-5 bg-white">
                    <?php if (isset($_GET['signup']) && $_GET['signup'] === 'success'): ?>
                        <div class="alert alert-warning alert-dismissible fade show border-start border-4 border-warning" role="alert">
                            <i class="fa-solid fa-envelope-open-text me-2"></i>Account created successfully. A verification code has been sent. Please <a href="verify_otp.php" class="alert-link fw-semibold text-decoration-underline">verify your email</a> to log in.
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($_GET['verify']) && $_GET['verify'] === 'success'): ?>
                        <div class="alert alert-success alert-dismissible fade show border-start border-4 border-success" role="alert">
                            <i class="fa-solid fa-circle-check me-2"></i>Email address verified successfully. You can now log in below.
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($_GET['verify']) && $_GET['verify'] === 'already'): ?>
                        <div class="alert alert-info alert-dismissible fade show border-start border-4 border-info" role="alert">
                            <i class="fa-solid fa-circle-info me-2"></i>Your email address is already verified. Please log in.
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($error_msg)): ?>
                        <div class="alert alert-danger alert-dismissible fade show border-start border-4 border-danger" role="alert">
                            <i class="fa-solid fa-triangle-exclamation me-2"></i><?php echo $error_msg; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="login.php" novalidate>
                        <div class="mb-3">
                            <label for="email" class="form-label fw-semibold">Email Address</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted"><i class="fa-solid fa-envelope"></i></span>
                                <input type="email" class="form-control" id="email" name="email" placeholder="name@college.edu" required autofocus>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="password" class="form-label fw-semibold">Password</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted"><i class="fa-solid fa-lock"></i></span>
                                <input type="password" class="form-control" id="password" name="password" placeholder="••••••••" required>
                            </div>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-warning text-dark fw-bold btn-lg">
                                <i class="fa-solid fa-right-to-bracket me-2"></i>Log In
                            </button>
                        </div>
                    </form>
                </div>
                <div class="card-footer bg-light text-center py-3">
                    <span class="text-muted small">New student? <a href="signup.php" class="text-decoration-none fw-semibold">Create an Account here</a></span>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
include $base_path . 'includes/footer.php';
?>
