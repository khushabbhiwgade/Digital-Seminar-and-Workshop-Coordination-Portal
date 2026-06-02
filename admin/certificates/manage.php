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
?>

<div class="container my-5">
    <!-- Header banner -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h2 mb-0"><i class="fa-solid fa-scroll text-primary me-2"></i>Certificates Board</h1>
            <p class="text-muted mb-0">Monitor, download, re-generate, and revoke academic credentials.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="generate.php" class="btn btn-warning text-dark fw-bold btn-sm shadow-sm"><i class="fa-solid fa-wand-magic-sparkles me-1"></i>Generate Desk</a>
            <a href="../dashboard.php" class="btn btn-outline-dark btn-sm"><i class="fa-solid fa-arrow-left me-1"></i>Back to Dashboard</a>
        </div>
    </div>

    <?php echo $alert_message; ?>

    <!-- Certificates Table -->
    <div class="card shadow-sm border-0 rounded-3 overflow-hidden bg-white mb-5">
        <div class="card-header bg-dark text-white py-3 d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fa-solid fa-award text-warning me-2"></i>Issued Credentials Journal</h5>
            <input type="text" id="certsSearchInput" class="form-control form-control-sm w-25" placeholder="Search name, code, or workshop...">
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="certsTable">
                    <thead class="table-dark">
                        <tr>
                            <th>Participant</th>
                            <th>Workshop Title</th>
                            <th>Certificate Number</th>
                            <th>Verification Code</th>
                            <th>Generation Date</th>
                            <th class="text-center">Status</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($certs) > 0): ?>
                            <?php foreach ($certs as $c): ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold text-dark"><?php echo htmlspecialchars($c['full_name']); ?></div>
                                        <div class="text-muted small"><?php echo htmlspecialchars($c['email']); ?></div>
                                    </td>
                                    <td class="small fw-semibold text-dark"><?php echo htmlspecialchars($c['ws_title']); ?></td>
                                    <td><code class="font-monospace fw-bold text-primary"><?php echo htmlspecialchars($c['certificate_no']); ?></code></td>
                                    <td><code class="font-monospace fw-bold text-success"><?php echo htmlspecialchars($c['verification_code']); ?></code></td>
                                    <td class="text-muted small"><?php echo date("M d, Y h:i A", strtotime($c['generated_at'])); ?></td>
                                    <td class="text-center">
                                        <?php if ($c['status'] === 'Revoked'): ?>
                                            <span class="badge bg-danger"><i class="fa-solid fa-ban me-1"></i>Revoked</span>
                                        <?php else: ?>
                                            <span class="badge bg-success"><i class="fa-solid fa-check me-1"></i>Generated</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <div class="d-inline-flex gap-1.5">
                                            <!-- View verification page -->
                                            <a href="../../verify_certificate.php?code=<?php echo urlencode($c['verification_code']); ?>" target="_blank" class="btn btn-xs btn-outline-primary" title="View Public Verification Page">
                                                <i class="fa-solid fa-eye"></i>
                                            </a>
                                            <!-- Download PDF -->
                                            <a href="manage.php?action=download&id=<?php echo $c['id']; ?>" class="btn btn-xs btn-success text-white" title="Download PDF Certificate">
                                                <i class="fa-solid fa-download"></i>
                                            </a>
                                            <!-- Re-generate -->
                                            <form method="POST" class="d-inline" onsubmit="return confirm('Re-generate PDF for <?php echo htmlspecialchars($c['certificate_no']); ?>? This will overwrite the file on disk.');">
                                                <input type="hidden" name="action" value="regenerate">
                                                <input type="hidden" name="id" value="<?php echo $c['id']; ?>">
                                                <button type="submit" class="btn btn-xs btn-warning text-dark" title="Re-generate PDF Layout">
                                                    <i class="fa-solid fa-rotate"></i>
                                                </button>
                                            </form>
                                            <!-- Revoke -->
                                            <?php if ($c['status'] !== 'Revoked'): ?>
                                                <form method="POST" class="d-inline" onsubmit="return confirm('WARNING: Are you sure you want to REVOKE certificate <?php echo htmlspecialchars($c['certificate_no']); ?>? This cannot be undone easily.');">
                                                    <input type="hidden" name="action" value="revoke">
                                                    <input type="hidden" name="id" value="<?php echo $c['id']; ?>">
                                                    <button type="submit" class="btn btn-xs btn-danger" title="Revoke Certificate">
                                                        <i class="fa-solid fa-ban"></i>
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <button class="btn btn-xs btn-outline-secondary" disabled title="Already Revoked">
                                                    <i class="fa-solid fa-ban"></i>
                                                </button>
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

<script>
    // Live table search filtering
    document.getElementById('certsSearchInput').addEventListener('keyup', function() {
        let filter = this.value.toLowerCase();
        let rows = document.querySelectorAll('#certsTable tbody tr');
        
        rows.forEach(row => {
            let text = row.textContent.toLowerCase();
            row.style.display = text.includes(filter) ? '' : 'none';
        });
    });
</script>

<style>
.btn-xs {
    padding: 0.25rem 0.4rem;
    font-size: 0.75rem;
    border-radius: 0.2rem;
    line-height: 1;
}
.gap-1.5 {
    gap: 0.35rem !important;
}
</style>

<?php
include $base_path . 'includes/footer.php';
?>
