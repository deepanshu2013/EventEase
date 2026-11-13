<?php
/**
 * EventEase - College Event Registration Platform
 * Homepage (Public Landing Page)
 */

$page_title = "EventEase – Discover. Participate. Celebrate.";
require_once __DIR__ . "/config/database.php";
require_once __DIR__ . "/includes/auth.php";

// Fetch platform statistics
$stats_query = "
    SELECT 
        (SELECT COUNT(*) FROM events) AS total_events,
        (SELECT COUNT(*) FROM registrations WHERE status != 'cancelled') AS total_registrations,
        (SELECT COUNT(*) FROM users WHERE role = 'student') AS total_students,
        (SELECT COUNT(DISTINCT category) FROM events) AS total_categories
";
$stats_result = mysqli_query($conn, $stats_query);
$stats = mysqli_fetch_assoc($stats_result);

// Fetch upcoming events (next 6 events)
$events_query = "
    SELECT 
        e.event_id,
        e.event_name,
        e.description,
        e.category,
        e.venue,
        e.event_date,
        e.capacity,
        u.full_name AS organizer_name,
        COUNT(CASE WHEN r.status IN ('registered', 'attended') THEN 1 END) AS registered_count
    FROM events e
    JOIN users u ON e.organizer_id = u.user_id
    LEFT JOIN registrations r ON e.event_id = r.event_id
    WHERE e.event_date >= NOW()
    GROUP BY e.event_id
    ORDER BY e.event_date ASC
    LIMIT 6
";
$upcoming_events = mysqli_query($conn, $events_query);

require_once __DIR__ . "/includes/header.php";
?>

<!-- ========================================================
     Hero Section
     ======================================================== -->
<section class="hero-wrapper text-center">
    <div class="container hero-content">
        <div class="hero-pill">
            <i class="bi bi-stars text-warning"></i> Welcome to Campus Life 2026
        </div>
        <h1 class="hero-title">
            Discover. Participate. <span class="gradient-text">Celebrate.</span>
        </h1>
        <p class="hero-subtitle">
            Your centralized collegiate hub for discovering hackathons, cultural festivals, sports tournaments, and technical workshops. Register online in seconds and never miss a campus moment.
        </p>
        <div class="d-flex justify-content-center gap-3 flex-wrap">
            <a href="#events" class="btn btn-primary btn-lg px-4 py-2 rounded-pill shadow-glow">
                <i class="bi bi-compass me-1"></i> Explore Events
            </a>
            <?php if (!is_logged_in()): ?>
                <a href="<?php echo BASE_URL; ?>auth/register.php" class="btn btn-outline-light btn-lg px-4 py-2 rounded-pill">
                    <i class="bi bi-person-plus me-1"></i> Student Sign Up
                </a>
            <?php else: ?>
                <a href="<?php echo (current_user_role() === 'organizer') ? BASE_URL . 'organizer/dashboard.php' : BASE_URL . 'student/dashboard.php'; ?>" class="btn btn-outline-light btn-lg px-4 py-2 rounded-pill">
                    <i class="bi bi-speedometer2 me-1"></i> Go to Dashboard
                </a>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- ========================================================
     Platform Statistics Banner
     ======================================================== -->
<div class="container">
    <div class="stats-banner">
        <div class="row g-4 justify-content-center">
            <div class="col-6 col-md-3">
                <div class="stat-item">
                    <div class="stat-number text-purple"><?php echo (int)($stats['total_events'] ?? 0); ?>+</div>
                    <div class="stat-label"><i class="bi bi-calendar-check me-1"></i> Campus Events</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-item border-start">
                    <div class="stat-number text-navy"><?php echo (int)($stats['total_registrations'] ?? 0); ?>+</div>
                    <div class="stat-label"><i class="bi bi-person-check me-1"></i> Registrations</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-item border-start-md">
                    <div class="stat-number text-success"><?php echo (int)($stats['total_students'] ?? 0); ?>+</div>
                    <div class="stat-label"><i class="bi bi-people me-1"></i> Active Students</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-item border-start">
                    <div class="stat-number text-warning"><?php echo (int)($stats['total_categories'] ?? 6); ?></div>
                    <div class="stat-label"><i class="bi bi-tags me-1"></i> Event Categories</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================
     Event Categories Section
     ======================================================== -->
