<?php
// admin/tickets/view.php
// ------------------------------------------------------------
// Admin — Ticket Information & Action Desk
// ------------------------------------------------------------
$base_path = '../../';
$page_title = 'Ticket File - Admin Panel';
$active_page = 'admin_tickets';

require_once $base_path . 'includes/auth.php';
require_role('admin');
require_once $base_path . 'config/db_connect.php';

$ticket_id = intval($_GET['id'] ?? 0);
$alert_message = '';

if ($ticket_id <= 0) {
    header("Location: manage.php");
    exit;
}

// Handle action submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $admin_id = get_user_id();

    try {
        // 1. Fetch current ticket details for validation
        $check_stmt = $conn->prepare("SELECT t.ticket_number, t.status, t.event_id FROM tickets t WHERE t.id = ?");
        $check_stmt->bind_param("i", $ticket_id);
        $check_stmt->execute();
        $tk = $check_stmt->get_result()->fetch_assoc();

        if (!$tk) {
            throw new Exception("The selected ticket does not exist.");
        }

        // Business rules check
        if ($tk['status'] === 'Used') {
            throw new Exception("Action denied. This ticket has already been used to check-in for attendance.");
        }

        // Verify event (workshop) exists
        $ws_stmt = $conn->prepare("SELECT id FROM workshops WHERE id = ?");
        $ws_stmt->bind_param("i", $tk['event_id']);
        $ws_stmt->execute();
        if ($ws_stmt->get_result()->num_rows === 0) {
            throw new Exception("Associated workshop does not exist.");
        }

        if ($action === 'verify') {
            if ($tk['status'] === 'Verified') {
                throw new Exception("This ticket has already been verified.");
            }

            // Update status = Verified, verified_by, verified_at, registration_status = Verified
            $upd_stmt = $conn->prepare("UPDATE tickets SET status = 'Verified', registration_status = 'Verified', verified_by = ?, verified_at = NOW() WHERE id = ?");
            $upd_stmt->bind_param("ii", $admin_id, $ticket_id);
            if ($upd_stmt->execute()) {
                $alert_message = '<div class="alert alert-success alert-dismissible fade show border-start border-4 border-success shadow-sm" role="alert">
                    <i class="fa-solid fa-circle-check me-2"></i><strong>Success!</strong> Ticket <code>' . htmlspecialchars($tk['ticket_number']) . '</code> has been marked as <strong>Verified</strong>.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
            } else {
                throw new Exception("Database error updating ticket.");
            }
        } elseif ($action === 'reject') {
            // Update status = Cancelled, registration_status = Closed
            $upd_stmt = $conn->prepare("UPDATE tickets SET status = 'Cancelled', registration_status = 'Closed', verified_by = ?, verified_at = NOW() WHERE id = ?");
            $upd_stmt->bind_param("ii", $admin_id, $ticket_id);
            if ($upd_stmt->execute()) {
                // If there's an attendance record, delete it
                $del_att = $conn->prepare("DELETE FROM attendance WHERE ticket_id = ?");
                $del_att->bind_param("i", $ticket_id);
                $del_att->execute();

                // If there's a certificate, delete it
                $del_cert = $conn->prepare("DELETE FROM certificates WHERE registration_id = ?");
                $del_cert->bind_param("i", $ticket_id);
                $del_cert->execute();

                $alert_message = '<div class="alert alert-warning alert-dismissible fade show border-start border-4 border-warning shadow-sm" role="alert">
                    <i class="fa-solid fa-circle-minus me-2"></i><strong>Cancelled!</strong> Ticket <code>' . htmlspecialchars($tk['ticket_number']) . '</code> has been marked as <strong>Cancelled/Rejected</strong>.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
            } else {
                throw new Exception("Database error cancelling ticket.");
            }
        }
    } catch (Exception $e) {
        $alert_message = '<div class="alert alert-danger alert-dismissible fade show border-start border-4 border-danger shadow-sm" role="alert">
            <i class="fa-solid fa-triangle-exclamation me-2"></i><strong>Error:</strong> ' . htmlspecialchars($e->getMessage()) . '
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
    }
}

