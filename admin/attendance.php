<?php
// admin/attendance.php
// ------------------------------------------------------------
// Admin — Attendance Desk (Token Check-In)
// ------------------------------------------------------------
$base_path = '../';
$page_title = 'Attendance Desk - Admin Panel';
$active_page = 'admin_attendance';

require_once $base_path . 'includes/auth.php';
require_role('admin');
require_once $base_path . 'config/db_connect.php';

// Handle token check-in submission
$attendanceMessage = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['token'])) {
    $token = trim($_POST['token']);

    // Use prepared statement for security
    $stmt = $conn->prepare("SELECT id, attended FROM registrations WHERE token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if ($row['attended'] == 0) {
            $update_stmt = $conn->prepare("UPDATE registrations SET attended = 1 WHERE id = ?");
            $update_stmt->bind_param("i", $row['id']);
            if ($update_stmt->execute()) {
                $attendanceMessage = '<div class="alert alert-success alert-dismissible fade show border-start border-4 border-success shadow-sm" role="alert">
                    <i class="fa-solid fa-circle-check text-success me-2"></i><strong>Success!</strong> Attendance logged for token <code>' . htmlspecialchars($token) . '</code>.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
            } else {
                $attendanceMessage = '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fa-solid fa-xmark me-2"></i>Error updating attendance record.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
            }
        } else {
            $attendanceMessage = '<div class="alert alert-warning alert-dismissible fade show" role="alert">
                <i class="fa-solid fa-triangle-exclamation me-2"></i><strong>Already Checked In!</strong> This token was previously used.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
        }
    } else {
        $attendanceMessage = '<div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fa-solid fa-xmark me-2"></i><strong>Invalid Token!</strong> No registration found for <code>' . htmlspecialchars($token) . '</code>.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
    }
}

// Fetch recent attendance log (last 50 check-ins)
$log_sql = "SELECT r.token, r.attended, r.created_at AS reg_date,
                   u.full_name, u.email,
                   e.title AS event_title, e.event_date
            FROM registrations r
            JOIN users u ON r.user_id = u.id
            JOIN events e ON r.event_id = e.id
            WHERE r.attended = 1
            ORDER BY r.created_at DESC
            LIMIT 50";
$log_result = $conn->query($log_sql);

// Summary stats
$total_checkins = 0;
$today_checkins = 0;
$today_date = date('Y-m-d');
$stat_sql = "SELECT COUNT(*) as total_attended FROM registrations WHERE attended = 1";
$stat_res = $conn->query($stat_sql);
if ($stat_res) $total_checkins = $stat_res->fetch_assoc()['total_attended'];

// Pending check-ins (registered but not attended)
$pending_sql = "SELECT COUNT(*) as pending FROM registrations WHERE attended = 0";
$pending_res = $conn->query($pending_sql);
$pending_checkins = 0;
if ($pending_res) $pending_checkins = $pending_res->fetch_assoc()['pending'];

include $base_path . 'includes/header.php';
include $base_path . 'includes/navbar.php';
?>

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h2 mb-0"><i class="fa-solid fa-clipboard-user text-success me-2"></i>Attendance Desk</h1>
            <p class="text-muted mb-0">Log student check-ins using registration tokens</p>
        </div>
        <a href="dashboard.php" class="btn btn-outline-dark btn-sm"><i class="fa-solid fa-arrow-left me-1"></i>Back to Dashboard</a>
    </div>

    <?php echo $attendanceMessage; ?>

    <!-- Summary Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm rounded-3 text-center p-3 bg-white">
                <div class="display-6 text-success mb-1"><i class="fa-solid fa-user-check"></i></div>
                <h3 class="fw-bold mb-0"><?php echo intval($total_checkins); ?></h3>
                <span class="text-muted small">Total Check-Ins</span>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-0 shadow-sm rounded-3 text-center p-3 bg-white">
                <div class="display-6 text-warning mb-1"><i class="fa-solid fa-hourglass-half"></i></div>
                <h3 class="fw-bold mb-0"><?php echo intval($pending_checkins); ?></h3>
                <span class="text-muted small">Pending (Registered, Not Attended)</span>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Token Check-In Form -->
        <div class="col-lg-4">
            <div class="card shadow-sm border-0 rounded-3 h-100">
                <div class="card-header bg-dark text-white py-3">
                    <h5 class="mb-0"><i class="fa-solid fa-qrcode text-warning me-2"></i>Quick Token Check-In</h5>
                </div>
                <div class="card-body p-4">
                    <form method="POST" action="attendance.php">
                        <div class="mb-3">
                            <label for="token" class="form-label fw-semibold">Student Registration Token</label>
                            <input type="text" class="form-control form-control-lg" id="token" name="token" placeholder="CAMPUS-XXXXXX" required autofocus>
                            <div class="form-text">Enter the token printed on the student's registration slip or QR scan.</div>
                        </div>
                        <button type="submit" class="btn btn-success w-100 fw-bold py-2">
                            <i class="fa-solid fa-check-double me-2"></i>Log Attendance
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Recent Attendance Log -->
        <div class="col-lg-8">
            <div class="card shadow-sm border-0 rounded-3 overflow-hidden h-100">
                <div class="card-header bg-dark text-white py-3">
                    <h5 class="mb-0"><i class="fa-solid fa-clock-rotate-left text-warning me-2"></i>Recent Check-In Log</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped align-middle mb-0">
                            <thead class="table-dark">
                                <tr>
                                    <th>Student Name</th>
                                    <th>Event</th>
                                    <th>Token</th>
                                    <th>Event Date</th>
                                    <th class="text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                if ($log_result && $log_result->num_rows > 0) {
                                    while ($log = $log_result->fetch_assoc()) {
                                        echo '<tr>';
                                        echo '<td class="fw-semibold">' . htmlspecialchars($log['full_name']) . '</td>';
                                        echo '<td>' . htmlspecialchars($log['event_title']) . '</td>';
                                        echo '<td><code>' . htmlspecialchars($log['token']) . '</code></td>';
                                        echo '<td>' . date("M d, Y", strtotime($log['event_date'])) . '</td>';
                                        echo '<td class="text-center"><span class="badge bg-success">Attended</span></td>';
                                        echo '</tr>';
                                    }
                                } else {
                                    echo '<tr><td colspan="5" class="text-center text-muted py-4">No attendance records yet. Use the token form to log check-ins.</td></tr>';
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
