<?php
/**
 * EventEase - Student Dashboard
 */

$page_title = "Student Dashboard | EventEase";
require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../includes/auth.php";

require_login('student');

$student_id = current_user_id();
$student_name = current_user_name();

// 1. Fetch Student Overview Statistics
$stats_query = "
    SELECT 
        COUNT(CASE WHEN r.status = 'registered' THEN 1 END) AS active_registrations,
        COUNT(CASE WHEN r.status = 'attended' THEN 1 END) AS attended_events,
        COUNT(CASE WHEN r.status = 'cancelled' THEN 1 END) AS cancelled_events
    FROM registrations r
    WHERE r.student_id = ?
";
$stmt = mysqli_prepare($conn, $stats_query);
mysqli_stmt_bind_param($stmt, "i", $student_id);
mysqli_stmt_execute($stmt);
$user_stats = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

// 2. Fetch the Next Upcoming Registered Event (Highlight Banner)
$next_event_query = "
    SELECT 
        e.event_id,
        e.event_name,
        e.category,
        e.venue,
        e.event_date,
        r.registration_id,
        r.registration_date,
        u.full_name AS organizer_name
    FROM registrations r
    JOIN events e ON r.event_id = e.event_id
    JOIN users u ON e.organizer_id = u.user_id
    WHERE r.student_id = ? 
      AND r.status = 'registered' 
      AND e.event_date >= NOW()
    ORDER BY e.event_date ASC
    LIMIT 1
";
$stmt = mysqli_prepare($conn, $next_event_query);
mysqli_stmt_bind_param($stmt, "i", $student_id);
mysqli_stmt_execute($stmt);
$next_event = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

// 3. Fetch Featured Upcoming Available Events (Limit 3)
$upcoming_query = "
    SELECT 
        e.event_id,
        e.event_name,
        e.description,
        e.category,
        e.venue,
        e.event_date,
        e.capacity,
        u.full_name AS organizer_name,
        COUNT(CASE WHEN r_all.status IN ('registered', 'attended') THEN 1 END) AS total_booked,
        r_user.status AS my_status
    FROM events e
    JOIN users u ON e.organizer_id = u.user_id
    LEFT JOIN registrations r_all ON e.event_id = r_all.event_id
    LEFT JOIN registrations r_user ON e.event_id = r_user.event_id AND r_user.student_id = ?
    WHERE e.event_date >= NOW()
    GROUP BY e.event_id
    ORDER BY e.event_date ASC
    LIMIT 3
";
$stmt = mysqli_prepare($conn, $upcoming_query);
mysqli_stmt_bind_param($stmt, "i", $student_id);
mysqli_stmt_execute($stmt);
$upcoming_events = mysqli_stmt_get_result($stmt);
mysqli_stmt_close($stmt);

// 4. Fetch Recent Registrations (Last 4)
$recent_regs_query = "
    SELECT 
        r.registration_id,
        r.status,
        r.registration_date,
        e.event_id,
        e.event_name,
        e.category,
        e.venue,
        e.event_date
    FROM registrations r
    JOIN events e ON r.event_id = e.event_id
    WHERE r.student_id = ?
    ORDER BY r.registration_date DESC
    LIMIT 4
";
$stmt = mysqli_prepare($conn, $recent_regs_query);
mysqli_stmt_bind_param($stmt, "i", $student_id);
mysqli_stmt_execute($stmt);
$recent_registrations = mysqli_stmt_get_result($stmt);
mysqli_stmt_close($stmt);

require_once __DIR__ . "/../includes/header.php";
?>

