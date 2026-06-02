<?php
$base_path = './';
$page_title = 'Digital Seminar & Workshop Coordination Portal';
$active_page = 'home';
include $base_path . 'includes/header.php';
include $base_path . 'includes/navbar.php';

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
                        <a href="events.php" class="btn btn-warning btn-lg px-4 text-dark fw-bold">
                            <i class="fa-solid fa-calendar-days me-2"></i>View All Events
                        </a>
                        <a href="participant/register.php" class="btn btn-outline-light btn-lg px-4">
                            <i class="fa-solid fa-user-plus me-2"></i>Register Now
                        </a>
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

    <section class="section-padding" style="padding: 60px 0; background-color: #f8fafc;">
        <div class="container">
            <div class="d-flex justify-content-between align-items-end mb-4">
                <div>
                    <span class="text-primary fw-bold text-uppercase">Events At A Glance</span>
                    <h2 class="mb-0">Featured Upcoming Events</h2>
                </div>
                <a href="events.php" class="btn btn-outline-primary d-none d-sm-inline-block">See All Events <i class="fa-solid fa-arrow-right ms-1"></i></a>
            </div>
            <div class="row g-4">
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 shadow-sm border-0 rounded-3 overflow-hidden">
                        <div class="position-relative">
                            <span class="badge bg-primary text-white position-absolute top-0 start-0 m-3 px-3 py-2" style="z-index: 10;">Seminar</span>
                            <div class="d-flex align-items-center justify-content-center text-white" style="height: 200px; background: linear-gradient(45deg, #10b981, #059669);">
                                <i class="fa-solid fa-microchip display-4"></i>
                            </div>
                        </div>
                        <div class="card-body p-4">
                            <div class="event-meta text-muted small mb-2">
                                <span><i class="fa-solid fa-calendar me-1"></i> May 28, 2026</span>
                                <span class="ms-3"><i class="fa-solid fa-location-dot me-1"></i> Seminar Hall A</span>
                            </div>
                            <h5 class="card-title fw-bold">National Seminar on AI &amp; ML</h5>
                            <p class="card-text text-muted small">A comprehensive session covering Neural Networks, Deep Learning trends, and realistic industry applications of AI.</p>
                            <div class="d-grid mt-3">
                                <a href="participant/register.php?event=AI%20%26%20ML" class="btn btn-primary">Register Now</a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 shadow-sm border-0 rounded-3 overflow-hidden">
                        <div class="position-relative">
                            <span class="badge bg-success text-white position-absolute top-0 start-0 m-3 px-3 py-2" style="z-index: 10;">Workshop</span>
                            <div class="d-flex align-items-center justify-content-center text-white" style="height: 200px; background: linear-gradient(45deg, #6366f1, #4f46e5);">
                                <i class="fa-solid fa-code display-4"></i>
                            </div>
                        </div>
                        <div class="card-body p-4">
                            <div class="event-meta text-muted small mb-2">
                                <span><i class="fa-solid fa-calendar me-1"></i> June 02, 2026</span>
                                <span class="ms-3"><i class="fa-solid fa-location-dot me-1"></i> CSE Lab 3</span>
                            </div>
                            <h5 class="card-title fw-bold">Web Development with React &amp; Node</h5>
                            <p class="card-text text-muted small">A full-day practical workshop building real-world single page applications. Beginner-friendly steps.</p>
                             <div class="d-grid mt-3">
                                <a href="participant/register.php?event=Web%20Development" class="btn btn-primary">Register Now</a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4 d-md-none d-lg-block">
                    <div class="card h-100 shadow-sm border-0 rounded-3 overflow-hidden">
                        <div class="position-relative">
                            <span class="badge bg-danger text-white position-absolute top-0 start-0 m-3 px-3 py-2" style="z-index: 10;">Seminar</span>
                            <div class="d-flex align-items-center justify-content-center text-white" style="height: 200px; background: linear-gradient(45deg, #ef4444, #dc2626);">
                                <i class="fa-solid fa-shield-halved display-4"></i>
                            </div>
                        </div>
                        <div class="card-body p-4">
                            <div class="event-meta text-muted small mb-2">
                                <span><i class="fa-solid fa-calendar me-1"></i> June 10, 2026</span>
                                <span class="ms-3"><i class="fa-solid fa-location-dot me-1"></i> Main Auditorium</span>
                            </div>
                            <h5 class="card-title fw-bold">Cyber Security &amp; Ethical Hacking</h5>
                            <p class="card-text text-muted small">Demystifying security protocols, firewalls, and exploring ethical hacking tools for modern network systems.</p>
                             <div class="d-grid mt-3">
                                <a href="participant/register.php?event=Cyber%20Security" class="btn btn-primary">Register Now</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

<?php
include $base_path . 'includes/footer.php';
?>