// Retrieve ticket details
$stmt = $conn->prepare("SELECT t.id, t.ticket_number, t.qr_code_path, t.status, t.created_at, t.verified_at,
                               w.title as ws_title, w.domain, w.host, w.speaker, w.venue, w.start_date,
                               u.full_name, u.email,
                               adm.full_name as admin_name
                        FROM tickets t
                        JOIN users u ON t.user_id = u.id
                        JOIN workshops w ON t.event_id = w.id
                        LEFT JOIN users adm ON t.verified_by = adm.id
                        WHERE t.id = ?");
$stmt->bind_param("i", $ticket_id);
$stmt->execute();
$ticket = $stmt->get_result()->fetch_assoc();

if (!$ticket) {
    header("Location: manage.php");
    exit;
}

include $base_path . 'includes/header.php';
include $base_path . 'includes/navbar.php';
?>

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h2 mb-0"><i class="fa-solid fa-file-invoice text-primary me-2"></i>Ticket Dossier</h1>
            <p class="text-muted mb-0">Audit specific student registration file and verify authentication states</p>
        </div>
        <a href="manage.php" class="btn btn-outline-dark btn-sm"><i class="fa-solid fa-arrow-left me-1"></i>Back to List</a>
    </div>

    <?php echo $alert_message; ?>

    <div class="row g-4">
        <!-- Student & Ticket File -->
        <div class="col-lg-8">
            <div class="card shadow-sm border-0 rounded-3 p-4 bg-white mb-4">
                <h4 class="fw-bold mb-4 border-bottom pb-2 text-dark"><i class="fa-solid fa-user-graduate text-secondary me-2"></i>Participant File</h4>
                <div class="row g-3">
                    <div class="col-md-6">
                        <span class="text-muted small d-block">Full Name</span>
                        <span class="fw-bold text-dark fs-5"><?php echo htmlspecialchars($ticket['full_name']); ?></span>
                    </div>
                    <div class="col-md-6">
                        <span class="text-muted small d-block">Email Address</span>
                        <span class="fw-semibold text-dark fs-5"><?php echo htmlspecialchars($ticket['email']); ?></span>
                    </div>
                    <div class="col-md-6">
                        <span class="text-muted small d-block">Registration Date</span>
                        <span class="text-dark fw-semibold"><?php echo date("F d, Y h:i A", strtotime($ticket['created_at'])); ?></span>
                    </div>
                    <div class="col-md-6">
                        <span class="text-muted small d-block">Current Status</span>
                        <?php
                        $badge_class = 'bg-secondary';
                        $disp_status = htmlspecialchars($ticket['status']);
                        if ($ticket['status'] === 'Pending') {
                            $badge_class = 'bg-warning text-dark';
                            $disp_status = 'Pending Verification';
                        } elseif ($ticket['status'] === 'Verified') {
                            $badge_class = 'bg-info text-dark';
                            $disp_status = 'Verified';
                        } elseif ($ticket['status'] === 'Used') {
                            $badge_class = 'bg-success';
                            $disp_status = 'Used (Attended)';
                        } elseif ($ticket['status'] === 'Cancelled') {
                            $badge_class = 'bg-danger';
                            $disp_status = 'Cancelled (Absent)';
                        }
                        ?>
                        <span class="badge <?php echo $badge_class; ?> px-3 py-1.5 fs-7 text-uppercase"><?php echo $disp_status; ?></span>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm border-0 rounded-3 p-4 bg-white">
                <h4 class="fw-bold mb-4 border-bottom pb-2 text-dark"><i class="fa-solid fa-chalkboard-user text-secondary me-2"></i>Workshop Details</h4>
                <div class="row g-3">
                    <div class="col-12">
                        <span class="text-muted small d-block">Workshop Title</span>
                        <span class="fw-bold text-primary fs-5"><?php echo htmlspecialchars($ticket['ws_title']); ?></span>
                    </div>
                    <div class="col-md-6">
                        <span class="text-muted small d-block">Domain Class</span>
                        <span class="badge bg-secondary text-uppercase"><?php echo htmlspecialchars($ticket['domain']); ?></span>
                    </div>
                    <div class="col-md-6">
                        <span class="text-muted small d-block">Keynote Speaker</span>
                        <span class="fw-semibold text-dark"><?php echo htmlspecialchars($ticket['speaker']); ?></span>
                    </div>
                    <div class="col-md-6">
                        <span class="text-muted small d-block">Host Department</span>
                        <span class="fw-semibold text-dark"><?php echo htmlspecialchars($ticket['host']); ?></span>
                    </div>
                    <div class="col-md-6">
                        <span class="text-muted small d-block">Venue Location</span>
                        <span class="fw-semibold text-danger"><i class="fa-solid fa-location-dot me-1"></i><?php echo htmlspecialchars($ticket['venue']); ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Sidebar: Ticket Details & Verification Panel -->
        <div class="col-lg-4">
            <div class="card shadow-sm border-0 rounded-3 p-4 bg-white mb-4 text-center">
                <h4 class="fw-bold mb-3 border-bottom pb-2 text-dark text-start"><i class="fa-solid fa-ticket-simple text-secondary me-2"></i>Verification File</h4>
                
                <div class="text-center mb-3 bg-light p-3 border rounded shadow-sm d-inline-block" style="width: 180px; height: 180px;">
                    <?php if (!empty($ticket['qr_code_path']) && file_exists($base_path . $ticket['qr_code_path'])): ?>
                        <img src="<?php echo $base_path . htmlspecialchars($ticket['qr_code_path']); ?>" alt="QR" class="img-fluid h-100" style="object-fit: contain;">
                    <?php else: ?>
                        <div class="d-flex align-items-center justify-content-center h-100 text-muted small">No QR Generated</div>
                    <?php endif; ?>
                </div>

                <div class="mb-3 text-start bg-light p-3 rounded border">
                    <div class="mb-2">
                        <span class="text-muted small d-block">Serial Number:</span>
                        <span class="font-monospace fw-bold text-dark fs-5"><?php echo htmlspecialchars($ticket['ticket_number']); ?></span>
                    </div>
                    <?php if ($ticket['verified_by']): ?>
                        <div class="mb-2">
                            <span class="text-muted small d-block">Verified By:</span>
                            <span class="fw-semibold text-dark"><?php echo htmlspecialchars($ticket['admin_name']); ?></span>
                        </div>
                        <div>
                            <span class="text-muted small d-block">Verified At:</span>
                            <span class="text-dark small"><?php echo date("M d, Y h:i A", strtotime($ticket['verified_at'])); ?></span>
                        </div>
                    <?php else: ?>
                        <div class="text-warning small"><i class="fa-solid fa-triangle-exclamation me-1"></i>Awaiting administrator verification</div>
                    <?php endif; ?>
                </div>

                <?php if ($ticket['status'] !== 'Used'): ?>
                    <div class="d-grid gap-2">
                        <?php if ($ticket['status'] !== 'Verified'): ?>
                            <form method="POST">
                                <input type="hidden" name="action" value="verify">
                                <button type="submit" class="btn btn-success w-100 fw-bold py-2 shadow-sm">
                                    <i class="fa-solid fa-circle-check me-2"></i>Verify Registration
                                </button>
                            </form>
                        <?php endif; ?>
                        <form method="POST">
                            <input type="hidden" name="action" value="reject">
                            <button type="submit" class="btn btn-danger w-100 fw-bold py-2 shadow-sm" onclick="return confirm('Cancel and reject this ticket registration?');">
                                <i class="fa-solid fa-circle-xmark me-2"></i>Cancel / Reject Ticket
                            </button>
                        </form>
                    </div>
                <?php else: ?>
                    <div class="alert alert-success small mb-0"><i class="fa-solid fa-circle-check me-1"></i>Ticket successfully utilized for check-in. Attendance logged.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
include $base_path . 'includes/footer.php';
?>
