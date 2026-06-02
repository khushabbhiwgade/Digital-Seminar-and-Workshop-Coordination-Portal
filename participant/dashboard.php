<?php
// participant/dashboard.php
$base_path = '../';
$page_title = 'Participant Dashboard - Campus Connect';
$active_page = 'participant_dashboard';

// Enforce participant role
require_once $base_path . 'includes/auth.php';
require_role(['participant', 'student']);

// Database connection
require_once $base_path . 'config/db_connect.php';

$user_id = get_user_id();
$user_name = get_user_name();

// 1. Fetch user details to get category and organization
$u_sql = "SELECT participant_type, organization FROM users WHERE id = ?";
$u_stmt = $conn->prepare($u_sql);
$u_stmt->bind_param("i", $user_id);
$u_stmt->execute();
$u_res = $u_stmt->get_result();
$user_meta = $u_res->fetch_assoc();
$participant_type = $user_meta['participant_type'] ?? 'Participant';
$organization = $user_meta['organization'] ?? 'Not Specified';

// 2. Handle feedback form submission relationally
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'submit_feedback') {
    $reg_id = intval($_POST['reg_id'] ?? 0);
    $rating = intval($_POST['rating'] ?? 5);
    $comments = trim($_POST['comments'] ?? '');

    // Get event_id for this registration
    $check_reg = "SELECT event_id FROM registrations WHERE id = ? AND user_id = ?";
    $check_stmt = $conn->prepare($check_reg);
    $check_stmt->bind_param("ii", $reg_id, $user_id);
    $check_stmt->execute();
    $check_res = $check_stmt->get_result();

    if ($check_res && $check_res->num_rows > 0) {
        $reg_data = $check_res->fetch_assoc();
        $event_id = $reg_data['event_id'];

        // Save feedback in database
        $feed_sql = "INSERT INTO feedback (user_id, event_id, rating, comments) VALUES (?, ?, ?, ?)
                     ON DUPLICATE KEY UPDATE rating = VALUES(rating), comments = VALUES(comments)";
        $feed_stmt = $conn->prepare($feed_sql);
        $feed_stmt->bind_param("iiis", $user_id, $event_id, $rating, $comments);
        if ($feed_stmt->execute()) {
            header("Location: dashboard.php?feedback=submitted");
            exit;
        }
    }
}

// 3. Fetch analytics count statistics
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

// Feedback Pending (Attended registrations that do not have a feedback row)
$pending_feedback_count = 0;
$count_sql3 = "SELECT COUNT(*) as cnt FROM registrations r 
               LEFT JOIN feedback f ON r.user_id = f.user_id AND r.event_id = f.event_id
               WHERE r.user_id = ? AND r.attended = 1 AND f.id IS NULL";
