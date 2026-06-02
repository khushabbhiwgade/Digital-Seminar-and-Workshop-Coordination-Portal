<?php
// admin/workshops/edit.php
$base_path = '../../';
$page_title = 'Edit Workshop - Campus Connect';
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

// 1. Fetch current workshop details
if (!isset($_GET['id'])) {
    header("Location: manage.php");
    exit;
}

$workshop_id = intval($_GET['id']);
$sql = "SELECT * FROM workshops WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $workshop_id);
$stmt->execute();
$res = $stmt->get_result();

if (!$res || $res->num_rows === 0) {
    header("Location: manage.php");
    exit;
}

$ws = $res->fetch_assoc();

// 2. Process updates
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
    $status        = trim($_POST['status'] ?? '');

    // Form validation
    if (empty($title) || empty($domain) || empty($host) || empty($speaker) || $capacity <= 0 || empty($start_date) || empty($end_date) || empty($venue) || empty($status)) {
        $error_msg = "Please fill in all mandatory fields and enter a positive capacity.";
    } elseif (strtotime($end_date) < strtotime($start_date)) {
        $error_msg = "End Date cannot be earlier than the Start Date.";
    } elseif (!in_array($domain, $domains)) {
        $error_msg = "Please select a valid workshop domain.";
    } elseif (!in_array($status, ['active', 'upcoming', 'archived'])) {
        $error_msg = "Please select a valid workshop status.";
    } else {
        $poster_filename = $ws['poster_image'];

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
                $upload_dir = $base_path . 'uploads/workshops/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }

                // Generate a clean unique filename
                $new_filename = 'workshop_' . time() . '_' . random_int(1000, 9999) . '.' . $file_ext;
                $destination = $upload_dir . $new_filename;

                if (move_uploaded_file($file_tmp, $destination)) {
                    // Success! Delete the old poster file from the server if it exists
                    if (!empty($ws['poster_image'])) {
                        $old_file_path = $upload_dir . $ws['poster_image'];
                        if (file_exists($old_file_path)) {
                            unlink($old_file_path);
                        }
                    }
                    $poster_filename = $new_filename;
                } else {
                    $error_msg = "Failed to save the newly uploaded image to the server uploads directory.";
                }
            }
        }

        // Save to DB if no validation errors have occurred
        if (empty($error_msg)) {
            // Adjust seats_remaining relative to changes in capacity
            $capacity_diff = $capacity - intval($ws['capacity']);
            $seats_remaining = max(0, intval($ws['seats_remaining']) + $capacity_diff);

            // Dynamically recalculate status if not explicitly archived
            if ($status !== 'archived') {
                $status = 'active';
                if (strtotime($start_date) > time()) {
                    $status = 'upcoming';
                }
            }

            // prepared statement
            $update_sql = "UPDATE workshops SET title = ?, domain = ?, host = ?, speaker = ?, description = ?, prerequisites = ?, capacity = ?, seats_remaining = ?, start_date = ?, end_date = ?, venue = ?, poster_image = ?, status = ? WHERE id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("ssssssiisssssi", $title, $domain, $host, $speaker, $description, $prerequisites, $capacity, $seats_remaining, $start_date, $end_date, $venue, $poster_filename, $status, $workshop_id);

            if ($update_stmt->execute()) {
                header("Location: manage.php?msg=edit_success");
                exit;
            } else {
                $error_msg = "Database update failed: " . $update_stmt->error;
            }
        }
    }
}

include $base_path . 'includes/header.php';
include $base_path . 'includes/navbar.php';
?>

