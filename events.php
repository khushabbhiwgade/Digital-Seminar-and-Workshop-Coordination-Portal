<?php
$base_path = './';
$page_title = 'Seminars & Workshops - Academic Portal';
$active_page = 'events';
include $base_path . 'includes/header.php';
include $base_path . 'includes/navbar.php';
?>

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
                        <img src="assets/images/event_ai_seminar.svg" class="card-img-top" alt="AI and Machine Learning Seminar">
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
                                <a href="student/register.php?event=AI%20%26%20ML" class="btn btn-primary w-100 w-sm-auto px-4">Register <i class="fa-solid fa-arrow-right ms-1"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-lg-6">
                <div class="card event-card position-relative h-100 d-flex flex-column justify-content-between">
                    <div>
                        <span class="event-badge workshop">Workshop</span>
                        <img src="assets/images/event_web_dev.svg" class="card-img-top" alt="Web Development Workshop">
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
                                <a href="student/register.php?event=Web%20Development" class="btn btn-primary w-100 w-sm-auto px-4">Register <i class="fa-solid fa-arrow-right ms-1"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-lg-6">
                <div class="card event-card position-relative h-100 d-flex flex-column justify-content-between">
                    <div>
                        <span class="event-badge">Seminar</span>
                        <img src="assets/images/event_cyber_security.svg" class="card-img-top" alt="Cyber Security Seminar">
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
                                <a href="student/register.php?event=Cyber%20Security" class="btn btn-primary w-100 w-sm-auto px-4">Register <i class="fa-solid fa-arrow-right ms-1"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-lg-6">
                <div class="card event-card position-relative h-100 d-flex flex-column justify-content-between">
                    <div>
                        <span class="event-badge workshop">Workshop</span>
                        <img src="assets/images/event_cloud_computing.svg" class="card-img-top" alt="Cloud Computing Workshop">
                        <div class="card-body p-4">
                            <div class="event-meta">
                                <span><i class="fa-solid fa-calendar"></i> June 15, 2026</span>
                                <span class="ms-3"><i class="fa-solid fa-clock"></i> 09:30 AM - 04:30 PM</span>
                                <br class="d-sm-none">
                                <span class="ms-sm-3"><i class="fa-solid fa-location-dot"></i> CSE Lab 5</span>
                            </div>
                            <h3 class="h4 card-title text-primary">Workshop on Cloud Computing &amp; AWS Services</h3>
                            <p class="card-text text-muted">A deep dive workshop focused on hosting and deploying services on Amazon Web Services. Hands-on configuration of AWS EC2 instances, S3 buckets, AWS Lambdas, and serverless compute pipelines. Excellent for projects.</p>
                        </div>
                    </div>
                    <div class="card-footer bg-white border-0 px-4 pb-4">
                        <div class="row align-items-center">
                            <div class="col-sm-6 text-muted py-2 py-sm-0">
                                <i class="fa-solid fa-user-tie me-1"></i> Mrs. Priya Verma (AWS Solutions Architect)
                            </div>
                            <div class="col-sm-6 text-sm-end">
                                <a href="student/register.php?event=Cloud%20Computing" class="btn btn-primary w-100 w-sm-auto px-4">Register <i class="fa-solid fa-arrow-right ms-1"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

<?php
include $base_path . 'includes/footer.php';
?>