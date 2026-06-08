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
            $del_cert = $conn->prepare("DELETE FROM certificates WHERE registration_id = ?");
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

// Fetch all registrations with additional details for the Dossier modal
$reg_sql = "SELECT t.id, t.ticket_number, t.registration_status, t.created_at, t.verified_at,
                   u.full_name, u.email, u.mobile, u.college, u.department, u.year_of_study,
                   w.title as ws_title, w.venue, w.domain, w.speaker, w.host,
                   adm.full_name as admin_name
            FROM tickets t
            JOIN users u ON t.user_id = u.id
            JOIN workshops w ON t.event_id = w.id
            LEFT JOIN users adm ON t.verified_by = adm.id
            ORDER BY t.created_at DESC";
$reg_res = $conn->query($reg_sql);

// Calculate registration stats
$cnt_total = 0;
$cnt_today = 0;
$cnt_attended = 0;
$cnt_pending_att = 0;

$res_total = $conn->query("SELECT COUNT(*) as cnt FROM tickets");
if ($res_total) $cnt_total = intval($res_total->fetch_assoc()['cnt']);

$res_today = $conn->query("SELECT COUNT(*) as cnt FROM tickets WHERE DATE(created_at) = CURDATE()");
if ($res_today) $cnt_today = intval($res_today->fetch_assoc()['cnt']);

$res_attended = $conn->query("SELECT COUNT(*) as cnt FROM tickets WHERE registration_status = 'Completed'");
if ($res_attended) $cnt_attended = intval($res_attended->fetch_assoc()['cnt']);

$res_pending_att = $conn->query("SELECT COUNT(*) as cnt FROM tickets WHERE registration_status = 'Verified'");
if ($res_pending_att) $cnt_pending_att = intval($res_pending_att->fetch_assoc()['cnt']);

include $base_path . 'includes/header.php';
include $base_path . 'includes/navbar.php';
?>

