<?php
// verify_certificate.php
// ------------------------------------------------------------
// Public Certificate Verification Portal (No Authentication Required)
// ------------------------------------------------------------

$base_path = './';
$page_title = 'Verify Certificate - Campus Connect';
$active_page = 'verify_certificate';

require_once $base_path . 'config/db_connect.php';

$search_code = '';
$cert = null;
$searched = false;

// Retrieve code from GET or POST
if (isset($_GET['code'])) {
    $search_code = trim($_GET['code']);
} elseif (isset($_POST['code'])) {
    $search_code = trim($_POST['code']);
}

if (!empty($search_code)) {
    $searched = true;
    
    // Fetch certificate with student and workshop details
    $stmt = $conn->prepare("
        SELECT c.id, c.certificate_no, c.verification_code, c.certificate_path, c.generated_at, c.status,
               u.full_name, w.title as ws_title, w.end_date as ws_date
        FROM certificates c
        JOIN users u ON c.user_id = u.id
        JOIN workshops w ON c.event_id = w.id
        WHERE c.verification_code = ? OR c.certificate_no = ?
    ");
    $stmt->bind_param("ss", $search_code, $search_code);
    $stmt->execute();
    $cert = $stmt->get_result()->fetch_assoc();
}

include $base_path . 'includes/header.php';
include $base_path . 'includes/navbar.php';
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-lg-7">
            <!-- Main Verification Search Box -->
            <div class="card border-0 shadow-lg rounded-3 bg-white mb-4 overflow-hidden">
                <div class="card-header bg-dark text-white text-center py-4">
                    <h3 class="mb-1 fw-bold"><i class="fa-solid fa-shield-halved text-warning me-2"></i>Certificate Verification Center</h3>
                    <p class="text-white-50 mb-0 small">Verify the authenticity of digital certificates issued by Campus Connect</p>
                </div>
                <div class="card-body p-4 p-md-5">
                    <form method="POST" action="verify_certificate.php" class="mb-0">
                        <div class="mb-3 text-center">
                            <label for="code" class="form-label fw-semibold text-dark fs-6">Enter Verification Code or Certificate Number</label>
                            <div class="input-group input-group-lg shadow-sm rounded">
                                <span class="input-group-text bg-light border-end-0 text-muted"><i class="fa-solid fa-key"></i></span>
                                <input type="text" name="code" id="code" class="form-control border-start-0 font-monospace text-uppercase" placeholder="E.G. VER-8F2A9C71 OR CERT-2026-000001" value="<?php echo htmlspecialchars($search_code); ?>" required autocomplete="off">
                                <button type="submit" class="btn btn-warning text-dark fw-bold px-4"><i class="fa-solid fa-magnifying-glass me-1"></i>Verify</button>
                            </div>
                            <div class="form-text mt-2 text-muted">Verification codes can be found at the bottom-left of printed or digital certificates.</div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Verification Outcome Results -->
            <?php if ($searched): ?>
                <?php if ($cert): ?>
                    <?php if ($cert['status'] === 'Generated'): ?>
                        <!-- SUCCESS VALID CERTIFICATE -->
                        <div class="card border-0 shadow-lg rounded-3 bg-white overflow-hidden border-start border-5 border-success animate-fade-in">
                            <div class="card-header bg-success text-white py-3 text-center">
                                <h4 class="mb-0 fw-bold"><i class="fa-solid fa-circle-check me-2"></i>Certificate Valid</h4>
                            </div>
                            <div class="card-body p-4">
                                <div class="text-center mb-4">
                                    <div class="display-1 text-success mb-2"><i class="fa-solid fa-award"></i></div>
                                    <h5 class="text-muted mb-1 text-uppercase fw-semibold tracking-wider">This is to certify that</h5>
                                    <h2 class="text-dark fw-bold mb-1"><?php echo htmlspecialchars($cert['full_name']); ?></h2>
                                    <p class="text-muted">has successfully completed the workshop</p>
                                    <h4 class="text-primary fw-bold px-3 py-2 bg-light rounded d-inline-block border border-primary-subtle mb-0">
                                        <?php echo htmlspecialchars($cert['ws_title']); ?>
                                    </h4>
                                </div>

                                <hr class="border-light-subtle">

                                <div class="row g-3">
                                    <div class="col-sm-6">
                                        <span class="text-muted small d-block">Participant Name</span>
                                        <strong class="text-dark"><?php echo htmlspecialchars($cert['full_name']); ?></strong>
                                    </div>
                                    <div class="col-sm-6">
                                        <span class="text-muted small d-block">Completion Date</span>
                                        <strong class="text-dark"><?php echo date('F d, Y', strtotime($cert['ws_date'])); ?></strong>
                                    </div>
                                    <div class="col-sm-6">
                                        <span class="text-muted small d-block">Certificate Number</span>
                                        <code class="text-primary fw-bold font-monospace"><?php echo htmlspecialchars($cert['certificate_no']); ?></code>
                                    </div>
                                    <div class="col-sm-6">
                                        <span class="text-muted small d-block">Verification Code</span>
                                        <code class="text-success fw-bold font-monospace"><?php echo htmlspecialchars($cert['verification_code']); ?></code>
                                    </div>
                                    <div class="col-sm-6">
                                        <span class="text-muted small d-block">Issuance Timestamp</span>
                                        <span class="text-dark small"><?php echo date('M d, Y h:i A', strtotime($cert['generated_at'])); ?></span>
                                    </div>
                                    <div class="col-sm-6">
                                        <span class="text-muted small d-block">Verification Status</span>
                                        <span class="badge bg-success"><i class="fa-solid fa-circle-check me-1"></i>Verified Active</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php else: // Revoked status ?>
                        <!-- REVOKED CERTIFICATE -->
                        <div class="card border-0 shadow-lg rounded-3 bg-white overflow-hidden border-start border-5 border-danger animate-fade-in">
                            <div class="card-header bg-danger text-white py-3 text-center">
                                <h4 class="mb-0 fw-bold"><i class="fa-solid fa-triangle-exclamation me-2"></i>Certificate Revoked</h4>
                            </div>
                            <div class="card-body p-4 text-center">
                                <div class="display-3 text-danger mb-3"><i class="fa-solid fa-ban animate-pulse"></i></div>
                                <h3 class="text-danger fw-bold mb-2">Notice: Certificate Void</h3>
                                <p class="text-muted mb-4 px-md-4">
                                    The certificate corresponding to code <strong><?php echo htmlspecialchars($search_code); ?></strong> has been revoked by the system administrator and is no longer valid.
                                </p>
                                
                                <div class="bg-light p-3 rounded border border-danger-subtle text-start font-monospace small mx-md-4">
                                    <div class="row g-2">
                                        <div class="col-5 text-muted text-end">Cert Number:</div>
                                        <div class="col-7 text-dark fw-bold"><?php echo htmlspecialchars($cert['certificate_no']); ?></div>
                                        <div class="col-5 text-muted text-end">Recipient:</div>
                                        <div class="col-7 text-dark"><?php echo htmlspecialchars($cert['full_name']); ?></div>
                                        <div class="col-5 text-muted text-end">Workshop:</div>
                                        <div class="col-7 text-dark"><?php echo htmlspecialchars($cert['ws_title']); ?></div>
                                        <div class="col-5 text-muted text-end">Status:</div>
                                        <div class="col-7 text-danger fw-bold"><i class="fa-solid fa-circle-xmark me-1"></i>REVOKED</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <!-- CERTIFICATE NOT FOUND -->
                    <div class="card border-0 shadow-lg rounded-3 bg-white overflow-hidden border-start border-5 border-dark animate-fade-in">
                        <div class="card-header bg-dark text-white py-3 text-center">
                            <h4 class="mb-0 fw-bold"><i class="fa-solid fa-circle-question me-2"></i>Certificate Not Found</h4>
                        </div>
                        <div class="card-body p-4 text-center">
                            <div class="display-3 text-muted mb-3"><i class="fa-solid fa-circle-exclamation"></i></div>
                            <h3 class="text-dark fw-bold mb-2">Record Not Found</h3>
                            <p class="text-muted mb-0 px-md-4">
                                The code <strong><?php echo htmlspecialchars($search_code); ?></strong> does not match any issued certificate in our portal. Please check the spelling and try again.
                            </p>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.animate-fade-in {
    animation: fadeIn 0.4s ease-out forwards;
}
.animate-pulse {
    animation: pulse 1.5s infinite;
}
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}
@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.05); }
    100% { transform: scale(1); }
}
.tracking-wider {
    letter-spacing: 0.1em;
}
</style>

<?php
include $base_path . 'includes/footer.php';
?>
