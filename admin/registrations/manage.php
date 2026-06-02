<?php
// admin/registrations/manage.php
// ------------------------------------------------------------
// Admin — Registration Control Desk (Approve, Reject, Cancel)
// ------------------------------------------------------------
$base_path = '../../';
$page_title = 'Registration Management - Admin Panel';
$active_page = 'admin_registrations';

require_once $base_path . 'includes/auth.php';
require_role('admin');
require_once $base_path . 'config/db_connect.php';

$alert_message = '';

// Process admin action submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['ticket_id'])) {
    $action = $_POST['action'];
    $ticket_id = intval($_POST['ticket_id']);
    $admin_id = get_user_id();

    try {
        // Fetch current ticket details for logging and verification
        $chk_stmt = $conn->prepare("SELECT t.ticket_number, t.status, t.registration_status, u.full_name, w.title as ws_title 
                                    FROM tickets t 
                                    JOIN users u ON t.user_id = u.id
                                    JOIN workshops w ON t.event_id = w.id
                                    WHERE t.id = ?");
        $chk_stmt->bind_param("i", $ticket_id);
        $chk_stmt->execute();
        $tk = $chk_stmt->get_result()->fetch_assoc();

        if (!$tk) {
            throw new Exception("Registration file does not exist.");
        }

        if ($tk['status'] === 'Used') {
            throw new Exception("Action denied. This ticket has already been used for attendance check-in.");
        }

        $conn->begin_transaction();

        if ($action === 'approve') {
            if ($tk['status'] === 'Verified') {
                throw new Exception("This registration is already approved/verified.");
            }

            // A. Update status & registration_status
            $upd_stmt = $conn->prepare("UPDATE tickets SET status = 'Verified', registration_status = 'Verified', verified_by = ?, verified_at = NOW() WHERE id = ?");
            $upd_stmt->bind_param("ii", $admin_id, $ticket_id);
            $upd_stmt->execute();

            // B. Log activity log
            $log_details = "Registration approved for student: " . $tk['full_name'] . " in workshop: " . $tk['ws_title'] . ". Ticket: " . $tk['ticket_number'] . ".";
            $log_stmt = $conn->prepare("INSERT INTO activity_logs (user_id, action, details) VALUES (?, 'Verification', ?)");
            $log_stmt->bind_param("is", $admin_id, $log_details);
            $log_stmt->execute();

            $alert_message = '<div class="alert alert-success alert-dismissible fade show border-start border-4 border-success shadow-sm" role="alert">
                <i class="fa-solid fa-circle-check text-success me-2"></i><strong>Approved!</strong> Registration approved and verified for <code>' . htmlspecialchars($tk['ticket_number']) . '</code>.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';

        } elseif ($action === 'reject') {
            // A. Update status & registration_status = Rejected
            $upd_stmt = $conn->prepare("UPDATE tickets SET status = 'Cancelled', registration_status = 'Rejected', verified_by = ?, verified_at = NOW() WHERE id = ?");
            $upd_stmt->bind_param("ii", $admin_id, $ticket_id);
            $upd_stmt->execute();

            // B. Log activity log
            $log_details = "Registration rejected/denied for student: " . $tk['full_name'] . " in workshop: " . $tk['ws_title'] . ". Ticket: " . $tk['ticket_number'] . ".";
            $log_stmt = $conn->prepare("INSERT INTO activity_logs (user_id, action, details) VALUES (?, 'Verification', ?)");
            $log_stmt->bind_param("is", $admin_id, $log_details);
            $log_stmt->execute();

            $alert_message = '<div class="alert alert-danger alert-dismissible fade show border-start border-4 border-danger shadow-sm" role="alert">
                <i class="fa-solid fa-circle-xmark text-danger me-2"></i><strong>Rejected!</strong> Registration rejected for <code>' . htmlspecialchars($tk['ticket_number']) . '</code>.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';

        } elseif ($action === 'cancel') {
            // A. Update status & registration_status = Cancelled
            $upd_stmt = $conn->prepare("UPDATE tickets SET status = 'Cancelled', registration_status = 'Cancelled', verified_by = ?, verified_at = NOW() WHERE id = ?");
            $upd_stmt->bind_param("ii", $admin_id, $ticket_id);
            $upd_stmt->execute();

            // B. Delete certificate and attendance logs if any exist
            $del_cert = $conn->prepare("DELETE FROM workshop_certificates WHERE ticket_id = ?");
            $del_cert->bind_param("i", $ticket_id);
            $del_cert->execute();

            $del_att = $conn->prepare("DELETE FROM attendance WHERE ticket_id = ?");
            $del_att->bind_param("i", $ticket_id);
            $del_att->execute();

            // C. Log activity log
            $log_details = "Registration cancelled for student: " . $tk['full_name'] . " in workshop: " . $tk['ws_title'] . ". Ticket: " . $tk['ticket_number'] . ".";
            $log_stmt = $conn->prepare("INSERT INTO activity_logs (user_id, action, details) VALUES (?, 'Verification', ?)");
            $log_stmt->bind_param("is", $admin_id, $log_details);
            $log_stmt->execute();

            $alert_message = '<div class="alert alert-warning alert-dismissible fade show border-start border-4 border-warning shadow-sm" role="alert">
                <i class="fa-solid fa-circle-minus text-warning me-2"></i><strong>Cancelled!</strong> Registration cancelled and ticket credentials revoked for <code>' . htmlspecialchars($tk['ticket_number']) . '</code>.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
        }

        $conn->commit();
    } catch (Exception $e) {
        $conn->rollback();
        $alert_message = '<div class="alert alert-danger alert-dismissible fade show border-start border-4 border-danger shadow-sm" role="alert">
            <i class="fa-solid fa-triangle-exclamation me-2"></i><strong>Error:</strong> ' . htmlspecialchars($e->getMessage()) . '
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
    }
}