<div class="container my-5">
    <!-- Header Section -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
        <div>
            <h1 class="h2 mb-0 fw-bold"><i class="fa-solid fa-graduation-cap text-primary me-2"></i>Registration Control Desk</h1>
            <p class="text-muted mb-0">Approve, reject, cancel, or audit workshop participant enrollment files.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="../tickets/manage.php" class="btn btn-outline-primary btn-sm fw-bold"><i class="fa-solid fa-ticket me-1"></i>Tickets Desk</a>
            <a href="../dashboard.php" class="btn btn-outline-dark btn-sm"><i class="fa-solid fa-arrow-left me-1"></i>Back to Dashboard</a>
        </div>
    </div>

    <?php echo $alert_message; ?>

    <!-- Registrations Statistics Cards -->
    <div class="row g-4 mb-4">
        <div class="col-md-3 col-sm-6">
            <div class="card h-100 border-0 shadow-sm rounded-3 p-3 bg-white text-center border-start border-4 border-primary">
                <div class="display-6 text-primary mb-2"><i class="fa-solid fa-users"></i></div>
                <h3 class="fw-bold mb-1"><?php echo $cnt_total; ?></h3>
                <span class="text-muted small fw-semibold">Total Registrations</span>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card h-100 border-0 shadow-sm rounded-3 p-3 bg-white text-center border-start border-4 border-success">
                <div class="display-6 text-success mb-2"><i class="fa-solid fa-user-plus"></i></div>
                <h3 class="fw-bold mb-1"><?php echo $cnt_today; ?></h3>
                <span class="text-muted small fw-semibold">New Registrations Today</span>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card h-100 border-0 shadow-sm rounded-3 p-3 bg-white text-center border-start border-4 border-info">
                <div class="display-6 text-info mb-2"><i class="fa-solid fa-user-check"></i></div>
                <h3 class="fw-bold mb-1"><?php echo $cnt_attended; ?></h3>
                <span class="text-muted small fw-semibold">Attended Participants</span>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card h-100 border-0 shadow-sm rounded-3 p-3 bg-white text-center border-start border-4 border-warning">
                <div class="display-6 text-warning mb-2"><i class="fa-solid fa-hourglass-half"></i></div>
                <h3 class="fw-bold mb-1"><?php echo $cnt_pending_att; ?></h3>
                <span class="text-muted small fw-semibold">Pending Attendance</span>
            </div>
        </div>
    </div>

    <!-- Search & Filters -->
    <div class="card shadow-sm border-0 rounded-3 bg-white mb-4">
        <div class="card-body p-3">
            <div class="row g-3 align-items-center">
                <!-- Search Box -->
                <div class="col-md-8">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light text-muted border-end-0"><i class="fa-solid fa-magnifying-glass"></i></span>
                        <input type="text" id="searchQuery" class="form-control border-start-0 ps-0" placeholder="Search by participant name, email, event, or token...">
                    </div>
                </div>
                <!-- Status Filter -->
                <div class="col-md-4">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light text-muted"><i class="fa-solid fa-filter"></i></span>
                        <select id="filterStatus" class="form-select">
                            <option value="">All Statuses</option>
                            <option value="Pending">Registered (Pending Verification)</option>
                            <option value="Verified">Attended (Approved / Verified)</option>
                            <option value="Completed">Completed</option>
                            <option value="Cancelled">Cancelled</option>
                            <option value="Absent">Absent</option>
                            <option value="Rejected">Rejected</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Registrations Table Card -->
    <div class="card shadow-sm border-0 rounded-3 overflow-hidden bg-white mb-5">
        <div class="card-header bg-dark text-white py-3 d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fa-solid fa-table-list text-warning me-2"></i>Enrollment Records Journal</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="registrationsJournalTable">
                    <thead class="table-dark">
                        <tr>
                            <th class="ps-4">Participant</th>
                            <th>Email</th>
                            <th>Event</th>
                            <th>Registration Date</th>
                            <th>Token</th>
                            <th class="text-center">Status</th>
                            <th class="text-center pe-4">Lifecycle Actions</th>
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
                                    $disp_status = 'Registered'; // Yellow
                                } elseif ($row['registration_status'] === 'Verified') {
                                    $badge_class = 'bg-primary';
                                    $disp_status = 'Attended'; // Blue
                                } elseif ($row['registration_status'] === 'Completed') {
                                    $badge_class = 'bg-success';
                                    $disp_status = 'Completed'; // Green
                                } elseif ($row['registration_status'] === 'Absent') {
                                    $badge_class = 'bg-danger';
                                    $disp_status = 'Absent';
                                } elseif ($row['registration_status'] === 'Cancelled') {
                                    $badge_class = 'bg-dark';
                                    $disp_status = 'Cancelled';
                                } elseif ($row['registration_status'] === 'Rejected') {
                                    $badge_class = 'bg-danger';
                                    $disp_status = 'Rejected';
                                }
                                ?>
                                <tr data-search-name="<?php echo htmlspecialchars($row['full_name']); ?>"
                                    data-search-email="<?php echo htmlspecialchars($row['email']); ?>"
                                    data-search-event="<?php echo htmlspecialchars($row['ws_title']); ?>"
                                    data-search-token="<?php echo htmlspecialchars($row['ticket_number']); ?>"
                                    data-search-status="<?php echo htmlspecialchars($row['registration_status']); ?>">
                                    <td class="ps-4">
                                        <div class="fw-bold text-dark"><?php echo htmlspecialchars($row['full_name']); ?></div>
                                    </td>
                                    <td>
                                        <div class="text-dark small"><?php echo htmlspecialchars($row['email']); ?></div>
                                    </td>
                                    <td>
                                        <div class="fw-semibold text-dark small" style="max-width: 250px;"><?php echo htmlspecialchars($row['ws_title']); ?></div>
                                    </td>
                                    <td class="text-muted small">
                                        <?php echo date("M d, Y h:i A", strtotime($row['created_at'])); ?>
                                    </td>
                                    <td>
                                        <code class="font-monospace fw-bold text-primary"><?php echo htmlspecialchars($row['ticket_number']); ?></code>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge <?php echo $badge_class; ?> px-2.5 py-1 text-uppercase fs-9"><?php echo $disp_status; ?></span>
                                    </td>
                                    <td class="text-center pe-4">
                                        <div class="d-inline-flex gap-1.5 align-items-center">
                                            <!-- View Details Modal Trigger -->
                                            <button type="button" class="btn btn-sm btn-outline-dark rounded-pill px-2.5" 
                                                    data-bs-toggle="modal" data-bs-target="#registrationDetailsModal"
                                                    data-name="<?php echo htmlspecialchars($row['full_name']); ?>"
                                                    data-email="<?php echo htmlspecialchars($row['email']); ?>"
                                                    data-mobile="<?php echo htmlspecialchars($row['mobile']); ?>"
                                                    data-college="<?php echo htmlspecialchars($row['college']); ?>"
                                                    data-dept="<?php echo htmlspecialchars($row['department']); ?>"
                                                    data-ws-title="<?php echo htmlspecialchars($row['ws_title']); ?>"
                                                    data-ws-domain="<?php echo htmlspecialchars($row['domain']); ?>"
                                                    data-ws-speaker="<?php echo htmlspecialchars($row['speaker']); ?>"
                                                    data-ws-venue="<?php echo htmlspecialchars($row['venue']); ?>"
                                                    data-ws-host="<?php echo htmlspecialchars($row['host']); ?>"
                                                    data-ticket-no="<?php echo htmlspecialchars($row['ticket_number']); ?>"
                                                    data-status="<?php echo htmlspecialchars($row['registration_status']); ?>"
                                                    data-created-at="<?php echo date("M d, Y h:i A", strtotime($row['created_at'])); ?>"
                                                    data-verified-by="<?php echo htmlspecialchars($row['admin_name'] ?? 'Not Verified Yet'); ?>"
                                                    data-verified-at="<?php echo $row['verified_at'] ? date("M d, Y h:i A", strtotime($row['verified_at'])) : 'Not Verified Yet'; ?>"
                                                    title="View Details">
                                                <i class="fa-solid fa-eye"></i>
                                            </button>

                                            <?php if ($row['registration_status'] === 'Pending'): ?>
                                                <form method="POST" class="d-inline mb-0">
                                                    <input type="hidden" name="action" value="approve">
                                                    <input type="hidden" name="ticket_id" value="<?php echo $row['id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-success rounded-pill px-2.5 fw-bold" title="Approve">
                                                        <i class="fa-solid fa-check"></i> Approve
                                                    </button>
                                                </form>
                                                <form method="POST" class="d-inline mb-0">
                                                    <input type="hidden" name="action" value="reject">
                                                    <input type="hidden" name="ticket_id" value="<?php echo $row['id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-danger rounded-pill px-2.5 fw-bold" title="Reject" onclick="return confirm('Reject this registration?');">
                                                        <i class="fa-solid fa-xmark"></i> Reject
                                                    </button>
                                                </form>
                                            <?php elseif ($row['registration_status'] === 'Verified'): ?>
                                                <!-- Shortcut to mark attendance check-in -->
                                                <a href="../attendance/index.php" class="btn btn-sm btn-outline-primary rounded-pill px-2.5 fw-bold" title="Mark Attendance">
                                                    <i class="fa-solid fa-clipboard-user"></i> Check-in
                                                </a>
                                                <form method="POST" class="d-inline mb-0">
                                                    <input type="hidden" name="action" value="cancel">
                                                    <input type="hidden" name="ticket_id" value="<?php echo $row['id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-dark rounded-pill px-2.5 fw-bold" title="Cancel" onclick="return confirm('Cancel this registration?');">
                                                        <i class="fa-solid fa-ban"></i> Cancel
                                                    </button>
                                                </form>
                                            <?php elseif ($row['registration_status'] === 'Completed'): ?>
                                                <!-- Shortcut to view/download certificates -->
                                                <a href="../certificates/manage.php" class="btn btn-sm btn-outline-success rounded-pill px-2.5 fw-bold" title="Manage Certificate">
                                                    <i class="fa-solid fa-award"></i> Certificate
                                                </a>
                                            <?php else: ?>
                                                <span class="text-muted small"><i class="fa-solid fa-lock me-1"></i>Closed</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="7" class="text-center text-muted py-5">No registrations found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Registration Details Modal -->
