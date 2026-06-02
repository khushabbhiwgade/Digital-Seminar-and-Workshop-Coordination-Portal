<?php
// participant/dashboard.php
$base_path = '../';
$page_title = 'Participant Dashboard - Campus Connect';
$active_page = 'participant_dashboard';

require_once $base_path . 'includes/auth.php';
require_role('student');

require_once $base_path . 'config/db_connect.php';

$user_id = get_user_id();
$user_name = get_user_name();

$success_msg = "";
$error_msg = "";

if (isset($_GET['msg']) && $_GET['msg'] === 'register_success') {
    $success_msg = "Successfully registered for the workshop! Your ticket is generated below.";
}
if (isset($_GET['msg']) && $_GET['msg'] === 'feedback_success') {
    $success_msg = "Thank you! Your workshop feedback has been submitted successfully.";
}
if (isset($_GET['error'])) {
    $error_msg = htmlspecialchars($_GET['error']);
}

// 1. Fetch Stats Count
// Available Workshops (Upcoming)
$avail_count = 0;
$count_res1 = $conn->query("SELECT COUNT(*) as cnt FROM workshops WHERE status IN ('active', 'upcoming') AND start_date >= CURDATE()");
if ($count_res1) {
    $avail_count = $count_res1->fetch_assoc()['cnt'];
}

// Active Workshops (Pending or Verified)
$active_count = 0;
$stmt2 = $conn->prepare("SELECT COUNT(*) as cnt FROM tickets WHERE user_id = ? AND registration_status IN ('Pending', 'Verified')");
$stmt2->bind_param("i", $user_id);
$stmt2->execute();
$count_res2 = $stmt2->get_result();
if ($count_res2) {
    $active_count = $count_res2->fetch_assoc()['cnt'];
}

// Completed Workshops
$comp_count = 0;
$stmt3 = $conn->prepare("SELECT COUNT(*) as cnt FROM tickets WHERE user_id = ? AND registration_status = 'Completed'");
$stmt3->bind_param("i", $user_id);
$stmt3->execute();
$count_res3 = $stmt3->get_result();
if ($count_res3) {
    $comp_count = $count_res3->fetch_assoc()['cnt'];
}

// Certificates Available
$cert_count = 0;
$stmt4 = $conn->prepare("SELECT COUNT(*) as cnt FROM certificates c JOIN tickets t ON c.registration_id = t.id WHERE t.user_id = ? AND c.status = 'Generated'");
$stmt4->bind_param("i", $user_id);
$stmt4->execute();
$count_res4 = $stmt4->get_result();
if ($count_res4) {
    $cert_count = $count_res4->fetch_assoc()['cnt'];
}

include $base_path . 'includes/header.php';
include $base_path . 'includes/navbar.php';
?>

