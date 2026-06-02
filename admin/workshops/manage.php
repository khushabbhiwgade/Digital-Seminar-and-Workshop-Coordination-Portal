<?php
// admin/workshops/manage.php
$base_path = '../../';
$page_title = 'Manage Workshops - Campus Connect';
$active_page = 'admin_workshops';

require_once $base_path . 'includes/auth.php';
require_role('admin');

require_once $base_path . 'config/db_connect.php';

$success_msg = "";
$error_msg = "";

// 1. Handle in-place toggle archiving
if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $workshop_id = intval($_GET['id']);
    
    if ($action === 'archive') {
        $stmt = $conn->prepare("UPDATE workshops SET status = 'archived' WHERE id = ?");
        $stmt->bind_param("i", $workshop_id);
        if ($stmt->execute()) {
            $success_msg = "Workshop successfully archived.";
        } else {
            $error_msg = "Error archiving workshop.";
        }
    } elseif ($action === 'unarchive') {
        // Find if date is in future to categorize as upcoming, else active
        $chk = $conn->prepare("SELECT start_date FROM workshops WHERE id = ?");
        $chk->bind_param("i", $workshop_id);
        $chk->execute();
        $ws_date = $chk->get_result()->fetch_assoc();
        
        $new_status = 'active';
        if ($ws_date && strtotime($ws_date['start_date']) > time()) {
            $new_status = 'upcoming';
        }
        
        $stmt = $conn->prepare("UPDATE workshops SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $new_status, $workshop_id);
        if ($stmt->execute()) {
            $success_msg = "Workshop successfully restored.";
        } else {
            $error_msg = "Error restoring workshop.";
        }
    }
}

// 2. Fetch redirect notifications
if (isset($_GET['msg'])) {
    $msg = $_GET['msg'];
    if ($msg === 'add_success') {
        $success_msg = "Workshop created successfully.";
    } elseif ($msg === 'edit_success') {
        $success_msg = "Workshop updated successfully.";
    } elseif ($msg === 'delete_success') {
        $success_msg = "Workshop deleted successfully.";
    }
}

// 3. Domain & Status Filter Setup
$domains = [
    'AI & ML', 'Data Science', 'Cybersecurity', 'Cloud Computing', 'IoT', 
    'Embedded Systems', 'Web Development', 'DevOps', 'Blockchain', 'VLSI', 
    'Computer Architecture', 'Robotics', 'AR/VR', 'UI/UX', 'Mobile Development'
];

$filter_domain = trim($_GET['domain'] ?? '');
$filter_status = trim($_GET['status'] ?? '');
$search_query = trim($_GET['search'] ?? '');

// 4. Construct Query
$sql = "SELECT * FROM workshops WHERE 1=1";
$params = [];
$types = "";

if (!empty($filter_domain)) {
    $sql .= " AND domain = ?";
    $params[] = $filter_domain;
    $types .= "s";
}

if (!empty($filter_status)) {
    if ($filter_status === 'all') {
        // show everything
    } else {
        $sql .= " AND status = ?";
        $params[] = $filter_status;
        $types .= "s";
    }
} else {
    // Default: show everything except archived, or show everything
}

if (!empty($search_query)) {
    $sql .= " AND (title LIKE ? OR speaker LIKE ? OR venue LIKE ?)";
    $like_term = "%" . $search_query . "%";
    $params[] = $like_term;
    $params[] = $like_term;
    $params[] = $like_term;
    $types .= "sss";
}

$sql .= " ORDER BY start_date DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$workshops_res = $stmt->get_result();

include $base_path . 'includes/header.php';
include $base_path . 'includes/navbar.php';
?>