<div class="container py-4">
    <!-- Breadcrumb & Greeting -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <span class="badge bg-purple-light text-purple px-2 py-1 rounded-pill small">Student Portal</span>
                <span class="text-muted small">&bull; Today is <?php echo date("l, d F Y"); ?></span>
            </div>
            <h2 class="fw-bold text-navy mb-0">Welcome back, <?php echo htmlspecialchars($student_name); ?>! 👋</h2>
        </div>
        <div class="mt-3 mt-md-0 d-flex gap-2">
            <a href="events.php" class="btn btn-primary rounded-pill">
                <i class="bi bi-compass me-1"></i> Browse All Events
            </a>
            <a href="my_registrations.php" class="btn btn-outline-secondary rounded-pill">
                <i class="bi bi-ticket-perforated me-1"></i> My Registrations
            </a>
        </div>
    </div>

    <!-- Flash message -->
    <?php display_flash_message(); ?>

    <!-- Overview Metric Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-4 col-sm-6">
            <div class="dashboard-widget">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="widget-value text-purple"><?php echo (int)($user_stats['active_registrations'] ?? 0); ?></div>
                        <div class="widget-title">Active Registrations</div>
                    </div>
                    <div class="widget-icon bg-purple-light text-purple">
                        <i class="bi bi-ticket-fill"></i>
                    </div>
                </div>
                <small class="text-muted mt-2 d-block">Upcoming events you are enrolled in</small>
            </div>
        </div>

        <div class="col-md-4 col-sm-6">
            <div class="dashboard-widget">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="widget-value text-success"><?php echo (int)($user_stats['attended_events'] ?? 0); ?></div>
                        <div class="widget-title">Attended Events</div>
                    </div>
                    <div class="widget-icon bg-success-subtle text-success">
                        <i class="bi bi-award-fill"></i>
                    </div>
                </div>
                <small class="text-muted mt-2 d-block">Campus activities verified by organizer</small>
            </div>
        </div>

        <div class="col-md-4 col-sm-12">
            <div class="dashboard-widget">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="widget-value text-secondary"><?php echo (int)($user_stats['cancelled_events'] ?? 0); ?></div>
                        <div class="widget-title">Cancelled Entries</div>
                    </div>
                    <div class="widget-icon bg-secondary-subtle text-secondary">
                        <i class="bi bi-x-circle-fill"></i>
                    </div>
                </div>
                <small class="text-muted mt-2 d-block">Withdrawn registrations</small>
            </div>
        </div>
    </div>

    <!-- Next Up Banner (If student has an active event) -->
    <?php if ($next_event): ?>
        <div class="card border-0 shadow-sm rounded-4 mb-4 text-white p-4" style="background: linear-gradient(135deg, #1e1b4b 0%, #312e81 60%, #4338ca 100%);">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <span class="badge bg-warning text-dark px-3 py-1 rounded-pill mb-2 fw-bold">
                        <i class="bi bi-bell-fill me-1"></i> Your Next Scheduled Event
                    </span>
                    <h3 class="fw-bold mb-2"><?php echo htmlspecialchars($next_event['event_name']); ?></h3>
                    <div class="d-flex flex-wrap gap-3 text-light small opacity-90">
                        <span><i class="bi bi-calendar3 me-1"></i> <?php echo format_event_date($next_event['event_date']); ?></span>
                        <span><i class="bi bi-geo-alt me-1"></i> <?php echo htmlspecialchars($next_event['venue']); ?></span>
                        <span><i class="bi bi-person-circle me-1"></i> <?php echo htmlspecialchars($next_event['organizer_name']); ?></span>
                    </div>
                </div>
                <div class="col-lg-4 text-lg-end mt-3 mt-lg-0">
                    <a href="event_details.php?id=<?php echo $next_event['event_id']; ?>" class="btn btn-light px-4 py-2 rounded-pill fw-bold text-navy shadow-sm">
                        <i class="bi bi-qr-code me-1"></i> View Event & Pass
                    </a>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Two-column Section: Featured Upcoming Events & Recent Activity -->
    <div class="row g-4">
        <!-- Upcoming Events to Explore -->
        <div class="col-lg-7">
            <div class="card-modern p-4 h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold text-navy mb-0">
                        <i class="bi bi-stars text-warning me-1"></i> Recommended Upcoming Events
                    </h5>
                    <a href="events.php" class="small text-purple fw-semibold text-decoration-none">
                        View all <i class="bi bi-chevron-right"></i>
                    </a>
                </div>

                <div class="vstack gap-3">
                    <?php if (mysqli_num_rows($upcoming_events) > 0): ?>
                        <?php while ($event = mysqli_fetch_assoc($upcoming_events)): 
                            $capacity = (int)$event['capacity'];
                            $booked = (int)$event['total_booked'];
                            $remaining = max(0, $capacity - $booked);
                            $my_status = $event['my_status'];
                        ?>
                            <div class="p-3 rounded-3 border bg-light d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3">
                                <div>
                                    <div class="d-flex align-items-center gap-2 mb-1">
                                        <?php echo get_category_badge($event['category']); ?>
                                        <small class="text-muted"><i class="bi bi-calendar-event me-1"></i><?php echo format_event_date_short($event['event_date']); ?></small>
                                    </div>
                                    <h6 class="fw-bold text-navy mb-1"><?php echo htmlspecialchars($event['event_name']); ?></h6>
                                    <small class="text-muted d-block text-truncate" style="max-width: 320px;">
                                        <i class="bi bi-geo-alt me-1"></i><?php echo htmlspecialchars($event['venue']); ?> &bull; <?php echo $remaining; ?> seats left
                                    </small>
                                </div>
                                <div class="text-sm-end">
                                    <?php if ($my_status === 'registered'): ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 rounded-pill">
                                            <i class="bi bi-check-circle me-1"></i> Registered
                                        </span>
                                    <?php elseif ($my_status === 'attended'): ?>
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-2 rounded-pill">
                                            <i class="bi bi-award me-1"></i> Attended
                                        </span>
                                    <?php elseif ($remaining <= 0): ?>
                                        <button class="btn btn-secondary btn-sm rounded-pill" disabled>Full</button>
                                    <?php else: ?>
                                        <form method="POST" action="register_event.php" class="d-inline">
                                            <?php echo csrf_field(); ?>
                                            <input type="hidden" name="event_id" value="<?php echo $event['event_id']; ?>">
                                            <input type="hidden" name="redirect_to" value="<?php echo BASE_URL; ?>student/dashboard.php">
                                            <button type="submit" class="btn btn-primary btn-sm rounded-pill px-3">
                                                Register
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="text-center py-4 text-muted">
                            <i class="bi bi-calendar-check fs-2 text-muted mb-2 d-block"></i>
                            No upcoming events currently scheduled.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Recent Registration Activity -->
        <div class="col-lg-5">
            <div class="card-modern p-4 h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold text-navy mb-0">
                        <i class="bi bi-clock-history text-purple me-1"></i> Recent Registrations
                    </h5>
                    <a href="my_registrations.php" class="small text-purple fw-semibold text-decoration-none">
                        All <i class="bi bi-chevron-right"></i>
                    </a>
                </div>

                <?php if (mysqli_num_rows($recent_registrations) > 0): ?>
                    <div class="list-group list-group-flush">
                        <?php while ($reg = mysqli_fetch_assoc($recent_registrations)): ?>
                            <div class="list-group-item px-0 py-3 border-bottom">
                                <div class="d-flex justify-content-between align-items-start mb-1">
                                    <h6 class="fw-bold text-navy mb-0 text-truncate" style="max-width: 200px;">
                                        <?php echo htmlspecialchars($reg['event_name']); ?>
                                    </h6>
                                    <?php echo get_status_badge($reg['status']); ?>
                                </div>
                                <div class="d-flex justify-content-between text-muted small mt-1">
                                    <span><i class="bi bi-calendar3 me-1"></i><?php echo format_event_date_short($reg['event_date']); ?></span>
                                    <span>Registered: <?php echo date("d M", strtotime($reg['registration_date'])); ?></span>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-ticket-perforated fs-1 text-muted mb-2 d-block"></i>
                        <p class="mb-2">You have not registered for any events yet.</p>
                        <a href="events.php" class="btn btn-outline-primary btn-sm rounded-pill">
                            Find an Event
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