<section class="py-5" id="categories">
    <div class="container py-4">
        <div class="text-center mb-5">
            <span class="badge bg-purple-light text-purple px-3 py-2 rounded-pill fw-semibold">Diverse Domains</span>
            <h2 class="mt-2 fw-bold text-navy">Explore by Category</h2>
            <p class="text-muted mx-auto" style="max-width: 600px;">
                Filter campus activities matching your passions, from competitive coding to theatrical arts.
            </p>
        </div>

        <div class="row g-4">
            <div class="col-6 col-md-4 col-lg-2">
                <a href="<?php echo BASE_URL; ?>student/events.php?category=Technical" class="category-box">
                    <div class="cat-icon-circle bg-primary-subtle text-primary">
                        <i class="bi bi-laptop"></i>
                    </div>
                    <h6 class="fw-bold mb-1">Technical</h6>
                    <small class="text-muted">Coding & AI</small>
                </a>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <a href="<?php echo BASE_URL; ?>student/events.php?category=Cultural" class="category-box">
                    <div class="cat-icon-circle bg-danger-subtle text-danger">
                        <i class="bi bi-music-note-beamed"></i>
                    </div>
                    <h6 class="fw-bold mb-1">Cultural</h6>
                    <small class="text-muted">Music & Dance</small>
                </a>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <a href="<?php echo BASE_URL; ?>student/events.php?category=Sports" class="category-box">
                    <div class="cat-icon-circle bg-success-subtle text-success">
                        <i class="bi bi-trophy"></i>
                    </div>
                    <h6 class="fw-bold mb-1">Sports</h6>
                    <small class="text-muted">Athletics & Games</small>
                </a>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <a href="<?php echo BASE_URL; ?>student/events.php?category=Workshop" class="category-box">
                    <div class="cat-icon-circle bg-warning-subtle text-warning">
                        <i class="bi bi-gear-wide-connected"></i>
                    </div>
                    <h6 class="fw-bold mb-1">Workshops</h6>
                    <small class="text-muted">Hands-on Skills</small>
                </a>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <a href="<?php echo BASE_URL; ?>student/events.php?category=Seminar" class="category-box">
                    <div class="cat-icon-circle bg-info-subtle text-info">
                        <i class="bi bi-megaphone"></i>
                    </div>
                    <h6 class="fw-bold mb-1">Seminars</h6>
                    <small class="text-muted">Keynotes & Talks</small>
                </a>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <a href="<?php echo BASE_URL; ?>student/events.php?category=Academic" class="category-box">
                    <div class="cat-icon-circle bg-secondary-subtle text-secondary">
                        <i class="bi bi-book"></i>
                    </div>
                    <h6 class="fw-bold mb-1">Academic</h6>
                    <small class="text-muted">Symposiums & Expo</small>
                </a>
            </div>
        </div>
    </div>
</section>

<!-- ========================================================
     Upcoming Events Section
     ======================================================== -->
