<?php
// admin/dashboard.php
$base_path = '../';
$page_title = 'Admin Dashboard - Campus Connect';
$active_page = 'admin_dashboard';

// Enforce admin login and role
require_once $base_path . 'includes/auth.php';
require_role('admin');

// Database connection
require_once $base_path . 'config/db_connect.php';

// Fetch admin certificate statistics (Part 14)
$cnt_gen = 0;
$res_gen = $conn->query("SELECT COUNT(*) as cnt FROM certificates WHERE status = 'Generated'");
if ($res_gen) $cnt_gen = $res_gen->fetch_assoc()['cnt'];

$cnt_rev = 0;
$res_rev = $conn->query("SELECT COUNT(*) as cnt FROM certificates WHERE status = 'Revoked'");
if ($res_rev) $cnt_rev = $res_rev->fetch_assoc()['cnt'];

$cnt_pen = 0;
$res_pen = $conn->query("
    SELECT COUNT(*) as cnt
    FROM tickets t
    JOIN attendance a ON t.id = a.ticket_id
    LEFT JOIN certificates c ON t.id = c.registration_id
    WHERE a.attendance_status = 'Present' 
      AND t.registration_status = 'Completed' 
      AND c.id IS NULL
");
if ($res_pen) $cnt_pen = $res_pen->fetch_assoc()['cnt'];

// Fetch admin feedback statistics (Part 14)
$cnt_fb_sub = 0;
$res_fb_sub = $conn->query("SELECT COUNT(*) as cnt FROM feedback");
if ($res_fb_sub) $cnt_fb_sub = $res_fb_sub->fetch_assoc()['cnt'];

$cnt_fb_pen = 0;
$res_fb_pen = $conn->query("
    SELECT COUNT(*) as cnt
    FROM tickets t
    JOIN attendance a ON t.id = a.ticket_id
    LEFT JOIN feedback f ON t.id = f.registration_id
    WHERE a.attendance_status = 'Present' 
      AND t.registration_status = 'Completed' 
      AND f.id IS NULL
");
if ($res_fb_pen) $cnt_fb_pen = $res_fb_pen->fetch_assoc()['cnt'];

$avg_rating = 0.0;
$res_avg = $conn->query("SELECT AVG(overall_rating) as avg_rate FROM feedback");
if ($res_avg && ($row = $res_avg->fetch_assoc())) {
    $avg_rating = $row['avg_rate'] !== null ? floatval($row['avg_rate']) : 0.0;
}

$highest_rated = "N/A";
$highest_score = 0.0;
$res_high = $conn->query("
    SELECT w.title, AVG(f.overall_rating) as avg_rate 
    FROM feedback f 
    JOIN workshops w ON f.event_id = w.id 
    GROUP BY f.event_id 
    ORDER BY avg_rate DESC LIMIT 1
");
if ($res_high && ($row = $res_high->fetch_assoc())) {
    $highest_rated = $row['title'];
    $highest_score = floatval($row['avg_rate']);
}

$lowest_rated = "N/A";
$lowest_score = 0.0;
$res_low = $conn->query("
    SELECT w.title, AVG(f.overall_rating) as avg_rate 
    FROM feedback f 
    JOIN workshops w ON f.event_id = w.id 
    GROUP BY f.event_id 
    ORDER BY avg_rate ASC LIMIT 1
");
if ($res_low && ($row = $res_low->fetch_assoc())) {
    $lowest_rated = $row['title'];
    $lowest_score = floatval($row['avg_rate']);
}

include $base_path . 'includes/header.php';
include $base_path . 'includes/navbar.php';
?>

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h2 mb-0">Admin Control Panel</h1>
            <p class="text-muted mb-0">Digital Seminar &amp; Workshop Coordination Portal</p>
        </div>
        <span class="badge bg-danger px-3 py-2 fs-6">Administrator Access</span>
    </div>

    <!-- Quick Link Actions for Admin Tasks -->
    <div class="row g-4 mb-4">
        <div class="col-md-3 col-sm-6">
            <a href="registrations/manage.php" class="text-decoration-none text-dark">
                <div class="card h-100 border-0 shadow-sm rounded-3 p-3 bg-white hover-card text-center">
                    <div class="display-6 text-info mb-2"><i class="fa-solid fa-graduation-cap"></i></div>
                    <h5 class="fw-bold mb-1">Registrations Desk</h5>
                    <span class="text-muted small">Approve and audit enrollments</span>
                </div>
            </a>
        </div>
        <div class="col-md-3 col-sm-6">
            <a href="attendance.php" class="text-decoration-none text-dark">
                <div class="card h-100 border-0 shadow-sm rounded-3 p-3 bg-white hover-card text-center">
                    <div class="display-6 text-success mb-2"><i class="fa-solid fa-clipboard-user"></i></div>
                    <h5 class="fw-bold mb-1">Attendance Desk</h5>
                    <span class="text-muted small">Log check-ins with tokens</span>
                </div>
            </a>
        </div>
        <div class="col-md-3 col-sm-6">
            <a href="certificates.php" class="text-decoration-none text-dark">
                <div class="card h-100 border-0 shadow-sm rounded-3 p-3 bg-white hover-card text-center">
                    <div class="display-6 text-warning mb-2"><i class="fa-solid fa-award"></i></div>
                    <h5 class="fw-bold mb-1">Certificates</h5>
                    <span class="text-muted small">Issue and manage credentials</span>
                </div>
            </a>
        </div>
        <div class="col-md-3 col-sm-6">
            <a href="feedback/manage.php" class="text-decoration-none text-dark">
                <div class="card h-100 border-0 shadow-sm rounded-3 p-3 bg-white hover-card text-center">
                    <div class="display-6 text-danger mb-2"><i class="fa-solid fa-comments"></i></div>
                    <h5 class="fw-bold mb-1">Feedback Board</h5>
                    <span class="text-muted small">Workshop evaluations &amp; scores</span>
                </div>
            </a>
        </div>
    </div>

    <!-- Certificate Metrics Row (Part 14) -->
    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card h-100 border-0 shadow-sm rounded-3 p-3 bg-white text-center border-start border-4 border-success">
                <div class="display-6 text-success mb-2"><i class="fa-solid fa-circle-check"></i></div>
                <h3 class="fw-bold mb-1"><?php echo intval($cnt_gen); ?></h3>
                <span class="text-muted small fw-semibold">Certificates Generated</span>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100 border-0 shadow-sm rounded-3 p-3 bg-white text-center border-start border-4 border-danger">
                <div class="display-6 text-danger mb-2"><i class="fa-solid fa-circle-xmark"></i></div>
                <h3 class="fw-bold mb-1"><?php echo intval($cnt_rev); ?></h3>
                <span class="text-muted small fw-semibold">Certificates Revoked</span>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100 border-0 shadow-sm rounded-3 p-3 bg-white text-center border-start border-4 border-warning">
                <div class="display-6 text-warning mb-2"><i class="fa-solid fa-hourglass-half"></i></div>
                <h3 class="fw-bold mb-1"><?php echo intval($cnt_pen); ?></h3>
                <span class="text-muted small fw-semibold">Certificates Pending</span>
            </div>
        </div>
    </div>

    <!-- Workshop Evaluation Metrics Row (Part 14) -->
    <div class="row g-4 mb-5">
        <div class="col-md-3 col-sm-6">
            <div class="card h-100 border-0 shadow-sm rounded-3 p-3 bg-white text-center border-start border-4 border-primary">
                <div class="display-6 text-primary mb-2"><i class="fa-solid fa-star-half-stroke"></i></div>
                <h3 class="fw-bold mb-1"><?php echo number_format($avg_rating, 2); ?> / 5.0</h3>
                <span class="text-muted small fw-semibold">Average Workshop Rating</span>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card h-100 border-0 shadow-sm rounded-3 p-3 bg-white text-center border-start border-4 border-success">
                <div class="display-6 text-success mb-2"><i class="fa-solid fa-chevron-up"></i></div>
                <h6 class="fw-bold mb-1 text-truncate" title="<?php echo htmlspecialchars($highest_rated); ?>"><?php echo htmlspecialchars($highest_rated); ?></h6>
                <span class="text-muted small fw-semibold">Highest (<?php echo number_format($highest_score, 2); ?> ★)</span>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card h-100 border-0 shadow-sm rounded-3 p-3 bg-white text-center border-start border-4 border-danger">
                <div class="display-6 text-danger mb-2"><i class="fa-solid fa-chevron-down"></i></div>
                <h6 class="fw-bold mb-1 text-truncate" title="<?php echo htmlspecialchars($lowest_rated); ?>"><?php echo htmlspecialchars($lowest_rated); ?></h6>
                <span class="text-muted small fw-semibold">Lowest (<?php echo number_format($lowest_score, 2); ?> ★)</span>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card h-100 border-0 shadow-sm rounded-3 p-3 bg-white text-center border-start border-4 border-warning">
                <div class="display-6 text-warning mb-2"><i class="fa-solid fa-clipboard-question"></i></div>
                <h3 class="fw-bold mb-1"><?php echo intval($cnt_fb_sub); ?> / <?php echo intval($cnt_fb_sub + $cnt_fb_pen); ?></h3>
                <span class="text-muted small fw-semibold">Feedback (<?php echo intval($cnt_fb_pen); ?> Pending)</span>
            </div>
        </div>
    </div>

    <!-- Full-Width Event Dashboard Analytics Table -->
    <div class="row">
        <div class="col-12 mb-4">
            <div class="card shadow-sm border-0 rounded-3 overflow-hidden">
                <div class="card-header bg-dark text-white py-3">
                    <h5 class="mb-0"><i class="fa-solid fa-chart-line text-warning me-2"></i>Event Performance &amp; Seat Capacities</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped align-middle mb-0">
                            <thead class="table-dark">
                                <tr>
                                    <th>Workshop / Seminar Title</th>
                                    <th>Host / Speaker</th>
                                    <th class="text-center">Total Seats</th>
                                    <th class="text-center">Registered Students</th>
                                    <th class="text-center">Seats Remaining</th>
                                    <th>Fullness Capacity</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // Fetch workshops data dynamically
                                $eventSql = "SELECT id, title, host, capacity as total_seats, seats_remaining FROM workshops ORDER BY id ASC";
                                $eventRes = $conn->query($eventSql);
                                if ($eventRes && $eventRes->num_rows > 0) {
                                    while ($event = $eventRes->fetch_assoc()) {
                                        $registered = $event['total_seats'] - $event['seats_remaining'];
                                        $percent = ($event['total_seats'] > 0) ? ($registered / $event['total_seats']) * 100 : 0;
                                        $percent = max(0, min(100, $percent));
                                        
                                        // Pick bar color based on percentage
                                        $bar_color = "bg-primary";
                                        if ($percent >= 90) { $bar_color = "bg-danger"; }
                                        elseif ($percent >= 50) { $bar_color = "bg-warning"; }
                                        
                                        echo '<tr>';
                                        echo '<td class="fw-semibold">' . htmlspecialchars($event['title']) . '</td>';
                                        echo '<td>' . htmlspecialchars($event['host']) . '</td>';
                                        echo '<td class="text-center">' . intval($event['total_seats']) . '</td>';
                                        echo '<td class="text-center">' . intval($registered) . '</td>';
                                        echo '<td class="text-center fw-bold text-success">' . intval($event['seats_remaining']) . '</td>';
                                        echo '<td>';
                                        echo '<div class="d-flex align-items-center">';
                                        echo '<div class="progress w-100 me-2" style="height:18px;">';
                                        echo '<div class="progress-bar ' . $bar_color . '" role="progressbar" style="width: ' . $percent . '%" aria-valuenow="' . $percent . '" aria-valuemin="0" aria-valuemax="100">' . round($percent, 1) . '%</div>';
                                        echo '</div>';
                                        echo '</div>';
                                        echo '</td>';
                                        echo '</tr>';
                                    }
                                } else {
                                    echo '<tr><td colspan="6" class="text-center text-muted py-4">No events found in the portal database.</td></tr>';
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.hover-card {
    transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
}
.hover-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 .5rem 1rem rgba(0,0,0,.15)!important;
}
</style>

<?php
include $base_path . 'includes/footer.php';
?>
