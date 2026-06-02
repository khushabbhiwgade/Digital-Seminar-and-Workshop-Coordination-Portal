<?php
$base_path = '../';
$page_title = 'Event Registration - Campus Connect Portal';
$active_page = 'register';
$load_main_js = true;

require_once $base_path . 'includes/auth.php';
require_once $base_path . 'config/db_connect.php';

require_role('student');
$user_id = get_user_id();
$sql = "SELECT full_name, email, mobile, college, department, year_of_study FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();
$user_details = $res->fetch_assoc();

// Determine which fields are already filled and should be readonly
$readonly_name = !empty($user_details['full_name']) ? 'readonly' : '';
$readonly_email = !empty($user_details['email']) ? 'readonly' : '';
$readonly_mobile = !empty($user_details['mobile']) ? 'readonly' : '';
$readonly_college = !empty($user_details['college']) ? 'readonly' : '';

include $base_path . 'includes/header.php';
include $base_path . 'includes/navbar.php';

$selected_event = trim($_GET['event'] ?? '');
?>

    <div class="bg-dark text-white py-4 mb-4 text-center" style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);">
        <div class="container">
            <h1 class="display-6 fw-bold">Student Event Registration</h1>
            <p class="lead text-muted mb-0">Fill out the registration details below to reserve your slot.</p>
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
                            <div class="d-flex">
                                <div class="fs-3 text-success me-3"><i class="fa-solid fa-circle-check"></i></div>
                                <div>
                                    <h5 class="alert-heading fw-bold">Registration Successful!</h5>
                                    <p class="mb-0"><?= $message ?></p>
                                    <hr class="my-2">
                                    <p class="mb-0 text-muted small"><i class="fa-solid fa-circle-info me-1"></i> A confirmation email simulation check was verified. Keep your mobile updated!</p>
                                </div>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                <?php
                    elseif ($status === 'duplicate'):
                ?>
                        <div class="alert alert-warning alert-dismissible fade show shadow-sm border-start border-4 border-warning p-4 mb-4" role="alert">
                            <div class="d-flex">
                                <div class="fs-3 text-warning me-3"><i class="fa-solid fa-circle-exclamation"></i></div>
                                <div>
                                    <h5 class="alert-heading fw-bold">Already Registered!</h5>
                                    <p class="mb-0"><?= $message ?></p>
                                    <p class="mt-2 mb-0 text-muted small"><i class="fa-solid fa-lightbulb me-1"></i> Choose a different seminar/workshop or write to portal.support@college.edu if you need to modify details.</p>
                                </div>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                <?php
                    elseif ($status === 'error'):
                ?>
                        <div class="alert alert-danger alert-dismissible fade show shadow-sm border-start border-4 border-danger p-4 mb-4" role="alert">
                            <div class="d-flex">
                                <div class="fs-3 text-danger me-3"><i class="fa-solid fa-circle-xmark"></i></div>
                                <div>
                                    <h5 class="alert-heading fw-bold">Registration Failed!</h5>
                                    <p class="mb-0"><?= $message ?></p>
                                </div>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                <?php
                    endif;
                endif;
                ?>

                <div class="card card-form p-4 p-md-5">
                    <div class="d-flex align-items-center mb-4">
                        <div class="bg-primary text-white p-3 rounded-3 me-3"><i class="fa-solid fa-file-signature fs-4"></i></div>
                        <div>
                            <h2 class="h4 mb-0">Fill out your Registration</h2>
                            <p class="text-muted small mb-0">Fields marked with <span class="text-danger">*</span> are mandatory</p>
                        </div>
                    </div>
                    
                    <form action="submit_registration.php" method="POST" id="registrationForm" novalidate>
                        
                        <div class="mb-3">
                            <label for="full_name" class="form-label">Full Name <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted"><i class="fa-solid fa-user"></i></span>
                                <input type="text" class="form-control" id="full_name" name="full_name" placeholder="John Doe" value="<?php echo htmlspecialchars($user_details['full_name'] ?? ''); ?>" <?php echo $readonly_name; ?> required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light text-muted"><i class="fa-solid fa-envelope"></i></span>
                                    <input type="email" class="form-control" id="email" name="email" placeholder="john.doe@example.com" value="<?php echo htmlspecialchars($user_details['email'] ?? ''); ?>" <?php echo $readonly_email; ?> required>
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="mobile" class="form-label">Mobile Number <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light text-muted"><span class="small fw-semibold">+91</span></span>
                                    <input type="tel" class="form-control" id="mobile" name="mobile" placeholder="9876543210" maxlength="10" value="<?php echo htmlspecialchars($user_details['mobile'] ?? ''); ?>" <?php echo $readonly_mobile; ?> required>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="college" class="form-label">College Name <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted"><i class="fa-solid fa-school"></i></span>
                                <input type="text" class="form-control" id="college" name="college" placeholder="e.g., Apex Institute of Technology" value="<?php echo htmlspecialchars($user_details['college'] ?? ''); ?>" <?php echo $readonly_college; ?> required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="department" class="form-label">Department <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light text-muted"><i class="fa-solid fa-building-columns"></i></span>
                                    <?php $dept = $user_details['department'] ?? ''; ?>
                                    <select class="form-select" id="department" name="department" required>
                                        <option value="" <?php echo empty($dept) ? 'selected' : ''; ?> disabled>Select Department</option>
                                        <option value="Computer Science & Engineering" <?php echo $dept === 'Computer Science & Engineering' ? 'selected' : ''; ?>>Computer Science &amp; Eng.</option>
                                        <option value="Information Technology" <?php echo $dept === 'Information Technology' ? 'selected' : ''; ?>>Information Technology</option>
                                        <option value="Electronics & Communication" <?php echo $dept === 'Electronics & Communication' ? 'selected' : ''; ?>>Electronics &amp; Comm.</option>
                                        <option value="Electrical Engineering" <?php echo $dept === 'Electrical Engineering' ? 'selected' : ''; ?>>Electrical Engineering</option>
                                        <option value="Mechanical Engineering" <?php echo $dept === 'Mechanical Engineering' ? 'selected' : ''; ?>>Mechanical Engineering</option>
                                        <option value="Civil Engineering" <?php echo $dept === 'Civil Engineering' ? 'selected' : ''; ?>>Civil Engineering</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="year_of_study" class="form-label">Year of Study <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light text-muted"><i class="fa-solid fa-calendar-days"></i></span>
                                    <?php $year = $user_details['year_of_study'] ?? ''; ?>
                                    <select class="form-select" id="year_of_study" name="year_of_study" required>
                                        <option value="" <?php echo empty($year) ? 'selected' : ''; ?> disabled>Select Academic Year</option>
                                        <option value="1st Year" <?php echo $year === '1st Year' ? 'selected' : ''; ?>>1st Year</option>
                                        <option value="2nd Year" <?php echo $year === '2nd Year' ? 'selected' : ''; ?>>2nd Year</option>
                                        <option value="3rd Year" <?php echo $year === '3rd Year' ? 'selected' : ''; ?>>3rd Year</option>
                                        <option value="4th Year" <?php echo $year === '4th Year' ? 'selected' : ''; ?>>4th Year</option>
                                    </select>
                                </div>
                            </div>
                        </div>



                        <div class="mb-4">
                            <label for="event_name" class="form-label">Select Seminar / Workshop <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted"><i class="fa-solid fa-chalkboard-user"></i></span>
                                <select class="form-select" id="event_name" name="event_name" required>
                                    <option value="" <?php echo empty($selected_event) ? 'selected' : ''; ?> disabled>Choose Event</option>
                                    <option value="National Seminar on AI & ML" <?php echo ($selected_event === 'AI & ML' || $selected_event === 'National Seminar on AI & ML') ? 'selected' : ''; ?>>National Seminar on AI &amp; ML (May 28)</option>
                                    <option value="Web Development with React & Node" <?php echo ($selected_event === 'Web Development' || $selected_event === 'Web Development with React & Node') ? 'selected' : ''; ?>>Web Development with React &amp; Node (June 02)</option>
                                    <option value="Cyber Security & Ethical Hacking" <?php echo ($selected_event === 'Cyber Security' || $selected_event === 'Cyber Security & Ethical Hacking') ? 'selected' : ''; ?>>Seminar on Cyber Security &amp; Ethical Hacking (June 10)</option>
                                    <option value="Cloud Computing & AWS Services" <?php echo ($selected_event === 'Cloud Computing' || $selected_event === 'Cloud Computing & AWS Services') ? 'selected' : ''; ?>>Workshop on Cloud Computing &amp; AWS Services (June 15)</option>
                                </select>
                            </div>
                        </div>

                        <div class="d-grid">
                            <button type="submit" name="register_submit" class="btn btn-primary btn-lg">
                                <i class="fa-solid fa-paper-plane me-2"></i>Submit Registration
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