<div class="container my-5">
    <!-- Page Header -->
    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center mb-4 gap-3">
        <div>
            <h1 class="h2 mb-0 fw-bold"><i class="fa-solid fa-chalkboard-user text-warning me-2"></i>Workshop Directory</h1>
            <p class="text-muted mb-0">List and organize academic seminars and training sessions dynamically.</p>
        </div>
        <a href="add.php" class="btn btn-warning text-dark fw-bold px-4 py-2 shadow-sm">
            <i class="fa-solid fa-plus me-2"></i>Add New Workshop
        </a>
    </div>

    <!-- Alert Notices -->
    <?php if (!empty($success_msg)): ?>
        <div class="alert alert-success alert-dismissible fade show border-start border-4 border-success shadow-sm" role="alert">
            <i class="fa-solid fa-circle-check me-2"></i><?php echo htmlspecialchars($success_msg); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (!empty($error_msg)): ?>
        <div class="alert alert-danger alert-dismissible fade show border-start border-4 border-danger shadow-sm" role="alert">
            <i class="fa-solid fa-triangle-exclamation me-2"></i><?php echo htmlspecialchars($error_msg); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Filters Dashboard -->
    <div class="card shadow-sm border-0 rounded-3 mb-4 bg-light">
        <div class="card-body p-4">
            <form method="GET" action="manage.php" class="row g-3">
                <div class="col-md-4">
                    <label for="search" class="form-label small fw-semibold text-muted">Search Title, Speaker, or Venue</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white text-muted"><i class="fa-solid fa-magnifying-glass"></i></span>
                        <input type="text" class="form-control border-start-0" id="search" name="search" placeholder="e.g. Transformers, Nair, Hall B" value="<?php echo htmlspecialchars($search_query); ?>">
                    </div>
                </div>

                <div class="col-md-3">
                    <label for="domain" class="form-label small fw-semibold text-muted">Domain</label>
                    <select class="form-select" id="domain" name="domain">
                        <option value="">All Domains</option>
                        <?php foreach ($domains as $d): ?>
                            <option value="<?php echo htmlspecialchars($d); ?>" <?php echo $filter_domain === $d ? 'selected' : ''; ?>><?php echo htmlspecialchars($d); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-3">
                    <label for="status" class="form-label small fw-semibold text-muted">Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="all" <?php echo $filter_status === 'all' ? 'selected' : ''; ?>>All Statuses</option>
                        <option value="active" <?php echo ($filter_status === 'active' || empty($filter_status)) ? 'selected' : ''; ?>>Active</option>
                        <option value="upcoming" <?php echo $filter_status === 'upcoming' ? 'selected' : ''; ?>>Upcoming</option>
                        <option value="archived" <?php echo $filter_status === 'archived' ? 'selected' : ''; ?>>Archived</option>
                    </select>
                </div>

                <div class="col-md-2 d-grid align-items-end">
                    <button type="submit" class="btn btn-dark fw-semibold py-2"><i class="fa-solid fa-sliders me-2"></i>Apply Filters</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Table Grid -->
    <div class="card shadow-sm border-0 rounded-3 overflow-hidden">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped align-middle mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>Poster</th>
                            <th>Workshop Title</th>
                            <th>Host &amp; Speaker</th>
                            <th>Dates &amp; Venue</th>
                            <th class="text-center">Capacity</th>
                            <th class="text-center">Status</th>
                            <th class="text-end px-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($workshops_res && $workshops_res->num_rows > 0): ?>
                            <?php while ($ws = $workshops_res->fetch_assoc()): ?>
                                <tr>
                                    <td class="px-3" style="width: 80px;">
                                        <?php if (!empty($ws['poster_image']) && file_exists($base_path . 'uploads/workshops/' . $ws['poster_image'])): ?>
                                            <img src="<?php echo $base_path . 'uploads/workshops/' . htmlspecialchars($ws['poster_image']); ?>" class="img-thumbnail rounded shadow-sm" style="max-height: 60px; max-width: 60px; object-fit: cover;" alt="Poster">
                                        <?php else: ?>
                                            <div class="bg-secondary text-white rounded d-flex align-items-center justify-content-center shadow-sm" style="height: 60px; width: 60px; font-size: 24px;">
                                                <i class="fa-solid fa-image-slash"></i>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-dark"><?php echo htmlspecialchars($ws['title']); ?></div>
                                        <span class="badge bg-secondary mt-1 text-uppercase letter-spacing-sm" style="font-size: 10px; background-color: #3b82f6 !important;"><?php echo htmlspecialchars($ws['domain']); ?></span>
                                    </td>
                                    <td>
                                        <div class="small fw-semibold text-muted"><i class="fa-solid fa-user-tie me-1"></i><?php echo htmlspecialchars($ws['speaker']); ?></div>
                                        <div class="small text-muted mt-1"><i class="fa-solid fa-building me-1"></i><?php echo htmlspecialchars($ws['host']); ?></div>
                                    </td>
                                    <td>
                                        <div class="small text-dark fw-semibold"><i class="fa-solid fa-calendar me-1"></i><?php echo date('M d, Y', strtotime($ws['start_date'])); ?></div>
                                        <div class="small text-muted mt-1"><i class="fa-solid fa-location-dot me-1"></i><?php echo htmlspecialchars($ws['venue']); ?></div>
                                    </td>
                                    <td class="text-center">
                                        <div class="fw-bold"><?php echo intval($ws['seats_remaining']); ?> <span class="text-muted fw-normal">/ <?php echo intval($ws['capacity']); ?></span></div>
                                        <div class="text-muted small fs-7">Seats Left</div>
                                    </td>
                                    <td class="text-center">
                                        <?php
                                        $status = $ws['status'];
                                        $badge_class = "bg-success";
                                        if ($status === 'archived') { $badge_class = "bg-secondary"; }
                                        elseif ($status === 'upcoming') { $badge_class = "bg-info text-dark"; }
                                        ?>
                                        <span class="badge <?php echo $badge_class; ?> px-2.5 py-1.5 text-capitalize"><?php echo htmlspecialchars($status); ?></span>
                                    </td>
                                    <td class="text-end px-4">
                                        <div class="btn-group shadow-sm">
                                            <a href="edit.php?id=<?php echo $ws['id']; ?>" class="btn btn-outline-dark btn-sm" title="Edit Workshop">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </a>
                                            <?php if ($status === 'archived'): ?>
                                                <a href="manage.php?action=unarchive&id=<?php echo $ws['id']; ?>&domain=<?php echo urlencode($filter_domain); ?>&status=<?php echo urlencode($filter_status); ?>&search=<?php echo urlencode($search_query); ?>" class="btn btn-outline-success btn-sm" title="Restore Workshop">
                                                    <i class="fa-solid fa-folder-open"></i>
                                                </a>
                                            <?php else: ?>
                                                <a href="manage.php?action=archive&id=<?php echo $ws['id']; ?>&domain=<?php echo urlencode($filter_domain); ?>&status=<?php echo urlencode($filter_status); ?>&search=<?php echo urlencode($search_query); ?>" class="btn btn-outline-warning btn-sm text-dark" title="Archive Workshop">
                                                    <i class="fa-solid fa-box-archive"></i>
                                                </a>
                                            <?php endif; ?>
                                            <a href="delete.php?id=<?php echo $ws['id']; ?>" class="btn btn-outline-danger btn-sm" onclick="return confirm('Are you sure you want to permanently delete this workshop? This action cannot be undone.');" title="Delete Workshop">
                                                <i class="fa-solid fa-trash-can"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-5 bg-white">
                                    <i class="fa-solid fa-magnifying-glass fs-2 text-secondary mb-3 d-block"></i>
                                    <h5>No workshops found matching the selected filters.</h5>
                                    <p class="text-muted small mb-0">Try adjusting your search criteria or register a new workshop.</p>
                                </td>
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
