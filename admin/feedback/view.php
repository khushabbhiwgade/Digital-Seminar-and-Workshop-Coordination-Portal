<?php
// admin/feedback/view.php
// ------------------------------------------------------------
// Admin — Individual Feedback Review Inspector (Part 11)
// ------------------------------------------------------------

$base_path = '../../';
$page_title = 'Review Inspector - Admin Panel';
$active_page = 'admin_feedback';

require_once $base_path . 'includes/auth.php';
require_role('admin');

require_once $base_path . 'config/db_connect.php';

$feedback_id = intval($_GET['id'] ?? 0);
$error_msg = '';
$fb = null;

try {
    if ($feedback_id <= 0) {
        throw new Exception("Invalid evaluation reference.");
    }

    // Fetch complete feedback record, participant profile, and workshop details
    $stmt = $conn->prepare("
        SELECT f.*, 
               u.full_name, u.email, u.mobile, u.college, u.department, u.year_of_study,
               w.title as ws_title, w.host as ws_host, w.speaker as ws_speaker, w.start_date as ws_date, w.venue as ws_venue,
               t.ticket_number
        FROM feedback f
        JOIN users u ON f.user_id = u.id
        JOIN workshops w ON f.event_id = w.id
        JOIN tickets t ON f.registration_id = t.id
        WHERE f.id = ?
    ");
    $stmt->bind_param("i", $feedback_id);
    $stmt->execute();
    $fb = $stmt->get_result()->fetch_assoc();

    if (!$fb) {
        throw new Exception("Workshop evaluation record not found.");
    }

} catch (Exception $e) {
    $error_msg = $e->getMessage();
}

include $base_path . 'includes/header.php';
include $base_path . 'includes/navbar.php';
?>

<div class="container my-5">
    <!-- Header bar -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h2 mb-0 fw-bold"><i class="fa-solid fa-circle-info text-primary me-2"></i>Evaluation Inspector</h1>
            <p class="text-muted mb-0">Detailed inspect view of an individual workshop evaluation.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="manage.php" class="btn btn-outline-dark btn-sm"><i class="fa-solid fa-arrow-left me-1"></i>Back to Feedback Board</a>
        </div>
    </div>

    <!-- Error Alert -->
    <?php if (!empty($error_msg)): ?>
        <div class="alert alert-danger border-start border-4 border-danger shadow-sm" role="alert">
            <i class="fa-solid fa-triangle-exclamation me-2"></i><strong>Review Inspector Error:</strong> <?php echo htmlspecialchars($error_msg); ?>
        </div>
    <?php endif; ?>

    <?php if (empty($error_msg) && $fb): ?>
        <div class="row g-4">
            <!-- Left Column: Ratings Details & Comments -->
            <div class="col-lg-7">
                <!-- Stars ratings details -->
                <div class="card border-0 shadow-sm rounded-3 bg-white p-4 mb-4">
                    <h5 class="fw-bold mb-4 border-bottom pb-2 text-dark"><i class="fa-solid fa-star-half-stroke text-warning me-2"></i>Evaluation Metrics</h5>
                    
                    <?php
                    $ratings = [
                        'Overall Experience' => $fb['overall_rating'],
                        'Speaker & Delivery' => $fb['speaker_rating'],
                        'Technical Content' => $fb['content_rating'],
                        'Session Organization' => $fb['organization_rating'],
                        'Venue & Facilities' => $fb['venue_rating']
                    ];
                    ?>
                    
                    <?php foreach ($ratings as $label => $val): ?>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <span class="fw-semibold text-dark d-block"><?php echo htmlspecialchars($label); ?></span>
                            </div>
                            <div class="text-end">
                                <span class="text-warning fw-bold fs-5">
                                    <?php for ($i = 1; $i <= 5; $i++) {
                                        echo ($i <= $val) ? '★' : '☆';
                                    } ?>
                                </span>
                                <span class="badge bg-secondary-subtle text-secondary border small ms-2" style="font-size: 0.75rem;"><?php echo $val; ?>/5</span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Written comments block -->
                <div class="card border-0 shadow-sm rounded-3 bg-white p-4 mb-4">
                    <h5 class="fw-bold mb-3 border-bottom pb-2 text-dark"><i class="fa-solid fa-quote-left text-primary me-2"></i>Written Comments</h5>
                    
                    <div class="bg-light p-4 rounded-3 border-start border-4 border-primary font-monospace text-dark lead fs-6 lh-base shadow-inner position-relative">
                        <span class="position-absolute text-body-tertiary opacity-25" style="top: 10px; right: 20px; font-size: 4rem;">”</span>
                        <?php echo nl2br(htmlspecialchars($fb['comments'])); ?>
                    </div>
                    
                    <div class="text-muted small text-end mt-2">
                        Comment Length: <strong class="text-dark"><?php echo mb_strlen($fb['comments']); ?></strong> characters. Prepared for AI sentiment summary.
                    </div>
                </div>
            </div>

            <!-- Right Column: Participant Profile & Workshop Meta -->
            <div class="col-lg-5">
                <!-- Participant Information Card -->
                <div class="card border-0 shadow-sm rounded-3 bg-white p-4 mb-4 border-top border-4 border-primary">
                    <h5 class="fw-bold mb-3 text-primary"><i class="fa-solid fa-user-graduate me-2"></i>Participant Profile</h5>
                    <div class="row g-2.5 small">
                        <div class="col-4 text-muted text-end">Full Name:</div>
                        <div class="col-8 text-dark fw-bold"><?php echo htmlspecialchars($fb['full_name']); ?></div>
                        
                        <div class="col-4 text-muted text-end">Email:</div>
                        <div class="col-8 text-dark text-truncate"><?php echo htmlspecialchars($fb['email']); ?></div>
                        
                        <div class="col-4 text-muted text-end">Mobile:</div>
                        <div class="col-8 text-dark"><?php echo htmlspecialchars($fb['mobile'] ?? 'N/A'); ?></div>
                        
                        <div class="col-4 text-muted text-end">College:</div>
                        <div class="col-8 text-dark"><?php echo htmlspecialchars($fb['college'] ?? 'N/A'); ?></div>
                        
                        <div class="col-4 text-muted text-end">Department:</div>
                        <div class="col-8 text-dark"><?php echo htmlspecialchars($fb['department'] ?? 'N/A'); ?></div>
                        
                        <div class="col-4 text-muted text-end">Study Year:</div>
                        <div class="col-8 text-dark text-capitalize"><?php echo htmlspecialchars($fb['year_of_study'] ?? 'N/A'); ?></div>
                    </div>
                </div>

                <!-- Workshop Information Card -->
                <div class="card border-0 shadow-sm rounded-3 bg-white p-4 mb-4 border-top border-4 border-success">
                    <h5 class="fw-bold mb-3 text-success"><i class="fa-solid fa-chalkboard-user me-2"></i>Workshop Metadata</h5>
                    <div class="row g-2.5 small">
                        <div class="col-4 text-muted text-end">Title:</div>
                        <div class="col-8 text-dark fw-bold"><?php echo htmlspecialchars($fb['ws_title']); ?></div>
                        
                        <div class="col-4 text-muted text-end">Speaker:</div>
                        <div class="col-8 text-dark"><?php echo htmlspecialchars($fb['ws_speaker']); ?></div>
                        
                        <div class="col-4 text-muted text-end">Host:</div>
                        <div class="col-8 text-dark"><?php echo htmlspecialchars($fb['ws_host']); ?></div>
                        
                        <div class="col-4 text-muted text-end">Held Date:</div>
                        <div class="col-8 text-dark"><?php echo date('M d, Y', strtotime($fb['ws_date'])); ?></div>
                        
                        <div class="col-4 text-muted text-end">Venue:</div>
                        <div class="col-8 text-dark"><?php echo htmlspecialchars($fb['ws_venue']); ?></div>
                        
                        <div class="col-4 text-muted text-end">Ticket Code:</div>
                        <div class="col-8 text-success font-monospace fw-bold"><?php echo htmlspecialchars($fb['ticket_number']); ?></div>
                    </div>
                </div>

                <!-- Submission info -->
                <div class="card border-0 shadow-sm rounded-3 bg-white p-4 text-center">
                    <span class="text-muted small d-block mb-1">EVALUATION METADATA</span>
                    <div class="small text-dark mb-2">
                        Submitted: <strong class="text-dark"><?php echo date('F d, Y h:i A', strtotime($fb['submitted_at'])); ?></strong>
                    </div>
                    <?php if (strtotime($fb['updated_at']) > strtotime($fb['submitted_at'])): ?>
                        <div class="small text-muted mb-3">
                            Updated: <strong class="text-dark"><?php echo date('F d, Y h:i A', strtotime($fb['updated_at'])); ?></strong>
                        </div>
                    <?php endif; ?>
                    
                    <a href="../workshops/scorecard.php?id=<?php echo $fb['event_id']; ?>" class="btn btn-warning text-dark fw-bold w-100 shadow-sm"><i class="fa-solid fa-square-poll-vertical me-2"></i>Inspect Workshop Scorecard</a>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
.g-2.5 {
    --bs-gutter-x: 0.5rem;
    --bs-gutter-y: 0.65rem;
}
.shadow-inner {
    box-shadow: inset 0 2px 4px 0 rgba(0,0,0,0.06);
}
</style>

<?php
include $base_path . 'includes/footer.php';
?>
