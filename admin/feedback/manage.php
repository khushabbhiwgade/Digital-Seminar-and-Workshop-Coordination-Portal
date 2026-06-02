<?php
// admin/feedback/manage.php
// ------------------------------------------------------------
// Admin — Feedback Management Board (Part 10)
// ------------------------------------------------------------

$base_path = '../../';
$page_title = 'Feedback Board - Admin Panel';
$active_page = 'admin_feedback';

require_once $base_path . 'includes/auth.php';
require_role('admin');

require_once $base_path . 'config/db_connect.php';

// Fetch workshops listing for filter dropdown
$workshops = [];
$ws_res = $conn->query("SELECT id, title FROM workshops ORDER BY title ASC");
if ($ws_res) {
    while ($row = $ws_res->fetch_assoc()) $workshops[] = $row;
}

// Retrieve filter criteria from GET/POST parameters
$search = trim($_GET['search'] ?? '');
$workshop_filter = intval($_GET['workshop_id'] ?? 0);
$rating_filter = $_GET['rating_range'] ?? '';
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';

// Build dynamic query matching selected filters
$query_parts = [];
$types = '';
$params = [];

$sql = "
    SELECT f.id, f.overall_rating, f.submitted_at, 
           u.full_name, u.email, 
           w.title as ws_title, w.id as ws_id
    FROM feedback f
    JOIN users u ON f.user_id = u.id
    JOIN workshops w ON f.event_id = w.id
    WHERE 1=1
";

if (!empty($search)) {
    $sql .= " AND (u.full_name LIKE ? OR w.title LIKE ?)";
    $like_search = "%$search%";
    $query_parts[] = $like_search;
    $query_parts[] = $like_search;
    $types .= 'ss';
}

if ($workshop_filter > 0) {
    $sql .= " AND f.event_id = ?";
    $query_parts[] = $workshop_filter;
    $types .= 'i';
}

if (!empty($rating_filter)) {
    if ($rating_filter === 'high') {
        $sql .= " AND f.overall_rating >= 4";
    } elseif ($rating_filter === 'mid') {
        $sql .= " AND f.overall_rating = 3";
    } elseif ($rating_filter === 'low') {
        $sql .= " AND f.overall_rating <= 2";
    }
}

if (!empty($start_date)) {
    $sql .= " AND f.submitted_at >= ?";
    $query_parts[] = $start_date . ' 00:00:00';
    $types .= 's';
}

if (!empty($end_date)) {
    $sql .= " AND f.submitted_at <= ?";
    $query_parts[] = $end_date . ' 23:59:59';
    $types .= 's';
}

$sql .= " ORDER BY f.submitted_at DESC";

$stmt = $conn->prepare($sql);
if (!empty($types)) {
    $stmt->bind_param($types, ...$query_parts);
}
$stmt->execute();
$feedback_res = $stmt->get_result();

$feedback_list = [];
if ($feedback_res) {
    while ($row = $feedback_res->fetch_assoc()) $feedback_list[] = $row;
}

include $base_path . 'includes/header.php';
include $base_path . 'includes/navbar.php';
?>