$stmt3 = $conn->prepare($count_sql3);
$stmt3->bind_param("i", $user_id);
$stmt3->execute();
$res3 = $stmt3->get_result();
if ($res3) {
    $pending_feedback_count = $res3->fetch_assoc()['cnt'];
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
    <div class="row align-items-center mb-5">
        <div class="col-md-8">
            <h1 class="h2 mb-1 fw-bold text-dark">Participant Dashboard</h1>
            <p class="text-muted mb-0">Welcome back, <span class="fw-semibold text-primary"><?php echo htmlspecialchars($user_name); ?></span> | Category: <span class="badge bg-secondary"><?php echo htmlspecialchars($participant_type); ?></span> | Org: <span class="fw-semibold text-dark"><?php echo htmlspecialchars($organization); ?></span></p>
        </div>
        <div class="col-md-4 text-md-end mt-3 mt-md-0">
            <a href="<?php echo $base_path; ?>events.php" class="btn btn-warning text-dark fw-bold px-4 py-2 shadow-sm"><i class="fa-solid fa-calendar-days me-2"></i>Browse Seminars</a>
        </div>
    </div>

    <!-- Notice alerts -->
    <?php if (isset($_GET['feedback']) && $_GET['feedback'] === 'submitted'): ?>
        <div class="alert alert-success alert-dismissible fade show border-start border-4 border-success shadow-sm mb-4" role="alert">
            <i class="fa-solid fa-circle-check text-success me-2 fs-5"></i><strong>Feedback Submitted:</strong> Thank you for your review! Your evaluation helps us coordinate premium workshops.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Dashboard Stat Cards -->
    <div class="row g-4 mb-5">
        <div class="col-md-3">
            <div class="card h-100 border-0 shadow-sm rounded-3 bg-white p-3 text-center border-bottom border-3 border-primary">
                <div class="display-6 text-primary mb-2"><i class="fa-solid fa-calendar-check"></i></div>
                <h3 class="fw-bold text-dark mb-1"><?php echo intval($upcoming_count); ?></h3>
                <span class="text-muted small fw-bold text-uppercase">Available Events</span>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100 border-0 shadow-sm rounded-3 bg-white p-3 text-center border-bottom border-3 border-success">
                <div class="display-6 text-success mb-2"><i class="fa-solid fa-id-badge"></i></div>
                <h3 class="fw-bold text-dark mb-1"><?php echo intval($my_reg_count); ?></h3>
                <span class="text-muted small fw-bold text-uppercase">My Registrations</span>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100 border-0 shadow-sm rounded-3 bg-white p-3 text-center border-bottom border-3 border-warning">
                <div class="display-6 text-warning mb-2"><i class="fa-solid fa-star-half-stroke"></i></div>
                <h3 class="fw-bold text-dark mb-1"><?php echo intval($pending_feedback_count); ?></h3>
                <span class="text-muted small fw-bold text-uppercase">Feedback Pending</span>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100 border-0 shadow-sm rounded-3 bg-white p-3 text-center border-bottom border-3 border-info">
                <div class="display-6 text-info mb-2"><i class="fa-solid fa-certificate"></i></div>
                <h3 class="fw-bold text-dark mb-1"><?php echo intval($cert_count); ?></h3>
                <span class="text-muted small fw-bold text-uppercase">Certificates Earned</span>
            </div>
        </div>
    </div>

    <!-- Main listings -->
    <div class="row g-4">
        <!-- 1. Upcoming Events Section -->
        <div class="col-lg-6">
            <div class="card shadow-sm border-0 rounded-3 mb-4 h-100">
                <div class="card-header bg-dark text-white py-3">
                    <h5 class="mb-0 fw-bold"><i class="fa-solid fa-bolt text-warning me-2"></i>Upcoming Seminars &amp; Workshops</h5>
                </div>
                <div class="card-body p-0">
                    <?php
                    // Fetch upcoming events
                    $events_sql = "SELECT id, title, host, event_date, location, seats_remaining, total_seats FROM events WHERE event_date >= CURDATE() ORDER BY event_date ASC";
                    $events_res = $conn->query($events_sql);

                    // Fetch user's registered event IDs
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
                            echo '<div class="text-muted small mb-1"><i class="fa-solid fa-calendar me-1"></i> ' . $date_f . ' | <i class="fa-solid fa-user-tie me-1"></i> ' . htmlspecialchars($ev['host']) . '</div>';
                            echo '<div class="text-muted small"><i class="fa-solid fa-location-dot text-danger me-1"></i> ' . htmlspecialchars($ev['location']) . ' | <i class="fa-solid fa-chair text-success me-1"></i> ' . intval($ev['seats_remaining']) . '/' . intval($ev['total_seats']) . ' Seats Remaining</div>';
                            echo '</div>';
                            
                            if ($is_registered) {
                                echo '<button class="btn btn-sm btn-outline-secondary px-3 align-self-center fw-bold" disabled><i class="fa-solid fa-check-circle text-success me-1"></i>Registered</button>';
                            } elseif ($ev['seats_remaining'] <= 0) {
                                echo '<button class="btn btn-sm btn-outline-danger px-3 align-self-center fw-bold" disabled>Full</button>';
                            } else {
                                echo '<a href="register.php?event_id=' . intval($ev['id']) . '" class="btn btn-sm btn-warning text-dark fw-bold px-3 align-self-center">Register</a>';
                            }
                            echo '</div>';
                        }
                    } else {
                        echo '<p class="text-muted text-center py-4 mb-0">No upcoming seminars listed at this time.</p>';
                    }
                    ?>
                </div>
            </div>
        </div>

        <!-- 2. Registered Events & Tickets Section -->
        <div class="col-lg-6">
            <div class="card shadow-sm border-0 rounded-3 mb-4 h-100" id="my-registrations">
                <div class="card-header bg-dark text-white py-3">
                    <h5 class="mb-0 fw-bold"><i class="fa-solid fa-id-card text-warning me-2"></i>My Registered Event Tickets</h5>
                </div>
                <div class="card-body">
                    <?php
                    // Fetch registered events
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
                                ? '<span class="badge bg-success"><i class="fa-solid fa-circle-check me-1"></i>Attended</span>' 
                                : '<span class="badge bg-secondary"><i class="fa-solid fa-ticket me-1"></i>Reserved</span>';
                                
                            echo '<div class="p-3 border rounded-3 mb-3 bg-light shadow-sm">';
                            echo '<div class="d-flex justify-content-between align-items-start">';
                            echo '<div>';
                            echo '<h6 class="fw-bold text-dark mb-1">' . htmlspecialchars($reg['title']) . '</h6>';
                            echo '<div class="text-muted small mb-2"><i class="fa-solid fa-calendar me-1"></i> ' . $date_f . ' | <i class="fa-solid fa-location-dot me-1"></i> ' . htmlspecialchars($reg['location']) . '</div>';
                            echo '<div class="font-monospace text-primary fw-bold small">Ticket Token: ' . htmlspecialchars($reg['token'] ?? 'Pending') . '</div>';
                            echo '</div>';
                            echo '<div>' . $status_badge . '</div>';
                            echo '</div>';
                            echo '</div>';
                        }
                    } else {
                        echo '<p class="text-muted text-center py-4 mb-0">You have not registered for any events yet.</p>';
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mt-2">
        <!-- 3. Feedback Surveys Section -->
        <div class="col-lg-6">
            <div class="card shadow-sm border-0 rounded-3 mb-4 h-100">
                <div class="card-header bg-dark text-white py-3">
                    <h5 class="mb-0 fw-bold"><i class="fa-solid fa-star text-warning me-2"></i>Feedback Surveys Pending</h5>
                </div>
                <div class="card-body p-0">
                    <?php
                    // Fetch attended events that do not have a feedback row yet
                    $feed_sql = "SELECT r.id as reg_id, e.title, e.event_date 
                                 FROM registrations r 
                                 JOIN events e ON r.event_id = e.id 
                                 LEFT JOIN feedback f ON r.user_id = f.user_id AND r.event_id = f.event_id
                                 WHERE r.user_id = ? AND r.attended = 1 AND f.id IS NULL
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
                            echo '<h6 class="fw-bold text-dark mb-1">' . htmlspecialchars($fed['title']) . '</h6>';
                            echo '<div class="text-muted small"><i class="fa-solid fa-calendar me-1"></i> Attended on ' . $date_f . '</div>';
                            echo '</div>';
                            echo '<button class="btn btn-sm btn-outline-warning text-dark fw-bold px-3" data-bs-toggle="modal" data-bs-target="#feedbackModal' . $fed['reg_id'] . '">Submit Feedback</button>';
                            echo '</div>';
                            
                            // Feedback Modal structure
                            ?>
                            <div class="modal fade" id="feedbackModal<?php echo $fed['reg_id']; ?>" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content border-0 shadow-lg">
                                        <div class="modal-header bg-dark text-white py-3" style="border-bottom: 2px solid #eab308;">
                                            <h5 class="modal-title fw-bold">
                                                <i class="fa-solid fa-star text-warning me-2"></i>Submit Event Feedback
                                            </h5>
                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <form method="POST" action="dashboard.php">
                                            <input type="hidden" name="action" value="submit_feedback">
                                            <input type="hidden" name="reg_id" value="<?php echo $fed['reg_id']; ?>">
                                            <div class="modal-body p-4 bg-white">
                                                <h6 class="fw-bold text-primary mb-3"><?php echo htmlspecialchars($fed['title']); ?></h6>
                                                
                                                <div class="mb-3">
                                                    <label class="form-label fw-bold small text-uppercase">Rate the Session</label>
                                                    <select class="form-select" name="rating" required>
                                                        <option value="5" selected>5 Stars - Excellent Session</option>
                                                        <option value="4">4 Stars - Good Session</option>
                                                        <option value="3">3 Stars - Average Session</option>
                                                        <option value="2">2 Stars - Below Average</option>
                                                        <option value="1">1 Star - Poor Session</option>
                                                    </select>
                                                </div>

                                                <div class="mb-3">
                                                    <label class="form-label fw-bold small text-uppercase">Your Review / Comments</label>
                                                    <textarea class="form-control" name="comments" rows="4" placeholder="What key insights did you learn? Any suggestions?" required></textarea>
                                                </div>
                                            </div>
                                            <div class="modal-footer bg-light border-0">
                                                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                                                <button type="submit" class="btn btn-warning text-dark fw-bold btn-sm px-4">Submit Evaluation</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <?php
                        }
                    } else {
                        echo '<p class="text-muted text-center py-4 mb-0">No feedback pending. Thank you for your evaluations!</p>';
                    }
                    ?>
                </div>
            </div>
        </div>

        <!-- 4. Download Certificates Section -->
        <div class="col-lg-6">
            <div class="card shadow-sm border-0 rounded-3 mb-4 h-100">
                <div class="card-header bg-dark text-white py-3">
                    <h5 class="mb-0 fw-bold"><i class="fa-solid fa-award text-warning me-2"></i>Download Certificates Earned</h5>
                </div>
                <div class="card-body p-0">
                    <?php
                    // Fetch certificates
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
                            echo '<h6 class="fw-bold text-dark mb-1"><i class="fa-solid fa-ribbon text-warning me-1"></i> ' . htmlspecialchars($cert['title']) . '</h6>';
                            echo '<div class="text-muted small">Verification Code: <span class="fw-semibold text-primary font-monospace">' . htmlspecialchars($cert['certificate_code']) . '</span></div>';
                            echo '<div class="text-muted small">Issued on ' . $issued_f . '</div>';
                            echo '</div>';
                            
                            echo '<a href="#" class="btn btn-sm btn-success px-3 fw-bold" onclick="alert(\'Downloading Certificate: ' . htmlspecialchars($cert['certificate_code']) . '\'); return false;"><i class="fa-solid fa-download me-1"></i>Download</a>';
                            echo '</div>';
                        }
                    } else {
                        echo '<p class="text-muted text-center py-4 mb-0">No certificates available yet. Attend your registered workshops and wait for issuance.</p>';
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
