<?php
// student/dashboard.php
$base_path = '../';
$page_title = 'Student Dashboard - Campus Connect';
$active_page = 'student_dashboard';

// Enforce student role
require_once $base_path . 'includes/auth.php';
require_role('student');

// Database connection
require_once $base_path . 'config/db_connect.php';

$user_id = get_user_id();
$user_name = get_user_name();

// 1. Fetch count stats
// Available Events (Upcoming future events)
$upcoming_count = 0;
$count_sql1 = "SELECT COUNT(*) as cnt FROM events WHERE event_date >= CURDATE()";
$res1 = $conn->query($count_sql1);
if ($res1) {
    $upcoming_count = $res1->fetch_assoc()['cnt'];
}

// My Registrations
$my_reg_count = 0;
$count_sql2 = "SELECT COUNT(*) as cnt FROM registrations WHERE user_id = ?";
$stmt2 = $conn->prepare($count_sql2);
$stmt2->bind_param("i", $user_id);
$stmt2->execute();
$res2 = $stmt2->get_result();
if ($res2) {
    $my_reg_count = $res2->fetch_assoc()['cnt'];
}

// Attendance Status (Attended events)
$attended_count = 0;
$count_sql3 = "SELECT COUNT(*) as cnt FROM registrations WHERE user_id = ? AND attended = 1";
$stmt3 = $conn->prepare($count_sql3);
$stmt3->bind_param("i", $user_id);
$stmt3->execute();
$res3 = $stmt3->get_result();
if ($res3) {
    $attended_count = $res3->fetch_assoc()['cnt'];
}

// Certificates Available
$cert_count = 0;
$count_sql4 = "SELECT COUNT(*) as cnt FROM certificates c 
               JOIN registrations r ON c.registration_id = r.id 
               WHERE r.user_id = ?";
$stmt4 = $conn->prepare($count_sql4);
$stmt4->bind_param("i", $user_id);
$stmt4->execute();
$res4 = $stmt4->get_result();
if ($res4) {
    $cert_count = $res4->fetch_assoc()['cnt'];
}

include $base_path . 'includes/header.php';
include $base_path . 'includes/navbar.php';
?>

