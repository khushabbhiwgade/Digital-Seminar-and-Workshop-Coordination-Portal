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
        <div class="col-md-4">
            <a href="participants.php" class="text-decoration-none text-dark">
                <div class="card h-100 border-0 shadow-sm rounded-3 p-3 bg-white hover-card text-center">
                    <div class="display-6 text-primary mb-2"><i class="fa-solid fa-users"></i></div>
                    <h5 class="fw-bold mb-1">Participant Accounts</h5>
                    <span class="text-muted small">Manage registered profiles &amp; access</span>
                </div>
            </a>
        </div>
        <div class="col-md-4">
            <a href="attendance.php" class="text-decoration-none text-dark">
                <div class="card h-100 border-0 shadow-sm rounded-3 p-3 bg-white hover-card text-center">
                    <div class="display-6 text-success mb-2"><i class="fa-solid fa-clipboard-user"></i></div>
                    <h5 class="fw-bold mb-1">Attendance Desk</h5>
                    <span class="text-muted small">Log student check-ins with tokens</span>
                </div>
            </a>
        </div>
        <div class="col-md-4">
            <a href="certificates.php" class="text-decoration-none text-dark">
                <div class="card h-100 border-0 shadow-sm rounded-3 p-3 bg-white hover-card text-center">
                    <div class="display-6 text-warning mb-2"><i class="fa-solid fa-award"></i></div>
                    <h5 class="fw-bold mb-1">Certificates</h5>
                    <span class="text-muted small">Issue credentials to attendees</span>
                </div>
            </a>
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
                                // Fetch events data
                                $eventSql = "SELECT id, title, host, total_seats, seats_remaining FROM events ORDER BY id ASC";
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

<?php
include $base_path . 'includes/footer.php';
?>