<div class="container my-5">
    <!-- Back button link -->
    <div class="mb-3">
        <a href="manage.php" class="text-decoration-none fw-semibold text-dark"><i class="fa-solid fa-arrow-left me-1"></i>Back to Workshops Directory</a>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow border-0 rounded-3 overflow-hidden bg-white">
                <div class="card-header bg-dark text-center text-white py-4" style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); border-bottom: 2px solid #eab308;">
                    <i class="fa-solid fa-pen-to-square text-warning fs-1 mb-2"></i>
                    <h3 class="fw-bold mb-0">Modify Workshop</h3>
                    <p class="text-muted small mb-0 mt-1">Update registration, host, or scheduling details.</p>
                </div>
                <div class="card-body p-4 p-md-5">
                    <?php if (!empty($error_msg)): ?>
                        <div class="alert alert-danger alert-dismissible fade show border-start border-4 border-danger shadow-sm" role="alert">
                            <i class="fa-solid fa-triangle-exclamation me-2"></i><?php echo htmlspecialchars($error_msg); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="edit.php?id=<?php echo $workshop_id; ?>" enctype="multipart/form-data" novalidate>
                        <!-- Title Field -->
                        <div class="mb-3">
                            <label for="title" class="form-label fw-semibold">Workshop Title <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($_POST['title'] ?? $ws['title']); ?>" required>
                        </div>

                        <div class="row">
                            <!-- Domain dropdown -->
                            <div class="col-md-6 mb-3">
                                <label for="domain" class="form-label fw-semibold">Workshop Domain <span class="text-danger">*</span></label>
                                <select class="form-select" id="domain" name="domain" required>
                                    <?php $current_domain = $_POST['domain'] ?? $ws['domain']; ?>
                                    <?php foreach ($domains as $d): ?>
                                        <option value="<?php echo htmlspecialchars($d); ?>" <?php echo $current_domain === $d ? 'selected' : ''; ?>><?php echo htmlspecialchars($d); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Host Field -->
                            <div class="col-md-6 mb-3">
                                <label for="host" class="form-label fw-semibold">Hosting Department / Club <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="host" name="host" value="<?php echo htmlspecialchars($_POST['host'] ?? $ws['host']); ?>" required>
                            </div>
                        </div>

                        <div class="row">
                            <!-- Speaker Field -->
                            <div class="col-md-6 mb-3">
                                <label for="speaker" class="form-label fw-semibold">Keynote Speaker <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light text-muted"><i class="fa-solid fa-user-tie"></i></span>
                                    <input type="text" class="form-control" id="speaker" name="speaker" value="<?php echo htmlspecialchars($_POST['speaker'] ?? $ws['speaker']); ?>" required>
                                </div>
                            </div>

                            <!-- Capacity Field -->
                            <div class="col-md-6 mb-3">
                                <label for="capacity" class="form-label fw-semibold">Seat Capacity <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light text-muted"><i class="fa-solid fa-users"></i></span>
                                    <input type="number" class="form-control" id="capacity" name="capacity" min="1" value="<?php echo htmlspecialchars($_POST['capacity'] ?? $ws['capacity']); ?>" required>
                                </div>
                            </div>
                        </div>

                        <!-- Description Field -->
                        <div class="mb-3">
                            <label for="description" class="form-label fw-semibold">Workshop Description</label>
                            <textarea class="form-control" id="description" name="description" rows="4"><?php echo htmlspecialchars($_POST['description'] ?? $ws['description']); ?></textarea>
                        </div>

                        <!-- Prerequisites Field -->
                        <div class="mb-3">
                            <label for="prerequisites" class="form-label fw-semibold">Academic Prerequisites</label>
                            <textarea class="form-control" id="prerequisites" name="prerequisites" rows="2"><?php echo htmlspecialchars($_POST['prerequisites'] ?? $ws['prerequisites']); ?></textarea>
                        </div>

                        <div class="row">
                            <!-- Start Date -->
                            <div class="col-md-6 mb-3">
                                <label for="start_date" class="form-label fw-semibold">Start Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo htmlspecialchars($_POST['start_date'] ?? $ws['start_date']); ?>" required>
                            </div>

                            <!-- End Date -->
                            <div class="col-md-6 mb-3">
                                <label for="end_date" class="form-label fw-semibold">End Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo htmlspecialchars($_POST['end_date'] ?? $ws['end_date']); ?>" required>
                            </div>
                        </div>

                        <div class="row">
                            <!-- Venue Field -->
                            <div class="col-md-6 mb-3">
                                <label for="venue" class="form-label fw-semibold">Event Venue <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light text-muted"><i class="fa-solid fa-location-dot"></i></span>
                                    <input type="text" class="form-control" id="venue" name="venue" value="<?php echo htmlspecialchars($_POST['venue'] ?? $ws['venue']); ?>" required>
                                </div>
                            </div>

                            <!-- Status Dropdown -->
                            <div class="col-md-6 mb-3">
                                <label for="status" class="form-label fw-semibold">Workshop Status <span class="text-danger">*</span></label>
                                <select class="form-select" id="status" name="status" required>
                                    <?php $current_status = $_POST['status'] ?? $ws['status']; ?>
                                    <option value="active" <?php echo $current_status === 'active' ? 'selected' : ''; ?>>Active / On-Going</option>
                                    <option value="upcoming" <?php echo $current_status === 'upcoming' ? 'selected' : ''; ?>>Upcoming</option>
                                    <option value="archived" <?php echo $current_status === 'archived' ? 'selected' : ''; ?>>Archived</option>
                                </select>
                            </div>
                        </div>

                        <!-- Poster Image Field -->
                        <div class="mb-4">
                            <label class="form-label fw-semibold d-block">Workshop Poster Image</label>
                            
                            <?php if (!empty($ws['poster_image']) && file_exists($base_path . 'uploads/workshops/' . $ws['poster_image'])): ?>
                                <div class="d-flex align-items-center gap-3 mb-3 p-3 bg-light rounded border">
                                    <img src="<?php echo $base_path . 'uploads/workshops/' . htmlspecialchars($ws['poster_image']); ?>" class="img-thumbnail" style="max-height: 80px; max-width: 80px; object-fit: cover;" alt="Current Poster">
                                    <div>
                                        <span class="small fw-semibold text-muted d-block">Current Poster Image</span>
                                        <span class="text-dark small text-break"><?php echo htmlspecialchars($ws['poster_image']); ?></span>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted"><i class="fa-solid fa-file-image"></i></span>
                                <input type="file" class="form-control" id="poster_image" name="poster_image" accept=".jpg,.jpeg,.png,.gif">
                            </div>
                            <div class="form-text text-muted small mt-1">Select a new image file only if you wish to replace the current poster. JPG, JPEG, PNG, GIF allowed (Max 2MB).</div>
                        </div>

                        <!-- Submit Buttons -->
                        <div class="d-grid gap-2 d-sm-flex justify-content-sm-end mt-4">
                            <a href="manage.php" class="btn btn-outline-secondary px-4 py-2 fw-semibold">Cancel</a>
                            <button type="submit" class="btn btn-warning text-dark fw-bold px-5 py-2">
                                <i class="fa-solid fa-square-check me-2"></i>Save Changes
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
