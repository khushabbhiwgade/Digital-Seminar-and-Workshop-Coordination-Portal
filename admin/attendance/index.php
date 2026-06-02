<?php
// admin/attendance/index.php
// ------------------------------------------------------------
// Admin — Attendance Journal (Verified Tickets Check-In)
// ------------------------------------------------------------
$base_path = '../../';
$page_title = 'Mark Attendance - Admin Panel';
$active_page = 'admin_attendance';

require_once $base_path . 'includes/auth.php';
require_role('admin');
require_once $base_path . 'config/db_connect.php';
require_once $base_path . 'includes/certificate_helper.php';

$alert_message = '';

// Handle mark attendance POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['ticket_id'])) {
    $action = $_POST['action'];
    $ticket_id = intval($_POST['ticket_id']);
    $admin_id = get_user_id();

    try {
        $conn->begin_transaction();

        // 1. Fetch ticket details to verify it exists and is Verified
        $stmt = $conn->prepare("SELECT t.id, t.ticket_number, t.status, t.registration_status, t.user_id, t.event_id, 
                                       u.full_name, w.title as ws_title 
                                FROM tickets t 
                                JOIN users u ON t.user_id = u.id
                                JOIN workshops w ON t.event_id = w.id
                                WHERE t.id = ?");
        $stmt->bind_param("i", $ticket_id);
        $stmt->execute();
        $tk = $stmt->get_result()->fetch_assoc();

        if (!$tk) {
            throw new Exception("Validation failed: Ticket registration file does not exist.");
        }

        // Completion Validation Checks (Part 9 & Part 10)
        // Check 1: Ticket must be Verified (Approve lifecycle state)
        if ($tk['registration_status'] !== 'Verified') {
            throw new Exception("Validation failed: Only verified tickets can be checked in. Current state: " . $tk['registration_status']);
        }

        $user_id = $tk['user_id'];
        $event_id = $tk['event_id'];

        // Check 2: Participant Exists in users table
        $user_chk = $conn->prepare("SELECT id FROM users WHERE id = ?");
        $user_chk->bind_param("i", $user_id);
        $user_chk->execute();
        if ($user_chk->get_result()->num_rows === 0) {
            throw new Exception("Validation failed: Registered participant no longer exists in the system.");
        }

        // Check 3: Workshop Exists in workshops table
        $ws_chk = $conn->prepare("SELECT id FROM workshops WHERE id = ?");
        $ws_chk->bind_param("i", $event_id);
        $ws_chk->execute();
        if ($ws_chk->get_result()->num_rows === 0) {
            throw new Exception("Validation failed: Associated workshop no longer exists.");
        }

        // Check 4: Prevent duplicate attendance logs
        $check_att = $conn->prepare("SELECT id FROM attendance WHERE ticket_id = ?");
        $check_att->bind_param("i", $ticket_id);
        $check_att->execute();
        if ($check_att->get_result()->num_rows > 0) {
            throw new Exception("Duplicate Error: Attendance has already been logged for this registration.");
        }

        if ($action === 'present') {
            // A. Create Present attendance record
            $ins_att = $conn->prepare("INSERT INTO attendance (ticket_id, user_id, event_id, attendance_status, marked_by) VALUES (?, ?, ?, 'Present', ?)");
            $ins_att->bind_param("iiii", $ticket_id, $user_id, $event_id, $admin_id);
            $ins_att->execute();

            // B. Update ticket: status = 'Used', registration_status = 'Completed'
            $upd_tk = $conn->prepare("UPDATE tickets SET status = 'Used', registration_status = 'Completed' WHERE id = ?");
            $upd_tk->bind_param("i", $ticket_id);
            $upd_tk->execute();

            // C. Generate and save certificate (Certificate Eligibility check: Present + Completed)
            $cert_res = generate_certificate($ticket_id, $admin_id);
            if (!$cert_res['success']) {
                throw new Exception("Certificate Generation Failed: " . $cert_res['message']);
            }

            // D. Log activity in activity_logs
            $log_details = "Attendance marked PRESENT for student: " . $tk['full_name'] . " in workshop: " . $tk['ws_title'] . ". Ticket: " . $tk['ticket_number'] . ". Certificate generated automatically.";
            $log_stmt = $conn->prepare("INSERT INTO activity_logs (user_id, action, details) VALUES (?, 'Attendance', ?)");
            $log_stmt->bind_param("is", $admin_id, $log_details);
            $log_stmt->execute();

            $log_cert_details = "Certificate issued for " . $tk['full_name'] . " on workshop " . $tk['ws_title'] . ". Code: " . $cert_code;
            $log_cert_stmt = $conn->prepare("INSERT INTO activity_logs (user_id, action, details) VALUES (?, 'Certificate Generation', ?)");
            $log_cert_stmt->bind_param("is", $admin_id, $log_cert_details);
            $log_cert_stmt->execute();

            $alert_message = '<div class="alert alert-success alert-dismissible fade show border-start border-4 border-success shadow-sm" role="alert">
                <i class="fa-solid fa-circle-check text-success me-2"></i><strong>Check-In Successful!</strong> Participant marked as <strong>Present</strong>. Workshop certificate generated.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';

        } elseif ($action === 'absent') {
            // A. Create Absent attendance record
            $ins_att = $conn->prepare("INSERT INTO attendance (ticket_id, user_id, event_id, attendance_status, marked_by) VALUES (?, ?, ?, 'Absent', ?)");
            $ins_att->bind_param("iiii", $ticket_id, $user_id, $event_id, $admin_id);
            $ins_att->execute();

            // B. Update ticket: status = 'Cancelled', registration_status = 'Absent'
            $upd_tk = $conn->prepare("UPDATE tickets SET status = 'Cancelled', registration_status = 'Absent' WHERE id = ?");
            $upd_tk->bind_param("i", $ticket_id);
            $upd_tk->execute();

            // C. Log activity in activity_logs
            $log_details = "Attendance marked ABSENT for student: " . $tk['full_name'] . " in workshop: " . $tk['ws_title'] . ". Ticket: " . $tk['ticket_number'] . ". State set to Closed/Absent.";
            $log_stmt = $conn->prepare("INSERT INTO activity_logs (user_id, action, details) VALUES (?, 'Attendance', ?)");
            $log_stmt->bind_param("is", $admin_id, $log_details);
            $log_stmt->execute();

            // Note: Do NOT generate certificate for absent attendees!

            $alert_message = '<div class="alert alert-warning alert-dismissible fade show border-start border-4 border-warning shadow-sm" role="alert">
                <i class="fa-solid fa-circle-xmark text-warning me-2"></i><strong>Check-In logged!</strong> Participant marked as <strong>Absent</strong>. Ticket cancelled/closed without certificate.
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

// Fetch only Verified tickets (awaiting check-in)
$verified_sql = "SELECT t.id, t.ticket_number, t.qr_code_path, t.status, t.created_at,
                        u.full_name, u.email,
                        w.title as ws_title, w.venue
                 FROM tickets t
                 JOIN users u ON t.user_id = u.id
                 JOIN workshops w ON t.event_id = w.id
                 WHERE t.registration_status = 'Verified'
                 ORDER BY t.created_at DESC";
$verified_res = $conn->query($verified_sql);
?>

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h2 mb-0"><i class="fa-solid fa-clipboard-user text-primary me-2"></i>Attendance Desk</h1>
            <p class="text-muted mb-0">Record present or absent attendance check-ins for verified participant tickets.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="../registrations/manage.php" class="btn btn-outline-primary btn-sm fw-bold"><i class="fa-solid fa-list-check me-1"></i>Registrations Desk</a>
            <a href="../tickets/manage.php" class="btn btn-outline-primary btn-sm fw-bold"><i class="fa-solid fa-ticket me-1"></i>Tickets Desk</a>
            <a href="../dashboard.php" class="btn btn-outline-dark btn-sm"><i class="fa-solid fa-arrow-left me-1"></i>Back to Dashboard</a>
        </div>
    </div>

    <?php echo $alert_message; ?>

    <div class="card shadow-sm border-0 rounded-3 overflow-hidden bg-white mb-5">
        <div class="card-header bg-primary text-white py-3 d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fa-solid fa-table-list text-warning me-2"></i>Verified Registrations Journal</h5>
            <input type="text" id="attendanceTableSearch" class="form-control form-control-sm w-50" placeholder="Type name, ticket number, or workshop to filter...">
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="verifiedTicketsTable">
                    <thead class="table-dark">
                        <tr>
                            <th>Participant</th>
                            <th>Ticket Code</th>
                            <th>Workshop & Venue</th>
                            <th class="text-center">Verification status</th>
                            <th class="text-center">Log Check-In</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($verified_res && $verified_res->num_rows > 0): ?>
                            <?php while ($tk = $verified_res->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold text-dark"><?php echo htmlspecialchars($tk['full_name']); ?></div>
                                        <div class="text-muted small"><?php echo htmlspecialchars($tk['email']); ?></div>
                                    </td>
                                    <td><code class="font-monospace fw-bold text-primary"><?php echo htmlspecialchars($tk['ticket_number']); ?></code></td>
                                    <td>
                                        <div class="text-dark fw-semibold small"><?php echo htmlspecialchars($tk['ws_title']); ?></div>
                                        <div class="text-muted small"><i class="fa-solid fa-location-dot me-1 text-danger"></i><?php echo htmlspecialchars($tk['venue']); ?></div>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-info text-dark px-2.5 py-1 text-uppercase fs-9"><i class="fa-solid fa-circle-check me-1"></i>Verified</span>
                                    </td>
                                    <td class="text-center">
                                        <div class="d-inline-flex gap-2">
                                            <form method="POST">
                                                <input type="hidden" name="action" value="present">
                                                <input type="hidden" name="ticket_id" value="<?php echo $tk['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-success px-3 fw-bold shadow-sm">
                                                    <i class="fa-solid fa-check me-1"></i>Present
                                                </button>
                                            </form>
                                            <form method="POST">
                                                <input type="hidden" name="action" value="absent">
                                                <input type="hidden" name="ticket_id" value="<?php echo $tk['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-danger px-3 fw-bold shadow-sm" onclick="return confirm('Mark this participant as ABSENT? This will cancel their ticket.');">
                                                    <i class="fa-solid fa-xmark me-1"></i>Absent
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted py-5">
                                    <i class="fa-solid fa-clipboard-question fs-2 d-block mb-2 text-secondary"></i>
                                    No verified registrations currently awaiting check-in. Go to the Registrations Desk to approve registrations first.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    // Live table search javascript filter
    document.getElementById('attendanceTableSearch').addEventListener('keyup', function() {
        let filter = this.value.toLowerCase();
        let rows = document.querySelectorAll('#verifiedTicketsTable tbody tr');
        
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
