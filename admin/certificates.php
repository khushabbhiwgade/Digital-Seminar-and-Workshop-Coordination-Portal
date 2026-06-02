<?php
// admin/certificates.php
// ------------------------------------------------------------
// Admin — Certificate Issuance Panel
// ------------------------------------------------------------
$base_path = '../';
$page_title = 'Certificate Management - Admin Panel';
$active_page = 'admin_certificates';

require_once $base_path . 'includes/auth.php';
require_role('admin');
require_once $base_path . 'config/db_connect.php';

$action_msg = '';

// Handle certificate issuance
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['issue_reg_id'])) {
    $reg_id = intval($_POST['issue_reg_id']);

    // Check if certificate already exists
    $check_stmt = $conn->prepare("SELECT id FROM certificates WHERE registration_id = ?");
    $check_stmt->bind_param("i", $reg_id);
    $check_stmt->execute();
    $check_res = $check_stmt->get_result();

    if ($check_res->num_rows > 0) {
        $action_msg = '<div class="alert alert-warning alert-dismissible fade show" role="alert">
            <i class="fa-solid fa-triangle-exclamation me-2"></i>Certificate already issued for this registration.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
    } else {
        // Verify the registration exists and is attended
        $verify_stmt = $conn->prepare("SELECT id FROM registrations WHERE id = ? AND attended = 1");
        $verify_stmt->bind_param("i", $reg_id);
        $verify_stmt->execute();
        $verify_res = $verify_stmt->get_result();

        if ($verify_res->num_rows > 0) {
            // Generate unique certificate code
            $cert_code = 'CERT-' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 10));

            $insert_stmt = $conn->prepare("INSERT INTO certificates (registration_id, certificate_code, issued_at) VALUES (?, ?, NOW())");
            $insert_stmt->bind_param("is", $reg_id, $cert_code);

            if ($insert_stmt->execute()) {
                $action_msg = '<div class="alert alert-success alert-dismissible fade show border-start border-4 border-success shadow-sm" role="alert">
                    <i class="fa-solid fa-award me-2"></i><strong>Certificate Issued!</strong> Code: <code>' . htmlspecialchars($cert_code) . '</code>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
            } else {
                $action_msg = '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fa-solid fa-xmark me-2"></i>Failed to issue certificate. Database error.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
            }
        } else {
            $action_msg = '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fa-solid fa-xmark me-2"></i>Cannot issue certificate. Registration not found or student has not attended.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
        }
    }
}

// Handle bulk issuance for an event
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_event_id'])) {
    $event_id = intval($_POST['bulk_event_id']);
    $issued = 0;
    $skipped = 0;

    // Find all attended registrations for this event that don't have a certificate
    $bulk_sql = "SELECT r.id 
                 FROM registrations r
                 LEFT JOIN certificates c ON r.id = c.registration_id
                 WHERE r.event_id = ? AND r.attended = 1 AND c.id IS NULL";
    $bulk_stmt = $conn->prepare($bulk_sql);
    $bulk_stmt->bind_param("i", $event_id);
    $bulk_stmt->execute();
    $bulk_res = $bulk_stmt->get_result();

    while ($row = $bulk_res->fetch_assoc()) {
        $cert_code = 'CERT-' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 10));
        $ins_stmt = $conn->prepare("INSERT INTO certificates (registration_id, certificate_code, issued_at) VALUES (?, ?, NOW())");
        $ins_stmt->bind_param("is", $row['id'], $cert_code);
        if ($ins_stmt->execute()) {
            $issued++;
        } else {
            $skipped++;
        }
    }

    if ($issued > 0) {
        $action_msg = '<div class="alert alert-success alert-dismissible fade show border-start border-4 border-success shadow-sm" role="alert">
            <i class="fa-solid fa-award me-2"></i><strong>Bulk Issuance Complete!</strong> ' . $issued . ' certificate(s) issued. ' . ($skipped > 0 ? $skipped . ' skipped.' : '') . '
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
    } else {
        $action_msg = '<div class="alert alert-info alert-dismissible fade show" role="alert">
            <i class="fa-solid fa-info-circle me-2"></i>No eligible attendees found for bulk issuance (all may already have certificates).
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
    }
}

// Stats
$total_certs = 0;
$tc_res = $conn->query("SELECT COUNT(*) as cnt FROM certificates");
if ($tc_res) $total_certs = $tc_res->fetch_assoc()['cnt'];

$eligible = 0;
$elig_res = $conn->query("SELECT COUNT(*) as cnt FROM registrations r LEFT JOIN certificates c ON r.id = c.registration_id WHERE r.attended = 1 AND c.id IS NULL");
if ($elig_res) $eligible = $elig_res->fetch_assoc()['cnt'];

// Fetch events for bulk issuance dropdown
$events_list = [];
$ev_res = $conn->query("SELECT id, title FROM events ORDER BY event_date DESC");
if ($ev_res) {
    while ($e = $ev_res->fetch_assoc()) $events_list[] = $e;
}

// Fetch issued certificates
$certs = [];
$certs_sql = "SELECT c.id, c.certificate_code, c.issued_at,
                     u.full_name, u.email,
                     e.title AS event_title
              FROM certificates c
              JOIN registrations r ON c.registration_id = r.id
              JOIN users u ON r.user_id = u.id
              JOIN events e ON r.event_id = e.id
              ORDER BY c.issued_at DESC";
