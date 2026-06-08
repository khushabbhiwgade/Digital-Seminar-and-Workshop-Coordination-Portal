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
                        <li class="mb-2"><a href="<?php echo $base_path; ?>index.php#featured-events" class="link-light text-white-50 text-decoration-none"><i class="fa-solid fa-chevron-right me-2 text-warning" style="font-size:0.7rem;"></i>Featured Workshops</a></li>
                        <?php if (is_logged_in() && get_user_role() === 'student'): ?>
                            <li class="mb-2"><a href="<?php echo $base_path; ?>participant/dashboard.php" class="link-light text-white-50 text-decoration-none"><i class="fa-solid fa-chevron-right me-2 text-warning" style="font-size:0.7rem;"></i>Student Dashboard</a></li>
                        <?php else: ?>
                            <li class="mb-2"><a href="<?php echo $base_path; ?>auth/signup.php" class="link-light text-white-50 text-decoration-none"><i class="fa-solid fa-chevron-right me-2 text-warning" style="font-size:0.7rem;"></i>Student Sign Up</a></li>
                        <?php endif; ?>
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

<?php if (is_logged_in() && get_user_role() === 'student'): 
    global $conn;
    $user_id = get_user_id();
    $user_name = get_user_name();
    $user_email = '';
    $user_mobile = '';
    
    $user_stmt = $conn->prepare("SELECT email, mobile FROM users WHERE id = ?");
    if ($user_stmt) {
        $user_stmt->bind_param("i", $user_id);
        $user_stmt->execute();
        $user_res = $user_stmt->get_result()->fetch_assoc();
        if ($user_res) {
            $user_email = $user_res['email'];
            $user_mobile = $user_res['mobile'];
        }
    }
