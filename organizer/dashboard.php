<?php
/**
 * EventEase - Organizer Analytics & Management Dashboard
 */

$page_title = "Organizer Dashboard | EventEase";
require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../includes/auth.php";

require_login('organizer');

$organizer_id = current_user_id();
$organizer_name = current_user_name();

// 1. Fetch Key Performance Indicators (KPIs)
$kpi_query = "
    SELECT 
        COUNT(DISTINCT e.event_id) AS total_events,
        COUNT(DISTINCT CASE WHEN e.event_date >= NOW() THEN e.event_id END) AS upcoming_events,
        COALESCE(SUM(e.capacity), 0) AS total_capacity,
        COUNT(CASE WHEN r.status IN ('registered', 'attended') THEN 1 END) AS total_signups,
        COUNT(CASE WHEN r.status = 'attended' THEN 1 END) AS total_attended
    FROM events e
    LEFT JOIN registrations r ON e.event_id = r.event_id
    WHERE e.organizer_id = ?
";
$stmt = mysqli_prepare($conn, $kpi_query);
mysqli_stmt_bind_param($stmt, "i", $organizer_id);
mysqli_stmt_execute($stmt);
$kpi = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

$total_events = (int)($kpi['total_events'] ?? 0);
$upcoming_events_count = (int)($kpi['upcoming_events'] ?? 0);
$total_capacity = (int)($kpi['total_capacity'] ?? 0);
$total_signups = (int)($kpi['total_signups'] ?? 0);
$total_attended = (int)($kpi['total_attended'] ?? 0);
$attendance_rate = ($total_signups > 0) ? round(($total_attended / $total_signups) * 100) : 0;
$capacity_rate = ($total_capacity > 0) ? round(($total_signups / $total_capacity) * 100) : 0;

// 2. Fetch Recent Registrations across organizer's events (Last 6)
$recent_signups_query = "
    SELECT 
        r.registration_id,
        r.registration_date,
        r.status,
        u.full_name AS student_name,
        u.email AS student_email,
        e.event_id,
        e.event_name,
        e.category
    FROM registrations r
    JOIN users u ON r.student_id = u.user_id
    JOIN events e ON r.event_id = e.event_id
    WHERE e.organizer_id = ?
    ORDER BY r.registration_date DESC
    LIMIT 6
";
$stmt = mysqli_prepare($conn, $recent_signups_query);
mysqli_stmt_bind_param($stmt, "i", $organizer_id);
mysqli_stmt_execute($stmt);
$recent_signups = mysqli_stmt_get_result($stmt);
mysqli_stmt_close($stmt);

// 3. Fetch Organizer's Active & Upcoming Events with Capacity Status
$events_summary_query = "
    SELECT 
        e.event_id,
        e.event_name,
        e.category,
        e.venue,
        e.event_date,
        e.capacity,
        COUNT(CASE WHEN r.status IN ('registered', 'attended') THEN 1 END) AS enrolled_count,
        COUNT(CASE WHEN r.status = 'attended' THEN 1 END) AS attended_count
    FROM events e
    LEFT JOIN registrations r ON e.event_id = r.event_id
    WHERE e.organizer_id = ?
    GROUP BY e.event_id
    ORDER BY e.event_date ASC
    LIMIT 5
";
$stmt = mysqli_prepare($conn, $events_summary_query);
mysqli_stmt_bind_param($stmt, "i", $organizer_id);
mysqli_stmt_execute($stmt);
$events_summary = mysqli_stmt_get_result($stmt);
mysqli_stmt_close($stmt);

require_once __DIR__ . "/../includes/header.php";
?>

