<?php
// workshop_details.php
$base_path = './';
$page_title = 'Workshop Details - Campus Connect';
$active_page = 'events';

require_once $base_path . 'includes/auth.php';
require_once $base_path . 'config/db_connect.php';

// 1. Fetch workshop ID from query string
if (!isset($_GET['id'])) {
    header("Location: events.php");
    exit;
}

$workshop_id = intval($_GET['id']);

// 2. Query workshop records using prepared statement
$sql = "SELECT * FROM workshops WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $workshop_id);
$stmt->execute();
$res = $stmt->get_result();

if (!$res || $res->num_rows === 0) {
    header("Location: events.php");
    exit;
}

$ws = $res->fetch_assoc();

// 3. Evaluate User Registration State
$is_guest = !is_logged_in();
$user_role = get_user_role();
$user_id = get_user_id();

$is_registered = false;
$reg_status = "";

if (!$is_guest && $user_role === 'student') {
    // Check if already registered
    $chk_sql = "SELECT id, status FROM workshop_registrations WHERE user_id = ? AND workshop_id = ?";
    $chk_stmt = $conn->prepare($chk_sql);
    $chk_stmt->bind_param("ii", $user_id, $workshop_id);
    $chk_stmt->execute();
    $chk_res = $chk_stmt->get_result();
    
    if ($chk_res && $chk_res->num_rows > 0) {
        $is_registered = true;
        $reg_status = $chk_res->fetch_assoc()['status'];
    }
}

include $base_path . 'includes/header.php';
include $base_path . 'includes/navbar.php';
?>

<!-- Banner Header -->
<div class="text-white py-5 mb-5" style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); border-bottom: 5px solid #eab308;">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-8 text-center text-lg-start">
                <span class="badge bg-warning text-dark text-uppercase fw-bold px-3 py-2 mb-3 fs-7 letter-spacing-sm"><?php echo htmlspecialchars($ws['domain']); ?></span>
                <h1 class="display-5 fw-bold lh-sm text-white"><?php echo htmlspecialchars($ws['title']); ?></h1>
                <p class="lead text-muted mb-0 mt-2">Presented by <span class="text-white fw-semibold"><?php echo htmlspecialchars($ws['speaker']); ?></span></p>
            </div>
            <div class="col-lg-4 text-center text-lg-end mt-4 mt-lg-0">
                <span class="badge bg-danger px-3 py-2 fs-6 text-capitalize"><i class="fa-solid fa-tag me-1"></i><?php echo htmlspecialchars($ws['status']); ?></span>
            </div>
        </div>
    </div>
</div>

