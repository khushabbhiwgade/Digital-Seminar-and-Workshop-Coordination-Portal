<?php
// participant/certificates.php
// ------------------------------------------------------------
// Participant Certificates Center (Secure Downloads & View)
// ------------------------------------------------------------

$base_path = '../';
$page_title = 'My Certificates - Campus Connect';
$active_page = 'participant_certificates';

require_once $base_path . 'includes/auth.php';
require_role('student');

require_once $base_path . 'config/db_connect.php';

$user_id = get_user_id();
$user_name = get_user_name();

$error_msg = '';
$success_msg = '';

// Handle Secure Download Action (Part 16 - Security & Part 17 - Error Handling)
if (isset($_GET['action']) && $_GET['action'] === 'download' && isset($_GET['id'])) {
    $cert_id = intval($_GET['id']);
    
    try {
        // Fetch certificate details with ownership validation
        $stmt = $conn->prepare("
            SELECT c.certificate_no, c.certificate_path, c.status, t.user_id 
            FROM certificates c 
            JOIN tickets t ON c.registration_id = t.id 
            WHERE c.id = ?
        ");
        $stmt->bind_param("i", $cert_id);
        $stmt->execute();
        $cert = $stmt->get_result()->fetch_assoc();
        
        if (!$cert) {
            throw new Exception("Error: Registration/Certificate record not found in the system.");
        }
        
        // 1. Ownership Check: Prevent downloading other people's certificates
        if (intval($cert['user_id']) !== intval($user_id)) {
            throw new Exception("Access Denied: You do not have permission to download this certificate.");
        }
        
        // 2. Revocation Check: Revoked certificates cannot be downloaded
        if ($cert['status'] === 'Revoked') {
            throw new Exception("Security Alert: This certificate has been revoked by the system administrator.");
        }
        
        // 3. File existence check
        $full_path = $base_path . $cert['certificate_path'];
        if (empty($cert['certificate_path']) || !file_exists($full_path)) {
            throw new Exception("Missing File: The certificate PDF file is missing on disk. Please request the administrator to re-generate it.");
        }
        
        // Securely stream the PDF download to client
        header('Content-Description: File Transfer');
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . basename($cert['certificate_path']) . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($full_path));
        
        // Clear system output buffers to prevent corrupted PDF streams
        ob_clean();
        flush();
        
        readfile($full_path);
        exit;
        
    } catch (Exception $e) {
        $error_msg = $e->getMessage();
    }
}

// Fetch all certificates earned by this student
$certs_sql = "
    SELECT c.id, c.certificate_no, c.verification_code, c.generated_at, c.status,
           w.title as ws_title, w.end_date as ws_date
    FROM certificates c
    JOIN tickets t ON c.registration_id = t.id
    JOIN workshops w ON c.event_id = w.id
    WHERE t.user_id = ?
    ORDER BY c.generated_at DESC
