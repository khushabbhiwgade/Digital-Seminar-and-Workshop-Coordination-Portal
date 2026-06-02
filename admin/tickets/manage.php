<?php
// admin/tickets/manage.php
// ------------------------------------------------------------
// Admin — Workshop Ticket Management Desk
// ------------------------------------------------------------
$base_path = '../../';
$page_title = 'Ticket Management - Admin Panel';
$active_page = 'admin_tickets';

require_once $base_path . 'includes/auth.php';
require_role('admin');
require_once $base_path . 'config/db_connect.php';

$filter_status = $_GET['status'] ?? 'All';
$search_query = trim($_GET['search'] ?? '');

// Build query
$sql = "SELECT t.id, t.ticket_number, t.status, t.created_at,
               u.full_name, u.email,
               w.title as ws_title
        FROM tickets t
        JOIN users u ON t.user_id = u.id
        JOIN workshops w ON t.event_id = w.id";

$where_clauses = [];
$params = [];
$types = '';

if ($filter_status !== 'All') {
    $where_clauses[] = "t.status = ?";
    $params[] = $filter_status;
    $types .= 's';
}

if (!empty($search_query)) {
    $where_clauses[] = "(t.ticket_number LIKE ? OR u.full_name LIKE ? OR u.email LIKE ?)";
    $search_param = '%' . $search_query . '%';
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= 'sss';
}

if (count($where_clauses) > 0) {
    $sql .= " WHERE " . implode(" AND ", $where_clauses);
}

$sql .= " ORDER BY t.created_at DESC";

$stmt = $conn->prepare($sql);
if (count($params) > 0) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$tickets_res = $stmt->get_result();

