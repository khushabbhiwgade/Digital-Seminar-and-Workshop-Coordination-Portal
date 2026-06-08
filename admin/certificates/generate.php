<?php
// admin/certificates/generate.php
// ------------------------------------------------------------
// Admin — Certificate Generation Desk (Single, Workshop, Bulk)
// ------------------------------------------------------------

$base_path = '../../';
$page_title = 'Generate Certificates - Admin Panel';
$active_page = 'admin_certificates';

require_once $base_path . 'includes/auth.php';
require_role('admin');
require_once $base_path . 'config/db_connect.php';
require_once $base_path . 'includes/certificate_helper.php';

$alert_message = '';
$processing_report = null;

// Handle certificate generation POST requests (Part 9)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $admin_id = get_user_id();
    
    $success_count = 0;
    $skip_count = 0;
    $errors = [];
    
    try {
        if ($action === 'single') {
            $ticket_id = intval($_POST['ticket_id'] ?? 0);
            if ($ticket_id <= 0) {
                throw new Exception("Please select a valid participant registration.");
            }
            
            $res = generate_certificate($ticket_id, $admin_id);
            if ($res['success']) {
                $success_count = 1;
                $alert_message = '<div class="alert alert-success alert-dismissible fade show border-start border-4 border-success shadow-sm" role="alert">
                    <i class="fa-solid fa-circle-check text-success me-2"></i><strong>Certificate Issued!</strong> Code: <code>' . htmlspecialchars($res['certificate_no']) . '</code> has been successfully generated and stored.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
            } else {
                throw new Exception($res['message']);
            }
            
        } elseif ($action === 'workshop') {
            $workshop_id = intval($_POST['workshop_id'] ?? 0);
            if ($workshop_id <= 0) {
                throw new Exception("Please select a valid workshop.");
            }
            
            // Fetch all present, completed registrations for this workshop that don't have a certificate
            $eligible_q = $conn->prepare("
                SELECT t.id, u.full_name 
                FROM tickets t
                JOIN attendance a ON t.id = a.ticket_id
                LEFT JOIN certificates c ON t.id = c.registration_id
                JOIN users u ON t.user_id = u.id
                WHERE t.event_id = ? 
                  AND a.attendance_status = 'Present' 
                  AND t.registration_status = 'Completed' 
                  AND c.id IS NULL
            ");
            $eligible_q->bind_param("i", $workshop_id);
            $eligible_q->execute();
            $eligible_res = $eligible_q->get_result();
            
            if ($eligible_res->num_rows === 0) {
                $alert_message = '<div class="alert alert-info alert-dismissible fade show border-start border-4 border-info shadow-sm" role="alert">
                    <i class="fa-solid fa-circle-info text-info me-2"></i>No eligible attendees awaiting certificates found for this workshop.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
            } else {
                while ($row = $eligible_res->fetch_assoc()) {
                    $res = generate_certificate($row['id'], $admin_id);
                    if ($res['success']) {
                        $success_count++;
                    } else {
                        $errors[] = $row['full_name'] . ": " . $res['message'];
                    }
                }
                
                $processing_report = [
                    'title' => 'Workshop Bulk Generation',
                    'success' => $success_count,
                    'skip' => $skip_count,
                    'errors' => $errors
                ];
            }
            
        } elseif ($action === 'bulk') {
            // Fetch ALL present, completed registrations in the entire system that don't have a certificate
            $eligible_res = $conn->query("
                SELECT t.id, u.full_name 
                FROM tickets t
                JOIN attendance a ON t.id = a.ticket_id
                LEFT JOIN certificates c ON t.id = c.registration_id
                JOIN users u ON t.user_id = u.id
                WHERE a.attendance_status = 'Present' 
                  AND t.registration_status = 'Completed' 
                  AND c.id IS NULL
            ");
            
            if ($eligible_res->num_rows === 0) {
                $alert_message = '<div class="alert alert-info alert-dismissible fade show border-start border-4 border-info shadow-sm" role="alert">
                    <i class="fa-solid fa-circle-info text-info me-2"></i>No eligible attendees awaiting certificates found in the entire portal.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
            } else {
                while ($row = $eligible_res->fetch_assoc()) {
                    $res = generate_certificate($row['id'], $admin_id);
                    if ($res['success']) {
                        $success_count++;
                    } else {
                        $errors[] = $row['full_name'] . ": " . $res['message'];
                    }
                }
                
                $processing_report = [
                    'title' => 'Global Bulk Generation',
                    'success' => $success_count,
                    'skip' => $skip_count,
                    'errors' => $errors
                ];
            }
        }
    } catch (Exception $e) {
        $alert_message = '<div class="alert alert-danger alert-dismissible fade show border-start border-4 border-danger shadow-sm" role="alert">
            <i class="fa-solid fa-triangle-exclamation me-2"></i><strong>Generation Failed:</strong> ' . htmlspecialchars($e->getMessage()) . '
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
    }
}

// Fetch all eligible participants for the individual dropdown selection
$eligible_dropdown = [];
$dropdown_res = $conn->query("
    SELECT t.id, t.ticket_number, u.full_name, w.title as ws_title
    FROM tickets t
    JOIN attendance a ON t.id = a.ticket_id
    LEFT JOIN certificates c ON t.id = c.registration_id
    JOIN users u ON t.user_id = u.id
    JOIN workshops w ON t.event_id = w.id
    WHERE a.attendance_status = 'Present' 
      AND t.registration_status = 'Completed' 
      AND c.id IS NULL
    ORDER BY u.full_name ASC
");
if ($dropdown_res) {
    while ($row = $dropdown_res->fetch_assoc()) {
        $eligible_dropdown[] = $row;
    }
}

// Fetch workshops listing for bulk workshop selection
$workshops_dropdown = [];
$ws_dropdown_res = $conn->query("
    SELECT DISTINCT w.id, w.title
    FROM workshops w
    JOIN tickets t ON w.id = t.event_id
    JOIN attendance a ON t.id = a.ticket_id
    LEFT JOIN certificates c ON t.id = c.registration_id
    WHERE a.attendance_status = 'Present' 
      AND t.registration_status = 'Completed' 
      AND c.id IS NULL
    ORDER BY w.title ASC
");
if ($ws_dropdown_res) {
    while ($row = $ws_dropdown_res->fetch_assoc()) {
        $workshops_dropdown[] = $row;
    }
}

// Fetch global pending count
$pending_global_res = $conn->query("
    SELECT COUNT(*) as cnt
    FROM tickets t
    JOIN attendance a ON t.id = a.ticket_id
    LEFT JOIN certificates c ON t.id = c.registration_id
    WHERE a.attendance_status = 'Present' 
      AND t.registration_status = 'Completed' 
      AND c.id IS NULL
");
$pending_global_count = $pending_global_res ? $pending_global_res->fetch_assoc()['cnt'] : 0;

include $base_path . 'includes/header.php';
include $base_path . 'includes/navbar.php';
?>

<div class="container my-5">
    <!-- Header Greeting -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
        <div>
            <h1 class="h2 mb-0 fw-bold"><i class="fa-solid fa-wand-magic-sparkles text-primary me-2"></i>Certificate Generation Desk</h1>
            <p class="text-muted mb-0">Validate eligibility and generate digital credentials in single or bulk batches.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="manage.php" class="btn btn-outline-primary btn-sm fw-bold"><i class="fa-solid fa-list me-1"></i>Certificates Board</a>
            <a href="../dashboard.php" class="btn btn-outline-dark btn-sm"><i class="fa-solid fa-arrow-left me-1"></i>Back to Dashboard</a>
        </div>
    </div>

    <?php echo $alert_message; ?>

    <!-- Bulk Report Summary -->
    <?php if ($processing_report !== null): ?>
        <div class="card border-0 shadow-sm rounded-3 overflow-hidden mb-4 border-start border-4 border-warning">
            <div class="card-header bg-dark text-white py-3">
                <h5 class="mb-0"><i class="fa-solid fa-square-poll-horizontal text-warning me-2"></i><?php echo htmlspecialchars($processing_report['title']); ?> Processing Summary</h5>
            </div>
            <div class="card-body p-4 bg-white">
                <div class="row text-center mb-3">
                    <div class="col-md-6 border-end">
                        <h3 class="fw-bold text-success mb-1"><?php echo intval($processing_report['success']); ?></h3>
                        <span class="text-muted small fw-semibold">Certificates Successfully Generated</span>
                    </div>
                    <div class="col-md-6">
                        <h3 class="fw-bold text-danger mb-1"><?php echo count($processing_report['errors']); ?></h3>
                        <span class="text-muted small fw-semibold">Errors Encountered</span>
                    </div>
                </div>
                
                <?php if (count($processing_report['errors']) > 0): ?>
                    <div class="bg-light p-3 rounded border border-danger-subtle font-monospace small">
                        <h6 class="fw-bold text-danger"><i class="fa-solid fa-triangle-exclamation me-1"></i>Error Details:</h6>
                        <ul class="mb-0 ps-3 text-danger">
                            <?php foreach ($processing_report['errors'] as $err): ?>
                                <li><?php echo htmlspecialchars($err); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php else: ?>
                    <div class="alert alert-success py-2 mb-0 small"><i class="fa-solid fa-circle-check me-1"></i>All eligible certificates generated without errors!</div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- Tab Form 1: Single Certificate -->
        <div class="col-lg-6">
            <div class="card shadow-sm border-0 rounded-3 h-100 bg-white">
                <div class="card-header bg-dark text-white py-3">
                    <h5 class="mb-0"><i class="fa-solid fa-user-graduate text-warning me-2"></i>Generate Single Certificate</h5>
                </div>
                <div class="card-body p-4 d-flex flex-column justify-content-between">
                    <form method="POST">
                        <input type="hidden" name="action" value="single">
                        
                        <div class="mb-3">
                            <label for="ticket_id" class="form-label fw-bold text-dark">Select Eligible Participant</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted"><i class="fa-solid fa-user"></i></span>
                                <select name="ticket_id" id="ticket_id" class="form-select" required>
                                    <option value="">— Select student registration —</option>
                                    <?php foreach ($eligible_dropdown as $student): ?>
                                        <option value="<?php echo $student['id']; ?>">
                                            <?php echo htmlspecialchars($student['full_name']); ?> — <?php echo htmlspecialchars($student['ws_title']); ?> (<?php echo htmlspecialchars($student['ticket_number']); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-text mt-2 text-muted">Listing only participants marked <strong>Present</strong> whose registrations are <strong>Completed</strong> and do not yet have a certificate.</div>
                        </div>
                        
                        <button type="submit" class="btn btn-warning text-dark fw-bold w-100 mt-3 shadow-sm rounded-pill py-2">
                            <i class="fa-solid fa-wand-magic-sparkles me-2"></i>Generate Certificate
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Tab Form 2: Bulk by Workshop -->
        <div class="col-lg-6">
            <div class="card shadow-sm border-0 rounded-3 h-100 bg-white">
                <div class="card-header bg-dark text-white py-3">
                    <h5 class="mb-0"><i class="fa-solid fa-layer-group text-warning me-2"></i>Generate Workshop Batch</h5>
                </div>
                <div class="card-body p-4 d-flex flex-column justify-content-between">
                    <form method="POST">
                        <input type="hidden" name="action" value="workshop">
                        
                        <div class="mb-3">
                            <label for="workshop_id" class="form-label fw-bold text-dark">Select Workshop</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted"><i class="fa-solid fa-chalkboard-user"></i></span>
                                <select name="workshop_id" id="workshop_id" class="form-select" required>
                                    <option value="">— Select workshop —</option>
                                    <?php foreach ($workshops_dropdown as $ws): ?>
                                        <option value="<?php echo $ws['id']; ?>">
                                            <?php echo htmlspecialchars($ws['title']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-text mt-2 text-muted">Processes bulk generation for all eligible attendees of the chosen workshop in a single action.</div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100 mt-3 shadow-sm fw-bold rounded-pill py-2" onclick="return confirm('Generate certificates for all eligible attendees of this workshop?');">
                            <i class="fa-solid fa-paper-plane me-2"></i>Generate Workshop Batch
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Tab Form 3: Global System Bulk -->
        <div class="col-12">
            <div class="card shadow-sm border-0 rounded-3 bg-white">
                <div class="card-header bg-dark text-white py-3">
                    <h5 class="mb-0"><i class="fa-solid fa-circle-nodes text-warning me-2"></i>Global Bulk Generation Desk</h5>
                </div>
                <div class="card-body p-4 text-center">
                    <div class="display-6 text-warning mb-3"><i class="fa-solid fa-scroll"></i></div>
                    <h4 class="fw-bold mb-2">Global Generation Dashboard</h4>
                    <p class="text-muted mb-4 px-md-5">
                        There are currently <span class="badge bg-warning text-dark fw-bold px-3 py-1.5 fs-7 rounded-pill"><?php echo intval($pending_global_count); ?></span> eligible participant check-ins awaiting digital credentials. Running a global bulk action will process and issue certificates for all outstanding records.
                    </p>
                    
                    <form method="POST">
                        <input type="hidden" name="action" value="bulk">
                        <?php if ($pending_global_count > 0): ?>
                            <button type="submit" class="btn btn-warning text-dark fw-bold px-5 py-2.5 shadow-sm rounded-pill" onclick="return confirm('WARNING: You are about to generate certificates globally for <?php echo $pending_global_count; ?> outstanding check-ins. Proceed?');">
                               <i class="fa-solid fa-bolt me-2 text-primary"></i>Run Global Bulk Generation (<?php echo $pending_global_count; ?> Pending)
                            </button>
                        <?php else: ?>
                            <button type="button" class="btn btn-outline-secondary px-5 py-2.5 rounded-pill" disabled>
                                <i class="fa-solid fa-ban me-2"></i>No Pending Generations
                            </button>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<?php
include $base_path . 'includes/footer.php';
?>
