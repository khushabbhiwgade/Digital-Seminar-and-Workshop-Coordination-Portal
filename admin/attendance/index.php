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

            // Store certificate code for logs
            $cert_code = $cert_res['certificate_no'] ?? 'N/A';
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

// Calculate attendance stats (Total Records, Present, Absent, Attendance Rate)
$total_cnt = 0;
$present_cnt = 0;
$absent_cnt = 0;
$att_rate = 0;

$stat_total_res = $conn->query("SELECT COUNT(*) as cnt FROM attendance");
if ($stat_total_res) $total_cnt = intval($stat_total_res->fetch_assoc()['cnt']);

$stat_present_res = $conn->query("SELECT COUNT(*) as cnt FROM attendance WHERE attendance_status = 'Present'");
if ($stat_present_res) $present_cnt = intval($stat_present_res->fetch_assoc()['cnt']);

$stat_absent_res = $conn->query("SELECT COUNT(*) as cnt FROM attendance WHERE attendance_status = 'Absent'");
if ($stat_absent_res) $absent_cnt = intval($stat_absent_res->fetch_assoc()['cnt']);

$att_rate = $total_cnt > 0 ? round(($present_cnt / $total_cnt) * 100, 1) : 0;

// Fetch verified tickets awaiting check-in, along with completed/absent ones for historical log & search
$verified_sql = "SELECT t.id, t.ticket_number, t.qr_code_path, t.status, t.registration_status, t.created_at,
                        u.full_name, u.email, u.mobile, u.college, u.department, u.year_of_study,
                        w.title as ws_title, w.venue, w.domain, w.speaker, w.start_date,
                        a.attendance_status, a.marked_at,
                        adm.full_name as admin_name
                 FROM tickets t
                 JOIN users u ON t.user_id = u.id
                 JOIN workshops w ON t.event_id = w.id
                 LEFT JOIN attendance a ON t.id = a.ticket_id
                 LEFT JOIN users adm ON a.marked_by = adm.id
                 WHERE t.registration_status IN ('Verified', 'Completed', 'Absent')
                 ORDER BY t.created_at DESC";
$verified_res = $conn->query($verified_sql);

include $base_path . 'includes/header.php';
include $base_path . 'includes/navbar.php';
?>

