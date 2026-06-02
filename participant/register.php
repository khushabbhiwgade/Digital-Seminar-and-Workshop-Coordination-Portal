<?php
// participant/register.php
$base_path = '../';
$page_title = 'Event Registration - Campus Connect Portal';
$active_page = 'register';

require_once $base_path . 'includes/auth.php';
require_once $base_path . 'config/db_connect.php';

require_role(['participant', 'student']);
$user_id = get_user_id();

// Fetch participant profile details
$sql = "SELECT full_name, email, mobile, participant_type, organization FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();
$user_details = $res->fetch_assoc();

// Check if attributes are set to mark them readonly
$readonly_name = !empty($user_details['full_name']) ? 'readonly' : '';
$readonly_email = !empty($user_details['email']) ? 'readonly' : '';
$readonly_mobile = !empty($user_details['mobile']) ? 'readonly' : '';
$readonly_org = !empty($user_details['organization']) ? 'readonly' : '';
$disabled_cat = !empty($user_details['participant_type']) ? 'disabled' : '';

include $base_path . 'includes/header.php';
include $base_path . 'includes/navbar.php';

$selected_event_id = intval($_GET['event_id'] ?? 0);
?>

<div class="bg-dark text-white py-4 mb-4 text-center" style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); border-bottom: 3px solid #eab308;">
    <div class="container">
        <h1 class="display-6 fw-bold">Event Registration</h1>
        <p class="lead text-muted mb-0">Confirm your participant credentials below to reserve your workshop seat.</p>
    </div>
</div>

<main class="container my-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            
            <?php
            if (isset($_GET['status'])):
                $status = $_GET['status'];
                $message = isset($_GET['msg']) ? htmlspecialchars($_GET['msg']) : '';
                
                if ($status === 'success'):
            ?>
                    <div class="alert alert-success alert-dismissible fade show shadow-sm border-start border-4 border-success p-4 mb-4" role="alert">
                        <div class="d-flex align-items-center">
                            <div class="fs-2 text-success me-3"><i class="fa-solid fa-circle-check"></i></div>
                            <div>
                                <h5 class="alert-heading fw-bold mb-1">Registration Successful!</h5>
                                <p class="mb-0"><?= $message ?></p>
                            </div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
            <?php
                elseif ($status === 'duplicate'):
            ?>
                    <div class="alert alert-warning alert-dismissible fade show shadow-sm border-start border-4 border-warning p-4 mb-4" role="alert">
                        <div class="d-flex align-items-center">
                            <div class="fs-2 text-warning me-3"><i class="fa-solid fa-circle-exclamation"></i></div>
                            <div>
                                <h5 class="alert-heading fw-bold mb-1">Already Registered!</h5>
                                <p class="mb-0"><?= $message ?></p>
                            </div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
            <?php
                elseif ($status === 'error'):
            ?>
                    <div class="alert alert-danger alert-dismissible fade show shadow-sm border-start border-4 border-danger p-4 mb-4" role="alert">
                        <div class="d-flex align-items-center">
                            <div class="fs-2 text-danger me-3"><i class="fa-solid fa-circle-xmark"></i></div>
                            <div>
                                <h5 class="alert-heading fw-bold mb-1">Registration Failed!</h5>
                                <p class="mb-0"><?= $message ?></p>
                            </div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
            <?php
                endif;
            endif;
            ?>

            <div class="card shadow border-0 p-4 p-md-5 bg-white rounded-3">
                <div class="d-flex align-items-center mb-4">
                    <div class="bg-primary text-white p-3 rounded-3 me-3"><i class="fa-solid fa-file-signature fs-4"></i></div>
                    <div>
                        <h2 class="h4 mb-0 fw-bold">Reserve Your Slot</h2>
                        <p class="text-muted small mb-0">Fields marked with <span class="text-danger">*</span> are required</p>
                    </div>
                </div>
                
                <form action="submit_registration.php" method="POST" id="registrationForm" novalidate>
                    
                    <div class="mb-3">
                        <label for="full_name" class="form-label fw-semibold">Full Name <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-muted"><i class="fa-solid fa-user"></i></span>
                            <input type="text" class="form-control" id="full_name" name="full_name" value="<?php echo htmlspecialchars($user_details['full_name'] ?? ''); ?>" <?php echo $readonly_name; ?> required>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label fw-semibold">Email Address <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted"><i class="fa-solid fa-envelope"></i></span>
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user_details['email'] ?? ''); ?>" <?php echo $readonly_email; ?> required>
                            </div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="mobile" class="form-label fw-semibold">Mobile Number <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted"><span class="small fw-semibold">+91</span></span>
                                <input type="tel" class="form-control" id="mobile" name="mobile" placeholder="9876543210" maxlength="10" value="<?php echo htmlspecialchars($user_details['mobile'] ?? ''); ?>" <?php echo $readonly_mobile; ?> required>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="participant_type" class="form-label fw-semibold">Category <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted"><i class="fa-solid fa-user-tag"></i></span>
                                <?php if ($disabled_cat): ?>
                                    <input type="hidden" name="participant_type" value="<?php echo htmlspecialchars($user_details['participant_type']); ?>">
                                <?php endif; ?>
                                <select class="form-select" id="participant_type" name="participant_type" <?php echo $disabled_cat; ?> required>
                                    <option value="" disabled>Select Category</option>
                                    <?php
                                    $categories = ['Student', 'Faculty', 'Professional', 'Researcher', 'Alumni', 'Other'];
                                    foreach ($categories as $cat) {
                                        $selected = ($user_details['participant_type'] === $cat) ? 'selected' : '';
                                        echo "<option value=\"$cat\" $selected>$cat</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="organization" class="form-label fw-semibold">Organization / College <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted"><i class="fa-solid fa-building"></i></span>
                                <input type="text" class="form-control" id="organization" name="organization" value="<?php echo htmlspecialchars($user_details['organization'] ?? ''); ?>" <?php echo $readonly_org; ?> required>
                            </div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="event_id" class="form-label fw-semibold">Select Seminar / Workshop <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-muted"><i class="fa-solid fa-chalkboard-user"></i></span>
                            <select class="form-select" id="event_id" name="event_id" required>
                                <option value="" disabled <?php echo ($selected_event_id === 0) ? 'selected' : ''; ?>>Choose Event</option>
                                <?php
                                $ev_sql = "SELECT id, title, event_date FROM events WHERE event_date >= CURDATE() ORDER BY event_date ASC";
                                $ev_res = $conn->query($ev_sql);
                                if ($ev_res) {
                                    while ($ev = $ev_res->fetch_assoc()) {
                                        $date_f = date("M d", strtotime($ev['event_date']));
                                        $selected = ($selected_event_id === intval($ev['id'])) ? 'selected' : '';
                                        echo "<option value=\"" . $ev['id'] . "\" $selected>" . htmlspecialchars($ev['title']) . " ($date_f)</option>";
                                    }
                                }
                                ?>
                            </select>
                        </div>
                    </div>

                    <div class="d-grid mt-4">
                        <button type="submit" class="btn btn-warning text-dark fw-bold btn-lg">
                            <i class="fa-solid fa-paper-plane me-2"></i>Confirm &amp; Submit Registration
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</main>

<?php
include $base_path . 'includes/footer.php';
?>