<div class="modal fade" id="registrationDetailsModal" tabindex="-1" aria-labelledby="registrationDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <div class="modal-header bg-dark text-white" style="border-bottom: 2px solid #eab308;">
                <h5 class="modal-title fw-bold text-white" id="registrationDetailsModalLabel">
                    <i class="fa-solid fa-graduation-cap text-warning me-2"></i>Enrollment & Ticket Dossier
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-4">
                    <!-- Participant Section -->
                    <div class="col-md-6">
                        <div class="p-3 bg-light rounded shadow-sm h-100 border-start border-3 border-primary">
                            <h5 class="fw-bold mb-3 text-primary"><i class="fa-solid fa-user-graduate me-2"></i>Participant Info</h5>
                            <table class="table table-sm table-borderless mb-0">
                                <tr>
                                    <td class="text-muted py-1" style="width: 100px;">Name:</td>
                                    <td class="fw-bold text-dark py-1" id="m_student_name">-</td>
                                </tr>
                                <tr>
                                    <td class="text-muted py-1">Email:</td>
                                    <td class="fw-semibold text-dark py-1 text-break" id="m_student_email">-</td>
                                </tr>
                                <tr>
                                    <td class="text-muted py-1">Mobile:</td>
                                    <td class="fw-semibold text-dark py-1" id="m_student_mobile">-</td>
                                </tr>
                                <tr>
                                    <td class="text-muted py-1">College:</td>
                                    <td class="fw-semibold text-dark py-1" id="m_student_college">-</td>
                                </tr>
                                <tr>
                                    <td class="text-muted py-1">Department:</td>
                                    <td class="fw-semibold text-dark py-1" id="m_student_dept">-</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    <!-- Event Section -->
                    <div class="col-md-6">
                        <div class="p-3 bg-light rounded shadow-sm h-100 border-start border-3 border-warning">
                            <h5 class="fw-bold mb-3 text-warning"><i class="fa-solid fa-chalkboard-user me-2"></i>Workshop Info</h5>
                            <table class="table table-sm table-borderless mb-0">
                                <tr>
                                    <td class="text-muted py-1" style="width: 100px;">Title:</td>
                                    <td class="fw-bold text-dark py-1" id="m_ws_title">-</td>
                                </tr>
                                <tr>
                                    <td class="text-muted py-1">Domain:</td>
                                    <td class="fw-semibold text-dark py-1 text-uppercase" id="m_ws_domain">-</td>
                                </tr>
                                <tr>
                                    <td class="text-muted py-1">Speaker:</td>
                                    <td class="fw-semibold text-dark py-1" id="m_ws_speaker">-</td>
                                </tr>
                                <tr>
                                    <td class="text-muted py-1">Venue:</td>
                                    <td class="fw-semibold text-danger py-1" id="m_ws_venue">-</td>
                                </tr>
                                <tr>
                                    <td class="text-muted py-1">Host:</td>
                                    <td class="fw-semibold text-dark py-1" id="m_ws_host">-</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    <!-- Registration Status Section -->
                    <div class="col-12">
                        <div class="p-3 bg-light rounded shadow-sm border-start border-3 border-success">
                            <h5 class="fw-bold mb-3 text-success"><i class="fa-solid fa-fingerprint me-2"></i>Registration & Lifecycle Logs</h5>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <span class="text-muted small d-block">Ticket Number</span>
                                    <code class="font-monospace fw-bold text-primary fs-6" id="m_ticket_number">-</code>
                                </div>
                                <div class="col-md-4">
                                    <span class="text-muted small d-block">Lifecycle Status</span>
                                    <div id="m_registration_status">-</div>
                                </div>
                                <div class="col-md-4">
                                    <span class="text-muted small d-block">Enrolled At</span>
                                    <span class="fw-semibold text-dark" id="m_created_at">-</span>
                                </div>
                                <div class="col-md-4">
                                    <span class="text-muted small d-block">Verified By</span>
                                    <span class="fw-semibold text-dark" id="m_verified_by">-</span>
                                </div>
                                <div class="col-md-4">
                                    <span class="text-muted small d-block">Verified At</span>
                                    <span class="fw-semibold text-dark" id="m_verified_at">-</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-dark fw-bold px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Details Modal Populating Logic
    const detailModal = document.getElementById('registrationDetailsModal');
    if (detailModal) {
        detailModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            
            // Extract info from data-* attributes
            const name = button.getAttribute('data-name');
            const email = button.getAttribute('data-email');
            const mobile = button.getAttribute('data-mobile') || 'N/A';
            const college = button.getAttribute('data-college') || 'N/A';
            const dept = button.getAttribute('data-dept') || 'N/A';
            
            const wsTitle = button.getAttribute('data-ws-title');
            const wsDomain = button.getAttribute('data-ws-domain');
            const wsSpeaker = button.getAttribute('data-ws-speaker');
            const wsVenue = button.getAttribute('data-ws-venue');
            const wsHost = button.getAttribute('data-ws-host');
            
            const ticketNo = button.getAttribute('data-ticket-no');
            const status = button.getAttribute('data-status');
            const createdAt = button.getAttribute('data-created-at');
            const verifiedBy = button.getAttribute('data-verified-by') || 'N/A';
            const verifiedAt = button.getAttribute('data-verified-at') || 'N/A';
            
            // Populate modal fields
            document.getElementById('m_student_name').textContent = name;
            document.getElementById('m_student_email').textContent = email;
            document.getElementById('m_student_mobile').textContent = mobile;
            document.getElementById('m_student_college').textContent = college;
            document.getElementById('m_student_dept').textContent = dept;
            
            document.getElementById('m_ws_title').textContent = wsTitle;
            document.getElementById('m_ws_domain').textContent = wsDomain;
            document.getElementById('m_ws_speaker').textContent = wsSpeaker;
            document.getElementById('m_ws_venue').textContent = wsVenue;
            document.getElementById('m_ws_host').textContent = wsHost;
            
            document.getElementById('m_ticket_number').textContent = ticketNo;
            document.getElementById('m_created_at').textContent = createdAt;
            document.getElementById('m_verified_by').textContent = verifiedBy;
            document.getElementById('m_verified_at').textContent = verifiedAt;
            
            // Setup status badge inside modal
            let badgeHtml = '';
            if (status === 'Pending') {
                badgeHtml = '<span class="badge bg-warning text-dark px-2.5 py-1 text-uppercase fs-9"><i class="fa-solid fa-clock me-1"></i>Registered</span>';
            } else if (status === 'Verified') {
                badgeHtml = '<span class="badge bg-primary px-2.5 py-1 text-uppercase fs-9"><i class="fa-solid fa-user-check me-1"></i>Attended / Approved</span>';
            } else if (status === 'Completed') {
                badgeHtml = '<span class="badge bg-success px-2.5 py-1 text-uppercase fs-9"><i class="fa-solid fa-circle-check me-1"></i>Completed</span>';
            } else if (status === 'Absent') {
                badgeHtml = '<span class="badge bg-danger px-2.5 py-1 text-uppercase fs-9"><i class="fa-solid fa-circle-xmark me-1"></i>Absent</span>';
            } else if (status === 'Cancelled') {
                badgeHtml = '<span class="badge bg-dark px-2.5 py-1 text-uppercase fs-9"><i class="fa-solid fa-ban me-1"></i>Cancelled</span>';
            } else if (status === 'Rejected') {
                badgeHtml = '<span class="badge bg-danger px-2.5 py-1 text-uppercase fs-9"><i class="fa-solid fa-circle-xmark me-1"></i>Rejected</span>';
            } else {
                badgeHtml = `<span class="badge bg-secondary px-2.5 py-1 text-uppercase fs-9">${status}</span>`;
            }
            document.getElementById('m_registration_status').innerHTML = badgeHtml;
        });
    }

    // Search and filtering logic
    function filterRegistrationsTable() {
        let query = document.getElementById('searchQuery').value.toLowerCase();
        let statusVal = document.getElementById('filterStatus').value; // 'Pending', 'Verified', 'Completed', etc.
        
        let rows = document.querySelectorAll('#registrationsJournalTable tbody tr');
        let matchedCount = 0;
        
        rows.forEach(row => {
            if (row.classList.contains('no-records-row')) return;
            
            let name = row.getAttribute('data-search-name').toLowerCase();
            let email = row.getAttribute('data-search-email').toLowerCase();
            let event = row.getAttribute('data-search-event').toLowerCase();
            let token = row.getAttribute('data-search-token').toLowerCase();
            let status = row.getAttribute('data-search-status'); // 'Pending', 'Verified', 'Completed', etc.
            
            let matchesQuery = !query || 
                                name.includes(query) || 
                                email.includes(query) || 
                                event.includes(query) || 
                                token.includes(query);
            
            let matchesStatus = !statusVal || (status === statusVal);
            
            if (matchesQuery && matchesStatus) {
                row.style.display = '';
                matchedCount++;
            } else {
                row.style.display = 'none';
            }
        });
        
        let noRec = document.getElementById('noRecordsRow');
        if (matchedCount === 0) {
            if (!noRec) {
                let tbody = document.querySelector('#registrationsJournalTable tbody');
                let tr = document.createElement('tr');
                tr.id = 'noRecordsRow';
                tr.className = 'no-records-row';
                tr.innerHTML = '<td colspan="7" class="text-center text-muted py-5"><i class="fa-solid fa-magnifying-glass fs-3 d-block mb-2"></i>No matching records found.</td>';
                tbody.appendChild(tr);
            } else {
                noRec.style.display = '';
            }
        } else if (noRec) {
            noRec.style.display = 'none';
        }
    }
    
    document.getElementById('searchQuery').addEventListener('keyup', filterRegistrationsTable);
    document.getElementById('filterStatus').addEventListener('change', filterRegistrationsTable);
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