<section class="py-5 bg-white border-top border-bottom" id="events">
    <div class="container py-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end mb-5">
            <div>
                <span class="badge bg-purple-light text-purple px-3 py-2 rounded-pill fw-semibold">Mark Your Calendar</span>
                <h2 class="mt-2 fw-bold text-navy mb-1">Upcoming Campus Events</h2>
                <p class="text-muted mb-0">Browse scheduled events and secure your seat before registrations close.</p>
            </div>
            <div class="mt-3 mt-md-0">
                <a href="<?php echo BASE_URL; ?>student/events.php" class="btn btn-outline-primary rounded-pill">
                    View All Events <i class="bi bi-arrow-right ms-1"></i>
                </a>
            </div>
        </div>

        <div class="row g-4">
            <?php if ($upcoming_events && mysqli_num_rows($upcoming_events) > 0): ?>
                <?php while ($event = mysqli_fetch_assoc($upcoming_events)): 
                    $capacity = (int)$event['capacity'];
                    $registered = (int)$event['registered_count'];
                    $remaining = max(0, $capacity - $registered);
                    $percentage = ($capacity > 0) ? min(100, round(($registered / $capacity) * 100)) : 0;
                    $is_full = ($remaining <= 0);
                ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="event-card-modern card-lift">
                            <div class="event-card-header">
                                <?php echo get_category_badge($event['category']); ?>
                                <span class="badge <?php echo $is_full ? 'bg-danger-subtle text-danger' : 'bg-success-subtle text-success'; ?> rounded-pill px-2 py-1 small">
                                    <i class="bi <?php echo $is_full ? 'bi-x-circle' : 'bi-check2-circle'; ?> me-1"></i>
                                    <?php echo $is_full ? 'Housefull' : "{$remaining} Seats Left"; ?>
                                </span>
                            </div>

                            <div class="event-card-body">
                                <h5 class="event-card-title"><?php echo htmlspecialchars($event['event_name']); ?></h5>
                                <p class="event-card-desc"><?php echo htmlspecialchars($event['description'] ?? 'Join your fellow students for this campus event.'); ?></p>
                                
                                <div class="event-meta-row">
                                    <i class="bi bi-calendar3"></i>
                                    <span><?php echo format_event_date($event['event_date']); ?></span>
                                </div>
                                <div class="event-meta-row">
                                    <i class="bi bi-geo-alt-fill"></i>
                                    <span class="text-truncate"><?php echo htmlspecialchars($event['venue']); ?></span>
                                </div>
                                <div class="event-meta-row">
                                    <i class="bi bi-person-badge"></i>
                                    <span>Organized by: <?php echo htmlspecialchars($event['organizer_name']); ?></span>
                                </div>

                                <!-- Seat capacity progress -->
                                <div class="mt-3">
                                    <div class="d-flex justify-content-between text-muted small">
                                        <span>Capacity Utilization</span>
                                        <span class="fw-semibold"><?php echo $registered; ?> / <?php echo $capacity; ?></span>
                                    </div>
                                    <div class="capacity-progress">
                                        <div class="progress-bar <?php echo ($percentage >= 90) ? 'bg-danger' : ''; ?>" style="width: <?php echo $percentage; ?>%"></div>
                                    </div>
                                </div>
                            </div>

                            <div class="event-card-footer d-flex gap-2">
                                <a href="<?php echo BASE_URL; ?>student/event_details.php?id=<?php echo $event['event_id']; ?>" class="btn btn-outline-secondary w-50 btn-sm rounded-pill">
                                    Details
                                </a>
                                <?php if (!is_logged_in()): ?>
                                    <a href="<?php echo BASE_URL; ?>auth/login.php" class="btn btn-primary w-50 btn-sm rounded-pill <?php echo $is_full ? 'disabled' : ''; ?>">
                                        Register
                                    </a>
                                <?php elseif (current_user_role() === 'student'): ?>
                                    <a href="<?php echo BASE_URL; ?>student/event_details.php?id=<?php echo $event['event_id']; ?>" class="btn btn-primary w-50 btn-sm rounded-pill <?php echo $is_full ? 'disabled' : ''; ?>">
                                        <?php echo $is_full ? 'Full' : 'Register'; ?>
                                    </a>
                                <?php else: ?>
                                    <a href="<?php echo BASE_URL; ?>organizer/manage_events.php" class="btn btn-navy w-50 btn-sm rounded-pill">
                                        Manage
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-12 text-center py-5">
                    <div class="p-5 bg-light rounded-4 border">
                        <i class="bi bi-calendar-x fs-1 text-muted mb-3 d-block"></i>
                        <h4 class="fw-bold">No Upcoming Events Found</h4>
                        <p class="text-muted">Check back later or log in as an organizer to schedule new college events.</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- ========================================================
     Why Choose EventEase Section
     ======================================================== -->