<div class="container my-5">
    <!-- Header Section -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
        <div>
            <h1 class="h2 mb-0 fw-bold"><i class="fa-solid fa-clipboard-user text-primary me-2"></i>Attendance Desk</h1>
            <p class="text-muted mb-0">Record present or absent attendance check-ins for verified participant tickets.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="../registrations/manage.php" class="btn btn-outline-primary btn-sm fw-bold"><i class="fa-solid fa-list-check me-1"></i>Registrations Desk</a>
            <a href="../tickets/manage.php" class="btn btn-outline-primary btn-sm fw-bold"><i class="fa-solid fa-ticket me-1"></i>Tickets Desk</a>
            <a href="../dashboard.php" class="btn btn-outline-dark btn-sm"><i class="fa-solid fa-arrow-left me-1"></i>Back to Dashboard</a>
        </div>
    </div>

    <?php echo $alert_message; ?>

    <!-- Attendance Stat Cards -->
    <div class="row g-4 mb-4">
        <div class="col-md-3 col-sm-6">
            <div class="card h-100 border-0 shadow-sm rounded-3 p-3 bg-white text-center border-start border-4 border-primary">
                <div class="display-6 text-primary mb-2"><i class="fa-solid fa-clipboard-list"></i></div>
                <h3 class="fw-bold mb-1"><?php echo $total_cnt; ?></h3>
                <span class="text-muted small fw-semibold">Total Records</span>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card h-100 border-0 shadow-sm rounded-3 p-3 bg-white text-center border-start border-4 border-success">
                <div class="display-6 text-success mb-2"><i class="fa-solid fa-user-check"></i></div>
                <h3 class="fw-bold mb-1"><?php echo $present_cnt; ?></h3>
                <span class="text-muted small fw-semibold">Present Check-Ins</span>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card h-100 border-0 shadow-sm rounded-3 p-3 bg-white text-center border-start border-4 border-danger">
                <div class="display-6 text-danger mb-2"><i class="fa-solid fa-user-xmark"></i></div>
                <h3 class="fw-bold mb-1"><?php echo $absent_cnt; ?></h3>
                <span class="text-muted small fw-semibold">Absent Logs</span>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card h-100 border-0 shadow-sm rounded-3 p-3 bg-white text-center border-start border-4 border-warning">
                <div class="display-6 text-warning mb-2"><i class="fa-solid fa-chart-pie"></i></div>
                <h3 class="fw-bold mb-1"><?php echo $att_rate; ?>%</h3>
                <span class="text-muted small fw-semibold">Attendance Rate</span>
            </div>
        </div>
    </div>

    <!-- Search & Filters -->
    <div class="card shadow-sm border-0 rounded-3 bg-white mb-4">
        <div class="card-body p-3">
            <div class="row g-3 align-items-center">
                <!-- Search fields -->
                <div class="col-md-3">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light text-muted border-end-0"><i class="fa-solid fa-user"></i></span>
                        <input type="text" id="searchParticipant" class="form-control border-start-0 ps-0" placeholder="Search Participant...">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light text-muted border-end-0"><i class="fa-solid fa-envelope"></i></span>
                        <input type="text" id="searchEmail" class="form-control border-start-0 ps-0" placeholder="Search Email...">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light text-muted border-end-0"><i class="fa-solid fa-chalkboard-user"></i></span>
                        <input type="text" id="searchEvent" class="form-control border-start-0 ps-0" placeholder="Search Event/Workshop...">
                    </div>
                </div>
                <!-- Status Filter -->
                <div class="col-md-3">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light text-muted"><i class="fa-solid fa-filter"></i></span>
                        <select id="filterStatus" class="form-select">
                            <option value="">All Statuses (Pending & Logs)</option>
                            <option value="Pending">Pending Check-In</option>
                            <option value="Present">Present</option>
                            <option value="Absent">Absent</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Attendance Table Card -->
    <div class="card shadow-sm border-0 rounded-3 overflow-hidden bg-white mb-5">
        <div class="card-header bg-dark text-white py-3 d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fa-solid fa-table-list text-warning me-2"></i>Attendance Check-In Journal</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="verifiedTicketsTable">
                    <thead class="table-dark">
                        <tr>
                            <th class="ps-4">Participant</th>
                            <th>Email</th>
                            <th>Event & Venue</th>
                            <th>Attendance Date</th>
                            <th class="text-center">Status</th>
                            <th class="text-center pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($verified_res && $verified_res->num_rows > 0): ?>
                            <?php while ($tk = $verified_res->fetch_assoc()): ?>
                                <?php
                                $status_val = $tk['registration_status'];
                                $badge_html = '';
                                if ($status_val === 'Verified') {
                                    $badge_html = '<span class="badge bg-warning text-dark px-2.5 py-1 text-uppercase fs-9"><i class="fa-solid fa-clock me-1"></i>Pending</span>';
                                } elseif ($status_val === 'Completed') {
                                    $badge_html = '<span class="badge bg-success px-2.5 py-1 text-uppercase fs-9 mb-1"><i class="fa-solid fa-circle-check me-1"></i>Present</span> <span class="badge bg-primary px-2.5 py-1 text-uppercase fs-9 d-block d-md-inline-block">Completed</span>';
                                } elseif ($status_val === 'Absent') {
                                    $badge_html = '<span class="badge bg-danger px-2.5 py-1 text-uppercase fs-9"><i class="fa-solid fa-circle-xmark me-1"></i>Absent</span>';
                                }
                                ?>
                                <tr data-search-name="<?php echo htmlspecialchars($tk['full_name']); ?>"
                                    data-search-email="<?php echo htmlspecialchars($tk['email']); ?>"
                                    data-search-event="<?php echo htmlspecialchars($tk['ws_title']); ?>"
                                    data-search-status="<?php echo htmlspecialchars($status_val); ?>">
                                    <td class="ps-4">
                                        <div class="fw-bold text-dark"><?php echo htmlspecialchars($tk['full_name']); ?></div>
                                        <span class="text-muted small">Code: <code class="font-monospace fw-bold text-primary"><?php echo htmlspecialchars($tk['ticket_number']); ?></code></span>
                                    </td>
                                    <td>
                                        <div class="text-dark small"><?php echo htmlspecialchars($tk['email']); ?></div>
                                    </td>
                                    <td>
                                        <div class="text-dark fw-semibold small" style="max-width: 250px;"><?php echo htmlspecialchars($tk['ws_title']); ?></div>
                                        <div class="text-muted small"><i class="fa-solid fa-location-dot me-1 text-danger"></i><?php echo htmlspecialchars($tk['venue']); ?></div>
                                    </td>
                                    <td class="text-muted small">
                                        <?php if ($tk['marked_at']): ?>
                                            <?php echo date("M d, Y h:i A", strtotime($tk['marked_at'])); ?>
                                        <?php else: ?>
                                            <span class="text-secondary">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php echo $badge_html; ?>
                                    </td>
                                    <td class="text-center pe-4">
                                        <div class="d-inline-flex gap-2 align-items-center">
                                            <!-- View Button -->
                                            <button type="button" class="btn btn-sm btn-outline-dark rounded-pill px-2.5" 
                                                    data-bs-toggle="modal" data-bs-target="#attendanceDetailsModal"
                                                    data-name="<?php echo htmlspecialchars($tk['full_name']); ?>"
                                                    data-email="<?php echo htmlspecialchars($tk['email']); ?>"
                                                    data-mobile="<?php echo htmlspecialchars($tk['mobile']); ?>"
                                                    data-college="<?php echo htmlspecialchars($tk['college']); ?>"
                                                    data-dept="<?php echo htmlspecialchars($tk['department']); ?>"
                                                    data-ws-title="<?php echo htmlspecialchars($tk['ws_title']); ?>"
                                                    data-ws-domain="<?php echo htmlspecialchars($tk['domain']); ?>"
                                                    data-ws-speaker="<?php echo htmlspecialchars($tk['speaker']); ?>"
                                                    data-ws-venue="<?php echo htmlspecialchars($tk['venue']); ?>"
                                                    data-ws-date="<?php echo date("M d, Y", strtotime($tk['start_date'])); ?>"
                                                    data-ticket-no="<?php echo htmlspecialchars($tk['ticket_number']); ?>"
                                                    data-status="<?php echo htmlspecialchars($status_val); ?>"
                                                    data-marked-at="<?php echo $tk['marked_at'] ? date("M d, Y h:i A", strtotime($tk['marked_at'])) : ''; ?>"
                                                    data-marked-by="<?php echo htmlspecialchars($tk['admin_name'] ?? ''); ?>"
                                                    title="View Detailed Dossier">
                                                <i class="fa-solid fa-eye"></i> View
                                            </button>

                                            <?php if ($status_val === 'Verified'): ?>
                                                <form method="POST" class="d-inline mb-0">
                                                    <input type="hidden" name="action" value="present">
                                                    <input type="hidden" name="ticket_id" value="<?php echo $tk['id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-success rounded-pill px-3 shadow-sm" title="Mark Present">
                                                        <i class="fa-solid fa-check me-1"></i>Present
                                                    </button>
                                                </form>
                                                <form method="POST" class="d-inline mb-0">
                                                    <input type="hidden" name="action" value="absent">
                                                    <input type="hidden" name="ticket_id" value="<?php echo $tk['id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-danger rounded-pill px-3 shadow-sm" 
                                                            onclick="return confirm('Mark this participant as ABSENT? This will cancel their ticket.');" title="Mark Absent">
                                                        <i class="fa-solid fa-xmark me-1"></i>Absent
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <span class="text-muted small"><i class="fa-solid fa-lock me-1"></i>Checked-in</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-5">
                                    <i class="fa-solid fa-clipboard-question fs-2 d-block mb-2 text-secondary"></i>
                                    No verified registrations or check-in logs found.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Attendance Details Modal -->
