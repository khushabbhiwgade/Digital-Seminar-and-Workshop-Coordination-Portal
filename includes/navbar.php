<?php
// includes/navbar.php
// ------------------------------------------------------------
// Central Navigation Bar - Role Aware
// ------------------------------------------------------------
require_once $base_path . 'includes/auth.php';
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center" href="<?php echo $base_path; ?>index.php">
            <i class="fa-solid fa-graduation-cap me-2 text-warning"></i>
            <span>Campus Connect Portal</span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto align-items-center">
                
                <?php if (!is_logged_in()): // Guest Menu ?>
                    <li class="nav-item">
                        <a class="nav-link<?php echo (isset($active_page) && $active_page === 'home') ? ' active' : ''; ?>" href="<?php echo $base_path; ?>index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link<?php echo (isset($active_page) && $active_page === 'events') ? ' active' : ''; ?>" href="<?php echo $base_path; ?>events.php">Events</a>
                    </li>
                    <li class="nav-item ms-lg-3 mt-2 mt-lg-0">
                        <a href="<?php echo $base_path; ?>auth/login.php" class="btn btn-outline-warning btn-sm px-3">Login</a>
                    </li>
                    <li class="nav-item ms-lg-2 mt-2 mt-lg-0">
                        <a href="<?php echo $base_path; ?>auth/signup.php" class="btn btn-warning btn-sm px-3 text-dark fw-bold">Sign Up</a>
                    </li>
                    
                <?php elseif (get_user_role() === 'student'): // Student Menu ?>
                    <li class="nav-item">
                        <a class="nav-link<?php echo (isset($active_page) && $active_page === 'home') ? ' active' : ''; ?>" href="<?php echo $base_path; ?>index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link<?php echo (isset($active_page) && $active_page === 'events') ? ' active' : ''; ?>" href="<?php echo $base_path; ?>events.php">Events</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link<?php echo (isset($active_page) && $active_page === 'student_dashboard') ? ' active' : ''; ?>" href="<?php echo $base_path; ?>student/dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item ms-lg-3 mt-2 mt-lg-0 text-white-50 d-flex align-items-center">
                        <span class="badge bg-secondary px-2 py-1 me-2 text-capitalize"><i class="fa-solid fa-user me-1"></i>Student</span>
                        <span class="text-light text-truncate d-inline-block align-middle" style="max-width: 120px;" title="<?php echo htmlspecialchars(get_user_name()); ?>"><?php echo htmlspecialchars(get_user_name()); ?></span>
                    </li>
                    <li class="nav-item ms-lg-3 mt-2 mt-lg-0">
                        <a href="<?php echo $base_path; ?>auth/logout.php" class="btn btn-outline-warning btn-sm px-3">
                            <i class="fa-solid fa-right-from-bracket me-1"></i> Logout
                        </a>
                    </li>

                <?php elseif (get_user_role() === 'admin'): // Admin Menu ?>
                    <li class="nav-item">
                        <a class="nav-link<?php echo (isset($active_page) && $active_page === 'admin_dashboard') ? ' active' : ''; ?>" href="<?php echo $base_path; ?>admin/dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link<?php echo (isset($active_page) && $active_page === 'events') ? ' active' : ''; ?>" href="<?php echo $base_path; ?>events.php">Events</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link<?php echo (isset($active_page) && $active_page === 'admin_students') ? ' active' : ''; ?>" href="<?php echo $base_path; ?>admin/students.php">Students</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link<?php echo (isset($active_page) && $active_page === 'admin_attendance') ? ' active' : ''; ?>" href="<?php echo $base_path; ?>admin/attendance.php">Attendance</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link<?php echo (isset($active_page) && $active_page === 'admin_certificates') ? ' active' : ''; ?>" href="<?php echo $base_path; ?>admin/certificates.php">Certificates</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link<?php echo (isset($active_page) && $active_page === 'admin_reports') ? ' active' : ''; ?>" href="<?php echo $base_path; ?>admin/reports.php">Reports</a>
                    </li>
                    <li class="nav-item ms-lg-3 mt-2 mt-lg-0 text-white-50 d-flex align-items-center">
                        <span class="badge bg-danger px-2 py-1 me-2">Admin</span>
                    </li>
                    <li class="nav-item ms-lg-3 mt-2 mt-lg-0">
                        <a href="<?php echo $base_path; ?>auth/logout.php" class="btn btn-outline-warning btn-sm px-3">
                            <i class="fa-solid fa-right-from-bracket me-1"></i> Logout
                        </a>
                    </li>

                <?php elseif (get_user_role() === 'coordinator'): // Coordinator Menu ?>
                    <li class="nav-item">
                        <a class="nav-link<?php echo (isset($active_page) && $active_page === 'coordinator_dashboard') ? ' active' : ''; ?>" href="<?php echo $base_path; ?>coordinator/dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link<?php echo (isset($active_page) && $active_page === 'events') ? ' active' : ''; ?>" href="<?php echo $base_path; ?>events.php">Events</a>
                    </li>
                    <li class="nav-item ms-lg-3 mt-2 mt-lg-0 text-white-50 d-flex align-items-center">
                        <span class="badge bg-primary px-2 py-1 me-2">Coordinator</span>
                    </li>
                    <li class="nav-item ms-lg-3 mt-2 mt-lg-0">
                        <a href="<?php echo $base_path; ?>auth/logout.php" class="btn btn-outline-warning btn-sm px-3">
                            <i class="fa-solid fa-right-from-bracket me-1"></i> Logout
                        </a>
                    </li>
                <?php endif; ?>

            </ul>
        </div>
    </div>
</nav>