include $base_path . 'includes/header.php';
include $base_path . 'includes/navbar.php';
?>

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h2 mb-0"><i class="fa-solid fa-ticket text-primary me-2"></i>Ticket Management Desk</h1>
            <p class="text-muted mb-0">Audit, search, and verify student registrations for all workshops</p>
        </div>
        <div class="d-flex gap-2">
            <a href="../attendance.php" class="btn btn-outline-primary btn-sm"><i class="fa-solid fa-clipboard-user me-1"></i>Attendance Desk</a>
            <a href="../dashboard.php" class="btn btn-outline-dark btn-sm"><i class="fa-solid fa-arrow-left me-1"></i>Back to Dashboard</a>
        </div>
    </div>

    <!-- Filters and Search Panel -->
    <div class="card shadow-sm border-0 rounded-3 p-3 mb-4 bg-white">
        <form method="GET" class="row g-3 align-items-center">
            <div class="col-md-3">
                <label for="status" class="form-label fw-semibold text-secondary small">Filter Status</label>
                <select name="status" id="status" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="All" <?php echo ($filter_status === 'All') ? 'selected' : ''; ?>>All Tickets</option>
                    <option value="Pending" <?php echo ($filter_status === 'Pending') ? 'selected' : ''; ?>>Pending Verification</option>
                    <option value="Verified" <?php echo ($filter_status === 'Verified') ? 'selected' : ''; ?>>Verified</option>
                    <option value="Used" <?php echo ($filter_status === 'Used') ? 'selected' : ''; ?>>Used (Attended)</option>
                    <option value="Cancelled" <?php echo ($filter_status === 'Cancelled') ? 'selected' : ''; ?>>Cancelled (Absent)</option>
                </select>
            </div>
            <div class="col-md-6 col-lg-7">
                <label for="search" class="form-label fw-semibold text-secondary small">Search Ticket, Student, or Email</label>
                <div class="input-group input-group-sm">
                    <input type="text" name="search" id="search" class="form-control" placeholder="Type ticket number (TKT-...), student name, or email..." value="<?php echo htmlspecialchars($search_query); ?>">
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-magnifying-glass me-1"></i>Search</button>
                    <?php if (!empty($search_query) || $filter_status !== 'All'): ?>
                        <a href="manage.php" class="btn btn-outline-secondary"><i class="fa-solid fa-xmark"></i></a>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    </div>

    <!-- Navigation status tab sub-filters -->
    <div class="d-flex flex-wrap gap-2 mb-3">
        <a href="?status=All&search=<?php echo urlencode($search_query); ?>" class="btn btn-sm px-3 rounded-pill <?php echo ($filter_status === 'All') ? 'btn-primary' : 'btn-light border'; ?>">All Tickets</a>
        <a href="?status=Pending&search=<?php echo urlencode($search_query); ?>" class="btn btn-sm px-3 rounded-pill <?php echo ($filter_status === 'Pending') ? 'btn-warning text-dark' : 'btn-light border'; ?>">Pending</a>
        <a href="?status=Verified&search=<?php echo urlencode($search_query); ?>" class="btn btn-sm px-3 rounded-pill <?php echo ($filter_status === 'Verified') ? 'btn-info text-dark' : 'btn-light border'; ?>">Verified</a>
        <a href="?status=Used&search=<?php echo urlencode($search_query); ?>" class="btn btn-sm px-3 rounded-pill <?php echo ($filter_status === 'Used') ? 'btn-success' : 'btn-light border'; ?>">Used</a>
        <a href="?status=Cancelled&search=<?php echo urlencode($search_query); ?>" class="btn btn-sm px-3 rounded-pill <?php echo ($filter_status === 'Cancelled') ? 'btn-danger' : 'btn-light border'; ?>">Cancelled</a>
    </div>

    <!-- Ticket Journal Table -->
    <div class="card shadow-sm border-0 rounded-3 overflow-hidden bg-white">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>Student Details</th>
                        <th>Ticket Number</th>
                        <th>Workshop Domain & Session</th>
                        <th>Date Registered</th>
                        <th class="text-center">Status</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($tickets_res && $tickets_res->num_rows > 0): ?>
                        <?php while ($tk = $tickets_res->fetch_assoc()): ?>
                            <?php
                            $badge_class = 'bg-secondary';
                            $disp_status = htmlspecialchars($tk['status']);
                            if ($tk['status'] === 'Pending') {
                                $badge_class = 'bg-warning text-dark';
                                $disp_status = 'Pending Verification';
                            } elseif ($tk['status'] === 'Verified') {
                                $badge_class = 'bg-info text-dark';
                                $disp_status = 'Verified';
                            } elseif ($tk['status'] === 'Used') {
                                $badge_class = 'bg-success';
                                $disp_status = 'Used';
                            } elseif ($tk['status'] === 'Cancelled') {
                                $badge_class = 'bg-danger';
                                $disp_status = 'Cancelled';
                            }
                            ?>
                            <tr>
                                <td>
                                    <div class="fw-bold text-dark"><?php echo htmlspecialchars($tk['full_name']); ?></div>
                                    <div class="text-muted small"><?php echo htmlspecialchars($tk['email']); ?></div>
                                </td>
                                <td>
                                    <code class="font-monospace fw-bold text-primary"><?php echo htmlspecialchars($tk['ticket_number']); ?></code>
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark text-truncate" style="max-width: 250px;"><?php echo htmlspecialchars($tk['ws_title']); ?></div>
                                </td>
                                <td class="text-muted small">
                                    <?php echo date("M d, Y h:i A", strtotime($tk['created_at'])); ?>
                                </td>
                                <td class="text-center">
                                    <span class="badge <?php echo $badge_class; ?> px-2.5 py-1 text-uppercase fs-9"><?php echo $disp_status; ?></span>
                                </td>
                                <td class="text-center">
                                    <a href="view.php?id=<?php echo $tk['id']; ?>" class="btn btn-sm btn-outline-dark px-3 fw-bold">
                                        <i class="fa-solid fa-eye me-1"></i>View File
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted py-5">
                                <i class="fa-solid fa-circle-question fs-2 d-block mb-2 text-secondary"></i>
                                No tickets found matching the filter and search parameters.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
.fs-9 {
    font-size: 11px !important;
}
</style>

<?php
include $base_path . 'includes/footer.php';
?>
