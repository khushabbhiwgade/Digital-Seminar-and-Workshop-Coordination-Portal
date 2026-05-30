<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seminars & Workshops - Academic Portal</title>
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
                        <a class="nav-link" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="events.php">Events</a>
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

    <div class="bg-dark text-white py-5 mb-5 text-center" style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);">
        <div class="container">
            <h1 class="display-5 fw-bold">Seminars &amp; Workshops</h1>
            <p class="lead text-muted mb-0">Browse through our current list of learning events and secure your seat today.</p>
        </div>
    </div>

    <main class="container mb-5">
        <div class="row g-4">
            
            <div class="col-md-6 col-lg-6">
                <div class="card event-card position-relative h-100 d-flex flex-column justify-content-between">
                    <div>
                        <span class="event-badge">Seminar</span>
                        <img src="images/event_ai_seminar.svg" class="card-img-top" alt="AI and Machine Learning Seminar">
                        <div class="card-body p-4">
                            <div class="event-meta">
                                <span><i class="fa-solid fa-calendar"></i> May 28, 2026</span>
                                <span class="ms-3"><i class="fa-solid fa-clock"></i> 10:00 AM - 01:00 PM</span>
                                <br class="d-sm-none">
                                <span class="ms-sm-3"><i class="fa-solid fa-location-dot"></i> Seminar Hall A</span>
                            </div>
                            <h3 class="h4 card-title text-primary">National Seminar on Artificial Intelligence &amp; Machine Learning</h3>
                            <p class="card-text text-muted">Join us for a detailed panel discussion led by research scientists exploring neural network foundations, transformers, deep learning applications, and future trends of artificial intelligence in software engineering.</p>
                        </div>
                    </div>
                    <div class="card-footer bg-white border-0 px-4 pb-4">
                        <div class="row align-items-center">
                            <div class="col-sm-6 text-muted py-2 py-sm-0">
                                <i class="fa-solid fa-user-tie me-1"></i> Dr. A. K. Sen (IIT)
                            </div>
                            <div class="col-sm-6 text-sm-end">
                                <a href="register.php?event=AI%20%26%20ML" class="btn btn-primary w-100 w-sm-auto px-4">Register <i class="fa-solid fa-arrow-right ms-1"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-lg-6">
                <div class="card event-card position-relative h-100 d-flex flex-column justify-content-between">
                    <div>
                        <span class="event-badge workshop">Workshop</span>
                        <img src="images/event_web_dev.svg" class="card-img-top" alt="Web Development Workshop">
                        <div class="card-body p-4">
                            <div class="event-meta">
                                <span><i class="fa-solid fa-calendar"></i> June 02, 2026</span>
                                <span class="ms-3"><i class="fa-solid fa-clock"></i> 09:30 AM - 04:30 PM</span>
                                <br class="d-sm-none">
                                <span class="ms-sm-3"><i class="fa-solid fa-location-dot"></i> CSE Lab 3</span>
                            </div>
                            <h3 class="h4 card-title text-primary">Hands-on Web Development with React &amp; Node.js</h3>
                            <p class="card-text text-muted">A hands-on coding workshop covering modern full-stack web architectures. Students will build a functional web application with React components, routing, Express API endpoints, and database connection. Highly practical.</p>
                        </div>
                    </div>
                    <div class="card-footer bg-white border-0 px-4 pb-4">
                        <div class="row align-items-center">
                            <div class="col-sm-6 text-muted py-2 py-sm-0">
                                <i class="fa-solid fa-user-tie me-1"></i> Prof. S. Sharma (Tech Lead)
                            </div>
                            <div class="col-sm-6 text-sm-end">
                                <a href="register.php?event=Web%20Development" class="btn btn-primary w-100 w-sm-auto px-4">Register <i class="fa-solid fa-arrow-right ms-1"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-lg-6">
                <div class="card event-card position-relative h-100 d-flex flex-column justify-content-between">
                    <div>
                        <span class="event-badge">Seminar</span>
                        <img src="images/event_cyber_security.svg" class="card-img-top" alt="Cyber Security Seminar">
                        <div class="card-body p-4">
                            <div class="event-meta">
                                <span><i class="fa-solid fa-calendar"></i> June 10, 2026</span>
                                <span class="ms-3"><i class="fa-solid fa-clock"></i> 11:00 AM - 02:00 PM</span>
                                <br class="d-sm-none">
                                <span class="ms-sm-3"><i class="fa-solid fa-location-dot"></i> Main Auditorium</span>
                            </div>
                            <h3 class="h4 card-title text-primary">Seminar on Cyber Security &amp; Ethical Hacking</h3>
                            <p class="card-text text-muted">Understand security vulnerabilities in standard network protocols, scanning techniques, firewalls, and data protection regulations. Features a live demonstration of hacking mitigation steps by network security professionals.</p>
                        </div>
                    </div>
                    <div class="card-footer bg-white border-0 px-4 pb-4">
                        <div class="row align-items-center">
                            <div class="col-sm-6 text-muted py-2 py-sm-0">
                                <i class="fa-solid fa-user-tie me-1"></i> Mr. Rajiv Malhotra (EC-Council)
                            </div>
                            <div class="col-sm-6 text-sm-end">
                                <a href="register.php?event=Cyber%20Security" class="btn btn-primary w-100 w-sm-auto px-4">Register <i class="fa-solid fa-arrow-right ms-1"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-lg-6">
                <div class="card event-card position-relative h-100 d-flex flex-column justify-content-between">
                    <div>
                        <span class="event-badge workshop">Workshop</span>
                        <img src="images/event_cloud_computing.svg" class="card-img-top" alt="Cloud Computing Workshop">
                        <div class="card-body p-4">
                            <div class="event-meta">
                                <span><i class="fa-solid fa-calendar"></i> June 15, 2026</span>
                                <span class="ms-3"><i class="fa-solid fa-clock"></i> 09:30 AM - 04:30 PM</span>
                                <br class="d-sm-none">
                                <span class="ms-sm-3"><i class="fa-solid fa-location-dot"></i> CSE Lab 5</span>
                            </div>
                            <h3 class="h4 card-title text-primary">Workshop on Cloud Computing &amp; AWS Services</h3>
                            <p class="card-text text-muted">A deep dive workshop focused on hosting and deploying services on Amazon Web Services. Hands-on configuration of EC2 instances, S3 buckets, AWS Lambdas, and serverless compute pipelines. Excellent for projects.</p>
                        </div>
                    </div>
                    <div class="card-footer bg-white border-0 px-4 pb-4">
                        <div class="row align-items-center">
                            <div class="col-sm-6 text-muted py-2 py-sm-0">
                                <i class="fa-solid fa-user-tie me-1"></i> Mrs. Priya Verma (AWS Solutions Architect)
                            </div>
                            <div class="col-sm-6 text-sm-end">
                                <a href="register.php?event=Cloud%20Computing" class="btn btn-primary w-100 w-sm-auto px-4">Register <i class="fa-solid fa-arrow-right ms-1"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

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

    <footer class="bg-dark text-white pt-5 pb-3 border-top border-warning border-4" style="background-color: #1a1a1a !important;">
        <div class="container">
            <div class="row g-4">
                <div class="col-md-5">
                    <h5 class="fw-bold mb-3 text-white"><i class="fa-solid fa-university me-2 text-warning"></i>College Coordination Portal</h5>
                    <p class="text-white-50 small">A minor project developed to digitize registration, scheduling, and attendance tracking for seminars &amp; workshops.</p>
                    <div class="d-flex gap-3 mt-3">
                        <a href="#" class="text-warning fs-5"><i class="fa-brands fa-facebook"></i></a>
                        <a href="#" class="text-warning fs-5"><i class="fa-brands fa-twitter"></i></a>
                        <a href="#" class="text-warning fs-5"><i class="fa-brands fa-linkedin"></i></a>
                        <a href="#" class="text-warning fs-5"><i class="fa-brands fa-github"></i></a>
                    </div>
                </div>
                <div class="col-md-3">
                    <h5 class="fw-bold mb-3 text-white">Quick Links</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="index.php" class="link-light text-white-50 text-decoration-none small"><i class="fa-solid fa-chevron-right me-2 text-warning" style="font-size:0.7rem;"></i>Home</a></li>
                        <li class="mb-2"><a href="events.php" class="link-light text-white-50 text-decoration-none small"><i class="fa-solid fa-chevron-right me-2 text-warning" style="font-size:0.7rem;"></i>Events Listing</a></li>
                        <li class="mb-2"><a href="register.php" class="link-light text-white-50 text-decoration-none small"><i class="fa-solid fa-chevron-right me-2 text-warning" style="font-size:0.7rem;"></i>Student Registration</a></li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h5 class="fw-bold mb-3 text-white">Contact Secretariat</h5>
                    <ul class="list-unstyled text-white-50 small">
                        <li class="mb-2"><i class="fa-solid fa-envelope me-2 text-warning"></i> portal.support@college.edu</li>
                        <li class="mb-2"><i class="fa-solid fa-phone me-2 text-warning"></i> +91 12345 67890</li>
                        <li class="mb-2"><i class="fa-solid fa-location-dot me-2 text-warning"></i> Department of CSE, Main Campus, India.</li>
                    </ul>
                </div>
            </div>
            <div class="text-center text-white-50 pt-3 mt-4 border-top border-secondary small">
                <p class="mb-0">&copy; 2026 Seminar and Workshop Coordination Portal. Developed for Academic Project evaluations.</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>