$certs_res = $conn->query($certs_sql);
if ($certs_res) {
    while ($c = $certs_res->fetch_assoc()) $certs[] = $c;
}

// Fetch eligible (attended but no certificate) for individual issuance
$pending_certs = [];
$pc_sql = "SELECT r.id AS reg_id, u.full_name, e.title AS event_title
           FROM registrations r
           JOIN users u ON r.user_id = u.id
           JOIN events e ON r.event_id = e.id
           LEFT JOIN certificates c ON r.id = c.registration_id
           WHERE r.attended = 1 AND c.id IS NULL
           ORDER BY e.event_date DESC";
$pc_res = $conn->query($pc_sql);
if ($pc_res) {
    while ($p = $pc_res->fetch_assoc()) $pending_certs[] = $p;
}

include $base_path . 'includes/header.php';
include $base_path . 'includes/navbar.php';
?>

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h2 mb-0"><i class="fa-solid fa-award text-warning me-2"></i>Certificate Management</h1>
            <p class="text-muted mb-0">Issue and manage participation certificates for attendees</p>
        </div>
        <a href="dashboard.php" class="btn btn-outline-dark btn-sm"><i class="fa-solid fa-arrow-left me-1"></i>Back to Dashboard</a>
    </div>

    <?php echo $action_msg; ?>

    <!-- Stats Row -->
    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm rounded-3 text-center p-3 bg-white">
                <div class="display-6 text-warning mb-1"><i class="fa-solid fa-certificate"></i></div>
                <h3 class="fw-bold mb-0"><?php echo intval($total_certs); ?></h3>
                <span class="text-muted small">Certificates Issued</span>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-0 shadow-sm rounded-3 text-center p-3 bg-white">
                <div class="display-6 text-info mb-1"><i class="fa-solid fa-hourglass-half"></i></div>
                <h3 class="fw-bold mb-0"><?php echo intval($eligible); ?></h3>
                <span class="text-muted small">Eligible (Awaiting Issuance)</span>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <!-- Bulk Issuance -->
        <div class="col-lg-6">
            <div class="card shadow-sm border-0 rounded-3 h-100">
                <div class="card-header bg-dark text-white py-3">
                    <h5 class="mb-0"><i class="fa-solid fa-layer-group text-warning me-2"></i>Bulk Issue by Event</h5>
                </div>
                <div class="card-body p-4">
                    <form method="POST">
                        <div class="mb-3">
                            <label for="bulk_event_id" class="form-label fw-semibold">Select Event</label>
                            <select name="bulk_event_id" id="bulk_event_id" class="form-select" required>
                                <option value="">— Choose an event —</option>
                                <?php foreach ($events_list as $ev): ?>
                                    <option value="<?php echo $ev['id']; ?>"><?php echo htmlspecialchars($ev['title']); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">Issues certificates to all attended students of the selected event who don't already have one.</div>
                        </div>
                        <button type="submit" class="btn btn-warning text-dark fw-bold w-100" onclick="return confirm('Issue certificates to all eligible attendees of this event?');">
                            <i class="fa-solid fa-paper-plane me-2"></i>Issue All Certificates
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Individual Issuance -->
        <div class="col-lg-6">
            <div class="card shadow-sm border-0 rounded-3 h-100">
                <div class="card-header bg-dark text-white py-3">
                    <h5 class="mb-0"><i class="fa-solid fa-user-graduate text-warning me-2"></i>Issue Individual Certificate</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive" style="max-height: 250px; overflow-y: auto;">
                        <table class="table table-striped align-middle mb-0">
                            <thead class="table-dark" style="position: sticky; top: 0;">
                                <tr>
                                    <th>Student</th>
                                    <th>Event</th>
                                    <th class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($pending_certs) > 0): ?>
                                    <?php foreach ($pending_certs as $pc): ?>
                                        <tr>
                                            <td class="fw-semibold"><?php echo htmlspecialchars($pc['full_name']); ?></td>
                                            <td><?php echo htmlspecialchars($pc['event_title']); ?></td>
                                            <td class="text-center">
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="issue_reg_id" value="<?php echo $pc['reg_id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-success"><i class="fa-solid fa-plus me-1"></i>Issue</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="3" class="text-center text-muted py-3">No eligible attendees awaiting certificates.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Issued Certificates Table -->
    <div class="card shadow-sm border-0 rounded-3 overflow-hidden">
        <div class="card-header bg-dark text-white py-3">
            <h5 class="mb-0"><i class="fa-solid fa-scroll text-warning me-2"></i>All Issued Certificates</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped align-middle mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th>Student Name</th>
                            <th>Email</th>
                            <th>Event</th>
                            <th>Certificate Code</th>
                            <th>Issued On</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($certs) > 0): ?>
                            <?php foreach ($certs as $idx => $cert): ?>
                                <tr>
                                    <td><?php echo $idx + 1; ?></td>
                                    <td class="fw-semibold"><?php echo htmlspecialchars($cert['full_name']); ?></td>
                                    <td><?php echo htmlspecialchars($cert['email']); ?></td>
                                    <td><?php echo htmlspecialchars($cert['event_title']); ?></td>
                                    <td><code><?php echo htmlspecialchars($cert['certificate_code']); ?></code></td>
                                    <td class="text-muted small"><?php echo date("M d, Y h:i A", strtotime($cert['issued_at'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="6" class="text-center text-muted py-4">No certificates have been issued yet.</td></tr>
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