<div class="container py-4">
    <!-- Header with Quick Action Buttons -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <span class="badge bg-purple text-white px-2 py-1 rounded-pill small">Faculty & Organizer Workspace</span>
                <span class="text-muted small">&bull; Session Active</span>
            </div>
            <h2 class="fw-bold text-navy mb-0">Organizer Dashboard</h2>
        </div>
        <div class="mt-3 mt-md-0 d-flex gap-2 flex-wrap">
            <a href="add_event.php" class="btn btn-primary rounded-pill shadow-sm">
                <i class="bi bi-plus-circle me-1"></i> Create New Event
            </a>
            <a href="manage_events.php" class="btn btn-outline-secondary rounded-pill">
                <i class="bi bi-kanban me-1"></i> Manage All Events
            </a>
            <a href="attendance.php" class="btn btn-navy rounded-pill">
                <i class="bi bi-check2-square me-1"></i> Mark Attendance
            </a>
        </div>
    </div>

    <!-- Flash message -->
    <?php display_flash_message(); ?>

    <!-- KPI Metrics Banner -->
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-sm-6">
            <div class="dashboard-widget">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="widget-value text-navy"><?php echo $total_events; ?></div>
                        <div class="widget-title">Total Events Created</div>
                    </div>
                    <div class="widget-icon bg-primary-subtle text-primary">
                        <i class="bi bi-calendar-event"></i>
                    </div>
                </div>
                <small class="text-muted mt-2 d-block">
                    <strong class="text-success"><?php echo $upcoming_events_count; ?></strong> upcoming active
                </small>
            </div>
        </div>

        <div class="col-xl-3 col-sm-6">
            <div class="dashboard-widget">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="widget-value text-purple"><?php echo $total_signups; ?></div>
                        <div class="widget-title">Total Registrations</div>
                    </div>
                    <div class="widget-icon bg-purple-light text-purple">
                        <i class="bi bi-people-fill"></i>
                    </div>
                </div>
                <small class="text-muted mt-2 d-block">
                    Overall capacity fill: <strong><?php echo $capacity_rate; ?>%</strong>
                </small>
            </div>
        </div>

        <div class="col-xl-3 col-sm-6">
            <div class="dashboard-widget">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="widget-value text-success"><?php echo $total_attended; ?></div>
                        <div class="widget-title">Verified Attendees</div>
                    </div>
                    <div class="widget-icon bg-success-subtle text-success">
                        <i class="bi bi-award-fill"></i>
                    </div>
                </div>
                <small class="text-muted mt-2 d-block">
                    Overall turnout: <strong><?php echo $attendance_rate; ?>%</strong>
                </small>
            </div>
        </div>

        <div class="col-xl-3 col-sm-6">
            <div class="dashboard-widget">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="widget-value text-secondary"><?php echo $total_capacity; ?></div>
                        <div class="widget-title">Total Seat Capacity</div>
                    </div>
                    <div class="widget-icon bg-secondary-subtle text-secondary">
                        <i class="bi bi-building-check"></i>
                    </div>
                </div>
                <small class="text-muted mt-2 d-block">Across all college venues</small>
            </div>
        </div>
    </div>

    <!-- Main Content: Events Overview Table + Recent Signups -->
    <div class="row g-4">
        <!-- Event Capacity & Management Table -->
        <div class="col-lg-8">
            <div class="card-modern p-4 h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold text-navy mb-0">
                        <i class="bi bi-calendar2-range text-purple me-1"></i> Events Capacity & Utilization
                    </h5>
                    <a href="manage_events.php" class="small text-purple fw-semibold text-decoration-none">
                        Manage all (<?php echo $total_events; ?>) <i class="bi bi-chevron-right"></i>
                    </a>
                </div>

                <div class="table-responsive">
                    <table class="table table-modern align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Event Name</th>
                                <th>Category</th>
                                <th>Event Date</th>
                                <th>Capacity Utilization</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (mysqli_num_rows($events_summary) > 0): ?>
                                <?php while ($ev = mysqli_fetch_assoc($events_summary)): 
                                    $cap = (int)$ev['capacity'];
                                    $enrolled = (int)$ev['enrolled_count'];
                                    $pct = ($cap > 0) ? min(100, round(($enrolled / $cap) * 100)) : 0;
                                ?>
                                    <tr>
                                        <td>
                                            <a href="../student/event_details.php?id=<?php echo $ev['event_id']; ?>" class="fw-bold text-navy text-decoration-none d-block">
                                                <?php echo htmlspecialchars($ev['event_name']); ?>
                                            </a>
                                            <small class="text-muted"><i class="bi bi-geo-alt me-1"></i><?php echo htmlspecialchars($ev['venue']); ?></small>
                                        </td>
                                        <td><?php echo get_category_badge($ev['category']); ?></td>
                                        <td>
                                            <div class="small fw-semibold text-navy"><?php echo format_event_date_short($ev['event_date']); ?></div>
                                            <small class="text-muted"><?php echo date("h:i A", strtotime($ev['event_date'])); ?></small>
                                        </td>
                                        <td style="min-width: 140px;">
                                            <div class="d-flex justify-content-between small text-muted mb-1">
                                                <span><?php echo $enrolled; ?> / <?php echo $cap; ?></span>
                                                <span class="fw-bold"><?php echo $pct; ?>%</span>
                                            </div>
                                            <div class="capacity-progress">
                                                <div class="progress-bar <?php echo ($pct >= 90) ? 'bg-danger' : ''; ?>" style="width: <?php echo $pct; ?>%"></div>
                                            </div>
                                        </td>
                                        <td class="text-end">
                                            <div class="btn-group btn-group-sm">
                                                <a href="participants.php?event_id=<?php echo $ev['event_id']; ?>" class="btn btn-outline-secondary" title="View Enrolled Students">
                                                    <i class="bi bi-people"></i>
                                                </a>
                                                <a href="attendance.php?event_id=<?php echo $ev['event_id']; ?>" class="btn btn-outline-primary" title="Attendance">
                                                    <i class="bi bi-check2-square"></i>
                                                </a>
                                                <a href="edit_event.php?id=<?php echo $ev['event_id']; ?>" class="btn btn-outline-dark" title="Edit Event">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">
                                        <i class="bi bi-calendar-plus fs-2 text-muted mb-2 d-block"></i>
                                        You have not created any events yet.
                                        <div class="mt-2">
                                            <a href="add_event.php" class="btn btn-primary btn-sm rounded-pill">Create First Event</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Recent Registrations Feed -->
        <div class="col-lg-4">
            <div class="card-modern p-4 h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold text-navy mb-0">
                        <i class="bi bi-person-lines-fill text-purple me-1"></i> Latest Signups
                    </h5>
                    <a href="participants.php" class="small text-purple fw-semibold text-decoration-none">
                        All <i class="bi bi-chevron-right"></i>
                    </a>
                </div>

                <?php if (mysqli_num_rows($recent_signups) > 0): ?>
                    <div class="list-group list-group-flush">
                        <?php while ($su = mysqli_fetch_assoc($recent_signups)): ?>
                            <div class="list-group-item px-0 py-3 border-bottom">
                                <div class="d-flex justify-content-between align-items-start mb-1">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="rounded-circle bg-purple-light text-purple fw-bold d-flex align-items-center justify-content-center" style="width:32px;height:32px;font-size:0.75rem;">
                                            <?php echo strtoupper(substr($su['student_name'], 0, 1)); ?>
                                        </div>
                                        <div>
                                            <h6 class="fw-bold text-navy mb-0 text-truncate" style="max-width: 140px;">
                                                <?php echo htmlspecialchars($su['student_name']); ?>
                                            </h6>
                                            <small class="text-muted d-block text-truncate" style="max-width: 140px;">
                                                <?php echo htmlspecialchars($su['student_email']); ?>
                                            </small>
                                        </div>
                                    </div>
                                    <?php echo get_status_badge($su['status']); ?>
                                </div>
                                <div class="small text-muted mt-2 d-flex justify-content-between">
                                    <span class="text-truncate" style="max-width: 170px;">
                                        <i class="bi bi-calendar-event me-1"></i><?php echo htmlspecialchars($su['event_name']); ?>
                                    </span>
                                    <span><?php echo date("d M, h:i A", strtotime($su['registration_date'])); ?></span>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-people fs-1 text-muted mb-2 d-block"></i>
                        <p class="mb-0">No student registrations recorded yet.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
