<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Digital Seminar & Workshop Coordination Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="index.php">
                <i class="fa-solid fa-graduation-cap me-2 text-warning"></i>
                <span>Campus Connect Portal</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item">
                        <a class="nav-link active" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="events.php">Events</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="register.php">Register</a>
                    </li>
                    <li class="nav-item ms-lg-3 mt-2 mt-lg-0">
                        <button class="btn btn-warning btn-sm px-3" data-bs-toggle="modal" data-bs-target="#loginModal">
                            <i class="fa-solid fa-right-to-bracket me-1"></i> Login
                        </button>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    
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
                        <a href="register.php" class="btn btn-outline-light btn-lg px-4">
                            <i class="fa-solid fa-user-plus me-2"></i>Register Now
                        </a>
                    </div>
                </div>
                <div class="col-lg-5 d-none d-lg-block text-center">
                    <img src="images/Screenshot 2026-05-26 161333.png" alt="Portal Illustration" class="img-fluid rounded-3 shadow-lg" style="border: 1px solid rgba(255, 255, 255, 0.1); max-height: 380px; object-fit: cover;">
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
                                <a href="register.php?event=AI%20%26%20ML" class="btn btn-primary">Register Now</a>
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
                                <a href="register.php?event=Web%20Development" class="btn btn-primary">Register Now</a>
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
                                <a href="register.php?event=Cyber%20Security" class="btn btn-primary">Register Now</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="modal fade" id="loginModal" tabindex="-1" aria-labelledby="loginModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title" id="loginModalLabel">
                        <i class="fa-solid fa-lock text-warning me-2"></i>Portal Login
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4 text-center">
                    <div class="mb-3 text-warning display-4"><i class="fa-solid fa-screwdriver-wrench"></i></div>
                    <h4>Module In Progress</h4>
                    <p class="text-muted">The Admin and Coordinator Login panel will be implemented in Week 2 tasks. Currently, only events page and registration forms are active.</p>
                    <button type="button" class="btn btn-secondary px-4 mt-3" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <footer class="college-footer bg-dark text-white pt-5 pb-3 mt-auto" style="border-top: 4px solid #ffc107;">
        <div class="container">
            <div class="row g-4">
                <div class="col-md-5">
                    <h5 class="text-white mb-3"><i class="fa-solid fa-university me-2 text-warning"></i>College Coordination Portal</h5>
                    <p class="small text-white-50">A minor project developed to digitize registration, scheduling, and attendance tracking for seminars &amp; workshops.</p>
                    <div class="d-flex gap-3 mt-3">
                        <a href="#" class="text-warning fs-5"><i class="fa-brands fa-facebook"></i></a>
                        <a href="#" class="text-warning fs-5"><i class="fa-brands fa-twitter"></i></a>
                        <a href="#" class="text-warning fs-5"><i class="fa-brands fa-linkedin"></i></a>
                        <a href="#" class="text-warning fs-5"><i class="fa-brands fa-github"></i></a>
                    </div>
                </div>
                <div class="col-md-3">
                    <h5 class="text-white mb-3">Quick Links</h5>
                    <ul class="list-unstyled small">
                        <li class="mb-2"><a href="index.php" class="link-light text-white-50 text-decoration-none"><i class="fa-solid fa-chevron-right me-2 text-warning" style="font-size:0.7rem;"></i>Home</a></li>
                        <li class="mb-2"><a href="events.php" class="link-light text-white-50 text-decoration-none"><i class="fa-solid fa-chevron-right me-2 text-warning" style="font-size:0.7rem;"></i>Events Listing</a></li>
                        <li class="mb-2"><a href="register.php" class="link-light text-white-50 text-decoration-none"><i class="fa-solid fa-chevron-right me-2 text-warning" style="font-size:0.7rem;"></i>Student Registration</a></li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h5 class="text-white mb-3">Contact Secretariat</h5>
                    <ul class="list-unstyled small text-white-50">
                        <li class="mb-2"><i class="fa-solid fa-envelope me-2 text-warning"></i> portal.support@college.edu</li>
                        <li class="mb-2"><i class="fa-solid fa-phone me-2 text-warning"></i> +91 12345 67890</li>
                        <li class="mb-2"><i class="fa-solid fa-location-dot me-2 text-warning"></i> Department of CSE, Main Campus, India.</li>
                    </ul>
                </div>
            </div>
            <hr class="border-secondary mt-4">
            <div class="footer-bottom text-center small text-white-50">
                <p class="mb-0">&copy; 2026 Seminar and Workshop Coordination Portal. Developed for Academic Project evaluations.</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>