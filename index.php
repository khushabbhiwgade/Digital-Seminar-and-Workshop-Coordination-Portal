<?php
$base_path = './';
$page_title = 'Digital Seminar & Workshop Coordination Portal';
$active_page = 'home';
include $base_path . 'includes/header.php';
include $base_path . 'includes/navbar.php';
require_once $base_path . 'config/db_connect.php';
require_once $base_path . 'includes/workshop_availability.php';

if (isset($_GET['error']) && $_GET['error'] === 'unauthorized'):
?>
    <div class="container my-3">
        <div class="alert alert-danger alert-dismissible fade show border-start border-4 border-danger shadow-sm mb-0" role="alert">
            <div class="d-flex align-items-center">
                <i class="fa-solid fa-triangle-exclamation fs-4 text-danger me-3"></i>
                <div>
                    <h6 class="alert-heading fw-bold mb-1">Access Denied</h6>
                    <p class="mb-0 text-muted small">You do not have permission to view that resource. Please log in with authorized credentials.</p>
                </div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    </div>
<?php
endif;
?>
    
    <header class="hero-section text-center text-lg-start" style="padding: 80px 0; background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); color: white;">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-7">
                    <span class="badge bg-warning text-dark mb-3 px-3 py-2 fw-semibold">ACADEMIC YEAR 2026-27</span>
                    <h1 class="display-4 fw-bold mb-3">Learn. Innovate. Grow.</h1>
                    <p class="lead mb-4">Welcome to the College Seminar and Workshop Coordination Portal. Discover upcoming guest lectures, technical workshops, and national seminars conducted by industry leaders and academic pioneers.</p>
                    <div class="d-flex flex-wrap gap-3 justify-content-center justify-content-lg-start">
                        <a href="#featured-events" class="btn btn-warning btn-lg px-4 text-dark fw-bold">
                            <i class="fa-solid fa-calendar-days me-2"></i>Explore Workshops
                        </a>
                        <?php if (is_logged_in() && get_user_role() === 'student'): ?>
                            <a href="participant/dashboard.php" class="btn btn-outline-light btn-lg px-4">
                                <i class="fa-solid fa-gauge me-2"></i>Go to Dashboard
                            </a>
                        <?php elseif (is_logged_in() && get_user_role() === 'admin'): ?>
                            <a href="admin/dashboard.php" class="btn btn-outline-light btn-lg px-4">
                                <i class="fa-solid fa-gauge me-2"></i>Go to Admin Board
                            </a>
                        <?php else: ?>
                            <a href="auth/signup.php" class="btn btn-outline-light btn-lg px-4">
                                <i class="fa-solid fa-user-plus me-2"></i>Sign Up
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-lg-5 d-none d-lg-block text-center">
                    <img src="assets/images/Screenshot 2026-05-26 161333.png" alt="Portal Illustration" class="img-fluid rounded-3 shadow-lg" style="border: 1px solid rgba(255, 255, 255, 0.1); max-height: 380px; object-fit: cover;">
                </div>
            </div>
        </div>
    </header>

    <section class="section-padding bg-white" style="padding: 60px 0;">
        <div class="container">
            <div class="row text-center g-4">
                <div class="col-md-4">
                    <div class="p-4 border rounded-3 bg-light shadow-sm">
                        <div class="display-6 text-primary mb-3"><i class="fa-solid fa-laptop-code"></i></div>
                        <h4 class="h5">Hands-On Workshops</h4>
                        <p class="text-muted mb-0">Learn-by-doing technical workshops teaching Web Dev, Cloud, and Ethical Hacking.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="p-4 border rounded-3 bg-light shadow-sm">
                        <div class="display-6 text-primary mb-3"><i class="fa-solid fa-brain"></i></div>
                        <h4 class="h5">National Seminars</h4>
                        <p class="text-muted mb-0">Seminars on emerging tech domains like AI/ML, Cyber Security, and Big Data.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="p-4 border rounded-3 bg-light shadow-sm">
                        <div class="display-6 text-primary mb-3"><i class="fa-solid fa-award"></i></div>
                        <h4 class="h5">Certifications</h4>
                        <p class="text-muted mb-0">Earn participation credentials and expand your portfolio for career development.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="featured-events" class="section-padding" style="padding: 60px 0; background-color: #f8fafc;">
        <div class="container">
            <div class="d-flex justify-content-between align-items-end mb-4">
                <div>
                    <span class="text-primary fw-bold text-uppercase">Events At A Glance</span>
                    <h2 class="mb-0">Featured Upcoming Events</h2>
                </div>
                <?php if (is_logged_in() && get_user_role() === 'student'): ?>
                    <a href="participant/dashboard.php" class="btn btn-outline-primary d-none d-sm-inline-block">See All Events in Dashboard <i class="fa-solid fa-arrow-right ms-1"></i></a>
                <?php else: ?>
                    <a href="auth/login.php" class="btn btn-outline-primary d-none d-sm-inline-block">Login to Register <i class="fa-solid fa-arrow-right ms-1"></i></a>
                <?php endif; ?>
            </div>
            <div class="row g-4">
                <?php
                // Fetch active upcoming workshops with dynamic availability
                $upcoming_workshops = get_upcoming_workshops_availability($conn, 3);

                if (count($upcoming_workshops) > 0):
                    $index = 0;
                    foreach ($upcoming_workshops as $ws):
                        $domain = strtolower($ws['domain']);
                        $badge_class = 'bg-primary';
                        $icon = 'fa-chalkboard-user';
                        $gradient = 'linear-gradient(45deg, #3b82f6, #1d4ed8)'; // Default Blue

                        if (strpos($domain, 'ai') !== false || strpos($domain, 'machine') !== false || strpos($domain, 'intel') !== false) {
                            $badge_class = 'bg-primary';
                            $icon = 'fa-microchip';
                            $gradient = 'linear-gradient(45deg, #10b981, #059669)'; // Green
                        } elseif (strpos($domain, 'web') !== false || strpos($domain, 'development') !== false || strpos($domain, 'react') !== false || strpos($domain, 'node') !== false) {
                            $badge_class = 'bg-success';
                            $icon = 'fa-code';
                            $gradient = 'linear-gradient(45deg, #6366f1, #4f46e5)'; // Indigo
                        } elseif (strpos($domain, 'security') !== false || strpos($domain, 'cyber') !== false || strpos($domain, 'hacking') !== false) {
                            $badge_class = 'bg-danger';
                            $icon = 'fa-shield-halved';
                            $gradient = 'linear-gradient(45deg, #ef4444, #dc2626)'; // Red
                        } elseif (strpos($domain, 'cloud') !== false || strpos($domain, 'aws') !== false) {
                            $badge_class = 'bg-info';
                            $icon = 'fa-cloud';
                            $gradient = 'linear-gradient(45deg, #0ea5e9, #2563eb)'; // Sky Blue
                        }

                        // Set button path based on roles
                        $btn_path = 'auth/login.php';
                        $btn_text = 'Register Now';
                        if (is_logged_in()) {
                            if (get_user_role() === 'student') {
                                $btn_path = 'participant/dashboard.php';
                                $btn_text = 'Register in Dashboard';
                            } else {
                                $btn_path = get_user_role() . '/dashboard.php';
                                $btn_text = 'Go to Dashboard';
                            }
                        }
                        ?>
                        <div class="col-md-6 col-lg-4" data-workshop-id="<?php echo intval($ws['id']); ?>">
                            <div class="card h-100 shadow-sm border-0 rounded-3 overflow-hidden bg-white">
                                <div class="position-relative">
                                    <span class="badge <?php echo $badge_class; ?> text-white position-absolute top-0 start-0 m-3 px-3 py-2 text-uppercase" style="z-index: 10;"><?php echo htmlspecialchars($ws['domain']); ?></span>
                                    <div class="d-flex align-items-center justify-content-center text-white" style="height: 200px; background: <?php echo $gradient; ?>;">
                                        <i class="fa-solid <?php echo $icon; ?> display-4"></i>
                                    </div>
                                </div>
                                <div class="card-body p-4">
                                    <div class="event-meta text-muted small mb-2">
                                        <span><i class="fa-solid fa-calendar me-1"></i> <?php echo date('M d, Y', strtotime($ws['start_date'])); ?></span>
                                        <br>
                                        <span class="d-inline-block mt-1"><i class="fa-solid fa-location-dot me-1 text-danger"></i> <?php echo htmlspecialchars($ws['venue']); ?></span>
                                    </div>
                                    <h5 class="card-title fw-bold text-dark text-truncate-2" style="min-height: 48px;"><?php echo htmlspecialchars($ws['title']); ?></h5>
                                    <p class="card-text text-muted small mb-3">Speaker: <strong><?php echo htmlspecialchars($ws['speaker']); ?></strong></p>
                                    <div class="d-flex justify-content-between align-items-center small mb-2 bg-light p-2 rounded">
                                        <span class="text-secondary">Seats Booked:</span>
                                        <span class="fw-bold text-success" data-avail-booked><i class="fa-solid fa-chair me-1"></i><?php echo intval($ws['registered']); ?> / <?php echo intval($ws['capacity']); ?></span>
                                    </div>
                                    <div class="mb-3">
                                        <div class="progress" style="height:6px;">
                                            <?php $pct = $ws['capacity'] > 0 ? round(($ws['registered'] / $ws['capacity']) * 100) : 0; ?>
                                            <div class="progress-bar <?php echo ($pct >= 100) ? 'bg-danger' : (($pct >= 75) ? 'bg-warning' : 'bg-primary'); ?>" data-avail-progress role="progressbar" style="width: <?php echo $pct; ?>%" aria-valuenow="<?php echo $pct; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>
                                        <div class="d-flex justify-content-between mt-1" style="font-size: 0.75rem;">
                                            <span class="text-muted"><span data-avail-available><?php echo intval($ws['available']); ?></span> seats left</span>
                                            <span class="badge <?php echo ($ws['status'] === 'Open') ? 'bg-success' : 'bg-danger'; ?>" data-avail-status><?php echo $ws['status']; ?></span>
                                        </div>
                                    </div>
                                    <div class="d-grid">
                                        <a href="<?php echo $btn_path; ?>" class="btn btn-warning text-dark fw-bold"><?php echo $btn_text; ?></a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-12 text-center py-5">
                        <i class="fa-solid fa-calendar-xmark text-muted display-4 mb-3 d-block"></i>
                        <h4 class="text-secondary fw-semibold">No Upcoming Events Available</h4>
                        <p class="text-muted">All our seminars and workshops have been completed. Please check back later for new programs.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

<script src="assets/js/availability.js"></script>
<?php
include $base_path . 'includes/footer.php';
?>