<div class="container my-5">
    <!-- Header banner -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h2 mb-0">Student Dashboard</h1>
            <p class="text-muted mb-0">Welcome back, <span class="fw-semibold text-dark"><?php echo htmlspecialchars($user_name); ?></span></p>
        </div>
        <a href="<?php echo $base_path; ?>events.php" class="btn btn-warning text-dark fw-bold"><i class="fa-solid fa-calendar-days me-2"></i>Browse Events</a>
    </div>

    <!-- Feedback success simulator notice -->
    <?php if (isset($_GET['feedback']) && $_GET['feedback'] === 'submitted'): ?>
        <div class="alert alert-success alert-dismissible fade show border-start border-4 border-success shadow-sm mb-4" role="alert">
            <i class="fa-solid fa-circle-check text-success me-2"></i><strong>Feedback Submitted:</strong> Thank you for sharing your thoughts on the session! Your input helps us improve future seminars.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Dashboard Stat Cards -->
    <div class="row g-4 mb-5">
        <div class="col-md-3">
            <div class="card h-100 border-0 shadow-sm rounded-3 bg-white p-3 text-center">
                <div class="display-6 text-primary mb-2"><i class="fa-solid fa-calendar-check"></i></div>
                <h3 class="fw-bold mb-1"><?php echo intval($upcoming_count); ?></h3>
                <span class="text-muted small fw-semibold">Available Events</span>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100 border-0 shadow-sm rounded-3 bg-white p-3 text-center">
                <div class="display-6 text-success mb-2"><i class="fa-solid fa-receipt"></i></div>
                <h3 class="fw-bold mb-1"><?php echo intval($my_reg_count); ?></h3>
                <span class="text-muted small fw-semibold">My Registrations</span>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100 border-0 shadow-sm rounded-3 bg-white p-3 text-center">
                <div class="display-6 text-warning mb-2"><i class="fa-solid fa-clipboard-user"></i></div>
                <h3 class="fw-bold mb-1"><?php echo intval($attended_count); ?></h3>
                <span class="text-muted small fw-semibold">Attended Status</span>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100 border-0 shadow-sm rounded-3 bg-white p-3 text-center">
                <div class="display-6 text-info mb-2"><i class="fa-solid fa-award"></i></div>
                <h3 class="fw-bold mb-1"><?php echo intval($cert_count); ?></h3>
                <span class="text-muted small fw-semibold">Certificates Earned</span>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- 1. Upcoming Events Section -->
        <div class="col-lg-6">
            <div class="card shadow-sm border-0 rounded-3 mb-4 h-100">
                <div class="card-header bg-dark text-white py-3">
                    <h5 class="mb-0"><i class="fa-solid fa-bell text-warning me-2"></i>Upcoming Learning Seminars &amp; Workshops</h5>
                </div>
                <div class="card-body">
                    <?php
                    // Fetch upcoming events
                    $events_sql = "SELECT id, title, host, event_date, location, seats_remaining, total_seats FROM events WHERE event_date >= CURDATE() ORDER BY event_date ASC";
                    $events_res = $conn->query($events_sql);

                    // Fetch current user's registration IDs to disable repeat registration button
                    $user_regs = [];
                    $reg_ids_sql = "SELECT event_id FROM registrations WHERE user_id = ?";
                    $reg_stmt = $conn->prepare($reg_ids_sql);
                    $reg_stmt->bind_param("i", $user_id);
                    $reg_stmt->execute();
                    $reg_res = $reg_stmt->get_result();
                    while ($r = $reg_res->fetch_assoc()) {
                        $user_regs[] = $r['event_id'];
                    }

                    if ($events_res && $events_res->num_rows > 0) {
                        while ($ev = $events_res->fetch_assoc()) {
                            $is_registered = in_array($ev['id'], $user_regs);
                            $date_f = date("F d, Y", strtotime($ev['event_date']));
                            
                            echo '<div class="d-flex align-items-start justify-content-between p-3 border-bottom">';
                            echo '<div>';
                            echo '<h6 class="fw-bold mb-1 text-primary">' . htmlspecialchars($ev['title']) . '</h6>';
                            echo '<div class="text-muted small"><i class="fa-solid fa-calendar me-1"></i> ' . $date_f . ' | <i class="fa-solid fa-user-tie me-1"></i> ' . htmlspecialchars($ev['host']) . '</div>';
                            echo '<div class="text-muted small mt-1"><i class="fa-solid fa-location-dot text-danger me-1"></i> ' . htmlspecialchars($ev['location']) . ' | <i class="fa-solid fa-chair text-success me-1"></i> ' . intval($ev['seats_remaining']) . '/' . intval($ev['total_seats']) . ' Seats Left</div>';
                            echo '</div>';
                            
                            if ($is_registered) {
                                echo '<button class="btn btn-sm btn-outline-secondary px-3 align-self-center" disabled><i class="fa-solid fa-circle-check text-success me-1"></i>Registered</button>';
                            } elseif ($ev['seats_remaining'] <= 0) {
                                echo '<button class="btn btn-sm btn-outline-danger px-3 align-self-center" disabled>Full</button>';
                            } else {
                                // Form to quick-register
                                echo '<form method="POST" action="submit_registration.php" class="align-self-center">';
                                echo '<input type="hidden" name="event_name" value="' . htmlspecialchars($ev['title']) . '">';
                                echo '<button type="submit" class="btn btn-sm btn-warning text-dark fw-bold px-3">Register</button>';
                                echo '</form>';
                            }
                            echo '</div>';
                        }
                    } else {
                        echo '<p class="text-muted text-center py-4 mb-0">No upcoming events listed at this time.</p>';
                    }
                    ?>
                </div>
            </div>
        </div>

        <!-- 2. Registered Events Section -->
        <div class="col-lg-6">
            <div class="card shadow-sm border-0 rounded-3 mb-4 h-100">
                <div class="card-header bg-dark text-white py-3">
                    <h5 class="mb-0"><i class="fa-solid fa-id-card text-warning me-2"></i>My Registered Event Tickets</h5>
                </div>
                <div class="card-body">
                    <?php
                    // Fetch student registrations
                    $my_sql = "SELECT r.token, r.attended, e.title, e.event_date, e.location 
                               FROM registrations r 
                               JOIN events e ON r.event_id = e.id 
                               WHERE r.user_id = ? 
                               ORDER BY e.event_date DESC";
                    $my_stmt = $conn->prepare($my_sql);
                    $my_stmt->bind_param("i", $user_id);
                    $my_stmt->execute();
                    $my_res = $my_stmt->get_result();

                    if ($my_res && $my_res->num_rows > 0) {
                        while ($reg = $my_res->fetch_assoc()) {
                            $date_f = date("M d, Y", strtotime($reg['event_date']));
                            $status_badge = $reg['attended'] == 1 
                                ? '<span class="badge bg-success">Attended</span>' 
                                : '<span class="badge bg-secondary">Registered</span>';
                                
                            echo '<div class="p-3 border rounded-3 mb-3 bg-light">';
                            echo '<div class="d-flex justify-content-between align-items-start">';
                            echo '<div>';
                            echo '<h6 class="fw-bold mb-1">' . htmlspecialchars($reg['title']) . '</h6>';
                            echo '<div class="text-muted small"><i class="fa-solid fa-calendar me-1"></i> ' . $date_f . ' | <i class="fa-solid fa-location-dot me-1"></i> ' . htmlspecialchars($reg['location']) . '</div>';
                            echo '<div class="font-monospace text-primary fw-bold mt-2 small">Token: ' . htmlspecialchars($reg['token'] ?? 'Pending') . '</div>';
                            echo '</div>';
                            echo '<div>' . $status_badge . '</div>';
                            echo '</div>';
                            echo '</div>';
                        }
                    } else {
                        echo '<p class="text-muted text-center py-4 mb-0">You haven\'t registered for any seminars yet.</p>';
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mt-1">
        <!-- 3. Feedback Pending Section -->
        <div class="col-lg-6">
            <div class="card shadow-sm border-0 rounded-3 mb-4 h-100">
                <div class="card-header bg-dark text-white py-3">
                    <h5 class="mb-0"><i class="fa-solid fa-comments-dollar text-warning me-2"></i>Feedback Surveys Pending</h5>
                </div>
                <div class="card-body">
                    <?php
                    // Fetch attended events for feedback
                    $feed_sql = "SELECT r.id as reg_id, e.title, e.event_date 
                                 FROM registrations r 
                                 JOIN events e ON r.event_id = e.id 
                                 WHERE r.user_id = ? AND r.attended = 1 
                                 ORDER BY e.event_date DESC";
                    $feed_stmt = $conn->prepare($feed_sql);
                    $feed_stmt->bind_param("i", $user_id);
                    $feed_stmt->execute();
                    $feed_res = $feed_stmt->get_result();

                    if ($feed_res && $feed_res->num_rows > 0) {
                        while ($fed = $feed_res->fetch_assoc()) {
                            $date_f = date("M d, Y", strtotime($fed['event_date']));
                            echo '<div class="d-flex align-items-center justify-content-between p-3 border-bottom">';
                            echo '<div>';
                            echo '<h6 class="fw-bold mb-1">' . htmlspecialchars($fed['title']) . '</h6>';
                            echo '<div class="text-muted small"><i class="fa-solid fa-calendar me-1"></i> Attended on ' . $date_f . '</div>';
                            echo '</div>';
                            // Open dynamic feedback simulator modal
                            echo '<button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#feedbackModal' . $fed['reg_id'] . '">Submit Feedback</button>';
                            echo '</div>';
                            
                            // Feedback Modal structure
                            ?>
                            <div class="modal fade" id="feedbackModal<?php echo $fed['reg_id']; ?>" tabindex="-1" aria-labelledby="feedbackLabel<?php echo $fed['reg_id']; ?>" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content">
                                        <div class="modal-header bg-dark text-white">
                                            <h5 class="modal-title" id="feedbackLabel<?php echo $fed['reg_id']; ?>">
                                                <i class="fa-solid fa-star text-warning me-2"></i>Event Feedback
                                            </h5>
                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <form method="GET" action="dashboard.php">
                                            <input type="hidden" name="feedback" value="submitted">
                                            <div class="modal-body p-4">
                                                <h6 class="fw-bold mb-3"><?php echo htmlspecialchars($fed['title']); ?></h6>
                                                
                                                <div class="mb-3">
                                                    <label class="form-label fw-semibold">Rate this workshop</label>
                                                    <select class="form-select" name="rating" required>
                                                        <option value="5" selected>5 Stars - Excellent Session</option>
                                                        <option value="4">4 Stars - Good Session</option>
                                                        <option value="3">3 Stars - Average Session</option>
                                                        <option value="2">2 Stars - Below Average</option>
                                                        <option value="1">1 Star - Poor Session</option>
                                                    </select>
                                                </div>

                                                <div class="mb-3">
                                                    <label class="form-label fw-semibold">Your Comments / Suggestions</label>
                                                    <textarea class="form-control" rows="4" placeholder="What did you learn? Any suggestions for future events?" required></textarea>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                <button type="submit" class="btn btn-warning text-dark fw-bold px-4">Submit</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <?php
                        }
                    } else {
                        echo '<p class="text-muted text-center py-4 mb-0">No feedback pending. Attended workshops will appear here.</p>';
                    }
                    ?>
                </div>
            </div>
        </div>

        <!-- 4. Download Certificates Section -->
        <div class="col-lg-6">
            <div class="card shadow-sm border-0 rounded-3 mb-4 h-100">
                <div class="card-header bg-dark text-white py-3">
                    <h5 class="mb-0"><i class="fa-solid fa-graduation-cap text-warning me-2"></i>Download Certificates Earned</h5>
                </div>
                <div class="card-body">
                    <?php
                    // Fetch student certificates
                    $cert_sql = "SELECT c.certificate_code, c.issued_at, e.title 
                                 FROM certificates c 
                                 JOIN registrations r ON c.registration_id = r.id 
                                 JOIN events e ON r.event_id = e.id 
                                 WHERE r.user_id = ? 
                                 ORDER BY c.issued_at DESC";
                    $cert_stmt = $conn->prepare($cert_sql);
                    $cert_stmt->bind_param("i", $user_id);
                    $cert_stmt->execute();
                    $cert_res = $cert_stmt->get_result();

                    if ($cert_res && $cert_res->num_rows > 0) {
                        while ($cert = $cert_res->fetch_assoc()) {
                            $issued_f = date("M d, Y", strtotime($cert['issued_at']));
                            echo '<div class="d-flex align-items-center justify-content-between p-3 border-bottom">';
                            echo '<div>';
                            echo '<h6 class="fw-bold mb-1"><i class="fa-solid fa-award text-warning me-1"></i> ' . htmlspecialchars($cert['title']) . '</h6>';
                            echo '<div class="text-muted small">Verification Code: ' . htmlspecialchars($cert['certificate_code']) . '</div>';
                            echo '<div class="text-muted small">Issued on ' . $issued_f . '</div>';
                            echo '</div>';
                            
                            // Download button (links to verification/download modal or generator helper stub)
                            echo '<a href="#" class="btn btn-sm btn-success" onclick="alert(\'Downloading Certificate: ' . htmlspecialchars($cert['certificate_code']) . '\'); return false;"><i class="fa-solid fa-download me-1"></i>Download</a>';
                            echo '</div>';
                        }
                    } else {
                        echo '<p class="text-muted text-center py-4 mb-0">No certificates available yet. Mark attendance and wait for admin approval.</p>';
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
include $base_path . 'includes/footer.php';
?>