<div class="container my-5">
    <!-- Header Greeting Banner -->
    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center mb-4 gap-3">
        <div>
            <h1 class="h2 mb-0 fw-bold">Participant Dashboard</h1>
            <p class="text-muted mb-0">Welcome back, <span class="fw-semibold text-dark"><?php echo htmlspecialchars($user_name); ?></span></p>
        </div>
        <div class="d-flex gap-2">
            <a href="my_workshops.php" class="btn btn-outline-primary btn-sm fw-bold"><i class="fa-solid fa-list-check me-1"></i>My Workshops Panel</a>
            <span class="badge bg-warning text-dark px-3 py-2 fs-6 fw-bold">Student Panel</span>
        </div>
    </div>

    <!-- Alert Messaging -->
    <?php if (!empty($success_msg)): ?>
        <div class="alert alert-success alert-dismissible fade show border-start border-4 border-success shadow-sm" role="alert">
            <i class="fa-solid fa-circle-check me-2"></i><?php echo htmlspecialchars($success_msg); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (!empty($error_msg)): ?>
        <div class="alert alert-danger alert-dismissible fade show border-start border-4 border-danger shadow-sm" role="alert">
            <i class="fa-solid fa-triangle-exclamation me-2"></i><?php echo htmlspecialchars($error_msg); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Stat Dashboard Cards -->
    <div class="row g-4 mb-5">
        <!-- Available Workshops Card -->
        <div class="col-md-3">
            <div class="card h-100 border-0 shadow-sm rounded-3 p-3 bg-white text-center">
                <div class="display-6 text-primary mb-2"><i class="fa-solid fa-chalkboard-user"></i></div>
                <h3 class="fw-bold mb-1"><?php echo intval($avail_count); ?></h3>
                <span class="text-muted small fw-semibold">Available Workshops</span>
            </div>
        </div>
        <!-- Active Workshops Card -->
        <div class="col-md-3">
            <div class="card h-100 border-0 shadow-sm rounded-3 p-3 bg-white text-center">
                <div class="display-6 text-info mb-2"><i class="fa-solid fa-hourglass-half"></i></div>
                <h3 class="fw-bold mb-1"><?php echo intval($active_count); ?></h3>
                <span class="text-muted small fw-semibold">Active Workshops</span>
            </div>
        </div>
        <!-- Completed Workshops Card -->
        <div class="col-md-3">
            <div class="card h-100 border-0 shadow-sm rounded-3 p-3 bg-white text-center">
                <div class="display-6 text-success mb-2"><i class="fa-solid fa-circle-check"></i></div>
                <h3 class="fw-bold mb-1"><?php echo intval($comp_count); ?></h3>
                <span class="text-muted small fw-semibold">Completed Workshops</span>
            </div>
        </div>
        <!-- Certificates Available Card -->
        <div class="col-md-3">
            <div class="card h-100 border-0 shadow-sm rounded-3 p-3 bg-white text-center">
                <div class="display-6 text-warning mb-2"><i class="fa-solid fa-award"></i></div>
                <h3 class="fw-bold mb-1"><?php echo intval($cert_count); ?></h3>
                <span class="text-muted small fw-semibold">Certificates Available</span>
            </div>
        </div>
    </div>

    <!-- Row 1: Upcoming Live Workshops & Active Workshops -->
    <div class="row g-4 mb-4">
        <!-- 1. Upcoming Workshops Section -->
        <div class="col-lg-6">
            <div class="card shadow-sm border-0 rounded-3 h-100">
                <div class="card-header bg-dark text-white py-3">
                    <h5 class="mb-0"><i class="fa-solid fa-bullhorn text-warning me-2"></i>Upcoming Live Workshops</h5>
                </div>
                <div class="card-body p-0">
                    <?php
                    // Fetch user registrations IDs first to filter out
                    $reg_ids = [];
                    $reg_q = $conn->prepare("SELECT event_id FROM tickets WHERE user_id = ?");
                    $reg_q->bind_param("i", $user_id);
                    $reg_q->execute();
                    $reg_res = $reg_q->get_result();
                    while ($r = $reg_res->fetch_assoc()) {
                        $reg_ids[] = intval($r['event_id']);
                    }

                    // Fetch active upcoming workshops
                    $ws_sql = "SELECT id, title, domain, host, speaker, start_date, capacity, seats_remaining FROM workshops WHERE status IN ('active', 'upcoming') AND start_date >= CURDATE() ORDER BY start_date ASC";
                    $ws_res = $conn->query($ws_sql);

                    if ($ws_res && $ws_res->num_rows > 0):
                        $shown_count = 0;
                        while ($ws = $ws_res->fetch_assoc()):
                            if (in_array(intval($ws['id']), $reg_ids)) continue;
                            $shown_count++;
                            ?>
                            <div class="d-flex align-items-start justify-content-between p-3 border-bottom hover-bg-light">
                                <div>
                                    <h6 class="fw-bold mb-1 text-primary"><?php echo htmlspecialchars($ws['title']); ?></h6>
                                    <div class="text-muted small mb-1">
                                        <span class="badge bg-secondary text-uppercase fs-8 me-2" style="background-color: #3b82f6 !important;"><?php echo htmlspecialchars($ws['domain']); ?></span>
                                        <i class="fa-solid fa-calendar me-1"></i><?php echo date('M d, Y', strtotime($ws['start_date'])); ?>
                                    </div>
                                    <div class="text-muted small">
                                        <i class="fa-solid fa-user-tie me-1"></i><?php echo htmlspecialchars($ws['speaker']); ?> | 
                                        <span class="fw-semibold"><i class="fa-solid fa-chair text-success me-1"></i><?php echo intval($ws['capacity']) - intval($ws['seats_remaining']); ?> / <?php echo intval($ws['capacity']); ?> Seats Booked</span>
                                    </div>
                                </div>
                                
                                <form method="POST" action="register_workshop.php" class="align-self-center">
                                    <input type="hidden" name="workshop_id" value="<?php echo $ws['id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-warning text-dark fw-bold px-3">Register</button>
                                </form>
                            </div>
                        <?php endwhile; ?>
                        
                        <?php if ($shown_count === 0): ?>
                            <p class="text-muted text-center py-5 mb-0"><i class="fa-solid fa-circle-info fs-3 d-block mb-2 text-secondary"></i>You are registered for all upcoming workshops.</p>
                        <?php endif; ?>
                    <?php else: ?>
                        <p class="text-muted text-center py-5 mb-0"><i class="fa-solid fa-circle-info fs-3 d-block mb-2 text-secondary"></i>No upcoming workshops listed at this time.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- 2. Active Workshops Section (Pending or Verified) -->
        <div class="col-lg-6">
            <div class="card shadow-sm border-0 rounded-3 h-100">
                <div class="card-header bg-dark text-white py-3">
                    <h5 class="mb-0"><i class="fa-solid fa-id-card text-warning me-2"></i>Active Workshops</h5>
                </div>
                <div class="card-body p-3" style="max-height: 400px; overflow-y: auto;">
                    <?php
                    // Fetch user active tickets (Pending or Verified)
                    $my_reg_q = $conn->prepare("SELECT t.ticket_number, t.qr_code_path, t.registration_status, w.title, w.start_date, w.venue FROM tickets t JOIN workshops w ON t.event_id = w.id WHERE t.user_id = ? AND t.registration_status IN ('Pending', 'Verified') ORDER BY w.start_date DESC");
                    $my_reg_q->bind_param("i", $user_id);
                    $my_reg_q->execute();
                    $my_reg_res = $my_reg_q->get_result();

                    if ($my_reg_res && $my_reg_res->num_rows > 0):
                        while ($tr = $my_reg_res->fetch_assoc()):
                            $badge_class = 'bg-warning text-dark';
                            $display_status = 'Pending Verification';
                            if ($tr['registration_status'] === 'Verified') {
                                $badge_class = 'bg-info text-dark';
                                $display_status = 'Verified';
                            }
                            ?>
                            <div class="p-3 border rounded-3 mb-3 bg-light shadow-sm">
                                <div class="d-flex flex-column flex-sm-row justify-content-between align-items-center gap-3">
                                    <div class="flex-grow-1">
                                        <div class="d-flex align-items-center gap-2 mb-2">
                                            <span class="badge <?php echo $badge_class; ?> px-2.5 py-1 text-uppercase fs-9"><?php echo $display_status; ?></span>
                                            <span class="badge bg-secondary px-2.5 py-1 text-uppercase fs-9 font-monospace"><?php echo htmlspecialchars($tr['ticket_number']); ?></span>
                                        </div>
                                        <h6 class="fw-bold mb-1 text-dark"><?php echo htmlspecialchars($tr['title']); ?></h6>
                                        <div class="text-muted small mb-1">
                                            <i class="fa-solid fa-calendar me-1"></i><?php echo date('M d, Y', strtotime($tr['start_date'])); ?>
                                        </div>
                                        <div class="text-muted small">
                                            <i class="fa-solid fa-location-dot me-1"></i><?php echo htmlspecialchars($tr['venue']); ?>
                                        </div>
                                    </div>
                                    <div class="text-center bg-white p-2 rounded border shadow-sm flex-shrink-0" style="width: 105px; height: 105px;">
                                        <?php if (!empty($tr['qr_code_path']) && file_exists($base_path . $tr['qr_code_path'])): ?>
                                            <img src="<?php echo $base_path . htmlspecialchars($tr['qr_code_path']); ?>" alt="QR Code" class="img-fluid" style="width: 100%; height: 100%; object-fit: contain;">
                                        <?php else: ?>
                                            <div class="d-flex align-items-center justify-content-center h-100 text-muted small bg-light">No QR</div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p class="text-muted text-center py-5 mb-0"><i class="fa-solid fa-ticket-simple fs-3 d-block mb-2 text-secondary"></i>You do not have any active registered workshop tickets.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Row 2: Completed Workshops & Rejected Workshops -->
    <div class="row g-4 mb-4">
        <!-- 3. Completed Workshops Section -->
        <div class="col-lg-6">
            <div class="card shadow-sm border-0 rounded-3 h-100">
                <div class="card-header bg-dark text-white py-3">
                    <h5 class="mb-0"><i class="fa-solid fa-circle-check text-warning me-2"></i>Completed Workshops</h5>
                </div>
                <div class="card-body p-0" style="max-height: 400px; overflow-y: auto;">
                    <?php
                    // Fetch completed tickets
                    $my_comp_q = $conn->prepare("SELECT t.registration_status, w.title, w.end_date FROM tickets t JOIN workshops w ON t.event_id = w.id WHERE t.user_id = ? AND t.registration_status = 'Completed' ORDER BY w.end_date DESC");
                    $my_comp_q->bind_param("i", $user_id);
                    $my_comp_q->execute();
                    $my_comp_res = $my_comp_q->get_result();

                    if ($my_comp_res && $my_comp_res->num_rows > 0):
                        while ($cw = $my_comp_res->fetch_assoc()):
                            ?>
                            <div class="d-flex align-items-center justify-content-between p-3 border-bottom">
                                <div>
                                    <h6 class="fw-bold mb-1 text-dark"><?php echo htmlspecialchars($cw['title']); ?></h6>
                                    <div class="text-muted small"><i class="fa-solid fa-calendar me-1"></i>Completed on <?php echo date('M d, Y', strtotime($cw['end_date'])); ?></div>
                                </div>
                                <span class="badge bg-success px-2 py-1"><i class="fa-solid fa-circle-check me-1"></i>Completed</span>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p class="text-muted text-center py-5 mb-0"><i class="fa-solid fa-graduation-cap fs-3 d-block mb-2 text-secondary"></i>Completed workshops will appear here after attendance is logged.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- 4. Rejected Workshops Section (Rejected, Absent, Cancelled) -->
        <div class="col-lg-6">
            <div class="card shadow-sm border-0 rounded-3 h-100">
                <div class="card-header bg-dark text-white py-3">
                    <h5 class="mb-0"><i class="fa-solid fa-circle-xmark text-warning me-2"></i>Rejected & Cancelled Workshops</h5>
                </div>
                <div class="card-body p-0" style="max-height: 400px; overflow-y: auto;">
                    <?php
                    // Fetch user rejected, absent, or cancelled tickets
                    $my_rej_q = $conn->prepare("SELECT t.ticket_number, t.registration_status, w.title, w.start_date FROM tickets t JOIN workshops w ON t.event_id = w.id WHERE t.user_id = ? AND t.registration_status IN ('Rejected', 'Absent', 'Cancelled') ORDER BY w.start_date DESC");
                    $my_rej_q->bind_param("i", $user_id);
                    $my_rej_q->execute();
                    $my_rej_res = $my_rej_q->get_result();

                    if ($my_rej_res && $my_rej_res->num_rows > 0):
                        while ($tr = $my_rej_res->fetch_assoc()):
                            $badge_class = 'bg-danger';
                            $disp_status = htmlspecialchars($tr['registration_status']);
                            if ($tr['registration_status'] === 'Cancelled') {
                                $badge_class = 'bg-dark';
                            }
                            ?>
                            <div class="d-flex align-items-center justify-content-between p-3 border-bottom">
                                <div>
                                    <h6 class="fw-bold mb-1 text-dark"><?php echo htmlspecialchars($tr['title']); ?></h6>
                                    <div class="text-muted small"><code class="font-monospace fw-bold me-2"><?php echo htmlspecialchars($tr['ticket_number']); ?></code> <i class="fa-solid fa-calendar me-1"></i><?php echo date('M d, Y', strtotime($tr['start_date'])); ?></div>
                                </div>
                                <span class="badge <?php echo $badge_class; ?> px-2 py-1"><?php echo $disp_status; ?></span>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p class="text-muted text-center py-5 mb-0"><i class="fa-solid fa-circle-check fs-3 d-block mb-2 text-secondary"></i>No cancelled, absent, or rejected registrations found.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Row 3: Latest Certificate & Available Certificates (Part 14) -->
    <div class="row g-4 mt-2">
        <div class="col-12">
            <div class="card shadow-sm border-0 rounded-3">
                <div class="card-header bg-dark text-white py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fa-solid fa-award text-warning me-2"></i>My Earned Certificates</h5>
                    <a href="certificates.php" class="btn btn-warning btn-sm text-dark fw-bold px-3">View All Certificates Center</a>
                </div>
                <div class="card-body p-4">
                    <?php
                    // Fetch latest certificate
                    $my_cert_q = $conn->prepare("
                        SELECT c.id, c.certificate_no, c.verification_code, c.generated_at, w.title 
                        FROM certificates c 
                        JOIN tickets t ON c.registration_id = t.id 
                        JOIN workshops w ON c.event_id = w.id 
                        WHERE t.user_id = ? AND c.status = 'Generated'
                        ORDER BY c.generated_at DESC LIMIT 1
                    ");
                    $my_cert_q->bind_param("i", $user_id);
                    $my_cert_q->execute();
                    $my_cert_res = $my_cert_q->get_result();

                    if ($my_cert_res && $my_cert_res->num_rows > 0):
                        $ct = $my_cert_res->fetch_assoc();
                        ?>
                        <div class="bg-light p-4 rounded-3 border border-success border-start border-4 mb-0">
                            <div class="row align-items-center">
                                <div class="col-lg-8 mb-3 mb-lg-0">
                                    <span class="badge bg-success text-uppercase mb-2"><i class="fa-solid fa-star me-1"></i>Latest Certificate</span>
                                    <h4 class="fw-bold mb-1 text-dark"><?php echo htmlspecialchars($ct['title']); ?></h4>
                                    <div class="text-muted small">
                                        Number: <code class="text-primary fw-bold"><?php echo htmlspecialchars($ct['certificate_no']); ?></code> | 
                                        Verification Code: <code class="text-success fw-bold"><?php echo htmlspecialchars($ct['verification_code']); ?></code> |
                                        Issued: <?php echo date('M d, Y', strtotime($ct['generated_at'])); ?>
                                    </div>
                                </div>
                                <div class="col-lg-4 text-lg-end">
                                    <div class="d-inline-flex gap-2">
                                        <a href="certificates.php?action=download&id=<?php echo $ct['id']; ?>" class="btn btn-success fw-bold shadow-sm">
                                            <i class="fa-solid fa-download me-1"></i>Download PDF
                                        </a>
                                        <a href="../verify_certificate.php?code=<?php echo urlencode($ct['verification_code']); ?>" class="btn btn-outline-primary fw-bold">
                                            <i class="fa-solid fa-shield-halved me-1"></i>Verify
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <p class="text-muted text-center py-4 mb-0"><i class="fa-solid fa-award fs-3 d-block mb-2 text-secondary"></i>No certificates earned yet. Complete your registered sessions to receive them automatically.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Row 4: Pending Feedback & Submitted Feedback (Part 9 & Part 14) -->
    <div class="row g-4 mt-2">
        <!-- 1. Pending Feedback Section -->
        <div class="col-lg-6">
            <div class="card shadow-sm border-0 rounded-3 h-100 bg-white">
                <div class="card-header bg-dark text-white py-3">
                    <h5 class="mb-0"><i class="fa-solid fa-comments text-warning me-2"></i>Pending Feedback Surveys</h5>
                </div>
                <div class="card-body p-0" style="max-height: 400px; overflow-y: auto;">
                    <?php
                    // Fetch completed tickets awaiting feedback
                    $pf_stmt = $conn->prepare("
                        SELECT t.id as ticket_id, w.title, w.start_date, w.host, w.speaker
                        FROM tickets t
                        JOIN workshops w ON t.event_id = w.id
                        JOIN attendance a ON t.id = a.ticket_id
                        LEFT JOIN feedback f ON t.id = f.registration_id
                        WHERE t.user_id = ? 
                          AND a.attendance_status = 'Present' 
                          AND t.registration_status = 'Completed' 
                          AND f.id IS NULL
                        ORDER BY w.start_date DESC
                    ");
                    $pf_stmt->bind_param("i", $user_id);
                    $pf_stmt->execute();
                    $pf_res = $pf_stmt->get_result();

                    if ($pf_res && $pf_res->num_rows > 0):
                        while ($pw = $pf_res->fetch_assoc()):
                            ?>
                            <div class="d-flex align-items-center justify-content-between p-3 border-bottom hover-bg-light">
                                <div class="pe-2">
                                    <h6 class="fw-bold mb-1 text-dark"><?php echo htmlspecialchars($pw['title']); ?></h6>
                                    <div class="text-muted small"><i class="fa-solid fa-user-tie me-1"></i><?php echo htmlspecialchars($pw['speaker']); ?> | <i class="fa-solid fa-calendar me-1"></i><?php echo date('M d, Y', strtotime($pw['start_date'])); ?></div>
                                </div>
                                <a href="feedback.php?id=<?php echo $pw['ticket_id']; ?>" class="btn btn-sm btn-warning text-dark fw-bold px-3 text-nowrap">
                                    Submit Feedback
                                </a>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p class="text-muted text-center py-5 mb-0"><i class="fa-solid fa-circle-question fs-3 d-block mb-2 text-secondary"></i>No pending feedback surveys at this time.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- 2. Submitted Feedback Section -->
        <div class="col-lg-6">
            <div class="card shadow-sm border-0 rounded-3 h-100 bg-white">
                <div class="card-header bg-dark text-white py-3">
                    <h5 class="mb-0"><i class="fa-solid fa-star text-warning me-2"></i>Submitted Feedback</h5>
                </div>
                <div class="card-body p-0" style="max-height: 400px; overflow-y: auto;">
                    <?php
                    // Fetch completed tickets with feedback submitted
                    $sf_stmt = $conn->prepare("
                        SELECT f.id as feedback_id, f.submitted_at, f.overall_rating, t.id as ticket_id,
                               w.title, w.start_date, w.host
                        FROM feedback f
                        JOIN tickets t ON f.registration_id = t.id
                        JOIN workshops w ON t.event_id = w.id
                        WHERE t.user_id = ?
                        ORDER BY f.submitted_at DESC
                    ");
                    $sf_stmt->bind_param("i", $user_id);
                    $sf_stmt->execute();
                    $sf_res = $sf_stmt->get_result();

                    if ($sf_res && $sf_res->num_rows > 0):
                        while ($sw = $sf_res->fetch_assoc()):
                            $submitted_time = strtotime($sw['submitted_at']);
                            $elapsed_hours = (time() - $submitted_time) / 3600;
                            $is_locked = ($elapsed_hours >= 24);
                            ?>
                            <div class="d-flex align-items-center justify-content-between p-3 border-bottom">
                                <div class="pe-2">
                                    <h6 class="fw-bold mb-1 text-dark"><?php echo htmlspecialchars($sw['title']); ?></h6>
                                    <div class="text-muted small">
                                        <i class="fa-solid fa-calendar me-1"></i>Rated: 
                                        <span class="text-warning fw-bold">
                                            <?php for ($i = 1; $i <= 5; $i++) {
                                                echo ($i <= $sw['overall_rating']) ? '★' : '☆';
                                            } ?>
                                        </span> | 
                                        Submitted: <?php echo date('M d, Y', $submitted_time); ?>
                                    </div>
                                </div>
                                
                                <?php if ($is_locked): ?>
                                    <span class="badge bg-secondary px-2.5 py-1 text-uppercase font-monospace fs-9 text-nowrap" title="Feedback locked after 24 hours.">
                                        <i class="fa-solid fa-lock me-1"></i>Locked
                                    </span>
                                <?php else: ?>
                                    <a href="feedback.php?id=<?php echo $sw['ticket_id']; ?>" class="btn btn-sm btn-outline-primary fw-semibold text-nowrap">
                                        Edit Feedback
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p class="text-muted text-center py-5 mb-0"><i class="fa-solid fa-star fs-3 d-block mb-2 text-secondary"></i>You have not submitted any feedback yet.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <br>
</div>

<style>
.hover-bg-light:hover {
    background-color: #f8fafc;
    transition: background-color 0.2s ease-in-out;
}
.fs-8 {
    font-size: 10px !important;
}
.fs-9 {
    font-size: 11px !important;
}
</style>

<?php
include $base_path . 'includes/footer.php';
?>
