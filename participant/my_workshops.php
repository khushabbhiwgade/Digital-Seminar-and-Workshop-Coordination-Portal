<?php
// participant/my_workshops.php
// ------------------------------------------------------------
// Participant — My Workshops Logs
// ------------------------------------------------------------
$base_path = '../';
$page_title = 'My Workshops - Campus Connect';
$active_page = 'participant_workshops';

require_once $base_path . 'includes/auth.php';
require_role('student');
require_once $base_path . 'config/db_connect.php';

$user_id = get_user_id();
$filter = $_GET['filter'] ?? 'All';

// Build query
$sql = "SELECT t.id, t.ticket_number, t.registration_status,
               w.title as ws_title, w.host, w.start_date, w.venue,
               a.attendance_status
        FROM tickets t
        JOIN workshops w ON t.event_id = w.id
        LEFT JOIN attendance a ON t.id = a.ticket_id
        WHERE t.user_id = ?";

$params = [$user_id];
$types = 'i';

if ($filter === 'Active') {
    $sql .= " AND t.registration_status IN ('Pending', 'Verified')";
} elseif ($filter === 'Completed') {
    $sql .= " AND t.registration_status = 'Completed'";
} elseif ($filter === 'Rejected') {
    $sql .= " AND t.registration_status IN ('Rejected', 'Absent', 'Cancelled')";
}

$sql .= " ORDER BY w.start_date DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$workshops_res = $stmt->get_result();

include $base_path . 'includes/header.php';
include $base_path . 'includes/navbar.php';
?>

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h2 mb-0"><i class="fa-solid fa-list-check text-primary me-2"></i>My Workshop Registrations</h1>
            <p class="text-muted mb-0">Track verification status, attendance logs, and completed workshop certificates</p>
        </div>
        <a href="dashboard.php" class="btn btn-outline-dark btn-sm"><i class="fa-solid fa-arrow-left me-1"></i>Back to Dashboard</a>
    </div>

    <!-- Filters Panel -->
    <div class="d-flex gap-2 mb-4">
        <a href="?filter=All" class="btn btn-sm px-3 rounded-pill <?php echo ($filter === 'All') ? 'btn-primary' : 'btn-light border'; ?>">All Workshops</a>
        <a href="?filter=Active" class="btn btn-sm px-3 rounded-pill <?php echo ($filter === 'Active') ? 'btn-info text-dark' : 'btn-light border'; ?>">Active (Pending/Verified)</a>
        <a href="?filter=Completed" class="btn btn-sm px-3 rounded-pill <?php echo ($filter === 'Completed') ? 'btn-success' : 'btn-light border'; ?>">Completed</a>
        <a href="?filter=Rejected" class="btn btn-sm px-3 rounded-pill <?php echo ($filter === 'Rejected') ? 'btn-danger' : 'btn-light border'; ?>">Rejected & Absent</a>
    </div>

    <!-- Table Journal -->
    <div class="card shadow-sm border-0 rounded-3 overflow-hidden bg-white">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>Workshop & Host</th>
                        <th>Ticket Code</th>
                        <th>Date & Session</th>
                        <th class="text-center">Registration Status</th>
                        <th class="text-center">Attendance Log</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($workshops_res && $workshops_res->num_rows > 0): ?>
                        <?php while ($row = $workshops_res->fetch_assoc()): ?>
                            <?php
                            $badge_class = 'bg-secondary';
                            $disp_status = htmlspecialchars($row['registration_status']);
                            if ($row['registration_status'] === 'Pending') {
                                $badge_class = 'bg-warning text-dark';
                                $disp_status = 'Pending Verification';
                            } elseif ($row['registration_status'] === 'Verified') {
                                $badge_class = 'bg-info text-dark';
                                $disp_status = 'Verified';
                            } elseif ($row['registration_status'] === 'Completed') {
                                $badge_class = 'bg-success';
                            } elseif ($row['registration_status'] === 'Absent') {
                                $badge_class = 'bg-danger';
                            } elseif ($row['registration_status'] === 'Cancelled') {
                                $badge_class = 'bg-dark';
                            } elseif ($row['registration_status'] === 'Rejected') {
                                $badge_class = 'bg-danger';
                            }
                            ?>
                            <tr>
                                <td>
                                    <div class="fw-bold text-dark"><?php echo htmlspecialchars($row['ws_title']); ?></div>
                                    <div class="text-muted small">Host: <?php echo htmlspecialchars($row['host']); ?></div>
                                </td>
                                <td>
                                    <code class="font-monospace fw-bold text-primary"><?php echo htmlspecialchars($row['ticket_number']); ?></code>
                                </td>
                                <td>
                                    <div class="small text-dark"><i class="fa-solid fa-calendar me-1"></i><?php echo date('M d, Y', strtotime($row['start_date'])); ?></div>
                                    <div class="small text-muted"><i class="fa-solid fa-location-dot me-1 text-danger"></i><?php echo htmlspecialchars($row['venue']); ?></div>
                                </td>
                                <td class="text-center">
                                    <span class="badge <?php echo $badge_class; ?> px-2.5 py-1 text-uppercase fs-9"><?php echo $disp_status; ?></span>
                                </td>
                                <td class="text-center">
                                    <?php if ($row['attendance_status'] === 'Present'): ?>
                                        <span class="badge bg-success px-2 py-1"><i class="fa-solid fa-check me-1"></i>Present</span>
                                    <?php elseif ($row['attendance_status'] === 'Absent'): ?>
                                        <span class="badge bg-danger px-2 py-1"><i class="fa-solid fa-xmark me-1"></i>Absent</span>
                                    <?php else: ?>
                                        <span class="text-muted small">Not Marked Yet</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted py-5">
                                <i class="fa-solid fa-graduation-cap fs-2 d-block mb-2 text-secondary"></i>
                                No workshop registrations found matching this filter category.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
.fs-9 {
    font-size: 11px !important;
}
</style>

<?php
include $base_path . 'includes/footer.php';
?>