<div class="modal fade" id="attendanceDetailsModal" tabindex="-1" aria-labelledby="attendanceDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <div class="modal-header bg-dark text-white" style="border-bottom: 2px solid #eab308;">
                <h5 class="modal-title fw-bold text-white" id="attendanceDetailsModalLabel">
                    <i class="fa-solid fa-clipboard-user text-warning me-2"></i>Attendance & Registration Dossier
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
                                    <td class="text-muted py-1">Date:</td>
                                    <td class="fw-semibold text-dark py-1" id="m_ws_date">-</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    <!-- Attendance Status Section -->
                    <div class="col-12">
                        <div class="p-3 bg-light rounded shadow-sm border-start border-3 border-success">
                            <h5 class="fw-bold mb-3 text-success"><i class="fa-solid fa-fingerprint me-2"></i>Attendance & Ticket Check-In Log</h5>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <span class="text-muted small d-block">Ticket Number</span>
                                    <code class="font-monospace fw-bold text-primary fs-6" id="m_ticket_number">-</code>
                                </div>
                                <div class="col-md-4">
                                    <span class="text-muted small d-block">Attendance Status</span>
                                    <div id="m_attendance_status">-</div>
                                </div>
                                <div class="col-md-4">
                                    <span class="text-muted small d-block">Check-In Date/Time</span>
                                    <span class="fw-semibold text-dark" id="m_marked_at">-</span>
                                </div>
                                <div class="col-md-4">
                                    <span class="text-muted small d-block">Logged By (Admin)</span>
                                    <span class="fw-semibold text-dark" id="m_marked_by">-</span>
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
    // Attendance Details Modal Populating Logic
    const detailModal = document.getElementById('attendanceDetailsModal');
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
            const wsDate = button.getAttribute('data-ws-date');
            
            const ticketNo = button.getAttribute('data-ticket-no');
            const status = button.getAttribute('data-status');
            const markedAt = button.getAttribute('data-marked-at') || 'Not Recorded Yet';
            const markedBy = button.getAttribute('data-marked-by') || 'Not Recorded Yet';
            
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
            document.getElementById('m_ws_date').textContent = wsDate;
            
            document.getElementById('m_ticket_number').textContent = ticketNo;
            document.getElementById('m_marked_at').textContent = markedAt;
            document.getElementById('m_marked_by').textContent = markedBy;
            
            // Setup status badge inside modal
            let badgeHtml = '';
            if (status === 'Verified') {
                badgeHtml = '<span class="badge bg-warning text-dark px-2.5 py-1 text-uppercase fs-9"><i class="fa-solid fa-clock me-1"></i>Pending Check-In</span>';
            } else if (status === 'Completed') {
                badgeHtml = '<span class="badge bg-success px-2.5 py-1 text-uppercase fs-9"><i class="fa-solid fa-circle-check me-1"></i>Present</span> <span class="badge bg-primary px-2.5 py-1 text-uppercase fs-9">Completed</span>';
            } else if (status === 'Absent') {
                badgeHtml = '<span class="badge bg-danger px-2.5 py-1 text-uppercase fs-9"><i class="fa-solid fa-circle-xmark me-1"></i>Absent</span>';
            } else {
                badgeHtml = `<span class="badge bg-secondary px-2.5 py-1 text-uppercase fs-9">${status}</span>`;
            }
            document.getElementById('m_attendance_status').innerHTML = badgeHtml;
        });
    }

    // Search and dynamic JavaScript filtering logic
    function filterAttendanceTable() {
        let partVal = document.getElementById('searchParticipant').value.toLowerCase();
        let emailVal = document.getElementById('searchEmail').value.toLowerCase();
        let eventVal = document.getElementById('searchEvent').value.toLowerCase();
        let statusVal = document.getElementById('filterStatus').value; // 'Pending', 'Present', 'Absent'
        
        let rows = document.querySelectorAll('#verifiedTicketsTable tbody tr');
        let matchedCount = 0;
        
        rows.forEach(row => {
            if (row.classList.contains('no-records-row')) return;
            
            let name = row.getAttribute('data-search-name').toLowerCase();
            let email = row.getAttribute('data-search-email').toLowerCase();
            let event = row.getAttribute('data-search-event').toLowerCase();
            let status = row.getAttribute('data-search-status'); // 'Verified', 'Completed', 'Absent'
            
            // Map status code
            let mappedStatus = '';
            if (status === 'Verified') mappedStatus = 'Pending';
            else if (status === 'Completed') mappedStatus = 'Present';
            else if (status === 'Absent') mappedStatus = 'Absent';
            
            let matchesPart = name.includes(partVal);
            if (partVal) {
                matchesPart = matchesPart || email.includes(partVal);
            }
            
            let matchesEmailField = email.includes(emailVal);
            let matchesEvent = event.includes(eventVal);
            let matchesStatus = !statusVal || (mappedStatus === statusVal);
            
            if (matchesPart && matchesEmailField && matchesEvent && matchesStatus) {
                row.style.display = '';
                matchedCount++;
            } else {
                row.style.display = 'none';
            }
        });
        
        // Handle no records display
        let noRec = document.getElementById('noRecordsRow');
        if (matchedCount === 0) {
            if (!noRec) {
                let tbody = document.querySelector('#verifiedTicketsTable tbody');
                let tr = document.createElement('tr');
                tr.id = 'noRecordsRow';
                tr.className = 'no-records-row';
                tr.innerHTML = '<td colspan="6" class="text-center text-muted py-5"><i class="fa-solid fa-magnifying-glass fs-3 d-block mb-2"></i>No matching records found.</td>';
                tbody.appendChild(tr);
            } else {
                noRec.style.display = '';
            }
        } else if (noRec) {
            noRec.style.display = 'none';
        }
    }
    
    document.getElementById('searchParticipant').addEventListener('keyup', filterAttendanceTable);
    document.getElementById('searchEmail').addEventListener('keyup', filterAttendanceTable);
    document.getElementById('searchEvent').addEventListener('keyup', filterAttendanceTable);
    document.getElementById('filterStatus').addEventListener('change', filterAttendanceTable);
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