<div class="container my-5">
    <!-- Header bar -->
    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center mb-4 gap-3">
        <div>
            <h1 class="h2 mb-0 fw-bold"><i class="fa-solid fa-comments text-warning me-2"></i>Evaluations &amp; Feedback Board</h1>
            <p class="text-muted mb-0">Browse, filter, and inspect workshop evaluations submitted by participants.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="export.php?format=csv<?php echo !empty($_SERVER['QUERY_STRING']) ? '&' . $_SERVER['QUERY_STRING'] : ''; ?>" class="btn btn-outline-success btn-sm fw-bold"><i class="fa-solid fa-file-excel me-1"></i>Export CSV</a>
            <a href="../dashboard.php" class="btn btn-outline-dark btn-sm"><i class="fa-solid fa-arrow-left me-1"></i>Back to Dashboard</a>
        </div>
    </div>

    <!-- Filters Expansion Card -->
    <div class="card border-0 shadow-sm rounded-3 bg-white p-4 mb-4">
        <h5 class="fw-bold mb-3"><i class="fa-solid fa-filter text-primary me-2"></i>Filter Feedback</h5>
        <form method="GET" action="manage.php" class="row g-3">
            <!-- Search Keyword -->
            <div class="col-md-3">
                <label for="search" class="form-label small fw-semibold text-muted">Search Keyword</label>
                <input type="text" name="search" id="search" class="form-control form-control-sm" placeholder="Participant or workshop..." value="<?php echo htmlspecialchars($search); ?>">
            </div>
            
            <!-- Workshop Selection -->
            <div class="col-md-3">
                <label for="workshop_id" class="form-label small fw-semibold text-muted">Workshop</label>
                <select name="workshop_id" id="workshop_id" class="form-select form-select-sm">
                    <option value="0">All Workshops</option>
                    <?php foreach ($workshops as $ws): ?>
                        <option value="<?php echo $ws['id']; ?>" <?php echo $workshop_filter === intval($ws['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($ws['title']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <!-- Rating Range -->
            <div class="col-md-2">
                <label for="rating_range" class="form-label small fw-semibold text-muted">Rating Range</label>
                <select name="rating_range" id="rating_range" class="form-select form-select-sm">
                    <option value="">All Ratings</option>
                    <option value="high" <?php echo $rating_filter === 'high' ? 'selected' : ''; ?>>High (4-5 Stars)</option>
                    <option value="mid" <?php echo $rating_filter === 'mid' ? 'selected' : ''; ?>>Medium (3 Stars)</option>
                    <option value="low" <?php echo $rating_filter === 'low' ? 'selected' : ''; ?>>Low (1-2 Stars)</option>
                </select>
            </div>
            
            <!-- Start Date -->
            <div class="col-md-2">
                <label for="start_date" class="form-label small fw-semibold text-muted">Start Date</label>
                <input type="date" name="start_date" id="start_date" class="form-control form-control-sm" value="<?php echo htmlspecialchars($start_date); ?>">
            </div>
            
            <!-- End Date -->
            <div class="col-md-2">
                <label for="end_date" class="form-label small fw-semibold text-muted">End Date</label>
                <input type="date" name="end_date" id="end_date" class="form-control form-control-sm" value="<?php echo htmlspecialchars($end_date); ?>">
            </div>
            
            <!-- Actions -->
            <div class="col-12 d-flex justify-content-end gap-2 mt-3 border-top pt-3">
                <a href="manage.php" class="btn btn-sm btn-outline-secondary px-3"><i class="fa-solid fa-arrow-rotate-left me-1"></i>Reset Filters</a>
                <button type="submit" class="btn btn-sm btn-primary px-4 fw-bold"><i class="fa-solid fa-magnifying-glass me-1"></i>Apply Filters</button>
            </div>
        </form>
    </div>

    <!-- Feedbacks Table -->
    <div class="card shadow-sm border-0 rounded-3 overflow-hidden bg-white">
        <div class="card-header bg-dark text-white py-3 d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fa-solid fa-table-list text-warning me-2"></i>Evaluations Journal</h5>
            <span class="badge bg-secondary font-monospace"><?php echo count($feedback_list); ?> records found</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>Participant</th>
                            <th>Workshop Title</th>
                            <th class="text-center">Overall Rating</th>
                            <th>Submission Date</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($feedback_list) > 0): ?>
                            <?php foreach ($feedback_list as $fb): ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold text-dark"><?php echo htmlspecialchars($fb['full_name']); ?></div>
                                        <div class="text-muted small"><?php echo htmlspecialchars($fb['email']); ?></div>
                                    </td>
                                    <td>
                                        <div class="small fw-semibold text-dark mb-0.5"><?php echo htmlspecialchars($fb['ws_title']); ?></div>
                                        <a href="../workshops/scorecard.php?id=<?php echo $fb['ws_id']; ?>" class="badge bg-secondary-subtle text-secondary text-decoration-none small fs-9 border"><i class="fa-solid fa-square-poll-vertical me-1"></i>Scorecard</a>
                                    </td>
                                    <td class="text-center">
                                        <span class="text-warning fw-bold fs-5">
                                            <?php for ($i = 1; $i <= 5; $i++) {
                                                echo ($i <= $fb['overall_rating']) ? '★' : '☆';
                                            } ?>
                                        </span>
                                        <span class="text-muted small d-block fs-9 fw-semibold"><?php echo $fb['overall_rating']; ?>/5 Stars</span>
                                    </td>
                                    <td class="text-muted small"><?php echo date("M d, Y h:i A", strtotime($fb['submitted_at'])); ?></td>
                                    <td class="text-center">
                                        <a href="view.php?id=<?php echo $fb['id']; ?>" class="btn btn-xs btn-outline-primary px-3 fw-bold">
                                            <i class="fa-solid fa-magnifying-glass me-1"></i>View Details
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted py-5">
                                    <i class="fa-solid fa-comment-slash fs-2 d-block mb-2 text-secondary"></i>
                                    No workshop evaluations match the selected criteria.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
.btn-xs {
    padding: 0.25rem 0.5rem;
    font-size: 0.75rem;
    border-radius: 0.2rem;
    line-height: 1.2;
}
.fs-9 {
    font-size: 11px !important;
}
</style>

<?php
include $base_path . 'includes/footer.php';
?>
