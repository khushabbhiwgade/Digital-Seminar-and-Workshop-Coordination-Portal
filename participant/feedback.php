<?php
// participant/feedback.php
// ------------------------------------------------------------
// Participant Workshop Feedback & Evaluation Form (Part 5)
// ------------------------------------------------------------

$base_path = '../';
$page_title = 'Workshop Evaluation - Campus Connect';
$active_page = 'participant_workshops';

require_once $base_path . 'includes/auth.php';
require_role('student');

require_once $base_path . 'config/db_connect.php';

$user_id = get_user_id();
$user_name = get_user_name();

$error_msg = '';
$success_msg = '';

$ticket_id = intval($_GET['id'] ?? 0);
if ($ticket_id <= 0) {
    header("Location: dashboard.php?error=" . urlencode("Invalid workshop registration reference."));
    exit;
}

try {
    // 1. Fetch ticket and workshop details to validate eligibility
    $stmt = $conn->prepare("
        SELECT t.id, t.ticket_number, t.registration_status, t.user_id, t.event_id,
               w.title as ws_title, w.host as ws_host, w.speaker as ws_speaker, w.start_date as ws_date
        FROM tickets t
        JOIN workshops w ON t.event_id = w.id
        WHERE t.id = ?
    ");
    $stmt->bind_param("i", $ticket_id);
    $stmt->execute();
    $tk = $stmt->get_result()->fetch_assoc();

    if (!$tk) {
        throw new Exception("Validation failed: Workshop registration record not found.");
    }

    // Security: Validate ownership
    if (intval($tk['user_id']) !== intval($user_id)) {
        throw new Exception("Access Denied: You do not have permission to evaluate this registration.");
    }

    $event_id = intval($tk['event_id']);
    $workshop_title = $tk['ws_title'];
    $workshop_host = $tk['ws_host'];
    $workshop_speaker = $tk['ws_speaker'];
    $workshop_date = $tk['ws_date'];

    // 2. Validate Eligibility (Part 3)
    // Only allow feedback when attendance_status = 'Present' AND registration_status = 'Completed'
    $att_stmt = $conn->prepare("SELECT attendance_status FROM attendance WHERE ticket_id = ?");
    $att_stmt->bind_param("i", $ticket_id);
    $att_stmt->execute();
    $att = $att_stmt->get_result()->fetch_assoc();
    $attendance_status = $att ? $att['attendance_status'] : null;

    if ($attendance_status !== 'Present' || $tk['registration_status'] !== 'Completed') {
        throw new Exception("Eligibility Blocked: Evaluations are only unlocked for successfully Completed workshops with Present attendance check-ins.");
    }

    // 3. Check if feedback already exists (Part 4)
    $fb_stmt = $conn->prepare("SELECT * FROM feedback WHERE registration_id = ?");
    $fb_stmt->bind_param("i", $ticket_id);
    $fb_stmt->execute();
    $existing_fb = $fb_stmt->get_result()->fetch_assoc();

    $is_edit = false;
    $is_locked = false;
    
    $overall_rating = 5;
    $speaker_rating = 5;
    $content_rating = 5;
    $organization_rating = 5;
    $venue_rating = 5;
    $comments = '';
    $submitted_at = '';

    if ($existing_fb) {
        $is_edit = true;
        $overall_rating = intval($existing_fb['overall_rating']);
        $speaker_rating = intval($existing_fb['speaker_rating']);
        $content_rating = intval($existing_fb['content_rating']);
        $organization_rating = intval($existing_fb['organization_rating']);
        $venue_rating = intval($existing_fb['venue_rating']);
        $comments = $existing_fb['comments'];
        $submitted_at = $existing_fb['submitted_at'];

        // Check 24-hour edit lockout window (Part 8)
        $submitted_time = strtotime($submitted_at);
        $elapsed_hours = (time() - $submitted_time) / 3600;
        if ($elapsed_hours >= 24) {
            $is_locked = true;
        }
    }

    // 4. Handle POST Form Submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Enforce lockout security
        if ($is_locked) {
            throw new Exception("Security Violation: This evaluation has been locked and can no longer be edited.");
        }

        $post_overall = intval($_POST['overall_rating'] ?? 5);
        $post_speaker = intval($_POST['speaker_rating'] ?? 5);
        $post_content = intval($_POST['content_rating'] ?? 5);
        $post_org = intval($_POST['organization_rating'] ?? 5);
        $post_venue = intval($_POST['venue_rating'] ?? 5);
        $post_comments = trim($_POST['comments'] ?? '');

        // Validation checks
        $ratings = [$post_overall, $post_speaker, $post_content, $post_org, $post_venue];
        foreach ($ratings as $r) {
            if ($r < 1 || $r > 5) {
                throw new Exception("Invalid ratings entered. Values must be between 1 and 5 stars.");
            }
        }

        // Comment validation: 20 to 2000 chars (Part 7)
        $comment_len = mb_strlen($post_comments);
        if (empty($post_comments)) {
            throw new Exception("Validation Error: Please share your feedback comments.");
        }
        if ($comment_len < 20) {
            throw new Exception("Validation Error: Comments must be at least 20 characters to provide helpful insights. Current count: $comment_len chars.");
        }
        if ($comment_len > 2000) {
            throw new Exception("Validation Error: Comments cannot exceed 2000 characters. Current count: $comment_len chars.");
        }

        // Save into database (Part 4)
        if ($is_edit) {
            $upd_stmt = $conn->prepare("
                UPDATE feedback 
                SET overall_rating = ?, speaker_rating = ?, content_rating = ?, organization_rating = ?, venue_rating = ?, comments = ? 
                WHERE registration_id = ?
            ");
            $upd_stmt->bind_param("iiiiisi", $post_overall, $post_speaker, $post_content, $post_org, $post_venue, $post_comments, $ticket_id);
            $upd_stmt->execute();

            // Log activity
            $log_desc = "Feedback updated for workshop: $workshop_title. Ticket: " . $tk['ticket_number'];
            $log_stmt = $conn->prepare("INSERT INTO activity_logs (user_id, action, details) VALUES (?, 'Feedback Edit', ?)");
            $log_stmt->bind_param("is", $user_id, $log_desc);
            $log_stmt->execute();
        } else {
            $ins_stmt = $conn->prepare("
                INSERT INTO feedback (user_id, event_id, registration_id, overall_rating, speaker_rating, content_rating, organization_rating, venue_rating, comments) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $ins_stmt->bind_param("iiiiiiiis", $user_id, $event_id, $ticket_id, $post_overall, $post_speaker, $post_content, $post_org, $post_venue, $post_comments);
            $ins_stmt->execute();

            // Log activity
            $log_desc = "Feedback submitted for workshop: $workshop_title. Ticket: " . $tk['ticket_number'];
            $log_stmt = $conn->prepare("INSERT INTO activity_logs (user_id, action, details) VALUES (?, 'Feedback Submission', ?)");
            $log_stmt->bind_param("is", $user_id, $log_desc);
            $log_stmt->execute();
        }

        header("Location: dashboard.php?msg=feedback_success");
        exit;
    }

} catch (Exception $e) {
    $error_msg = $e->getMessage();
}

include $base_path . 'includes/header.php';
include $base_path . 'includes/navbar.php';
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-lg-8 col-md-10">
            <!-- Header bar -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 fw-bold mb-0">Workshop Evaluation</h1>
                <a href="dashboard.php" class="btn btn-outline-dark btn-sm"><i class="fa-solid fa-arrow-left me-1"></i>Back to Dashboard</a>
            </div>

            <!-- Error Notification Alert -->
            <?php if (!empty($error_msg)): ?>
                <div class="alert alert-danger border-start border-4 border-danger shadow-sm mb-4" role="alert">
                    <i class="fa-solid fa-triangle-exclamation me-2"></i><strong>Evaluation Blocked:</strong> <?php echo htmlspecialchars($error_msg); ?>
                </div>
            <?php endif; ?>

            <?php if (empty($error_msg) || isset($workshop_title)): ?>
                <!-- Workshop metadata info card -->
                <div class="card border-0 shadow-sm rounded-3 bg-white p-4 mb-4">
                    <div class="row align-items-center">
                        <div class="col-sm-8">
                            <span class="badge bg-primary text-uppercase mb-2">Evaluated Workshop</span>
                            <h4 class="fw-bold text-dark mb-1"><?php echo htmlspecialchars($workshop_title); ?></h4>
                            <p class="text-muted small mb-0">
                                <i class="fa-solid fa-user-tie me-1"></i>Speaker: <?php echo htmlspecialchars($workshop_speaker); ?> | 
                                <i class="fa-solid fa-building me-1"></i>Host: <?php echo htmlspecialchars($workshop_host); ?> | 
                                <i class="fa-solid fa-calendar me-1"></i>Held: <?php echo date('F d, Y', strtotime($workshop_date)); ?>
                            </p>
                        </div>
                        <div class="col-sm-4 text-sm-end mt-3 mt-sm-0">
                            <span class="badge bg-secondary font-monospace fs-7"><?php echo htmlspecialchars($tk['ticket_number']); ?></span>
                        </div>
                    </div>
                </div>

                <!-- Star rated Form -->
                <div class="card border-0 shadow-sm rounded-3 bg-white overflow-hidden mb-5">
                    <div class="card-header bg-dark text-white py-3 d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fa-solid fa-star-half-stroke text-warning me-2"></i>
                            <?php echo $is_edit ? 'Edit Submitted Feedback' : 'Submit New Feedback'; ?>
                        </h5>
                        <?php if ($is_locked): ?>
                            <span class="badge bg-danger"><i class="fa-solid fa-lock me-1"></i>Feedback Locked</span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="card-body p-4 p-md-5">
                        <?php if ($is_locked): ?>
                            <div class="alert alert-danger alert-dismissible fade show border-start border-4 border-danger shadow-sm mb-4" role="alert">
                                <i class="fa-solid fa-lock text-danger me-2"></i><strong>Feedback Locked:</strong> This feedback was submitted on <?php echo date('M d, Y h:i A', strtotime($submitted_at)); ?>. Editing is locked after 24 hours.
                            </div>
                        <?php endif; ?>

                        <form method="POST" id="feedbackForm" class="mb-0">
                            <!-- Star Selection Layout (Part 6) -->
                            <?php
                            $categories = [
                                'overall' => ['label' => 'Overall Experience', 'val' => $overall_rating, 'desc' => 'How would you rate your overall experience in this workshop?'],
                                'speaker' => ['label' => 'Speaker & Presenter', 'val' => $speaker_rating, 'desc' => 'How effectively did the speaker communicate the subject matter?'],
                                'content' => ['label' => 'Workshop Content', 'val' => $content_rating, 'desc' => 'How useful and structured was the technical content provided?'],
                                'organization' => ['label' => 'Session Organization', 'val' => $organization_rating, 'desc' => 'How well-organized was the workshop schedule, onboarding, and coordination?'],
                                'venue' => ['label' => 'Venue & Facilities', 'val' => $venue_rating, 'desc' => 'How comfortable was the lab, venue seating, tools, and digital platform?']
                            ];
                            ?>

                            <?php foreach ($categories as $key => $cat): ?>
                                <div class="mb-4">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <label class="form-label fw-bold text-dark mb-0"><?php echo htmlspecialchars($cat['label']); ?></label>
                                        <span class="text-muted small fw-semibold rating-badge badge bg-secondary-subtle text-secondary" id="<?php echo $key; ?>-badge"><?php echo $cat['val']; ?> Stars</span>
                                    </div>
                                    <p class="text-muted small mb-2"><?php echo htmlspecialchars($cat['desc']); ?></p>
                                    
                                    <!-- Stars line -->
                                    <div class="star-rating-container d-inline-flex gap-2" data-category="<?php echo $key; ?>">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <?php 
                                            $star_class = ($i <= $cat['val']) ? 'fa-solid text-warning' : 'fa-regular text-secondary';
                                            $disabled_class = $is_locked ? 'star-disabled' : 'star-clickable';
                                            ?>
                                            <i class="fa-star fs-3 <?php echo $star_class; ?> <?php echo $disabled_class; ?>" data-value="<?php echo $i; ?>"></i>
                                        <?php endfor; ?>
                                    </div>
                                    <input type="hidden" name="<?php echo $key; ?>_rating" id="<?php echo $key; ?>_rating" value="<?php echo $cat['val']; ?>">
                                </div>
                                <hr class="border-light-subtle my-3">
                            <?php endforeach; ?>

                            <!-- Comments text box (Part 7) -->
                            <div class="mb-4">
                                <label for="comments" class="form-label fw-bold text-dark mb-1">Your Detailed Comments & Feedback</label>
                                <p class="text-muted small mb-2">Please share what you liked, what could be improved, or key technical insights learned. Required for AI summary analysis.</p>
                                <textarea name="comments" id="comments" class="form-control" rows="5" placeholder="Write your feedback here... (Minimum 20 characters, Maximum 2000 characters)" required minlength="20" maxlength="2000" <?php echo $is_locked ? 'readonly' : ''; ?>><?php echo htmlspecialchars($comments); ?></textarea>
                                <div class="d-flex justify-content-between align-items-center mt-1 text-muted small">
                                    <span id="charCount">0 characters</span>
                                    <span>Min: 20 | Max: 2000</span>
                                </div>
                            </div>

                            <!-- Buttons -->
                            <div class="d-flex gap-3">
                                <?php if ($is_locked): ?>
                                    <button type="button" class="btn btn-secondary px-5 w-100 fw-bold" disabled>
                                        <i class="fa-solid fa-lock me-2"></i>Feedback Locked (24hr Expired)
                                    </button>
                                <?php else: ?>
                                    <button type="submit" class="btn btn-warning text-dark fw-bold px-5 w-100 shadow-sm py-2.5">
                                        <i class="fa-solid fa-paper-plane me-2"></i><?php echo $is_edit ? 'Update Evaluation Feedback' : 'Submit Evaluation Feedback'; ?>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.star-clickable {
    cursor: pointer;
    transition: transform 0.15s ease;
}
.star-clickable:hover {
    transform: scale(1.2);
}
.star-disabled {
    opacity: 0.65;
}
.fs-7 {
    font-size: 0.85rem !important;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const isLocked = <?php echo $is_locked ? 'true' : 'false'; ?>;
    
    // Character count tracker
    const commentsArea = document.getElementById('comments');
    const charCount = document.getElementById('charCount');
    
    if (commentsArea) {
        const updateCount = () => {
            const count = commentsArea.value.length;
            charCount.textContent = `${count} characters`;
            if (count < 20) {
                charCount.className = 'text-danger fw-semibold small';
            } else if (count > 2000) {
                charCount.className = 'text-danger fw-semibold small';
            } else {
                charCount.className = 'text-success fw-semibold small';
            }
        };
        commentsArea.addEventListener('input', updateCount);
        updateCount(); // run once on load
    }

    // Stars Interactive CSS/JS Logic (Part 6)
    if (!isLocked) {
        const containers = document.querySelectorAll('.star-rating-container');
        
        containers.forEach(container => {
            const category = container.getAttribute('data-category');
            const hiddenInput = document.getElementById(category + '_rating');
            const badge = document.getElementById(category + '-badge');
            const stars = container.querySelectorAll('.fa-star');
            
            stars.forEach(star => {
                // Hover effect: highlight temporarily
                star.addEventListener('mouseover', function() {
                    const value = parseInt(this.getAttribute('data-value'));
                    stars.forEach((s, idx) => {
                        if (idx < value) {
                            s.className = 'fa-star fs-3 fa-solid text-warning star-clickable';
                        } else {
                            s.className = 'fa-star fs-3 fa-regular text-secondary star-clickable';
                        }
                    });
                });
                
                // Mouse leave: restore original values
                star.addEventListener('mouseleave', function() {
                    const value = parseInt(hiddenInput.value);
                    stars.forEach((s, idx) => {
                        if (idx < value) {
                            s.className = 'fa-star fs-3 fa-solid text-warning star-clickable';
                        } else {
                            s.className = 'fa-star fs-3 fa-regular text-secondary star-clickable';
                        }
                    });
                });
                
                // Click: lock the selected rating value
                star.addEventListener('click', function() {
                    const value = parseInt(this.getAttribute('data-value'));
                    hiddenInput.value = value;
                    badge.textContent = `${value} Stars`;
                });
            });
        });
    }
});
</script>

<?php
include $base_path . 'includes/footer.php';
?>
