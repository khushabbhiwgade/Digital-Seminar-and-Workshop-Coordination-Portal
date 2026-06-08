<?php
// admin/certificates/manage.php
// ------------------------------------------------------------
// Admin — Certificates Board & Management Panel
// ------------------------------------------------------------

$base_path = '../../';
$page_title = 'Manage Certificates - Admin Panel';
$active_page = 'admin_certificates';

require_once $base_path . 'includes/auth.php';
require_role('admin');
require_once $base_path . 'config/db_connect.php';
require_once $base_path . 'includes/certificate_helper.php';

$alert_message = '';

// Handle Administrative Actions (Part 12 & Part 13)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['id'])) {
    $cert_id = intval($_POST['id']);
    $action = $_POST['action'];
    $admin_id = get_user_id();
    
    try {
        // Fetch certificate details
        $stmt = $conn->prepare("
            SELECT c.id, c.certificate_no, c.verification_code, c.certificate_path, c.registration_id,
                   u.full_name, w.title as ws_title
            FROM certificates c
            JOIN users u ON c.user_id = u.id
            JOIN workshops w ON c.event_id = w.id
            WHERE c.id = ?
        ");
        $stmt->bind_param("i", $cert_id);
        $stmt->execute();
        $cert = $stmt->get_result()->fetch_assoc();
        
        if (!$cert) {
            throw new Exception("Certificate record does not exist.");
        }
        
        if ($action === 'revoke') {
            // Revoke Certificate (Part 13)
            $upd = $conn->prepare("UPDATE certificates SET status = 'Revoked' WHERE id = ?");
            $upd->bind_param("i", $cert_id);
            $upd->execute();
            
            // Log administrative action
            $log_details = "Certificate revoked by administrator for student: " . $cert['full_name'] . ". Certificate: " . $cert['certificate_no'] . ". Status set to Revoked.";
            $log_stmt = $conn->prepare("INSERT INTO activity_logs (user_id, action, details) VALUES (?, 'Certificate Revocation', ?)");
            $log_stmt->bind_param("is", $admin_id, $log_details);
            $log_stmt->execute();
            
            $alert_message = '<div class="alert alert-warning alert-dismissible fade show border-start border-4 border-warning shadow-sm" role="alert">
                <i class="fa-solid fa-circle-check text-warning me-2"></i><strong>Certificate Revoked:</strong> Code <code>' . htmlspecialchars($cert['certificate_no']) . '</code> has been marked as <strong>Revoked</strong>.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
                
        } elseif ($action === 'regenerate') {
            // Re-generate Certificate PDF (Part 12)
            // Re-trigger PDF generation preserving original details
            $registration_id = intval($cert['registration_id']);
            $certificate_no = $cert['certificate_no'];
            $verification_code = $cert['verification_code'];
            
            // Fetch complete original details needed for rendering
            $details_q = $conn->prepare("
                SELECT u.full_name, w.title as ws_title, w.domain as ws_domain, w.host as ws_host, w.end_date as ws_date
                FROM tickets t
                JOIN users u ON t.user_id = u.id
                JOIN workshops w ON t.event_id = w.id
                WHERE t.id = ?
            ");
            $details_q->bind_param("i", $registration_id);
            $details_q->execute();
            $tk = $details_q->get_result()->fetch_assoc();
            
            if (!$tk) {
                throw new Exception("Registration details no longer exist.");
            }
            
            $student_name = $tk['full_name'];
            $workshop_name = $tk['ws_title'];
            $workshop_domain = $tk['ws_domain'];
            $host_org = $tk['ws_host'];
            $completion_date = $tk['ws_date'];
            
            // Re-generate QR Code image (in case it was deleted)
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $verification_url = "$protocol://$host/seminar_portal/verify_certificate.php?code=" . urlencode($verification_code);
            
            $qrcodes_dir = $base_path . 'storage/qrcodes/';
            if (!is_dir($qrcodes_dir)) mkdir($qrcodes_dir, 0777, true);
            
            $qr_absolute_path = $qrcodes_dir . $verification_code . '.png';
            $api_url = "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=" . urlencode($verification_url);
            
            $ctx = stream_context_create(['http' => ['timeout' => 3.0]]);
            $qr_image = @file_get_contents($api_url, false, $ctx);
            if ($qr_image !== false) {
                file_put_contents($qr_absolute_path, $qr_image);
            }
            
            // Re-generate PDF with TCPDF
            require_once $base_path . 'includes/tcpdf/tcpdf.php';
            
            $certificates_dir = $base_path . 'storage/certificates/';
            if (!is_dir($certificates_dir)) mkdir($certificates_dir, 0777, true);
            $pdf_absolute_path = $certificates_dir . $certificate_no . '.pdf';
            
            class ManagePDF extends TCPDF {
                public function Header() {
                    $this->SetLineStyle(['width' => 1.5, 'color' => [234, 179, 8]]);
                    $this->Rect(8, 8, $this->getPageWidth() - 16, $this->getPageHeight() - 16);
                    $this->SetLineStyle(['width' => 0.5, 'color' => [15, 23, 42]]);
                    $this->Rect(10, 10, $this->getPageWidth() - 20, $this->getPageHeight() - 20);
                }
                public function Footer() {}
            }
            
            $pdf = new ManagePDF('L', 'mm', 'A4', true, 'UTF-8', false);
            $pdf->SetCreator('Campus Connect Portal');
            $pdf->SetTitle('Certificate - ' . $certificate_no);
            $pdf->SetMargins(15, 15, 15);
            $pdf->SetAutoPageBreak(false);
            $pdf->AddPage();
            
            // Brand headers
            $pdf->SetFont('helvetica', 'B', 14);
            $pdf->SetTextColor(15, 23, 42);
            $pdf->Cell(0, 8, 'CAMPUS CONNECT PORTAL', 0, 1, 'C');
            $pdf->SetFont('helvetica', 'I', 9);
            $pdf->SetTextColor(100, 116, 139);
            $pdf->Cell(0, 4, 'Digital Seminar and Workshop Coordination Desk', 0, 1, 'C');
            $pdf->Ln(4);
            $pdf->SetDrawColor(234, 179, 8);
            $pdf->SetLineWidth(0.8);
            $pdf->Line(100, 31, 197, 31);
            $pdf->Ln(10);
            
            // Title
            $pdf->SetFont('times', 'B', 28);
            $pdf->SetTextColor(15, 23, 42);
            $pdf->Cell(0, 12, 'CERTIFICATE OF COMPLETION', 0, 1, 'C');
            $pdf->Ln(4);
            
            $pdf->SetFont('times', 'I', 15);
            $pdf->SetTextColor(51, 65, 85);
            $pdf->Cell(0, 8, 'This is proudly presented to', 0, 1, 'C');
            
            $pdf->Ln(2);
            $pdf->SetFont('times', 'B', 24);
            $pdf->SetTextColor(30, 41, 59);
            $pdf->Cell(0, 10, strtoupper($student_name), 0, 1, 'C');
            $pdf->SetDrawColor(15, 23, 42);
            $pdf->SetLineWidth(0.5);
            $pdf->Line(60, 93, 237, 93);
            
            $pdf->Ln(8);
            $pdf->SetFont('times', 'I', 14);
            $pdf->SetTextColor(51, 65, 85);
            $pdf->Cell(0, 6, 'for successfully attending and completing the digital workshop on', 0, 1, 'C');
            
            $pdf->Ln(3);
            $pdf->SetFont('times', 'B', 18);
            $pdf->SetTextColor(234, 179, 8);
            $pdf->Cell(0, 8, '"' . $workshop_name . '"', 0, 1, 'C');
            $pdf->SetFont('helvetica', 'B', 10);
            $pdf->SetTextColor(71, 85, 105);
            $pdf->Cell(0, 6, 'DOMAIN: ' . strtoupper($workshop_domain), 0, 1, 'C');
            
            $pdf->Ln(6);
            $pdf->SetFont('times', 'I', 13);
            $pdf->SetTextColor(51, 65, 85);
            $date_formatted = date('F d, Y', strtotime($completion_date));
            $pdf->Cell(0, 6, 'hosted by ' . $host_org . ' on ' . $date_formatted . '.', 0, 1, 'C');
            
            // Footer Info
            $pdf->SetY(148);
            $pdf->SetX(20);
            $pdf->SetFont('helvetica', 'B', 8);
            $pdf->SetTextColor(100, 116, 139);
            $pdf->Cell(80, 4, 'CERTIFICATE DETAILS', 0, 1, 'L');
            $pdf->SetFont('courier', '', 9);
            $pdf->SetTextColor(15, 23, 42);
            $pdf->SetX(20);
            $pdf->Cell(80, 4, 'Number: ' . $certificate_no, 0, 1, 'L');
            $pdf->SetX(20);
            $pdf->Cell(80, 4, 'Verify: ' . $verification_code, 0, 1, 'L');
            
            $pdf->SetY(142);
            $pdf->SetX(100);
            $pdf->SetFont('times', 'I', 16);
            $pdf->SetTextColor(15, 23, 42);
            $pdf->Cell(97, 8, 'Coordination Desk', 0, 1, 'C');
            $pdf->SetDrawColor(148, 163, 184);
            $pdf->SetLineWidth(0.3);
            $pdf->Line(115, 151, 182, 151);
            $pdf->SetY(152);
            $pdf->SetX(100);
            $pdf->SetFont('helvetica', 'B', 8);
            $pdf->SetTextColor(100, 116, 139);
            $pdf->Cell(97, 4, 'AUTHORIZED SIGNATURE', 0, 1, 'C');
            
            $pdf->Image($qr_absolute_path, 235, 140, 32, 32, 'PNG');
            $pdf->SetY(172);
            $pdf->SetX(215);
            $pdf->SetFont('helvetica', '', 7);
            $pdf->SetTextColor(100, 116, 139);
            $pdf->Cell(70, 4, 'Scan to Verify Authenticity', 0, 1, 'C');
            
            $pdf->Output($pdf_absolute_path, 'F');
            
            // Set status to Generated in case it was revoked before
            $upd2 = $conn->prepare("UPDATE certificates SET status = 'Generated' WHERE id = ?");
            $upd2->bind_param("i", $cert_id);
            $upd2->execute();
            
            // Log administrative action
            $log_details = "Certificate regenerated by administrator for student: " . $cert['full_name'] . ". Certificate: " . $cert['certificate_no'] . ". PDF overwritten.";
            $log_stmt = $conn->prepare("INSERT INTO activity_logs (user_id, action, details) VALUES (?, 'Certificate Regeneration', ?)");
            $log_stmt->bind_param("is", $admin_id, $log_details);
            $log_stmt->execute();
            
            $alert_message = '<div class="alert alert-success alert-dismissible fade show border-start border-4 border-success shadow-sm" role="alert">
                <i class="fa-solid fa-circle-check text-success me-2"></i><strong>Certificate Re-generated:</strong> PDF layout for <code>' . htmlspecialchars($cert['certificate_no']) . '</code> has been successfully overwritten.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
        }
    } catch (Exception $e) {
        $alert_message = '<div class="alert alert-danger alert-dismissible fade show border-start border-4 border-danger shadow-sm" role="alert">
            <i class="fa-solid fa-triangle-exclamation me-2"></i><strong>Action Failed:</strong> ' . htmlspecialchars($e->getMessage()) . '
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
    }
}

// Handle administrative PDF download directly (Part 12)
if (isset($_GET['action']) && $_GET['action'] === 'download' && isset($_GET['id'])) {
    $cert_id = intval($_GET['id']);
    
    try {
        $stmt = $conn->prepare("SELECT certificate_path FROM certificates WHERE id = ?");
        $stmt->bind_param("i", $cert_id);
        $stmt->execute();
        $c = $stmt->get_result()->fetch_assoc();
        
        if (!$c) {
            throw new Exception("Certificate file record not found.");
        }
        
        $full_path = $base_path . $c['certificate_path'];
        if (empty($c['certificate_path']) || !file_exists($full_path)) {
            throw new Exception("File not found on disk: " . $c['certificate_path']);
        }
        
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . basename($c['certificate_path']) . '"');
        header('Content-Length: ' . filesize($full_path));
        
        ob_clean();
        flush();
        readfile($full_path);
        exit;
    } catch (Exception $e) {
        $alert_message = '<div class="alert alert-danger alert-dismissible fade show border-start border-4 border-danger shadow-sm" role="alert">
            <i class="fa-solid fa-triangle-exclamation me-2"></i><strong>Download Failed:</strong> ' . htmlspecialchars($e->getMessage()) . '
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
    }
}

