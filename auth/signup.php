<?php
// auth/signup.php
$base_path = '../';
$page_title = 'Create Student Account - Campus Connect';
$active_page = 'signup';

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
$success_msg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');
    $participant_type = trim($_POST['participant_type'] ?? '');
    $organization = trim($_POST['organization'] ?? '');

    // Validation
    if (empty($full_name) || empty($email) || empty($password) || empty($confirm_password) || empty($participant_type) || empty($organization)) {
        $error_msg = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_msg = "Please enter a valid email address.";
    } elseif (strlen($password) < 8) {
        $error_msg = "Password must be at least 8 characters long.";
    } elseif ($password !== $confirm_password) {
        $error_msg = "Password and confirm password do not match.";
    } else {
        // Check if email already exists
        $check_sql = "SELECT id FROM users WHERE email = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("s", $email);
        $check_stmt->execute();
        $check_res = $check_stmt->get_result();

        if ($check_res && $check_res->num_rows > 0) {
            $error_msg = "An account with this email address already exists.";
        } else {
            // Hash password and insert user
            $hashed_pass = password_hash($password, PASSWORD_BCRYPT);
            $insert_sql = "INSERT INTO users (email, password, full_name, participant_type, organization, role) VALUES (?, ?, ?, ?, ?, 'participant')";
            $insert_stmt = $conn->prepare($insert_sql);
            $insert_stmt->bind_param("sssss", $email, $hashed_pass, $full_name, $participant_type, $organization);

            if ($insert_stmt->execute()) {
                // Redirect on success
                header("Location: login.php?signup=success");
                exit;
            } else {
                $error_msg = "Registration failed. Please try again later.";
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
                    <i class="fa-solid fa-user-plus text-warning fs-1 mb-2"></i>
                    <h3 class="fw-bold mb-0">Create Account</h3>
                    <p class="text-muted small mb-0 mt-1">Participant Signup Portal</p>
                </div>
                <div class="card-body p-4 p-md-5 bg-white">
                    <?php if (!empty($error_msg)): ?>
                        <div class="alert alert-danger alert-dismissible fade show border-start border-4 border-danger" role="alert">
                            <i class="fa-solid fa-triangle-exclamation me-2"></i><?php echo htmlspecialchars($error_msg); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="signup.php" novalidate>
                        <div class="mb-3">
                            <label for="full_name" class="form-label fw-semibold">Full Name <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted"><i class="fa-solid fa-user"></i></span>
                                <input type="text" class="form-control" id="full_name" name="full_name" placeholder="John Doe" value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label fw-semibold">Email Address <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted"><i class="fa-solid fa-envelope"></i></span>
                                <input type="email" class="form-control" id="email" name="email" placeholder="name@college.edu" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="participant_type" class="form-label fw-semibold">Participant Category <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted"><i class="fa-solid fa-user-tag"></i></span>
                                <select class="form-select" id="participant_type" name="participant_type" required>
                                    <option value="" disabled <?php echo !isset($_POST['participant_type']) ? 'selected' : ''; ?>>Select Category</option>
                                    <?php
                                    $categories = ['Student', 'Faculty', 'Professional', 'Researcher', 'Alumni', 'Other'];
                                    foreach ($categories as $cat) {
                                        $selected = (isset($_POST['participant_type']) && $_POST['participant_type'] === $cat) ? 'selected' : '';
                                        echo "<option value=\"$cat\" $selected>$cat</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="organization" class="form-label fw-semibold">Organization / College <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted"><i class="fa-solid fa-building"></i></span>
                                <input type="text" class="form-control" id="organization" name="organization" placeholder="e.g., Apex Institute / Tech Corp" value="<?php echo isset($_POST['organization']) ? htmlspecialchars($_POST['organization']) : ''; ?>" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label fw-semibold">Password <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted"><i class="fa-solid fa-lock"></i></span>
                                <input type="password" class="form-control" id="password" name="password" placeholder="Min. 8 characters" required>
                            </div>
                            <div class="form-text text-muted small">Must be at least 8 characters long.</div>
                        </div>

                        <div class="mb-4">
                            <label for="confirm_password" class="form-label fw-semibold">Confirm Password <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted"><i class="fa-solid fa-lock"></i></span>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Retype password" required>
                            </div>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-warning text-dark fw-bold btn-lg">
                                <i class="fa-solid fa-user-plus me-2"></i>Sign Up
                            </button>
                        </div>
                    </form>
                </div>
                <div class="card-footer bg-light text-center py-3">
                    <span class="text-muted small">Already have an account? <a href="login.php" class="text-decoration-none fw-semibold">Log In Here</a></span>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
include $base_path . 'includes/footer.php';
?>
