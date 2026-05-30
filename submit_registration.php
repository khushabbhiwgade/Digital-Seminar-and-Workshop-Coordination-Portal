<?php
// 1. Include the database configuration file
// Change 'db.php' to your actual database file name if it's different!
require_once 'db_connect.php'; 

// Look inside your db.php file. If your connection variable is named something like 
// $connect or $link, change this line below to match it:
if (!isset($conn) && isset($connect)) { $conn = $connect; }

// Initialize message variables for Bootstrap alerts
$message = "";
$message_type = "";

// 2. Process form submission when the POST request arrives
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Collect and sanitize form inputs
    $full_name   = trim($_POST['full_name'] ?? '');
    $email       = trim($_POST['email'] ?? '');
    $mobile      = trim($_POST['mobile'] ?? '');
    $college     = trim($_POST['college'] ?? '');
    $department  = trim($_POST['department'] ?? '');
    $year_of_study = trim($_POST['year_of_study'] ?? '');
    $event_name  = trim($_POST['event_name'] ?? '');

    // Validation: Ensure mandatory fields are not empty
    if (empty($full_name) || empty($email) || empty($mobile) || empty($event_name)) {
        $message = "Please fill in all mandatory fields marked with an asterisk (*).";
        $message_type = "danger";
    } else {
        // Double check that the database connection variable actually exists
        if (!isset($conn) || $conn === null) {
            $message = "Database connection setup missing. Please ensure your database file initializes <strong>\$conn</strong>.";
            $message_type = "danger";
        } else {
            try {
                // 3. Prepare SQL query (Adjust table name 'registrations' if yours is named differently)
                $sql = "INSERT INTO registrations (full_name, email, mobile, college, department, year_of_study, event_name) 
                        VALUES (?, ?, ?, ?, ?, ?, ?)";
                
                $stmt = $conn->prepare($sql);
                
                if ($stmt) {
                    // Bind parameters and execute
                    $stmt->bind_param("sssssss", $full_name, $email, $mobile, $college, $department, $year_of_study, $event_name);
                    
                    if ($stmt->execute()) {
                        $message = "Registration Successful! Thank you for registering for <strong>" . htmlspecialchars($event_name) . "</strong>.";
                        $message_type = "success";
                    } else {
                        $message = "Execution failed: " . $stmt->error;
                        $message_type = "danger";
                    }
                    $stmt->close();
                } else {
                    $message = "Failed to prepare query: " . $conn->error;
                    $message_type = "danger";
                }
            } catch (Exception $e) {
                $message = "An unexpected error occurred: " . $e->getMessage();
                $message_type = "danger";
            }
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
            <a class="navbar-brand d-flex align-items-center" href="index.php">
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
                        <p class="text-muted small px-3">A confirmation flag has been generated. You can now return back to explore more upcoming events.</p>
                    <?php else: ?>
                        <div class="text-danger display-1 mb-3">
                            <i class="fa-solid fa-circle-exclcancel fa-circle-xmark"></i>
                        </div>
                        <h2 class="fw-bold text-dark mb-3">Registration Failed</h2>
                        <div class="alert alert-danger border-0 text-start">
                            <i class="fa-solid fa-triangle-exclamation me-2"></i><?php echo $message; ?>
                        </div>
                        <p class="text-muted small">If this error persists, ensure Apache and MySQL are running inside your XAMPP Control Panel control deck.</p>
                    <?php endif; ?>
                    
                    <div class="d-grid gap-2 d-sm-flex justify-content-sm-center mt-4">
                        <a href="index.php" class="btn btn-dark px-4 py-2"><i class="fa-solid fa-house me-2"></i>Go to Home</a>
                        <a href="register.php" class="btn btn-outline-secondary px-4 py-2"><i class="fa-solid fa-arrow-left me-2"></i>Back to Form</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Minimal Clean Footer -->
    <footer class="bg-dark text-white-50 text-center py-3 mt-auto">
        <p class="mb-0 small">&copy; 2026 CampusConnect Coordination Suite. Database Verification Module.</p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>