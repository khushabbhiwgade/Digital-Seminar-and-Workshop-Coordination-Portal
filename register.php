<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Registration - Campus Connect Portal</title>
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
                        <a class="nav-link" href="events.php">Events</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="register.php">Register</a>
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
                                <input type="text" class="form-control" id="full_name" name="full_name" placeholder="John Doe" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light text-muted"><i class="fa-solid fa-envelope"></i></span>
                                    <input type="email" class="form-control" id="email" name="email" placeholder="john.doe@example.com" required>
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="mobile" class="form-label">Mobile Number <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light text-muted"><span class="small fw-semibold">+91</span></span>
                                    <input type="tel" class="form-control" id="mobile" name="mobile" placeholder="9876543210" maxlength="10" required>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="college_name" class="form-label">College Name <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted"><i class="fa-solid fa-school"></i></span>
                                <input type="text" class="form-control" id="college_name" name="college_name" placeholder="e.g., Apex Institute of Technology" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="department" class="form-label">Department <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light text-muted"><i class="fa-solid fa-building-columns"></i></span>
                                    <select class="form-select" id="department" name="department" required>
                                        <option value="" selected disabled>Select Department</option>
                                        <option value="Computer Science & Engineering">Computer Science &amp; Eng.</option>
                                        <option value="Information Technology">Information Technology</option>
                                        <option value="Electronics & Communication">Electronics &amp; Comm.</option>
                                        <option value="Electrical Engineering">Electrical Engineering</option>
                                        <option value="Mechanical Engineering">Mechanical Engineering</option>
                                        <option value="Civil Engineering">Civil Engineering</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="year" class="form-label">Year of Study <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light text-muted"><i class="fa-solid fa-calendar-days"></i></span>
                                    <select class="form-select" id="year" name="year" required>
                                        <option value="" selected disabled>Select Academic Year</option>
                                        <option value="1st Year">1st Year</option>
                                        <option value="2nd Year">2nd Year</option>
                                        <option value="3rd Year">3rd Year</option>
                                        <option value="4th Year">4th Year</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="event_name" class="form-label">Select Seminar / Workshop <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted"><i class="fa-solid fa-chalkboard-user"></i></span>
                                <select class="form-select" id="event_name" name="event_name" required>
                                    <option value="" selected disabled>Choose Event</option>
                                    <option value="National Seminar on AI & ML">National Seminar on AI &amp; ML (May 28)</option>
                                    <option value="Web Development with React & Node">Web Development with React &amp; Node (June 02)</option>
                                    <option value="Cyber Security & Ethical Hacking">Seminar on Cyber Security &amp; Ethical Hacking (June 10)</option>
                                    <option value="Cloud Computing & AWS Services">Workshop on Cloud Computing &amp; AWS Services (June 15)</option>
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
    <script src="js/main.js"></script>
</body>
</html>