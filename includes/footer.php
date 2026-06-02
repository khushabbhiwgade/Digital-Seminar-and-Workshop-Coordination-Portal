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
                        <li class="mb-2"><a href="<?php echo $base_path; ?>index.php" class="link-light text-white-50 text-decoration-none"><i class="fa-solid fa-chevron-right me-2 text-warning" style="font-size:0.7rem;"></i>Home</a></li>
                        <li class="mb-2"><a href="<?php echo $base_path; ?>events.php" class="link-light text-white-50 text-decoration-none"><i class="fa-solid fa-chevron-right me-2 text-warning" style="font-size:0.7rem;"></i>Events Listing</a></li>
                        <li class="mb-2"><a href="<?php echo $base_path; ?>student/register.php" class="link-light text-white-50 text-decoration-none"><i class="fa-solid fa-chevron-right me-2 text-warning" style="font-size:0.7rem;"></i>Student Registration</a></li>
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
    <?php if (isset($load_main_js) && $load_main_js): ?>
    <script src="<?php echo $base_path; ?>assets/js/main.js"></script>
    <?php endif; ?>
</body>
</html>
