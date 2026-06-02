<?php
// events.php
// Dynamically loads events from the database
$base_path = './';
$page_title = 'Seminars & Workshops - Campus Connect';
$active_page = 'events';

require_once $base_path . 'config/db_connect.php';
require_once $base_path . 'includes/auth.php';

// Fetch all events from DB ordered by date
$events = [];
$events_sql = "SELECT id, title, description, host, event_date, start_time, end_time, location, total_seats, seats_remaining FROM events ORDER BY event_date ASC";
$events_res = $conn->query($events_sql);
if ($events_res) {
    while ($row = $events_res->fetch_assoc()) {
        $events[] = $row;
    }
}

// Map event index to badge type and SVG image
$badge_types  = ['Seminar', 'Workshop'];
$event_images = [
    0 => 'assets/images/event_ai_seminar.svg',
    1 => 'assets/images/event_web_dev.svg',
    2 => 'assets/images/event_cyber_security.svg',
    3 => 'assets/images/event_cloud_computing.svg',
];
$default_image = 'assets/images/event_ai_seminar.svg';

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
        <?php if (empty($events)): ?>
            <div class="text-center py-5">
                <div class="display-1 text-muted mb-3"><i class="fa-solid fa-calendar-xmark"></i></div>
                <h4 class="text-muted">No events available at the moment.</h4>
                <p class="text-muted">Please check back later or contact the portal admin.</p>
            </div>
        <?php else: ?>
        <div class="row g-4">
            <?php foreach ($events as $idx => $ev):
                $registered     = $ev['total_seats'] - $ev['seats_remaining'];
                $is_full        = ($ev['seats_remaining'] <= 0);
                $date_fmt       = date("F d, Y", strtotime($ev['event_date']));
                $time_start     = date("h:i A", strtotime($ev['start_time']));
                $time_end       = date("h:i A", strtotime($ev['end_time']));
                // Alternate badge type based on index; detect "workshop" keyword in title too
                $is_workshop    = stripos($ev['title'], 'workshop') !== false;
                $badge_class    = $is_workshop ? 'event-badge workshop' : 'event-badge';
                $badge_label    = $is_workshop ? 'Workshop' : 'Seminar';
                $img_src        = $event_images[$idx] ?? $default_image;

                // Register link: pass event ID
                $reg_url = 'participant/register.php?event_id=' . intval($ev['id']);
            ?>
            <div class="col-md-6 col-lg-6">
                <div class="card event-card position-relative h-100 d-flex flex-column justify-content-between">
                    <div>
                        <span class="<?php echo $badge_class; ?>"><?php echo $badge_label; ?></span>
                        <img src="<?php echo htmlspecialchars($img_src); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($ev['title']); ?>">
                        <div class="card-body p-4">
                            <div class="event-meta">
                                <span><i class="fa-solid fa-calendar"></i> <?php echo $date_fmt; ?></span>
                                <span class="ms-3"><i class="fa-solid fa-clock"></i> <?php echo $time_start . ' - ' . $time_end; ?></span>
                                <br class="d-sm-none">
                                <span class="ms-sm-3"><i class="fa-solid fa-location-dot"></i> <?php echo htmlspecialchars($ev['location']); ?></span>
                            </div>
                            <h3 class="h4 card-title text-primary"><?php echo htmlspecialchars($ev['title']); ?></h3>
                            <p class="card-text text-muted"><?php echo htmlspecialchars($ev['description']); ?></p>

                            <!-- Seat availability bar -->
                            <?php
                            $seat_pct   = ($ev['total_seats'] > 0) ? round(($registered / $ev['total_seats']) * 100) : 0;
                            $bar_color  = 'bg-success';
                            if ($seat_pct >= 90)     $bar_color = 'bg-danger';
                            elseif ($seat_pct >= 60) $bar_color = 'bg-warning';
                            ?>
                            <div class="mt-3">
                                <div class="d-flex justify-content-between small text-muted mb-1">
                                    <span><i class="fa-solid fa-chair me-1"></i><?php echo intval($ev['seats_remaining']); ?> seats left</span>
                                    <span><?php echo intval($ev['total_seats']); ?> total</span>
                                </div>
                                <div class="progress" style="height: 6px;">
                                    <div class="progress-bar <?php echo $bar_color; ?>" style="width: <?php echo $seat_pct; ?>%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-white border-0 px-4 pb-4">
                        <div class="row align-items-center">
                            <div class="col-sm-6 text-muted py-2 py-sm-0">
                                <i class="fa-solid fa-user-tie me-1"></i> <?php echo htmlspecialchars($ev['host']); ?>
                            </div>
                            <div class="col-sm-6 text-sm-end">
                                <?php if ($is_full): ?>
                                    <button class="btn btn-secondary w-100 w-sm-auto px-4" disabled>
                                        <i class="fa-solid fa-ban me-1"></i>Fully Booked
                                    </button>
                                <?php elseif (!is_logged_in()): ?>
                                    <a href="auth/login.php" class="btn btn-outline-primary w-100 w-sm-auto px-4">
                                        <i class="fa-solid fa-right-to-bracket me-1"></i>Login to Register
                                    </a>
                                <?php elseif (get_user_role() === 'participant' || get_user_role() === 'student'): ?>
                                    <a href="<?php echo $reg_url; ?>" class="btn btn-primary w-100 w-sm-auto px-4">
                                        Register <i class="fa-solid fa-arrow-right ms-1"></i>
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted small">Admin/Coordinator view</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </main>

<?php
include $base_path . 'includes/footer.php';
?>