// Fetch all generated certificates in the system
$certs = [];
$certs_res = $conn->query("
    SELECT c.id, c.certificate_no, c.verification_code, c.generated_at, c.status,
           u.full_name, u.email,
           w.title as ws_title
    FROM certificates c
    JOIN users u ON c.user_id = u.id
    JOIN workshops w ON c.event_id = w.id
    ORDER BY c.generated_at DESC
");
if ($certs_res) {
    while ($row = $certs_res->fetch_assoc()) {
        $certs[] = $row;
    }
}

// Fetch pending certificates (eligible but not generated) to show in the board
$pending_res = $conn->query("
    SELECT NULL as id, 'PENDING' as certificate_no, 'PENDING' as verification_code, NULL as generated_at, 'Pending' as status,
           u.full_name, u.email,
           w.title as ws_title
    FROM tickets t
    JOIN attendance a ON t.id = a.ticket_id
    LEFT JOIN certificates c ON t.id = c.registration_id
    JOIN users u ON t.user_id = u.id
    JOIN workshops w ON t.event_id = w.id
    WHERE a.attendance_status = 'Present' 
      AND t.registration_status = 'Completed' 
      AND c.id IS NULL
    ORDER BY t.created_at DESC
");
if ($pending_res) {
    while ($row = $pending_res->fetch_assoc()) {
        $certs[] = $row;
    }
}

// Calculate certificate stats
$cnt_total = 0;
$cnt_today = 0;
$cnt_verified = 0;
$cnt_pending = 0;

$res_total = $conn->query("SELECT COUNT(*) as cnt FROM certificates");
if ($res_total) $cnt_total = intval($res_total->fetch_assoc()['cnt']);

$res_today = $conn->query("SELECT COUNT(*) as cnt FROM certificates WHERE DATE(generated_at) = CURDATE()");
if ($res_today) $cnt_today = intval($res_today->fetch_assoc()['cnt']);

$res_verified = $conn->query("SELECT COUNT(*) as cnt FROM certificates WHERE status = 'Generated'");
if ($res_verified) $cnt_verified = intval($res_verified->fetch_assoc()['cnt']);

$res_pending_q = $conn->query("
    SELECT COUNT(*) as cnt
    FROM tickets t
    JOIN attendance a ON t.id = a.ticket_id
    LEFT JOIN certificates c ON t.id = c.registration_id
    WHERE a.attendance_status = 'Present' 
      AND t.registration_status = 'Completed' 
      AND c.id IS NULL
");
if ($res_pending_q) $cnt_pending = intval($res_pending_q->fetch_assoc()['cnt']);

include $base_path . 'includes/header.php';
include $base_path . 'includes/navbar.php';
?>

<div class="container my-5">
    <!-- Header banner -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
        <div>
            <h1 class="h2 mb-0 fw-bold"><i class="fa-solid fa-scroll text-primary me-2"></i>Certificates Board</h1>
            <p class="text-muted mb-0">Monitor, download, re-generate, and revoke academic credentials.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="generate.php" class="btn btn-warning text-dark fw-bold btn-sm shadow-sm"><i class="fa-solid fa-wand-magic-sparkles me-1"></i>Generate Desk</a>
            <a href="../dashboard.php" class="btn btn-outline-dark btn-sm"><i class="fa-solid fa-arrow-left me-1"></i>Back to Dashboard</a>
        </div>
    </div>

    <?php echo $alert_message; ?>

    <!-- Certificates Stat Cards -->
    <div class="row g-4 mb-4">
        <div class="col-md-3 col-sm-6">
            <div class="card h-100 border-0 shadow-sm rounded-3 p-3 bg-white text-center border-start border-4 border-primary">
                <div class="display-6 text-primary mb-2"><i class="fa-solid fa-certificate"></i></div>
                <h3 class="fw-bold mb-1"><?php echo $cnt_total; ?></h3>
                <span class="text-muted small fw-semibold">Total Certificates</span>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card h-100 border-0 shadow-sm rounded-3 p-3 bg-white text-center border-start border-4 border-success">
                <div class="display-6 text-success mb-2"><i class="fa-solid fa-calendar-day"></i></div>
                <h3 class="fw-bold mb-1"><?php echo $cnt_today; ?></h3>
                <span class="text-muted small fw-semibold">Generated Today</span>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card h-100 border-0 shadow-sm rounded-3 p-3 bg-white text-center border-start border-4 border-info">
                <div class="display-6 text-info mb-2"><i class="fa-solid fa-shield-check"></i></div>
                <h3 class="fw-bold mb-1"><?php echo $cnt_verified; ?></h3>
                <span class="text-muted small fw-semibold">Verified / Active</span>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card h-100 border-0 shadow-sm rounded-3 p-3 bg-white text-center border-start border-4 border-warning">
                <div class="display-6 text-warning mb-2"><i class="fa-solid fa-hourglass-half"></i></div>
                <h3 class="fw-bold mb-1"><?php echo $cnt_pending; ?></h3>
                <span class="text-muted small fw-semibold">Pending Issues</span>
            </div>
        </div>
    </div>

    <!-- Search & Filters -->
    <div class="card shadow-sm border-0 rounded-3 bg-white mb-4">
        <div class="card-body p-3">
            <div class="row g-3 align-items-center">
                <div class="col-md-3">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light text-muted border-end-0"><i class="fa-solid fa-user"></i></span>
                        <input type="text" id="searchParticipant" class="form-control border-start-0 ps-0" placeholder="Search Participant...">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light text-muted border-end-0"><i class="fa-solid fa-hashtag"></i></span>
                        <input type="text" id="searchCertNo" class="form-control border-start-0 ps-0" placeholder="Search Certificate No...">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light text-muted border-end-0"><i class="fa-solid fa-chalkboard-user"></i></span>
                        <input type="text" id="searchEvent" class="form-control border-start-0 ps-0" placeholder="Search Event/Workshop...">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light text-muted"><i class="fa-solid fa-filter"></i></span>
                        <select id="filterStatus" class="form-select">
                            <option value="">All Statuses</option>
                            <option value="Generated">Generated</option>
                            <option value="Verified">Verified / Active</option>
                            <option value="Pending">Pending</option>
                            <option value="Revoked">Revoked</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Certificates Table -->
    <div class="card shadow-sm border-0 rounded-3 overflow-hidden bg-white mb-5">
        <div class="card-header bg-dark text-white py-3 d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fa-solid fa-award text-warning me-2"></i>Issued Credentials Journal</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="certsTable">
                    <thead class="table-dark">
                        <tr>
                            <th class="ps-4">Participant</th>
                            <th>Workshop Title</th>
                            <th>Certificate Number</th>
                            <th>Verification Code</th>
                            <th>Issue Date</th>
                            <th class="text-center">Status</th>
                            <th class="text-center pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($certs) > 0): ?>
                            <?php foreach ($certs as $c): ?>
                                <?php
                                $status_val = $c['status'];
                                $badge_html = '';
                                if ($status_val === 'Revoked') {
                                    $badge_html = '<span class="badge bg-danger px-2.5 py-1 text-uppercase fs-9"><i class="fa-solid fa-ban me-1"></i>Revoked</span>';
                                } elseif ($status_val === 'Pending') {
                                    $badge_html = '<span class="badge bg-warning text-dark px-2.5 py-1 text-uppercase fs-9"><i class="fa-solid fa-clock me-1"></i>Pending</span>';
                                } else {
                                    $badge_html = '<span class="badge bg-success px-2.5 py-1 text-uppercase fs-9 mb-1"><i class="fa-solid fa-check me-1"></i>Generated</span> <span class="badge bg-primary px-2.5 py-1 text-uppercase fs-9 d-block d-md-inline-block">Verified</span>';
                                }
                                ?>
                                <tr data-search-name="<?php echo htmlspecialchars($c['full_name']); ?>"
                                    data-search-email="<?php echo htmlspecialchars($c['email']); ?>"
                                    data-search-cert-no="<?php echo htmlspecialchars($c['certificate_no']); ?>"
                                    data-search-event="<?php echo htmlspecialchars($c['ws_title']); ?>"
                                    data-search-status="<?php echo htmlspecialchars($status_val); ?>">
                                    <td class="ps-4">
                                        <div class="fw-bold text-dark"><?php echo htmlspecialchars($c['full_name']); ?></div>
                                        <div class="text-muted small"><?php echo htmlspecialchars($c['email']); ?></div>
                                    </td>
                                    <td>
                                        <div class="small fw-semibold text-dark" style="max-width: 250px;"><?php echo htmlspecialchars($c['ws_title']); ?></div>
                                    </td>
                                    <td>
                                        <?php if ($status_val === 'Pending'): ?>
                                            <span class="text-muted small">—</span>
                                        <?php else: ?>
                                            <code class="font-monospace fw-bold text-primary"><?php echo htmlspecialchars($c['certificate_no']); ?></code>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($status_val === 'Pending'): ?>
                                            <span class="text-muted small">—</span>
                                        <?php else: ?>
                                            <code class="font-monospace fw-bold text-success"><?php echo htmlspecialchars($c['verification_code']); ?></code>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-muted small">
                                        <?php if ($c['generated_at']): ?>
                                            <?php echo date("M d, Y h:i A", strtotime($c['generated_at'])); ?>
                                        <?php else: ?>
                                            <span class="text-secondary">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php echo $badge_html; ?>
                                    </td>
                                    <td class="text-center pe-4">
                                        <div class="d-inline-flex gap-1.5 align-items-center">
                                            <!-- View Details Modal Button -->
                                            <button type="button" class="btn btn-sm btn-outline-dark rounded-pill px-2.5" 
                                                    data-bs-toggle="modal" data-bs-target="#certificateDetailsModal"
                                                    data-name="<?php echo htmlspecialchars($c['full_name']); ?>"
                                                    data-email="<?php echo htmlspecialchars($c['email']); ?>"
                                                    data-workshop="<?php echo htmlspecialchars($c['ws_title']); ?>"
                                                    data-cert-no="<?php echo htmlspecialchars($c['certificate_no']); ?>"
                                                    data-verify-code="<?php echo htmlspecialchars($c['verification_code']); ?>"
                                                    data-status="<?php echo htmlspecialchars($status_val); ?>"
                                                    data-issue-date="<?php echo $c['generated_at'] ? date("M d, Y h:i A", strtotime($c['generated_at'])) : ''; ?>"
                                                    title="View Dossier">
                                                <i class="fa-solid fa-eye"></i>
                                            </button>

                                            <?php if ($status_val === 'Pending'): ?>
                                                <!-- Generate shortcut button -->
                                                <a href="generate.php" class="btn btn-sm btn-warning text-dark rounded-pill px-3 fw-bold shadow-sm" title="Go to Generate Desk">
                                                    <i class="fa-solid fa-wand-magic-sparkles me-1"></i>Generate
                                                </a>
                                            <?php elseif ($status_val !== 'Revoked'): ?>
                                                <!-- View Verification Page -->
                                                <a href="../../verify_certificate.php?code=<?php echo urlencode($c['verification_code']); ?>" target="_blank" class="btn btn-sm btn-outline-primary rounded-pill px-2" title="Verify Online">
                                                    <i class="fa-solid fa-shield-halved"></i>
                                                </a>
                                                <!-- Download PDF -->
                                                <a href="manage.php?action=download&id=<?php echo $c['id']; ?>" class="btn btn-sm btn-success text-white rounded-pill px-2" title="Download PDF">
                                                    <i class="fa-solid fa-download"></i>
                                                </a>
                                                <!-- Re-generate -->
                                                <form method="POST" class="d-inline mb-0" onsubmit="return confirm('Re-generate PDF for <?php echo htmlspecialchars($c['certificate_no']); ?>? This will overwrite the file on disk.');">
                                                    <input type="hidden" name="action" value="regenerate">
                                                    <input type="hidden" name="id" value="<?php echo $c['id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-warning text-dark rounded-pill px-2" title="Re-generate PDF">
                                                        <i class="fa-solid fa-rotate"></i>
                                                    </button>
                                                </form>
                                                <!-- Revoke -->
                                                <form method="POST" class="d-inline mb-0" onsubmit="return confirm('WARNING: Are you sure you want to REVOKE certificate <?php echo htmlspecialchars($c['certificate_no']); ?>? This cannot be undone easily.');">
                                                    <input type="hidden" name="action" value="revoke">
                                                    <input type="hidden" name="id" value="<?php echo $c['id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-danger rounded-pill px-2" title="Revoke Certificate">
                                                        <i class="fa-solid fa-ban"></i>
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <!-- Revoked details but allow regeneration if needed -->
                                                <form method="POST" class="d-inline mb-0" onsubmit="return confirm('Restore and Re-generate PDF for <?php echo htmlspecialchars($c['certificate_no']); ?>?');">
                                                    <input type="hidden" name="action" value="regenerate">
                                                    <input type="hidden" name="id" value="<?php echo $c['id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-warning text-dark rounded-pill px-3 fw-bold" title="Re-issue & Regenerate">
                                                        <i class="fa-solid fa-rotate-right me-1"></i>Re-issue
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-5">
                                    <i class="fa-solid fa-award fs-2 d-block mb-2 text-secondary"></i>
                                    No certificates have been issued by the system yet.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Certificate Details Modal -->
<div class="modal fade" id="certificateDetailsModal" tabindex="-1" aria-labelledby="certificateDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <div class="modal-header bg-dark text-white" style="border-bottom: 2px solid #eab308;">
                <h5 class="modal-title fw-bold text-white" id="certificateDetailsModalLabel">
                    <i class="fa-solid fa-award text-warning me-2"></i>Certificate Dossier
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="p-3 bg-light rounded border border-light-subtle mb-4 border-start border-3 border-primary">
                    <h6 class="fw-bold text-primary mb-3"><i class="fa-solid fa-user-graduate me-2"></i>Recipient Information</h6>
                    <div class="mb-2"><strong>Name:</strong> <span id="m_recipient_name">-</span></div>
                    <div><strong>Email:</strong> <span id="m_recipient_email">-</span></div>
                </div>
                <div class="p-3 bg-light rounded border border-light-subtle mb-4 border-start border-3 border-warning">
                    <h6 class="fw-bold text-warning mb-3"><i class="fa-solid fa-chalkboard-user me-2"></i>Workshop Details</h6>
                    <div class="mb-2"><strong>Title:</strong> <span id="m_workshop_title">-</span></div>
                </div>
                <div class="p-3 bg-light rounded border border-light-subtle border-start border-3 border-success">
                    <h6 class="fw-bold text-success mb-3"><i class="fa-solid fa-shield-halved me-2"></i>Credential Details</h6>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <span class="text-muted small d-block">Certificate No.</span>
                            <code class="font-monospace fw-bold text-primary" id="m_cert_no">-</code>
                        </div>
                        <div class="col-md-6">
                            <span class="text-muted small d-block">Verification Code</span>
                            <code class="font-monospace fw-bold text-success" id="m_verify_code">-</code>
                        </div>
                        <div class="col-md-6">
                            <span class="text-muted small d-block">Status</span>
                            <div id="m_cert_status">-</div>
                        </div>
                        <div class="col-md-6">
                            <span class="text-muted small d-block">Issue Date</span>
                            <span class="text-dark fw-semibold" id="m_issue_date">-</span>
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
    // Certificate Details Modal Populating Logic
    const detailModal = document.getElementById('certificateDetailsModal');
    if (detailModal) {
        detailModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            
            const name = button.getAttribute('data-name');
            const email = button.getAttribute('data-email');
            const workshop = button.getAttribute('data-workshop');
            const certNo = button.getAttribute('data-cert-no') || 'Not Generated';
            const verifyCode = button.getAttribute('data-verify-code') || 'Not Generated';
            const status = button.getAttribute('data-status');
            const issueDate = button.getAttribute('data-issue-date') || 'Not Issued Yet';
            
            document.getElementById('m_recipient_name').textContent = name;
            document.getElementById('m_recipient_email').textContent = email;
            document.getElementById('m_workshop_title').textContent = workshop;
            document.getElementById('m_cert_no').textContent = certNo;
            document.getElementById('m_verify_code').textContent = verifyCode;
            document.getElementById('m_issue_date').textContent = issueDate;
            
            let badgeHtml = '';
            if (status === 'Revoked') {
                badgeHtml = '<span class="badge bg-danger px-2.5 py-1 text-uppercase fs-9"><i class="fa-solid fa-ban me-1"></i>Revoked</span>';
            } else if (status === 'Pending') {
                badgeHtml = '<span class="badge bg-warning text-dark px-2.5 py-1 text-uppercase fs-9"><i class="fa-solid fa-clock me-1"></i>Pending</span>';
            } else {
                badgeHtml = '<span class="badge bg-success px-2.5 py-1 text-uppercase fs-9 mb-1"><i class="fa-solid fa-check me-1"></i>Generated</span> <span class="badge bg-primary px-2.5 py-1 text-uppercase fs-9 d-block d-md-inline-block">Verified</span>';
            }
            document.getElementById('m_cert_status').innerHTML = badgeHtml;
        });
    }

    // Search and dynamic filtering logic
    function filterCertsTable() {
        let partVal = document.getElementById('searchParticipant').value.toLowerCase();
        let certVal = document.getElementById('searchCertNo').value.toLowerCase();
        let eventVal = document.getElementById('searchEvent').value.toLowerCase();
        let statusVal = document.getElementById('filterStatus').value;
        
        let rows = document.querySelectorAll('#certsTable tbody tr');
        let matchedCount = 0;
        
        rows.forEach(row => {
            if (row.classList.contains('no-records-row')) return;
            
            let name = row.getAttribute('data-search-name').toLowerCase();
            let email = row.getAttribute('data-search-email').toLowerCase();
            let certNo = row.getAttribute('data-search-cert-no').toLowerCase();
            let event = row.getAttribute('data-search-event').toLowerCase();
            let status = row.getAttribute('data-search-status'); // 'Generated', 'Revoked', 'Pending'
            
            let matchesPart = name.includes(partVal) || email.includes(partVal);
            let matchesCert = certNo.includes(certVal);
            let matchesEvent = event.includes(eventVal);
            
            let matchesStatus = true;
            if (statusVal === 'Generated') {
                matchesStatus = (status === 'Generated');
            } else if (statusVal === 'Pending') {
                matchesStatus = (status === 'Pending');
            } else if (statusVal === 'Verified') {
                matchesStatus = (status === 'Generated');
            } else if (statusVal === 'Revoked') {
                matchesStatus = (status === 'Revoked');
            }
            
            if (matchesPart && matchesCert && matchesEvent && matchesStatus) {
                row.style.display = '';
                matchedCount++;
            } else {
                row.style.display = 'none';
            }
        });
        
        let noRec = document.getElementById('noRecordsRow');
        if (matchedCount === 0) {
            if (!noRec) {
                let tbody = document.querySelector('#certsTable tbody');
                let tr = document.createElement('tr');
                tr.id = 'noRecordsRow';
                tr.className = 'no-records-row';
                tr.innerHTML = '<td colspan="7" class="text-center text-muted py-5"><i class="fa-solid fa-magnifying-glass fs-3 d-block mb-2"></i>No matching certificates found.</td>';
                tbody.appendChild(tr);
            } else {
                noRec.style.display = '';
            }
        } else if (noRec) {
            noRec.style.display = 'none';
        }
    }
    
    document.getElementById('searchParticipant').addEventListener('keyup', filterCertsTable);
    document.getElementById('searchCertNo').addEventListener('keyup', filterCertsTable);
    document.getElementById('searchEvent').addEventListener('keyup', filterCertsTable);
    document.getElementById('filterStatus').addEventListener('change', filterCertsTable);
});
</script>

<style>
.fs-9 {
    font-size: 11px !important;
}
.gap-1.5 {
    gap: 0.35rem !important;
}
</style>

<?php
include $base_path . 'includes/footer.php';
?>

