<?php
// admin/reports.php
// ------------------------------------------------------------
// Admin — Reports & Analytics Overview
// ------------------------------------------------------------
$base_path = '../';
$page_title = 'Reports & Analytics - Admin Panel';
$active_page = 'admin_reports';

require_once $base_path . 'includes/auth.php';
require_role('admin');
require_once $base_path . 'config/db_connect.php';

// ---- Aggregate Statistics ----

// Total users by role
$role_stats = ['admin' => 0, 'coordinator' => 0, 'student' => 0];
$role_res = $conn->query("SELECT role, COUNT(*) as cnt FROM users GROUP BY role");
if ($role_res) {
    while ($r = $role_res->fetch_assoc()) {
        $role_stats[$r['role']] = intval($r['cnt']);
    }
}

// Total events
$total_events = 0;
$ev_res = $conn->query("SELECT COUNT(*) as cnt FROM events");
if ($ev_res) $total_events = $ev_res->fetch_assoc()['cnt'];

// Upcoming events
$upcoming_events = 0;
$ue_res = $conn->query("SELECT COUNT(*) as cnt FROM events WHERE event_date >= CURDATE()");
if ($ue_res) $upcoming_events = $ue_res->fetch_assoc()['cnt'];

// Total registrations
$total_regs = 0;
$tr_res = $conn->query("SELECT COUNT(*) as cnt FROM registrations");
if ($tr_res) $total_regs = $tr_res->fetch_assoc()['cnt'];

// Total attended
$total_attended = 0;
$ta_res = $conn->query("SELECT COUNT(*) as cnt FROM registrations WHERE attended = 1");
if ($ta_res) $total_attended = $ta_res->fetch_assoc()['cnt'];

// Total certificates
$total_certs = 0;
$tc_res = $conn->query("SELECT COUNT(*) as cnt FROM certificates");
if ($tc_res) $total_certs = $tc_res->fetch_assoc()['cnt'];

// Attendance rate
$attendance_rate = ($total_regs > 0) ? round(($total_attended / $total_regs) * 100, 1) : 0;

// ---- Per-Event Report ----
$event_report = [];
$er_sql = "SELECT e.id, e.title, e.host, e.event_date, e.location, e.total_seats, e.seats_remaining,
                  COUNT(r.id) AS reg_count,
                  SUM(CASE WHEN r.attended = 1 THEN 1 ELSE 0 END) AS attended_count,
                  (SELECT COUNT(*) FROM certificates c2 JOIN registrations r2 ON c2.registration_id = r2.id WHERE r2.event_id = e.id) AS cert_count
           FROM events e
           LEFT JOIN registrations r ON e.id = r.event_id
           GROUP BY e.id
           ORDER BY e.event_date DESC";
$er_res = $conn->query($er_sql);
if ($er_res) {
    while ($row = $er_res->fetch_assoc()) $event_report[] = $row;
}

// ---- Top Registered Events ----
$top_events = [];
$top_sql = "SELECT e.title, COUNT(r.id) AS reg_count
            FROM events e
            JOIN registrations r ON e.id = r.event_id
            GROUP BY e.id
            ORDER BY reg_count DESC
            LIMIT 5";
$top_res = $conn->query($top_sql);
if ($top_res) {
    while ($t = $top_res->fetch_assoc()) $top_events[] = $t;
}

