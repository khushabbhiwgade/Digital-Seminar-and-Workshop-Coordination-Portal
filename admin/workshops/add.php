<?php
// admin/workshops/add.php
$base_path = '../../';
$page_title = 'Add New Workshop - Campus Connect';
$active_page = 'admin_workshops';

require_once $base_path . 'includes/auth.php';
require_role('admin');

require_once $base_path . 'config/db_connect.php';

$error_msg = "";
$success_msg = "";

$domains = [
    'AI & ML', 'Data Science', 'Cybersecurity', 'Cloud Computing', 'IoT', 
    'Embedded Systems', 'Web Development', 'DevOps', 'Blockchain', 'VLSI', 
    'Computer Architecture', 'Robotics', 'AR/VR', 'UI/UX', 'Mobile Development'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title         = trim($_POST['title'] ?? '');
    $domain        = trim($_POST['domain'] ?? '');
    $host          = trim($_POST['host'] ?? '');
    $speaker       = trim($_POST['speaker'] ?? '');
    $description   = trim($_POST['description'] ?? '');
    $prerequisites = trim($_POST['prerequisites'] ?? '');
    $capacity      = intval($_POST['capacity'] ?? 0);
    $start_date    = trim($_POST['start_date'] ?? '');
    $end_date      = trim($_POST['end_date'] ?? '');
    $venue         = trim($_POST['venue'] ?? '');

    // Form validation
    if (empty($title) || empty($domain) || empty($host) || empty($speaker) || $capacity <= 0 || empty($start_date) || empty($end_date) || empty($venue)) {
        $error_msg = "Please fill in all mandatory fields and enter a positive capacity.";
    } elseif (strtotime($end_date) < strtotime($start_date)) {
        $error_msg = "End Date cannot be earlier than the Start Date.";
    } elseif (!in_array($domain, $domains)) {
        $error_msg = "Please select a valid workshop domain.";
    } else {
        $poster_filename = null;

        // Image file upload validation & processing
        if (isset($_FILES['poster_image']) && $_FILES['poster_image']['error'] !== UPLOAD_ERR_NO_FILE) {
            $file = $_FILES['poster_image'];
            $file_name = $file['name'];
            $file_size = $file['size'];
            $file_tmp  = $file['tmp_name'];
            $file_err  = $file['error'];

            $allowed_exts = ['jpg', 'jpeg', 'png', 'gif'];
            $file_info = pathinfo($file_name);
            $file_ext  = strtolower($file_info['extension'] ?? '');

            if ($file_err !== UPLOAD_ERR_OK) {
                $error_msg = "File upload encountered an error (Code: $file_err).";
            } elseif (!in_array($file_ext, $allowed_exts)) {
                $error_msg = "Invalid file type. Only JPG, JPEG, PNG, and GIF images are allowed.";
            } elseif ($file_size > 2 * 1024 * 1024) { // 2MB Limit
                $error_msg = "File size must be under 2MB.";
            } else {
                // Ensure directory exists and is writable
                $upload_dir = $base_path . 'uploads/workshops/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }

                // Generate clean unique filename to prevent duplicates
                $poster_filename = 'workshop_' . time() . '_' . random_int(1000, 9999) . '.' . $file_ext;
                $destination = $upload_dir . $poster_filename;

                if (!move_uploaded_file($file_tmp, $destination)) {
                    $error_msg = "Failed to save the uploaded image to the server uploads directory.";
                    $poster_filename = null;
                }
            }
        }

        // Save to DB if no validation errors have occurred
        if (empty($error_msg)) {
            // Dynamically calculate status (upcoming or active)
            $status = 'active';
            if (strtotime($start_date) > time()) {
                $status = 'upcoming';
            }

            // prepared statement
            $sql = "INSERT INTO workshops (title, domain, host, speaker, description, prerequisites, capacity, seats_remaining, start_date, end_date, venue, poster_image, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            
            // seats_remaining matches capacity on creation
            $seats_remaining = $capacity;
            $stmt->bind_param("ssssssiisssss", $title, $domain, $host, $speaker, $description, $prerequisites, $capacity, $seats_remaining, $start_date, $end_date, $venue, $poster_filename, $status);

            if ($stmt->execute()) {
                header("Location: manage.php?msg=add_success");
                exit;
            } else {
                $error_msg = "Database insert failed: " . $stmt->error;
                // Garbage-collect the uploaded file if database fails
                if ($poster_filename && file_exists($base_path . 'uploads/workshops/' . $poster_filename)) {
                    unlink($base_path . 'uploads/workshops/' . $poster_filename);
                }
            }
        }
    }
}

include $base_path . 'includes/header.php';
include $base_path . 'includes/navbar.php';
?>