?>
<!-- Workshop Registration Modal -->
<div class="modal fade" id="registerModal" tabindex="-1" aria-labelledby="registerModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content text-dark">
            <div class="modal-header bg-dark text-white" style="border-bottom: 2px solid #eab308;">
                <h5 class="modal-title fw-bold text-white" id="registerModalLabel"><i class="fa-solid fa-receipt text-warning me-2"></i>Workshop Registration</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="modalRegisterForm" method="POST" action="">
                <div class="modal-body text-start">
                    <div id="modalAlert" class="alert alert-danger d-none"></div>
                    
                    <input type="hidden" name="workshop_id" id="modal_workshop_id">
                    
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-dark">Event / Workshop Name</label>
                        <input type="text" class="form-control bg-light text-dark" id="modal_workshop_name" readonly>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-dark">Participant Name</label>
                        <input type="text" class="form-control bg-light text-dark" id="modal_participant_name" value="<?php echo htmlspecialchars($user_name); ?>" readonly>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-dark">Email Address</label>
                        <input type="email" class="form-control bg-light text-dark" id="modal_participant_email" value="<?php echo htmlspecialchars($user_email); ?>" readonly>
                    </div>
                    
                    <div class="mb-3">
                        <label for="modal_participant_mobile" class="form-label fw-semibold text-dark">Mobile Number</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-muted"><i class="fa-solid fa-phone"></i></span>
                            <input type="text" class="form-control text-dark" id="modal_participant_mobile" name="mobile" placeholder="Enter mobile number" value="<?php echo htmlspecialchars($user_mobile); ?>" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning text-dark fw-bold" id="modalSubmitBtn">Confirm Registration</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const registerModal = document.getElementById('registerModal');
    if (!registerModal) return;

    const modalWorkshopId = document.getElementById('modal_workshop_id');
    const modalWorkshopName = document.getElementById('modal_workshop_name');
    const modalAlert = document.getElementById('modalAlert');
    const modalForm = document.getElementById('modalRegisterForm');
    const modalSubmitBtn = document.getElementById('modalSubmitBtn');

    let triggeringButton = null;

    registerModal.addEventListener('show.bs.modal', function (event) {
        modalAlert.classList.add('d-none');
        modalAlert.textContent = '';
        triggeringButton = event.relatedTarget;
        if (!triggeringButton) return;

        const workshopId = triggeringButton.getAttribute('data-workshop-id');
        const workshopName = triggeringButton.getAttribute('data-workshop-name');

        modalWorkshopId.value = workshopId;
        modalWorkshopName.value = workshopName;
    });

    modalForm.addEventListener('submit', function (e) {
        e.preventDefault();

        modalSubmitBtn.disabled = true;
        modalSubmitBtn.textContent = 'Registering...';
        modalAlert.classList.add('d-none');

        const formData = new FormData(modalForm);
        const isInParticipantFolder = window.location.pathname.includes('/participant/');
        const actionUrl = isInParticipantFolder ? 'register_workshop.php?ajax=1' : 'participant/register_workshop.php?ajax=1';

        fetch(actionUrl, {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const modalInstance = bootstrap.Modal.getInstance(registerModal);
                if (modalInstance) {
                    modalInstance.hide();
                }

                const container = document.querySelector('.container');
                if (container) {
                    const successDiv = document.createElement('div');
                    successDiv.className = 'alert alert-success alert-dismissible fade show border-start border-4 border-success shadow-sm mb-4';
                    successDiv.setAttribute('role', 'alert');
                    successDiv.innerHTML = '<i class="fa-solid fa-circle-check me-2"></i>' + data.message + '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
                    container.insertBefore(successDiv, container.firstChild);
                }

                if (triggeringButton) {
                    const isDetailsPageBtn = triggeringButton.classList.contains('btn-lg');
                    if (isDetailsPageBtn) {
                        const formParent = triggeringButton.closest('form');
                        if (formParent) {
                            const registeredBtn = document.createElement('button');
                            registeredBtn.className = 'btn btn-secondary btn-lg w-100 py-2.5';
                            registeredBtn.disabled = true;
                            registeredBtn.innerHTML = '<i class="fa-solid fa-circle-check me-2"></i>Registered';
                            
                            const alertDiv = document.createElement('div');
                            alertDiv.className = 'alert alert-success border-start border-4 border-success text-start mb-3';
                            alertDiv.setAttribute('role', 'alert');
                            alertDiv.innerHTML = '<i class="fa-solid fa-circle-check me-2 text-success"></i>You are already registered! Check your active ticket inside your Dashboard.';
                            
                            formParent.parentNode.insertBefore(alertDiv, formParent);
                            formParent.parentNode.replaceChild(registeredBtn, formParent);
                        }
                    } else {
                        const formParent = triggeringButton.closest('form');
                        if (formParent) {
                            const registeredBtn = document.createElement('button');
                            registeredBtn.className = 'btn btn-sm btn-secondary disabled fw-bold px-3';
                            registeredBtn.disabled = true;
                            registeredBtn.textContent = 'Registered';
                            formParent.parentNode.replaceChild(registeredBtn, formParent);
                        } else {
                            triggeringButton.textContent = 'Registered';
                            triggeringButton.disabled = true;
                            triggeringButton.className = 'btn btn-sm btn-secondary disabled fw-bold px-3';
                        }
                    }
                }

                const workshopId = modalWorkshopId.value;
                const seatsEl = document.querySelector('.display-6.fw-bold.text-success');
                if (seatsEl) {
                    const parts = seatsEl.innerHTML.split(' / ');
                    if (parts.length === 2) {
                        const currentBooked = parseInt(parts[0]) + 1;
                        const capacityText = parts[1];
                        seatsEl.innerHTML = currentBooked + ' <span class="fs-5 text-muted"> / ' + capacityText + '</span>';
                    }
                }
                
                const wsCard = document.querySelector(`[data-workshop-id="${workshopId}"]`);
                if (wsCard) {
                    const bookedEl = wsCard.querySelector('[data-avail-booked]');
                    if (bookedEl) {
                        const parts = bookedEl.textContent.split(' / ');
                        if (parts.length === 2) {
                            const currentBooked = parseInt(parts[0]) + 1;
                            const capacity = parseInt(parts[1]);
                            bookedEl.innerHTML = '<i class="fa-solid fa-chair me-1"></i>' + currentBooked + ' / ' + capacity;
                            
                            const leftEl = wsCard.querySelector('[data-avail-available]');
                            if (leftEl) {
                                leftEl.textContent = capacity - currentBooked;
                            }
                            
                            const progressEl = wsCard.querySelector('[data-avail-progress]');
                            if (progressEl) {
                                const pct = Math.round((currentBooked / capacity) * 100);
                                progressEl.style.width = pct + '%';
                                progressEl.setAttribute('aria-valuenow', pct);
                                progressEl.textContent = pct + '%';
                            }
                        }
                    }
                }

                if (window.location.pathname.includes('dashboard.php')) {
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                }
            } else {
                modalAlert.textContent = data.message;
                modalAlert.classList.remove('d-none');
            }
        })
        .catch(err => {
            modalAlert.textContent = 'A network error occurred. Please try again.';
            modalAlert.classList.remove('d-none');
        })
        .finally(() => {
            modalSubmitBtn.disabled = false;
            modalSubmitBtn.textContent = 'Confirm Registration';
        });
    });
});
</script>
<?php endif; ?>
</body>
</html>