// Fetch all registrations
$reg_sql = "SELECT t.id, t.ticket_number, t.registration_status, t.created_at,
                   u.full_name, u.email,
                   w.title as ws_title
            FROM tickets t
            JOIN users u ON t.user_id = u.id
            JOIN workshops w ON t.event_id = w.id
            ORDER BY t.created_at DESC";
$reg_res = $conn->query($reg_sql);
?>

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h2 mb-0"><i class="fa-solid fa-graduation-cap text-primary me-2"></i>Registration Control Desk</h1>
            <p class="text-muted mb-0">Approve, reject, cancel, or audit workshop participant enrollment files.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="../tickets/manage.php" class="btn btn-outline-primary btn-sm fw-bold"><i class="fa-solid fa-ticket me-1"></i>Tickets Desk</a>
            <a href="../dashboard.php" class="btn btn-outline-dark btn-sm"><i class="fa-solid fa-arrow-left me-1"></i>Back to Dashboard</a>
        </div>
    </div>

    <?php echo $alert_message; ?>

    <div class="card shadow-sm border-0 rounded-3 overflow-hidden bg-white">
        <div class="card-header bg-dark text-white py-3 d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fa-solid fa-table-list text-warning me-2"></i>Enrollment Records Journal</h5>
            <input type="text" id="regTableSearch" class="form-control form-control-sm w-50" placeholder="Type participant, ticket code, or workshop to filter...">
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="registrationsJournalTable">
                    <thead class="table-dark">
                        <tr>
                            <th>Participant</th>
                            <th>Workshop</th>
                            <th>Ticket</th>
                            <th class="text-center">Status</th>
                            <th class="text-center">Lifecycle Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($reg_res && $reg_res->num_rows > 0): ?>
                            <?php while ($row = $reg_res->fetch_assoc()): ?>
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
                                        <div class="fw-bold text-dark"><?php echo htmlspecialchars($row['full_name']); ?></div>
                                        <div class="text-muted small"><?php echo htmlspecialchars($row['email']); ?></div>
                                    </td>
                                    <td><div class="fw-semibold text-dark small" style="max-width: 250px;"><?php echo htmlspecialchars($row['ws_title']); ?></div></td>
                                    <td><code class="font-monospace fw-bold text-primary"><?php echo htmlspecialchars($row['ticket_number']); ?></code></td>
                                    <td class="text-center">
                                        <span class="badge <?php echo $badge_class; ?> px-2.5 py-1 text-uppercase fs-9"><?php echo $disp_status; ?></span>
                                    </td>
                                    <td class="text-center">
                                        <div class="d-inline-flex gap-1.5 align-items-center">
                                            <?php if ($row['registration_status'] === 'Pending'): ?>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="action" value="approve">
                                                    <input type="hidden" name="ticket_id" value="<?php echo $row['id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-success px-2.5 fw-bold" title="Approve">
                                                        <i class="fa-solid fa-check"></i> Approve
                                                    </button>
                                                </form>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="action" value="reject">
                                                    <input type="hidden" name="ticket_id" value="<?php echo $row['id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-danger px-2.5 fw-bold" title="Reject" onclick="return confirm('Reject this registration?');">
                                                        <i class="fa-solid fa-xmark"></i> Reject
                                                    </button>
                                                </form>
                                            <?php elseif ($row['registration_status'] === 'Verified'): ?>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="action" value="cancel">
                                                    <input type="hidden" name="ticket_id" value="<?php echo $row['id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-dark px-2.5 fw-bold" title="Cancel" onclick="return confirm('Cancel this registration?');">
                                                        <i class="fa-solid fa-ban"></i> Cancel
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <span class="text-muted small">Closed / Handled</span>
                                            <?php endif; ?>
                                            
                                            <a href="../tickets/view.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-dark px-2.5" title="View Dossier">
                                                <i class="fa-solid fa-eye"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="5" class="text-center text-muted py-5">No registrations found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    // Live table search javascript filter
    document.getElementById('regTableSearch').addEventListener('keyup', function() {
        let filter = this.value.toLowerCase();
        let rows = document.querySelectorAll('#registrationsJournalTable tbody tr');
        
        rows.forEach(row => {
            let text = row.textContent.toLowerCase();
            if (text.includes(filter)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });
</script>

<style>
.fs-9 {
    font-size: 11px !important;
}
</style>

<?php
include $base_path . 'includes/footer.php';
?>