<div class="container my-5">
    <!-- Back to management page button link -->
    <div class="mb-3">
        <a href="manage.php" class="text-decoration-none fw-semibold text-dark"><i class="fa-solid fa-arrow-left me-1"></i>Back to Workshops Directory</a>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow border-0 rounded-3 overflow-hidden bg-white">
                <div class="card-header bg-dark text-center text-white py-4" style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); border-bottom: 2px solid #eab308;">
                    <i class="fa-solid fa-circle-plus text-warning fs-1 mb-2"></i>
                    <h3 class="fw-bold mb-0">Create Workshop</h3>
                    <p class="text-muted small mb-0 mt-1">Register a new seminar or interactive training session.</p>
                </div>
                <div class="card-body p-4 p-md-5">
                    <?php if (!empty($error_msg)): ?>
                        <div class="alert alert-danger alert-dismissible fade show border-start border-4 border-danger shadow-sm" role="alert">
                            <i class="fa-solid fa-triangle-exclamation me-2"></i><?php echo htmlspecialchars($error_msg); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="add.php" enctype="multipart/form-data" novalidate>
                        <!-- Title Field -->
                        <div class="mb-3">
                            <label for="title" class="form-label fw-semibold">Workshop Title <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="title" name="title" placeholder="e.g. Masterclass in Transformers & Large Language Models" value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>" required>
                        </div>

                        <div class="row">
                            <!-- Domain dropdown -->
                            <div class="col-md-6 mb-3">
                                <label for="domain" class="form-label fw-semibold">Workshop Domain <span class="text-danger">*</span></label>
                                <select class="form-select" id="domain" name="domain" required>
                                    <option value="" disabled <?php echo !isset($_POST['domain']) ? 'selected' : ''; ?>>Select Domain</option>
                                    <?php foreach ($domains as $d): ?>
                                        <option value="<?php echo htmlspecialchars($d); ?>" <?php echo (isset($_POST['domain']) && $_POST['domain'] === $d) ? 'selected' : ''; ?>><?php echo htmlspecialchars($d); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Host Field -->
                            <div class="col-md-6 mb-3">
                                <label for="host" class="form-label fw-semibold">Hosting Department / Club <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="host" name="host" placeholder="e.g. Department of AI & ML" value="<?php echo isset($_POST['host']) ? htmlspecialchars($_POST['host']) : ''; ?>" required>
                            </div>
                        </div>

                        <div class="row">
                            <!-- Speaker Field -->
                            <div class="col-md-6 mb-3">
                                <label for="speaker" class="form-label fw-semibold">Keynote Speaker <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light text-muted"><i class="fa-solid fa-user-tie"></i></span>
                                    <input type="text" class="form-control" id="speaker" name="speaker" placeholder="Dr. John Doe" value="<?php echo isset($_POST['speaker']) ? htmlspecialchars($_POST['speaker']) : ''; ?>" required>
                                </div>
                            </div>

                            <!-- Capacity Field -->
                            <div class="col-md-6 mb-3">
                                <label for="capacity" class="form-label fw-semibold">Seat Capacity <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light text-muted"><i class="fa-solid fa-users"></i></span>
                                    <input type="number" class="form-control" id="capacity" name="capacity" min="1" placeholder="50" value="<?php echo isset($_POST['capacity']) ? htmlspecialchars($_POST['capacity']) : ''; ?>" required>
                                </div>
                            </div>
                        </div>

                        <!-- Description Field -->
                        <div class="mb-3">
                            <label for="description" class="form-label fw-semibold">Workshop Description</label>
                            <textarea class="form-control" id="description" name="description" rows="4" placeholder="Detail the timeline, topics, and practical labs included in this workshop..."><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                        </div>

                        <!-- Prerequisites Field -->
                        <div class="mb-3">
                            <label for="prerequisites" class="form-label fw-semibold">Academic Prerequisites</label>
                            <textarea class="form-control" id="prerequisites" name="prerequisites" rows="2" placeholder="e.g. Basic logic in Python, understanding of matrices..."><?php echo isset($_POST['prerequisites']) ? htmlspecialchars($_POST['prerequisites']) : ''; ?></textarea>
                        </div>

                        <div class="row">
                            <!-- Start Date -->
                            <div class="col-md-6 mb-3">
                                <label for="start_date" class="form-label fw-semibold">Start Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo isset($_POST['start_date']) ? htmlspecialchars($_POST['start_date']) : ''; ?>" required>
                            </div>

                            <!-- End Date -->
                            <div class="col-md-6 mb-3">
                                <label for="end_date" class="form-label fw-semibold">End Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo isset($_POST['end_date']) ? htmlspecialchars($_POST['end_date']) : ''; ?>" required>
                            </div>
                        </div>

                        <div class="row">
                            <!-- Venue Field -->
                            <div class="col-md-6 mb-3">
                                <label for="venue" class="form-label fw-semibold">Event Venue <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light text-muted"><i class="fa-solid fa-location-dot"></i></span>
                                    <input type="text" class="form-control" id="venue" name="venue" placeholder="Seminar Hall A, CSE Lab 3" value="<?php echo isset($_POST['venue']) ? htmlspecialchars($_POST['venue']) : ''; ?>" required>
                                </div>
                            </div>

                            <!-- Poster File Image Field -->
                            <div class="col-md-6 mb-3">
                                <label for="poster_image" class="form-label fw-semibold">Upload Poster Image</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light text-muted"><i class="fa-solid fa-file-image"></i></span>
                                    <input type="file" class="form-control" id="poster_image" name="poster_image" accept=".jpg,.jpeg,.png,.gif">
                                </div>
                                <div class="form-text text-muted small">Max file size 2MB. Extensions allowed: JPG, JPEG, PNG, GIF.</div>
                            </div>
                        </div>

                        <!-- Action Submit Buttons -->
                        <div class="d-grid gap-2 d-sm-flex justify-content-sm-end mt-4">
                            <a href="manage.php" class="btn btn-outline-secondary px-4 py-2 fw-semibold">Cancel</a>
                            <button type="submit" class="btn btn-warning text-dark fw-bold px-5 py-2">
                                <i class="fa-solid fa-paper-plane me-2"></i>Publish Workshop
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
include $base_path . 'includes/footer.php';
?>