<main class="container my-5">
    <div class="row g-5">
        <!-- Left Column: Details -->
        <div class="col-lg-8">
            <!-- Poster Display -->
            <?php if (!empty($ws['poster_image']) && file_exists($base_path . 'uploads/workshops/' . $ws['poster_image'])): ?>
                <div class="mb-4 rounded-3 overflow-hidden shadow-md">
                    <img src="<?php echo $base_path . 'uploads/workshops/' . htmlspecialchars($ws['poster_image']); ?>" class="img-fluid w-100" style="max-height: 400px; object-fit: cover;" alt="Workshop Poster">
                </div>
            <?php endif; ?>

            <!-- Description Card -->
            <div class="card border-0 shadow-sm rounded-3 mb-4 bg-white">
                <div class="card-body p-4 p-md-5">
                    <h3 class="fw-bold mb-3 text-dark"><i class="fa-solid fa-circle-info text-primary me-2"></i>Workshop Overview</h3>
                    <div class="text-muted leading-relaxed" style="white-space: pre-line;">
                        <?php echo htmlspecialchars($ws['description'] ?? 'No overview description provided.'); ?>
                    </div>
                </div>
            </div>

            <!-- Prerequisites Card -->
            <?php if (!empty($ws['prerequisites'])): ?>
                <div class="card border-0 shadow-sm rounded-3 mb-4 bg-white">
                    <div class="card-body p-4 p-md-5">
                        <h3 class="fw-bold mb-3 text-dark"><i class="fa-solid fa-list-check text-warning me-2"></i>Academic Prerequisites</h3>
                        <div class="text-muted leading-relaxed" style="white-space: pre-line;">
                            <?php echo htmlspecialchars($ws['prerequisites']); ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Right Column: Registration & Metadata Sidebar -->
        <div class="col-lg-4">
            <!-- Action / Status Card -->
            <div class="card border-0 shadow-lg rounded-3 mb-4 overflow-hidden bg-white text-center">
                <div class="card-header bg-dark py-3 text-white fw-bold">
                    <i class="fa-solid fa-receipt me-2 text-warning"></i>Registration Desk
                </div>
                <div class="card-body p-4">
                    <!-- Seats Availability Counter -->
                    <div class="mb-4 p-3 bg-light rounded-3 border">
                        <span class="text-muted small text-uppercase fw-bold d-block mb-1">Seats Remaining</span>
                        <div class="display-6 fw-bold text-success"><?php echo intval($ws['seats_remaining']); ?> <span class="fs-5 text-muted">/ <?php echo intval($ws['capacity']); ?></span></div>
                    </div>

                    <!-- Dynamic Action Buttons -->
                    <?php if ($is_guest): ?>
                        <!-- 1. Guest state -->
                        <div class="alert alert-warning border-start border-4 border-warning small text-start mb-3" role="alert">
                            <i class="fa-solid fa-circle-info me-2 text-warning"></i>Please log in to your participant account to secure your seat.
                        </div>
                        <a href="<?php echo $base_path; ?>auth/login.php" class="btn btn-warning text-dark fw-bold btn-lg w-100 shadow-sm py-2.5">
                            <i class="fa-solid fa-right-to-bracket me-2"></i>Login to Register
                        </a>

                    <?php elseif ($user_role !== 'student'): ?>
                        <!-- 2. Admin/Coordinator Staff -->
                        <div class="alert alert-secondary small mb-0" role="alert">
                            <i class="fa-solid fa-user-gear me-2"></i>Registered as staff (<?php echo htmlspecialchars($user_role); ?>). Participant registration is restricted.
                        </div>

                    <?php elseif ($is_registered): ?>
                        <!-- 3. Already Registered -->
                        <div class="alert alert-success border-start border-4 border-success text-start mb-3" role="alert">
                            <i class="fa-solid fa-circle-check me-2 text-success"></i>You are already registered! Check your active ticket inside your Dashboard.
                        </div>
                        <button class="btn btn-secondary btn-lg w-100 py-2.5" disabled>
                            <i class="fa-solid fa-circle-check me-2"></i>Registered
                        </button>

                    <?php elseif ($ws['seats_remaining'] <= 0): ?>
                        <!-- 4. Workshop Full -->
                        <div class="alert alert-danger border-start border-4 border-danger text-start mb-3" role="alert">
                            <i class="fa-solid fa-triangle-exclamation me-2 text-danger"></i>Sorry, this workshop is fully booked.
                        </div>
                        <button class="btn btn-danger btn-lg w-100 py-2.5" disabled>
                            <i class="fa-solid fa-circle-xmark me-2"></i>Fully Booked
                        </button>

                    <?php else: ?>
                        <!-- 5. Active & Logged-in Student: Register Form -->
                        <form method="POST" action="<?php echo $base_path; ?>participant/register_workshop.php">
                            <input type="hidden" name="workshop_id" value="<?php echo $ws['id']; ?>">
                            <button type="submit" class="btn btn-warning text-dark fw-bold btn-lg w-100 shadow-sm py-2.5">
                                <i class="fa-solid fa-user-plus me-2"></i>Register for Workshop
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Schedule & Speaker Details Card -->
            <div class="card border-0 shadow-sm rounded-3 bg-white mb-4">
                <div class="card-body p-4">
                    <h5 class="fw-bold mb-4 text-dark"><i class="fa-solid fa-calendar-days text-primary me-2"></i>Schedule &amp; Info</h5>
                    
                    <!-- Date -->
                    <div class="d-flex align-items-start mb-3">
                        <div class="fs-4 text-primary me-3 mt-1"><i class="fa-solid fa-calendar"></i></div>
                        <div>
                            <span class="small text-muted d-block fw-bold text-uppercase">Dates</span>
                            <span class="text-dark small fw-semibold">
                                <?php 
                                if ($ws['start_date'] === $ws['end_date']) {
                                    echo date('M d, Y', strtotime($ws['start_date']));
                                } else {
                                    echo date('M d, Y', strtotime($ws['start_date'])) . ' - ' . date('M d, Y', strtotime($ws['end_date']));
                                }
                                ?>
                            </span>
                        </div>
                    </div>

                    <!-- Venue -->
                    <div class="d-flex align-items-start mb-3">
                        <div class="fs-4 text-danger me-3 mt-1"><i class="fa-solid fa-location-dot"></i></div>
                        <div>
                            <span class="small text-muted d-block fw-bold text-uppercase">Venue Location</span>
                            <span class="text-dark small fw-semibold"><?php echo htmlspecialchars($ws['venue']); ?></span>
                        </div>
                    </div>

                    <!-- Speaker -->
                    <div class="d-flex align-items-start mb-3">
                        <div class="fs-4 text-warning me-3 mt-1"><i class="fa-solid fa-user-tie"></i></div>
                        <div>
                            <span class="small text-muted d-block fw-bold text-uppercase">Keynote Speaker</span>
                            <span class="text-dark small fw-semibold"><?php echo htmlspecialchars($ws['speaker']); ?></span>
                        </div>
                    </div>

                    <!-- Host -->
                    <div class="d-flex align-items-start">
                        <div class="fs-4 text-info me-3 mt-1"><i class="fa-solid fa-building"></i></div>
                        <div>
                            <span class="small text-muted d-block fw-bold text-uppercase">Hosting Department</span>
                            <span class="text-dark small fw-semibold"><?php echo htmlspecialchars($ws['host']); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<style>
.fs-7 {
    font-size: 11px !important;
}
.letter-spacing-sm {
    letter-spacing: 1px;
}
.leading-relaxed {
    line-height: 1.7;
}
</style>

<?php
include $base_path . 'includes/footer.php';
?>