<section class="py-5" id="why-us">
    <div class="container py-4">
        <div class="text-center mb-5">
            <span class="badge bg-purple-light text-purple px-3 py-2 rounded-pill fw-semibold">Why EventEase</span>
            <h2 class="mt-2 fw-bold text-navy">Designed for College Campuses</h2>
            <p class="text-muted mx-auto" style="max-width: 650px;">
                Engineered specifically for academic institutions to eliminate paper queues, manual spreadsheets, and chaotic check-ins.
            </p>
        </div>

        <div class="row g-4">
            <div class="col-md-6 col-lg-3">
                <div class="p-4 bg-white rounded-4 border h-100 shadow-sm text-center">
                    <div class="rounded-circle bg-purple-light text-purple d-inline-flex align-items-center justify-content-center p-3 mb-3" style="width:60px;height:60px;">
                        <i class="bi bi-lightning-charge-fill fs-4"></i>
                    </div>
                    <h5 class="fw-bold mb-2">1-Click Registration</h5>
                    <p class="text-muted small mb-0">Students can register instantly for any open event with zero hassle and instant confirmation status.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="p-4 bg-white rounded-4 border h-100 shadow-sm text-center">
                    <div class="rounded-circle bg-success-subtle text-success d-inline-flex align-items-center justify-content-center p-3 mb-3" style="width:60px;height:60px;">
                        <i class="bi bi-shield-lock-fill fs-4"></i>
                    </div>
                    <h5 class="fw-bold mb-2">Strict Capacity Control</h5>
                    <p class="text-muted small mb-0">Never overbook an auditorium again. Transactions and concurrency guards lock registrations when capacity is full.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="p-4 bg-white rounded-4 border h-100 shadow-sm text-center">
                    <div class="rounded-circle bg-info-subtle text-info d-inline-flex align-items-center justify-content-center p-3 mb-3" style="width:60px;height:60px;">
                        <i class="bi bi-qr-code-scan fs-4"></i>
                    </div>
                    <h5 class="fw-bold mb-2">Attendance Tracking</h5>
                    <p class="text-muted small mb-0">Organizers can mark attendance directly on campus, calculate turnout rates, and verify participation.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="p-4 bg-white rounded-4 border h-100 shadow-sm text-center">
                    <div class="rounded-circle bg-warning-subtle text-warning d-inline-flex align-items-center justify-content-center p-3 mb-3" style="width:60px;height:60px;">
                        <i class="bi bi-bar-chart-line-fill fs-4"></i>
                    </div>
                    <h5 class="fw-bold mb-2">Real-Time Analytics</h5>
                    <p class="text-muted small mb-0">Monitor total signups, category popularity, and turnout statistics directly from the organizer portal.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ========================================================
     Call To Action
     ======================================================== -->
<section class="py-5 bg-purple-light">
    <div class="container py-4 text-center">
        <h2 class="fw-bold text-navy mb-3">Ready to Experience Campus Events the Modern Way?</h2>
        <p class="text-muted mx-auto mb-4" style="max-width: 600px;">
            Join hundreds of students and faculty organizers today. Create your free account in under 30 seconds.
        </p>
        <div class="d-flex justify-content-center gap-3">
            <a href="<?php echo BASE_URL; ?>auth/register.php" class="btn btn-primary btn-lg rounded-pill px-4">
                <i class="bi bi-rocket-takeoff me-1"></i> Get Started Free
            </a>
            <a href="<?php echo BASE_URL; ?>auth/login.php" class="btn btn-outline-primary btn-lg rounded-pill px-4">
                Sign In
            </a>
        </div>
    </div>
</section>

<?php require_once __DIR__ . "/includes/footer.php"; ?>