include $base_path . 'includes/header.php';
include $base_path . 'includes/navbar.php';
?>

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h2 mb-0"><i class="fa-solid fa-chart-pie text-info me-2"></i>Reports &amp; Analytics</h1>
            <p class="text-muted mb-0">Portal-wide statistics and event performance reports</p>
        </div>
        <a href="dashboard.php" class="btn btn-outline-dark btn-sm"><i class="fa-solid fa-arrow-left me-1"></i>Back to Dashboard</a>
    </div>

    <!-- KPI Summary Cards -->
    <div class="row g-3 mb-5">
        <div class="col-md-2 col-6">
            <div class="card border-0 shadow-sm rounded-3 text-center p-3 bg-white h-100">
                <div class="fs-3 text-primary mb-1"><i class="fa-solid fa-calendar-days"></i></div>
                <h4 class="fw-bold mb-0"><?php echo intval($total_events); ?></h4>
                <span class="text-muted small">Total Events</span>
            </div>
        </div>
        <div class="col-md-2 col-6">
            <div class="card border-0 shadow-sm rounded-3 text-center p-3 bg-white h-100">
                <div class="fs-3 text-success mb-1"><i class="fa-solid fa-user-graduate"></i></div>
                <h4 class="fw-bold mb-0"><?php echo intval($role_stats['student']); ?></h4>
                <span class="text-muted small">Students</span>
            </div>
        </div>
        <div class="col-md-2 col-6">
            <div class="card border-0 shadow-sm rounded-3 text-center p-3 bg-white h-100">
                <div class="fs-3 text-info mb-1"><i class="fa-solid fa-clipboard-list"></i></div>
                <h4 class="fw-bold mb-0"><?php echo intval($total_regs); ?></h4>
                <span class="text-muted small">Registrations</span>
            </div>
        </div>
        <div class="col-md-2 col-6">
            <div class="card border-0 shadow-sm rounded-3 text-center p-3 bg-white h-100">
                <div class="fs-3 text-warning mb-1"><i class="fa-solid fa-user-check"></i></div>
                <h4 class="fw-bold mb-0"><?php echo intval($total_attended); ?></h4>
                <span class="text-muted small">Attended</span>
            </div>
        </div>
        <div class="col-md-2 col-6">
            <div class="card border-0 shadow-sm rounded-3 text-center p-3 bg-white h-100">
                <div class="fs-3 text-danger mb-1"><i class="fa-solid fa-percent"></i></div>
                <h4 class="fw-bold mb-0"><?php echo $attendance_rate; ?>%</h4>
                <span class="text-muted small">Attendance Rate</span>
            </div>
        </div>
        <div class="col-md-2 col-6">
            <div class="card border-0 shadow-sm rounded-3 text-center p-3 bg-white h-100">
                <div class="fs-3 text-secondary mb-1"><i class="fa-solid fa-award"></i></div>
                <h4 class="fw-bold mb-0"><?php echo intval($total_certs); ?></h4>
                <span class="text-muted small">Certificates</span>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <!-- Top Events -->
        <div class="col-lg-5">
            <div class="card shadow-sm border-0 rounded-3 h-100">
                <div class="card-header bg-dark text-white py-3">
                    <h5 class="mb-0"><i class="fa-solid fa-trophy text-warning me-2"></i>Top 5 Most Registered Events</h5>
                </div>
                <div class="card-body">
                    <?php if (count($top_events) > 0): ?>
                        <?php foreach ($top_events as $rank => $te): ?>
                            <div class="d-flex align-items-center justify-content-between py-2 <?php echo $rank < count($top_events) - 1 ? 'border-bottom' : ''; ?>">
                                <div class="d-flex align-items-center">
                                    <span class="badge <?php echo $rank === 0 ? 'bg-warning text-dark' : 'bg-secondary'; ?> rounded-pill me-3 fs-6"><?php echo $rank + 1; ?></span>
                                    <span class="fw-semibold"><?php echo htmlspecialchars($te['title']); ?></span>
                                </div>
                                <span class="badge bg-primary rounded-pill"><?php echo intval($te['reg_count']); ?> reg</span>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-muted text-center py-3 mb-0">No registrations recorded yet.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- User Role Breakdown -->
        <div class="col-lg-7">
            <div class="card shadow-sm border-0 rounded-3 h-100">
                <div class="card-header bg-dark text-white py-3">
                    <h5 class="mb-0"><i class="fa-solid fa-users-rectangle text-warning me-2"></i>User Accounts Summary</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3 text-center">
                        <div class="col-4">
                            <div class="p-3 rounded-3 bg-light">
                                <i class="fa-solid fa-shield-halved fs-2 text-danger mb-2 d-block"></i>
                                <h3 class="fw-bold mb-0"><?php echo $role_stats['admin']; ?></h3>
                                <span class="text-muted small">Admins</span>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="p-3 rounded-3 bg-light">
                                <i class="fa-solid fa-chalkboard-user fs-2 text-primary mb-2 d-block"></i>
                                <h3 class="fw-bold mb-0"><?php echo $role_stats['coordinator']; ?></h3>
                                <span class="text-muted small">Coordinators</span>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="p-3 rounded-3 bg-light">
                                <i class="fa-solid fa-user-graduate fs-2 text-success mb-2 d-block"></i>
                                <h3 class="fw-bold mb-0"><?php echo $role_stats['student']; ?></h3>
                                <span class="text-muted small">Students</span>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div class="text-center">
                        <span class="text-muted small">Upcoming Events: <strong class="text-dark"><?php echo intval($upcoming_events); ?></strong> | Past Events: <strong class="text-dark"><?php echo intval($total_events - $upcoming_events); ?></strong></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Full Event Performance Report -->
    <div class="card shadow-sm border-0 rounded-3 overflow-hidden">
        <div class="card-header bg-dark text-white py-3">
            <h5 class="mb-0"><i class="fa-solid fa-table-cells text-warning me-2"></i>Full Event Performance Report</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped align-middle mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>Event Title</th>
                            <th>Host</th>
                            <th>Date</th>
                            <th>Location</th>
                            <th class="text-center">Seats</th>
                            <th class="text-center">Registered</th>
                            <th class="text-center">Attended</th>
                            <th class="text-center">Certs</th>
                            <th>Attendance Rate</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($event_report) > 0): ?>
                            <?php foreach ($event_report as $er): ?>
                                <?php
                                $er_rate = ($er['reg_count'] > 0) ? round(($er['attended_count'] / $er['reg_count']) * 100, 1) : 0;
                                $bar_color = 'bg-primary';
                                if ($er_rate >= 80) $bar_color = 'bg-success';
                                elseif ($er_rate >= 50) $bar_color = 'bg-warning';
                                elseif ($er_rate > 0) $bar_color = 'bg-danger';
                                ?>
                                <tr>
                                    <td class="fw-semibold"><?php echo htmlspecialchars($er['title']); ?></td>
                                    <td><?php echo htmlspecialchars($er['host']); ?></td>
                                    <td><?php echo date("M d, Y", strtotime($er['event_date'])); ?></td>
                                    <td><i class="fa-solid fa-location-dot text-danger me-1"></i><?php echo htmlspecialchars($er['location']); ?></td>
                                    <td class="text-center"><?php echo intval($er['total_seats']); ?></td>
                                    <td class="text-center"><span class="badge bg-primary rounded-pill"><?php echo intval($er['reg_count']); ?></span></td>
                                    <td class="text-center"><span class="badge bg-success rounded-pill"><?php echo intval($er['attended_count']); ?></span></td>
                                    <td class="text-center"><span class="badge bg-warning text-dark rounded-pill"><?php echo intval($er['cert_count']); ?></span></td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="progress w-100 me-2" style="height: 18px;">
                                                <div class="progress-bar <?php echo $bar_color; ?>" role="progressbar" style="width: <?php echo $er_rate; ?>%"><?php echo $er_rate; ?>%</div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="9" class="text-center text-muted py-4">No events found in the portal database.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php
include $base_path . 'includes/footer.php';
?>
