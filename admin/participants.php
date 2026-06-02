<?php
// admin/participants.php
// ------------------------------------------------------------
// Admin — Participant Account Management
// ------------------------------------------------------------
$base_path = '../';
$page_title = 'Participant Accounts - Admin Panel';
$active_page = 'admin_participants';

require_once $base_path . 'includes/auth.php';
require_role('admin');
require_once $base_path . 'config/db_connect.php';

// Handle actions: toggle active/inactive, or delete
$action_msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['toggle_id'])) {
        $toggle_id = intval($_POST['toggle_id']);
        // Flip the is_active status
        $stmt = $conn->prepare("UPDATE users SET is_active = NOT is_active WHERE id = ? AND role IN ('participant', 'student')");
        $stmt->bind_param("i", $toggle_id);
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            $action_msg = '<div class="alert alert-success alert-dismissible fade show" role="alert"><i class="fa-solid fa-circle-check me-2"></i>Participant account status updated successfully.<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
        } else {
            $action_msg = '<div class="alert alert-warning alert-dismissible fade show" role="alert"><i class="fa-solid fa-triangle-exclamation me-2"></i>No changes made.<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
        }
    }

    if (isset($_POST['delete_id'])) {
        $delete_id = intval($_POST['delete_id']);
        // Delete registrations first (FK), then user
        $conn->begin_transaction();
        try {
            $stmt1 = $conn->prepare("DELETE FROM registrations WHERE user_id = ?");
            $stmt1->bind_param("i", $delete_id);
            $stmt1->execute();

            $stmt2 = $conn->prepare("DELETE FROM users WHERE id = ? AND role IN ('participant', 'student')");
            $stmt2->bind_param("i", $delete_id);
            $stmt2->execute();

            if ($stmt2->affected_rows > 0) {
                $conn->commit();
                $action_msg = '<div class="alert alert-success alert-dismissible fade show" role="alert"><i class="fa-solid fa-trash-can me-2"></i>Participant account and associated registrations deleted.<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
            } else {
                $conn->rollback();
                $action_msg = '<div class="alert alert-danger alert-dismissible fade show" role="alert"><i class="fa-solid fa-xmark me-2"></i>Could not delete. Participant not found.<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
            }
        } catch (Exception $e) {
            $conn->rollback();
            $action_msg = '<div class="alert alert-danger alert-dismissible fade show" role="alert"><i class="fa-solid fa-xmark me-2"></i>Error: ' . htmlspecialchars($e->getMessage()) . '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
        }
    }
}

// Fetch all participants
$participants = [];
$sql = "SELECT u.id, u.full_name, u.email, u.mobile, u.participant_type, u.organization, u.is_active, u.created_at,
               COUNT(r.id) AS reg_count
        FROM users u
        LEFT JOIN registrations r ON u.id = r.user_id
        WHERE u.role IN ('participant', 'student')
        GROUP BY u.id
        ORDER BY u.created_at DESC";
$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $participants[] = $row;
    }
}

include $base_path . 'includes/header.php';
include $base_path . 'includes/navbar.php';
?>

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h2 mb-0"><i class="fa-solid fa-users text-primary me-2"></i>Participant Accounts</h1>
            <p class="text-muted mb-0">Manage registered profiles and portal access permissions</p>
        </div>
        <a href="dashboard.php" class="btn btn-outline-dark btn-sm"><i class="fa-solid fa-arrow-left me-1"></i>Back to Dashboard</a>
    </div>

    <?php echo $action_msg; ?>

    <!-- Summary Cards -->
    <div class="row g-3 mb-4">
        <?php
        $total = count($participants);
        $active_count = 0;
        $inactive_count = 0;
        foreach ($participants as $p) {
            if ($p['is_active']) $active_count++;
            else $inactive_count++;
        }
        ?>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-3 text-center p-3 bg-white">
                <div class="display-6 text-primary mb-1"><i class="fa-solid fa-user-group"></i></div>
                <h3 class="fw-bold mb-0"><?php echo $total; ?></h3>
                <span class="text-muted small">Total Participants</span>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-3 text-center p-3 bg-white">
                <div class="display-6 text-success mb-1"><i class="fa-solid fa-user-check"></i></div>
                <h3 class="fw-bold mb-0"><?php echo $active_count; ?></h3>
                <span class="text-muted small">Active Accounts</span>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-3 text-center p-3 bg-white">
                <div class="display-6 text-danger mb-1"><i class="fa-solid fa-user-xmark"></i></div>
                <h3 class="fw-bold mb-0"><?php echo $inactive_count; ?></h3>
                <span class="text-muted small">Inactive Accounts</span>
            </div>
        </div>
    </div>

    <!-- Participants Table -->
    <div class="card shadow-sm border-0 rounded-3 overflow-hidden">
        <div class="card-header bg-dark text-white py-3">
            <h5 class="mb-0 fw-bold"><i class="fa-solid fa-table-list text-warning me-2"></i>All Registered Participant Records</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped align-middle mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th>Full Name</th>
                            <th>Email</th>
                            <th>Category</th>
                            <th>Organization</th>
                            <th>Mobile</th>
                            <th class="text-center">Regs</th>
                            <th class="text-center">Status</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($participants) > 0): ?>
                            <?php foreach ($participants as $idx => $part): ?>
                                <tr>
                                    <td class="fw-semibold"><?php echo $idx + 1; ?></td>
                                    <td class="fw-semibold"><?php echo htmlspecialchars($part['full_name']); ?></td>
                                    <td><a href="mailto:<?php echo htmlspecialchars($part['email']); ?>" class="text-decoration-none"><?php echo htmlspecialchars($part['email']); ?></a></td>
                                    <td><span class="badge bg-secondary"><?php echo htmlspecialchars($part['participant_type'] ?? 'Other'); ?></span></td>
                                    <td><?php echo htmlspecialchars($part['organization'] ?? '—'); ?></td>
                                    <td><?php echo htmlspecialchars($part['mobile'] ?? '—'); ?></td>
                                    <td class="text-center"><span class="badge bg-primary rounded-pill"><?php echo intval($part['reg_count']); ?></span></td>
                                    <td class="text-center">
                                        <?php if ($part['is_active']): ?>
                                            <span class="badge bg-success">Active</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="toggle_id" value="<?php echo $part['id']; ?>">
                                            <button type="submit" class="btn btn-sm <?php echo $part['is_active'] ? 'btn-outline-warning' : 'btn-outline-success'; ?>" title="<?php echo $part['is_active'] ? 'Deactivate' : 'Activate'; ?>">
                                                <i class="fa-solid <?php echo $part['is_active'] ? 'fa-ban' : 'fa-check'; ?>"></i>
                                            </button>
                                        </form>
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to permanently delete this participant and all their registrations?');">
                                            <input type="hidden" name="delete_id" value="<?php echo $part['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete Account">
                                                <i class="fa-solid fa-trash-can"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" class="text-center text-muted py-4">No participant accounts found in the system.</td>
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
