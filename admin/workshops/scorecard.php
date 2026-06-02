<?php
// admin/workshops/scorecard.php
// ------------------------------------------------------------
// Admin — Workshop Evaluation Metrics Scorecard (Part 13)
// ------------------------------------------------------------

$base_path = '../../';
$page_title = 'Workshop Scorecard - Admin Panel';
$active_page = 'admin_workshops';

require_once $base_path . 'includes/auth.php';
require_role('admin');

require_once $base_path . 'config/db_connect.php';

$workshop_id = intval($_GET['id'] ?? 0);
$error_msg = '';
$ws = null;
$stats = null;
$reviews = [];

try {
    if ($workshop_id <= 0) {
        throw new Exception("Invalid workshop reference.");
    }

    // 1. Fetch workshop details
    $ws_stmt = $conn->prepare("SELECT * FROM workshops WHERE id = ?");
    $ws_stmt->bind_param("i", $workshop_id);
    $ws_stmt->execute();
    $ws = $ws_stmt->get_result()->fetch_assoc();

    if (!$ws) {
        throw new Exception("Associated workshop does not exist in the system.");
    }

    // 2. Fetch feedback statistics (Part 12 - Feedback Aggregation Engine)
    $stats_stmt = $conn->prepare("
        SELECT COUNT(*) as total_count,
               AVG(overall_rating) as avg_overall,
               AVG(speaker_rating) as avg_speaker,
               AVG(content_rating) as avg_content,
               AVG(organization_rating) as avg_org,
               AVG(venue_rating) as avg_venue
        FROM feedback
        WHERE event_id = ?
    ");
    $stats_stmt->bind_param("i", $workshop_id);
    $stats_stmt->execute();
    $stats = $stats_stmt->get_result()->fetch_assoc();

    // 3. Fetch individual reviews for list output
    $reviews_stmt = $conn->prepare("
        SELECT f.id, f.overall_rating, f.submitted_at, f.comments, u.full_name, u.email
        FROM feedback f
        JOIN users u ON f.user_id = u.id
        WHERE f.event_id = ?
        ORDER BY f.submitted_at DESC
    ");
    $reviews_stmt->bind_param("i", $workshop_id);
    $reviews_stmt->execute();
    $reviews_res = $reviews_stmt->get_result();
    if ($reviews_res) {
        while ($row = $reviews_res->fetch_assoc()) $reviews[] = $row;
    }

} catch (Exception $e) {
    $error_msg = $e->getMessage();
}

include $base_path . 'includes/header.php';
include $base_path . 'includes/navbar.php';
?>

<div class="container my-5">
    <!-- Header bar -->
    <?php if (empty($error_msg) && $ws): ?>
        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center mb-4 gap-3">
            <div>
                <h1 class="h2 mb-0 fw-bold"><i class="fa-solid fa-square-poll-vertical text-warning me-2"></i>Workshop Scorecard</h1>
                <p class="text-muted mb-0">Performance evaluation metrics and analytics for <strong><?php echo htmlspecialchars($ws['title']); ?></strong></p>
            </div>
            <div class="d-flex gap-2">
                <a href="../feedback/export.php?format=pdf&workshop_id=<?php echo $workshop_id; ?>" class="btn btn-danger btn-sm fw-bold shadow-sm" target="_blank"><i class="fa-solid fa-file-pdf me-1"></i>Export PDF Report</a>
                <a href="../feedback/manage.php?workshop_id=<?php echo $workshop_id; ?>" class="btn btn-outline-primary btn-sm"><i class="fa-solid fa-comments me-1"></i>All Feedback</a>
                <a href="../dashboard.php" class="btn btn-outline-dark btn-sm"><i class="fa-solid fa-arrow-left me-1"></i>Back</a>
            </div>
        </div>

        <!-- Workshop Details Banner Card -->
        <div class="card border-0 shadow-sm rounded-3 bg-white p-4 mb-4">
            <div class="row align-items-center">
                <div class="col-md-9">
                    <span class="badge bg-success text-uppercase mb-2">Evaluated Workshop</span>
                    <h3 class="fw-bold text-dark mb-1"><?php echo htmlspecialchars($ws['title']); ?></h3>
                    <p class="text-muted small mb-0">
                        <i class="fa-solid fa-user-tie me-1"></i>Speaker: <span class="fw-semibold text-dark"><?php echo htmlspecialchars($ws['speaker']); ?></span> | 
                        <i class="fa-solid fa-building me-1"></i>Host: <?php echo htmlspecialchars($ws['host']); ?> | 
                        <i class="fa-solid fa-layer-group me-1"></i>Domain: <?php echo htmlspecialchars($ws['domain']); ?> | 
                        <i class="fa-solid fa-calendar me-1"></i>Held: <?php echo date('M d, Y', strtotime($ws['start_date'])); ?>
                    </p>
                </div>
                <div class="col-md-3 text-md-end mt-3 mt-md-0">
                    <div class="bg-light p-3 rounded text-center border">
                        <span class="text-muted small d-block text-uppercase fw-bold">Feedback Count</span>
                        <h2 class="fw-bold text-primary mb-0"><?php echo intval($stats['total_count']); ?></h2>
                    </div>
                </div>
            </div>
        </div>

        <?php if (intval($stats['total_count']) > 0): ?>
            <!-- Analytics Visual Chart & Metrics Grid -->
            <div class="row g-4 mb-5">
                <!-- Metrics Averages Grid -->
                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm rounded-3 bg-white p-4 h-100">
                        <h5 class="fw-bold mb-4 text-dark"><i class="fa-solid fa-chart-line text-primary me-2"></i>Performance Scores</h5>
                        
                        <?php
                        $metrics = [
                            ['label' => 'Overall Rating', 'avg' => $stats['avg_overall'], 'color' => 'bg-warning', 'text_color' => 'text-warning'],
                            ['label' => 'Speaker Performance', 'avg' => $stats['avg_speaker'], 'color' => 'bg-primary', 'text_color' => 'text-primary'],
                            ['label' => 'Technical Content', 'avg' => $stats['avg_content'], 'color' => 'bg-success', 'text_color' => 'text-success'],
                            ['label' => 'Session Organization', 'avg' => $stats['avg_org'], 'color' => 'bg-info', 'text_color' => 'text-info'],
                            ['label' => 'Venue & Facilities', 'avg' => $stats['avg_venue'], 'color' => 'bg-dark', 'text_color' => 'text-dark']
                        ];
                        ?>
                        
                        <?php foreach ($metrics as $m): ?>
                            <?php 
                            $percent = (floatval($m['avg']) / 5) * 100;
                            ?>
                            <div class="mb-3.5">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="fw-semibold text-dark small"><?php echo htmlspecialchars($m['label']); ?></span>
                                    <span class="fw-bold <?php echo $m['text_color']; ?> small"><?php echo number_format($m['avg'], 2); ?> / 5.00</span>
                                </div>
                                <div class="progress" style="height: 10px;">
                                    <div class="progress-bar <?php echo $m['color']; ?>" role="progressbar" style="width: <?php echo $percent; ?>%" aria-valuenow="<?php echo $percent; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Interactive Canvas Radar Chart (Part 13) -->
                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm rounded-3 bg-white p-4 h-100">
                        <h5 class="fw-bold mb-4 text-dark"><i class="fa-solid fa-compass text-success me-2"></i>Evaluation Visual Insights</h5>
                        <div class="chart-container" style="position: relative; height: 260px;">
                            <canvas id="scorecardRadarChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Participant Comments Journal -->
            <div class="card shadow-sm border-0 rounded-3 overflow-hidden bg-white mb-5">
                <div class="card-header bg-dark text-white py-3">
                    <h5 class="mb-0"><i class="fa-solid fa-quote-left text-warning me-2"></i>Participant Comments &amp; Written Feedback</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped align-middle mb-0">
                            <thead class="table-dark">
                                <tr>
                                    <th style="width: 25%;">Attendee</th>
                                    <th class="text-center" style="width: 15%;">Overall Rate</th>
                                    <th style="width: 45%;">Written Comments</th>
                                    <th style="width: 15%;">Submitted On</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($reviews as $rev): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-bold text-dark small"><?php echo htmlspecialchars($rev['full_name']); ?></div>
                                            <div class="text-muted small fs-9"><?php echo htmlspecialchars($rev['email']); ?></div>
                                        </td>
                                        <td class="text-center">
                                            <span class="text-warning fw-bold fs-6">
                                                <?php for ($i = 1; $i <= 5; $i++) {
                                                    echo ($i <= $rev['overall_rating']) ? '★' : '☆';
                                                } ?>
                                            </span>
                                            <span class="text-muted small d-block fs-9"><?php echo $rev['overall_rating']; ?> Stars</span>
                                        </td>
                                        <td class="small text-dark font-monospace" style="white-space: pre-wrap;"><?php echo htmlspecialchars($rev['comments']); ?></td>
                                        <td class="text-muted small"><?php echo date("M d, Y h:i A", strtotime($rev['submitted_at'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php else: // No feedback yet ?>
            <div class="card border-0 shadow-sm rounded-3 p-5 bg-white text-center">
                <div class="display-3 text-muted mb-3"><i class="fa-solid fa-comment-slash"></i></div>
                <h3 class="fw-bold text-dark">No Feedback Received Yet</h3>
                <p class="text-muted mb-0">Evaluation statistics and scoring radars will become available as soon as attendees complete the session and submit their feedback form.</p>
            </div>
        <?php endif; ?>

    <?php else: ?>
        <div class="alert alert-danger border-start border-4 border-danger shadow-sm" role="alert">
            <i class="fa-solid fa-triangle-exclamation me-2"></i><strong>Scorecard Error:</strong> <?php echo htmlspecialchars($error_msg); ?>
        </div>
    <?php endif; ?>
</div>

<?php if (empty($error_msg) && intval($stats['total_count']) > 0): ?>
    <!-- Load Chart.js from secure CDN (jsDelivr) -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const ctx = document.getElementById('scorecardRadarChart').getContext('2d');
        
        // Define clean data sets
        const data = {
            labels: [
                'Overall Experience',
                'Speaker Communication',
                'Technical Content',
                'Session Organization',
                'Venue & Facilities'
            ],
            datasets: [{
                label: 'Average Score',
                data: [
                    <?php echo number_format($stats['avg_overall'], 2); ?>,
                    <?php echo number_format($stats['avg_speaker'], 2); ?>,
                    <?php echo number_format($stats['avg_content'], 2); ?>,
                    <?php echo number_format($stats['avg_org'], 2); ?>,
                    <?php echo number_format($stats['avg_venue'], 2); ?>
                ],
                fill: true,
                backgroundColor: 'rgba(59, 130, 246, 0.2)', // Sleek primary blue accent transparent fill (#3b82f6)
                borderColor: '#3b82f6', // Solid primary blue border
                pointBackgroundColor: '#3b82f6',
                pointBorderColor: '#fff',
                pointHoverBackgroundColor: '#fff',
                pointHoverBorderColor: '#3b82f6',
                borderWidth: 2
            }]
        };

        // Render beautiful radar chart
        new Chart(ctx, {
            type: 'radar',
            data: data,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    r: {
                        angleLines: {
                            display: true,
                            color: '#e2e8f0'
                        },
                        suggestedMin: 1,
                        suggestedMax: 5,
                        ticks: {
                            stepSize: 1,
                            color: '#64748b'
                        },
                        pointLabels: {
                            font: {
                                size: 10,
                                weight: 'bold'
                            },
                            color: '#1e293b'
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false // hide legend since we only have 1 dataset
                    }
                }
            }
        });
    });
    </script>
<?php endif; ?>

<style>
.mb-3.5 {
    margin-bottom: 1.15rem !important;
}
.fs-9 {
    font-size: 11px !important;
}
</style>

<?php
include $base_path . 'includes/footer.php';
?>
