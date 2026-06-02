<?php
// admin/feedback.php
// ------------------------------------------------------------
// Admin — Participant Feedback Desk
// ------------------------------------------------------------
$base_path = '../';
$page_title = 'Session Feedback - Admin Panel';
$active_page = 'admin_feedback';

require_once $base_path . 'includes/auth.php';
require_role('admin');
require_once $base_path . 'config/db_connect.php';

// Fetch overall evaluation statistics
$avg_rating = 0.0;
$total_feedbacks = 0;
$stats_sql = "SELECT AVG(rating) as avg_rate, COUNT(*) as cnt FROM feedback";
$stats_res = $conn->query($stats_sql);
if ($stats_res) {
    $row = $stats_res->fetch_assoc();
    $avg_rating = round(floatval($row['avg_rate']), 1);
    $total_feedbacks = intval($row['cnt']);
}

// Fetch rating breakdowns
$rating_breakdowns = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
$break_sql = "SELECT rating, COUNT(*) as cnt FROM feedback GROUP BY rating";
$break_res = $conn->query($break_sql);
if ($break_res) {
    while ($r = $break_res->fetch_assoc()) {
        $rating_breakdowns[intval($r['rating'])] = intval($r['cnt']);
    }
}

// Fetch all feedback with user & event info
$feedbacks = [];
$sql = "SELECT f.id, f.rating, f.comments, f.submitted_at, 
               u.full_name, u.participant_type, u.organization, u.email,
               e.title AS event_title, e.host AS speaker
        FROM feedback f
        JOIN users u ON f.user_id = u.id
        JOIN events e ON f.event_id = e.id
        ORDER BY f.submitted_at DESC";
$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $feedbacks[] = $row;
    }
}

include $base_path . 'includes/header.php';
include $base_path . 'includes/navbar.php';
?>

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h2 mb-0"><i class="fa-solid fa-comments text-primary me-2"></i>Participant Feedback Desk</h1>
            <p class="text-muted mb-0">Evaluate seminar reviews, ratings, and learning outcomes</p>
        </div>
        <a href="dashboard.php" class="btn btn-outline-dark btn-sm"><i class="fa-solid fa-arrow-left me-1"></i>Back to Dashboard</a>
    </div>

    <!-- Rating Summary Dashboard Cards -->
    <div class="row g-4 mb-5">
        <div class="col-lg-4">
            <div class="card h-100 border-0 shadow-sm rounded-3 p-4 bg-white text-center d-flex flex-column justify-content-center">
                <span class="text-muted small fw-bold text-uppercase mb-2">Overall Portal Rating</span>
                <h1 class="display-3 fw-bold text-warning mb-1"><?php echo $avg_rating > 0 ? $avg_rating : '—'; ?></h1>
                <div class="text-warning mb-2 fs-4">
                    <?php
                    $full_stars = floor($avg_rating);
                    $has_half = ($avg_rating - $full_stars) >= 0.5;
                    for ($i = 1; $i <= 5; $i++) {
                        if ($i <= $full_stars) {
                            echo '<i class="fa-solid fa-star"></i>';
                        } elseif ($i == $full_stars + 1 && $has_half) {
                            echo '<i class="fa-solid fa-star-half-stroke"></i>';
                        } else {
                            echo '<i class="fa-regular fa-star"></i>';
                        }
                    }
                    ?>
                </div>
                <span class="text-muted small">Based on <?php echo $total_feedbacks; ?> student &amp; faculty reviews</span>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card h-100 border-0 shadow-sm rounded-3 p-4 bg-white">
                <h5 class="fw-bold mb-3">Rating Distribution</h5>
                <?php foreach ([5, 4, 3, 2, 1] as $star): 
                    $count = $rating_breakdowns[$star];
                    $percent = ($total_feedbacks > 0) ? ($count / $total_feedbacks) * 100 : 0;
                    $color = 'bg-success';
                    if ($star === 3) $color = 'bg-warning';
                    if ($star <= 2) $color = 'bg-danger';
                ?>
                    <div class="d-flex align-items-center mb-2">
                        <span class="fw-bold me-2 small" style="width: 50px;"><?php echo $star; ?> Stars</span>
                        <div class="progress flex-grow-1" style="height: 8px;">
                            <div class="progress-bar <?php echo $color; ?>" style="width: <?php echo $percent; ?>%"></div>
                        </div>
                        <span class="ms-3 text-muted small fw-semibold" style="width: 40px; text-align: right;"><?php echo $count; ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Feedbacks List Table -->
    <div class="card shadow-sm border-0 rounded-3 overflow-hidden">
        <div class="card-header bg-dark text-white py-3">
            <h5 class="mb-0 fw-bold"><i class="fa-solid fa-star-half-stroke text-warning me-2"></i>Dynamic Session Evaluations</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped align-middle mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th>Participant</th>
                            <th>Category</th>
                            <th>Seminar Title</th>
                            <th class="text-center">Rating</th>
                            <th>Evaluation Comments</th>
                            <th>Date Submitted</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($feedbacks) > 0): ?>
                            <?php foreach ($feedbacks as $idx => $feed): ?>
                                <tr>
                                    <td class="fw-semibold"><?php echo $idx + 1; ?></td>
                                    <td>
                                        <div class="fw-semibold"><?php echo htmlspecialchars($feed['full_name']); ?></div>
                                        <small class="text-muted"><?php echo htmlspecialchars($feed['organization']); ?></small>
                                    </td>
                                    <td><span class="badge bg-secondary"><?php echo htmlspecialchars($feed['participant_type']); ?></span></td>
                                    <td>
                                        <div class="fw-semibold text-primary"><?php echo htmlspecialchars($feed['event_title']); ?></div>
                                        <small class="text-muted">Speaker: <?php echo htmlspecialchars($feed['speaker']); ?></small>
                                    </td>
                                    <td class="text-center">
                                        <div class="text-warning">
                                            <?php
                                            for ($i = 1; $i <= 5; $i++) {
                                                if ($i <= intval($feed['rating'])) {
                                                    echo '<i class="fa-solid fa-star small"></i>';
                                                } else {
                                                    echo '<i class="fa-regular fa-star small"></i>';
                                                }
                                            }
                                            ?>
                                        </div>
                                    </td>
                                    <td><span class="text-dark small">"<?php echo htmlspecialchars($feed['comments']); ?>"</span></td>
                                    <td class="text-muted small"><?php echo date("M d, Y", strtotime($feed['submitted_at'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">No evaluations or session feedback recorded yet.</td>
                            </tr>
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
