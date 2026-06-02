<?php
// coordinator/dashboard.php
$base_path = '../';
$page_title = 'Coordinator Dashboard - Campus Connect';
$active_page = 'coordinator_dashboard';

// Enforce coordinator role
require_once $base_path . 'includes/auth.php';
require_role('coordinator');

// Database connection
require_once $base_path . 'config/db_connect.php';

$coordinator_id = get_user_id();
$coordinator_name = get_user_name();

include $base_path . 'includes/header.php';
include $base_path . 'includes/navbar.php';
?>

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h2 mb-0">Coordinator Panel</h1>
            <p class="text-muted mb-0">Welcome back, <span class="fw-semibold text-dark"><?php echo htmlspecialchars($coordinator_name); ?></span></p>
        </div>
        <span class="badge bg-primary px-3 py-2 fs-6">Academic Coordinator</span>
    </div>

    <div class="row">
        <!-- Main Panel: Coordinated Seminars & Workshops -->
        <div class="col-12">
            <div class="card shadow-sm border-0 rounded-3 overflow-hidden">
                <div class="card-header bg-dark text-white py-3">
                    <h5 class="mb-0"><i class="fa-solid fa-list-check text-warning me-2"></i>My Coordinated Seminars &amp; Workshops</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped align-middle mb-0">
                            <thead class="table-dark">
                                <tr>
                                    <th>Event Title</th>
                                    <th>Host / Speaker</th>
                                    <th>Date &amp; Time</th>
                                    <th>Location</th>
                                    <th class="text-center">Total Seats</th>
                                    <th class="text-center">Registered</th>
                                    <th class="text-center">Remaining</th>
                                    <th style="width: 200px;">Fullness</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // Fetch events coordinated by this user
                                $sql = "SELECT id, title, host, event_date, start_time, end_time, location, total_seats, seats_remaining 
                                        FROM events 
                                        WHERE coordinator_id = ?";
                                
                                $stmt = $conn->prepare($sql);
                                $stmt->bind_param("i", $coordinator_id);
                                $stmt->execute();
                                $res = $stmt->get_result();

                                if ($res && $res->num_rows > 0) {
                                    while ($event = $res->fetch_assoc()) {
                                        $registered = $event['total_seats'] - $event['seats_remaining'];
                                        $percent = ($event['total_seats'] > 0) ? ($registered / $event['total_seats']) * 100 : 0;
                                        $percent = max(0, min(100, $percent));
                                        
                                        $date_formatted = date("M d, Y", strtotime($event['event_date']));
                                        $time_formatted = date("h:i A", strtotime($event['start_time'])) . ' - ' . date("h:i A", strtotime($event['end_time']));
                                        
                                        echo '<tr>';
                                        echo '<td class="fw-semibold">' . htmlspecialchars($event['title']) . '</td>';
                                        echo '<td>' . htmlspecialchars($event['host']) . '</td>';
                                        echo '<td><div>' . $date_formatted . '</div><small class="text-muted">' . $time_formatted . '</small></td>';
                                        echo '<td><i class="fa-solid fa-location-dot text-danger me-1"></i>' . htmlspecialchars($event['location']) . '</td>';
                                        echo '<td class="text-center">' . intval($event['total_seats']) . '</td>';
                                        echo '<td class="text-center">' . intval($registered) . '</td>';
                                        echo '<td class="text-center fw-bold text-success">' . intval($event['seats_remaining']) . '</td>';
                                        echo '<td>';
                                        echo '<div class="progress" style="height:18px;">';
                                        echo '<div class="progress-bar bg-info" role="progressbar" style="width: ' . $percent . '%" aria-valuenow="' . $percent . '" aria-valuemin="0" aria-valuemax="100">' . round($percent, 1) . '%</div>';
                                        echo '</div>';
                                        echo '</td>';
                                        echo '</tr>';
                                    }
                                } else {
                                    echo '<tr><td colspan="8" class="text-center text-muted py-4"><i class="fa-solid fa-calendar-xmark display-6 mb-2"></i><br>No events currently assigned to you. Contact the Portal Administrator to link your account to your seminars.</td></tr>';
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