";
$stmt = $conn->prepare($certs_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$certs_res = $stmt->get_result();

// Calculate student certificate statistics
$cnt_earned = 0;
$cnt_active = 0;
$cnt_revoked = 0;

$res_earned = $conn->prepare("SELECT COUNT(*) as cnt FROM certificates c JOIN tickets t ON c.registration_id = t.id WHERE t.user_id = ?");
$res_earned->bind_param("i", $user_id);
$res_earned->execute();
$cnt_earned = intval($res_earned->get_result()->fetch_assoc()['cnt']);

$res_active = $conn->prepare("SELECT COUNT(*) as cnt FROM certificates c JOIN tickets t ON c.registration_id = t.id WHERE t.user_id = ? AND c.status = 'Generated'");
$res_active->bind_param("i", $user_id);
$res_active->execute();
$cnt_active = intval($res_active->get_result()->fetch_assoc()['cnt']);

$res_revoked = $conn->prepare("SELECT COUNT(*) as cnt FROM certificates c JOIN tickets t ON c.registration_id = t.id WHERE t.user_id = ? AND c.status = 'Revoked'");
$res_revoked->bind_param("i", $user_id);
$res_revoked->execute();
$cnt_revoked = intval($res_revoked->get_result()->fetch_assoc()['cnt']);

include $base_path . 'includes/header.php';
include $base_path . 'includes/navbar.php';
?>

<div class="container my-5">
    <!-- Header banner -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
        <div>
            <h1 class="h2 mb-0 fw-bold"><i class="fa-solid fa-graduation-cap text-primary me-2"></i>My Certificates Center</h1>
            <p class="text-muted mb-0">View, download, and verify your credentials earned from completing workshops.</p>
        </div>
        <a href="dashboard.php" class="btn btn-outline-dark btn-sm"><i class="fa-solid fa-arrow-left me-1"></i>Back to Dashboard</a>
    </div>

    <!-- Alert Messaging -->
    <?php if (!empty($error_msg)): ?>
        <div class="alert alert-danger alert-dismissible fade show border-start border-4 border-danger shadow-sm mb-4" role="alert">
            <i class="fa-solid fa-triangle-exclamation me-2"></i><strong>Error:</strong> <?php echo htmlspecialchars($error_msg); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Student Certificate Stats -->
    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card h-100 border-0 shadow-sm rounded-3 p-3 bg-white text-center border-start border-4 border-primary">
                <div class="display-6 text-primary mb-2"><i class="fa-solid fa-scroll"></i></div>
                <h3 class="fw-bold mb-1"><?php echo $cnt_earned; ?></h3>
                <span class="text-muted small fw-semibold">Total Earned</span>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100 border-0 shadow-sm rounded-3 p-3 bg-white text-center border-start border-4 border-success">
                <div class="display-6 text-success mb-2"><i class="fa-solid fa-circle-check"></i></div>
                <h3 class="fw-bold mb-1"><?php echo $cnt_active; ?></h3>
                <span class="text-muted small fw-semibold">Active &amp; Verified</span>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100 border-0 shadow-sm rounded-3 p-3 bg-white text-center border-start border-4 border-danger">
                <div class="display-6 text-danger mb-2"><i class="fa-solid fa-triangle-exclamation"></i></div>
                <h3 class="fw-bold mb-1"><?php echo $cnt_revoked; ?></h3>
                <span class="text-muted small fw-semibold">Revoked Credentials</span>
            </div>
        </div>
    </div>

    <!-- Display Certificates Grid -->
    <div class="row g-4">
        <?php if ($certs_res && $certs_res->num_rows > 0): ?>
            <?php while ($ct = $certs_res->fetch_assoc()): ?>
                <?php 
                $is_revoked = ($ct['status'] === 'Revoked');
                $card_border = $is_revoked ? 'border-danger border-start border-4' : 'border-success border-start border-4';
                ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 border-0 shadow-sm rounded-3 bg-white <?php echo $card_border; ?> hover-card">
                        <div class="card-body p-4 d-flex flex-column">
                            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                                <code class="font-monospace fw-bold text-primary fs-7"><?php echo htmlspecialchars($ct['certificate_no']); ?></code>
                                <div class="d-flex gap-1">
                                    <?php if ($is_revoked): ?>
                                        <span class="badge bg-danger px-2.5 py-1 text-uppercase fs-9"><i class="fa-solid fa-ban me-1"></i>Revoked</span>
                                    <?php else: ?>
                                        <span class="badge bg-success px-2.5 py-1 text-uppercase fs-9"><i class="fa-solid fa-check me-1"></i>Active</span>
                                        <span class="badge bg-primary px-2.5 py-1 text-uppercase fs-9">Verified</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <h5 class="fw-bold text-dark mb-2 flex-grow-0" style="min-height: 48px;"><?php echo htmlspecialchars($ct['ws_title']); ?></h5>
                            <p class="text-muted small mb-3"><i class="fa-solid fa-calendar me-1"></i>Completed on <?php echo date('M d, Y', strtotime($ct['ws_date'])); ?></p>
                            
                            <div class="bg-light p-3 rounded border border-light-subtle mb-4 small flex-grow-1 font-monospace">
                                <div class="text-muted text-uppercase fs-9 mb-1 fw-bold">Verification Code:</div>
                                <div class="text-primary fw-bold fs-6"><?php echo htmlspecialchars($ct['verification_code']); ?></div>
                            </div>
                            
                            <div class="d-flex gap-2 mt-auto">
                                <?php if ($is_revoked): ?>
                                    <button class="btn btn-sm btn-outline-danger w-50 rounded-pill" disabled><i class="fa-solid fa-ban me-1"></i>Blocked</button>
                                <?php else: ?>
                                    <a href="certificates.php?action=download&id=<?php echo $ct['id']; ?>" class="btn btn-sm btn-success w-50 fw-semibold shadow-sm rounded-pill">
                                        <i class="fa-solid fa-download me-1"></i>Download PDF
                                    </a>
                                <?php endif; ?>
                                <a href="../verify_certificate.php?code=<?php echo urlencode($ct['verification_code']); ?>" target="_blank" class="btn btn-sm btn-outline-primary w-50 fw-semibold rounded-pill">
                                    <i class="fa-solid fa-shield-halved me-1"></i>Verify Page
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-12 text-center py-5">
                <div class="card border-0 shadow-sm rounded-3 p-5 bg-white text-center">
                    <div class="display-3 text-muted mb-3"><i class="fa-solid fa-scroll text-secondary"></i></div>
                    <h3 class="fw-bold text-dark">No Certificates Earned Yet</h3>
                    <p class="text-muted mb-4">Complete an active workshop, attend the sessions, and mark your presence to automatically receive your credentials.</p>
                    <a href="my_workshops.php" class="btn btn-warning text-dark fw-bold px-4 py-2 shadow-sm rounded-pill"><i class="fa-solid fa-chalkboard-user me-2"></i>Go to My Workshops</a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.fs-9 {
    font-size: 11px !important;
}
.hover-card {
    transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
}
.hover-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 .5rem 1rem rgba(0,0,0,.15)!important;
}
</style>

<?php
include $base_path . 'includes/footer.php';
?>

