<?php
// student/submit_registration.php
// ------------------------------------------------------------
// Process student event registration with transaction integrity
// ------------------------------------------------------------

// 1. Include database connection and auth helpers
require_once '../config/db_connect.php'; 
require_once '../includes/auth.php';

// Enforce student role
require_role('student');

// Initialize messaging variables
$message = "";
$message_type = "";
$ticket_token = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = get_user_id();
    
    // Collect and sanitize form inputs
    $full_name     = trim($_POST['full_name'] ?? '');
    $email         = trim($_POST['email'] ?? '');
    $mobile        = trim($_POST['mobile'] ?? '');
    $college       = trim($_POST['college'] ?? '');
    $department    = trim($_POST['department'] ?? '');
    $year_of_study = trim($_POST['year_of_study'] ?? '');
    $event_name    = trim($_POST['event_name'] ?? '');

    $errors = [];

    if (empty($event_name)) {
        $errors[] = "Please select a seminar / workshop.";
    }

    if (!empty($errors)) {
        $message = implode("<br>", $errors);
        $message_type = "danger";
    } else {
        try {
            // Retrieve Event ID and check seat availability
            $event_sql = "SELECT id, seats_remaining, title FROM events WHERE title = ?";
            $event_stmt = $conn->prepare($event_sql);
            $event_stmt->bind_param("s", $event_name);
            $event_stmt->execute();
            $event_res = $event_stmt->get_result();

            if ($event_res && $event_res->num_rows > 0) {
                $event_data = $event_res->fetch_assoc();
                $event_id = $event_data['id'];
                $seats_remaining = $event_data['seats_remaining'];
                
                if ($seats_remaining <= 0) {
                    $message = "Sorry, all seats for '<strong>" . htmlspecialchars($event_data['title']) . "</strong>' are fully booked.";
                    $message_type = "danger";
                }
            } else {
                $message = "The selected event is invalid.";
                $message_type = "danger";
            }

            if (empty($message)) {
                // Start Transaction to guarantee user profile update, registration, and seat deduction consistency
                $conn->begin_transaction();

                // If mobile/college/department/year are supplied, update user profile if currently empty
                // We fetch current profile first to check
                $profile_sql = "SELECT mobile, college, department, year_of_study FROM users WHERE id = ?";
                $profile_stmt = $conn->prepare($profile_sql);
                $profile_stmt->bind_param("i", $user_id);
                $profile_stmt->execute();
                $profile_res = $profile_stmt->get_result();
                
                if ($profile_res && $profile_res->num_rows > 0) {
                    $profile = $profile_res->fetch_assoc();
                    
                    // Update only empty columns
                    $new_mobile = !empty($profile['mobile']) ? $profile['mobile'] : $mobile;
                    $new_college = !empty($profile['college']) ? $profile['college'] : $college;
                    $new_department = !empty($profile['department']) ? $profile['department'] : $department;
                    $new_year = !empty($profile['year_of_study']) ? $profile['year_of_study'] : $year_of_study;

                    $update_profile_sql = "UPDATE users SET mobile = ?, college = ?, department = ?, year_of_study = ? WHERE id = ?";
                    $update_stmt = $conn->prepare($update_profile_sql);
                    $update_stmt->bind_param("ssssi", $new_mobile, $new_college, $new_department, $new_year, $user_id);
                    if (!$update_stmt->execute()) {
                        throw new Exception("Error updating student profile details.");
                    }
                }

                // Check if already registered for this event
                $reg_check_sql = "SELECT id, token FROM registrations WHERE user_id = ? AND event_id = ?";
                $reg_check_stmt = $conn->prepare($reg_check_sql);
                $reg_check_stmt->bind_param("ii", $user_id, $event_id);
                $reg_check_stmt->execute();
                $reg_check_res = $reg_check_stmt->get_result();

                if ($reg_check_res && $reg_check_res->num_rows > 0) {
                    $existing_reg = $reg_check_res->fetch_assoc();
                    $message = "You have already registered for this event! Your existing ticket token is <strong>" . htmlspecialchars($existing_reg['token']) . "</strong>.";
                    $message_type = "warning";
                    $conn->rollback();
                } else {
                    // Generate a secure ticket token: CAMPUS-XXXXXX
                    $token = "CAMPUS-" . strtoupper(substr(md5(uniqid(rand(), true)), 0, 6));

                    // Insert registration
                    $insert_reg_sql = "INSERT INTO registrations (user_id, event_id, token, attended) VALUES (?, ?, ?, 0)";
                    $insert_reg_stmt = $conn->prepare($insert_reg_sql);
                    $insert_reg_stmt->bind_param("iis", $user_id, $event_id, $token);

                    if ($insert_reg_stmt->execute()) {
                        // Decrement event seats
                        $update_seats_sql = "UPDATE events SET seats_remaining = seats_remaining - 1 WHERE id = ?";
                        $update_seats_stmt = $conn->prepare($update_seats_sql);
                        $update_seats_stmt->bind_param("i", $event_id);

                        if ($update_seats_stmt->execute()) {
                            $conn->commit();
                            $ticket_token = $token;
                            $message = "Registration Successful! Thank you for registering for <strong>" . htmlspecialchars($event_name) . "</strong>.";
                            $message_type = "success";
                        } else {
                            throw new Exception("Error updating event seats: " . $update_seats_stmt->error);
                        }
                    } else {
                        throw new Exception("Error saving registration details: " . $insert_reg_stmt->error);
                    }
                }
            }
        } catch (Exception $e) {
            $conn->rollback();
            $message = "An unexpected error occurred: " . $e->getMessage();
            $message_type = "danger";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration Status - Campus Connect</title>
    <!-- Bootstrap 5 CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-light d-flex flex-column min-vh-100">

    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="../index.php">
                <i class="fa-solid fa-graduation-cap text-warning me-2"></i>
                <span>Campus Connect Portal</span>
            </a>
        </div>
    </nav>

    <!-- Main Content Container -->
    <div class="container my-auto py-5">
        <div class="row justify-content-center">
            <div class="col-md-7">
                <div class="card shadow border-0 p-4 rounded-3 text-center">
                    
                    <?php if ($message_type === "success"): ?>
                        <div class="text-success display-1 mb-3">
                            <i class="fa-solid fa-circle-check"></i>
                        </div>
                        <h2 class="fw-bold text-dark mb-3">Submission Received!</h2>
                        <div class="alert alert-success border-0 px-3">
                            <?php echo $message; ?>
                        </div>
                        <?php if (!empty($ticket_token)): ?>
                            <div class="card bg-light border-dashed my-3 p-3">
                                <span class="text-muted small text-uppercase fw-bold">Your Ticket Check-in Token</span>
                                <h3 class="font-monospace text-primary fw-bold mt-1 mb-0"><?php echo htmlspecialchars($ticket_token); ?></h3>
                                <p class="text-muted small mb-0 mt-2"><i class="fa-solid fa-qrcode me-1"></i> Present this token code to the event coordinator desk on the day of the event.</p>
                            </div>
                        <?php endif; ?>
                        <p class="text-muted small px-3">We have successfully reserved your slot. You can view this registration inside your student dashboard.</p>
                    <?php else: ?>
                        <div class="text-<?php echo ($message_type === 'warning') ? 'warning' : 'danger'; ?> display-1 mb-3">
                            <i class="fa-solid <?php echo ($message_type === 'warning') ? 'fa-circle-exclamation' : 'fa-circle-xmark'; ?>"></i>
                        </div>
                        <h2 class="fw-bold text-dark mb-3">Registration Issue</h2>
                        <div class="alert alert-<?php echo $message_type; ?> border-0 text-start">
                            <i class="fa-solid fa-triangle-exclamation me-2"></i><?php echo $message; ?>
                        </div>
                        <p class="text-muted small">Please verify your details and make sure you aren't already registered for this workshop.</p>
                    <?php endif; ?>
                    
                    <div class="d-grid gap-2 d-sm-flex justify-content-sm-center mt-4">
                        <a href="dashboard.php" class="btn btn-dark px-4 py-2"><i class="fa-solid fa-table-columns me-2"></i>Go to Dashboard</a>
                        <a href="register.php" class="btn btn-outline-secondary px-4 py-2"><i class="fa-solid fa-arrow-left me-2"></i>Back to Form</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Minimal Clean Footer -->
    <footer class="bg-dark text-white-50 text-center py-3 mt-auto">
        <p class="mb-0 small">&copy; 2026 CampusConnect Coordination Suite. Authentication System.</p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
