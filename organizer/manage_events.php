<?php
/**
 * EventEase - Organizer Manage Events (List, Edit, Delete, Links)
 */

$page_title = "Manage Events | EventEase";
require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../includes/auth.php";

require_login('organizer');

$organizer_id = current_user_id();

// Query all events created by this organizer with signup and attendance metrics
$query = "
    SELECT 
        e.*,
        COUNT(CASE WHEN r.status IN ('registered', 'attended') THEN 1 END) AS enrolled_count,
        COUNT(CASE WHEN r.status = 'attended' THEN 1 END) AS attended_count
    FROM events e
    LEFT JOIN registrations r ON e.event_id = r.event_id
    WHERE e.organizer_id = ?
    GROUP BY e.event_id
    ORDER BY e.event_date DESC
";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $organizer_id);
mysqli_stmt_execute($stmt);
$events = mysqli_stmt_get_result($stmt);

require_once __DIR__ . "/../includes/header.php";
?>

<div class="container py-4">
    <!-- Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
        <div>
            <span class="badge bg-purple text-white px-2 py-1 rounded-pill small">Event Administration</span>
            <h2 class="fw-bold text-navy mb-1">Manage College Events</h2>
            <p class="text-muted small mb-0">Create, update details, track registrations, and manage check-ins.</p>
        </div>
        <div class="mt-3 mt-md-0 d-flex gap-2">
            <a href="add_event.php" class="btn btn-primary rounded-pill shadow-sm">
                <i class="bi bi-plus-circle me-1"></i> Add New Event
            </a>
            <a href="attendance.php" class="btn btn-navy rounded-pill">
                <i class="bi bi-check2-square me-1"></i> Attendance
            </a>
        </div>
    </div>

    <!-- Flash message -->
    <?php display_flash_message(); ?>

    <!-- Filter Bar Card -->
    <div class="card-modern p-3 mb-4">
        <div class="row g-2 align-items-center">
            <div class="col-md-6">
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
                    <input type="text" class="form-control border-start-0" placeholder="Filter events by name, venue or category..." data-filter-target="#manageEventsTable" data-filter-item="tbody tr">
                </div>
            </div>
            <div class="col-md-6 text-md-end text-muted small">
                Showing <strong><?php echo mysqli_num_rows($events); ?></strong> events created by your department
            </div>
        </div>
    </div>

    <!-- Events Management Table -->
    <div class="card-modern overflow-hidden">
        <div class="table-responsive">
            <table class="table table-modern align-middle mb-0" id="manageEventsTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Event Details</th>
                        <th>Category</th>
                        <th>Venue</th>
                        <th>Scheduled Date</th>
                        <th>Capacity & Enrolled</th>
                        <th>Timing</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($events) > 0): ?>
                        <?php while ($ev = mysqli_fetch_assoc($events)): 
                            $cap = (int)$ev['capacity'];
                            $enrolled = (int)$ev['enrolled_count'];
                            $pct = ($cap > 0) ? min(100, round(($enrolled / $cap) * 100)) : 0;
                            $is_past = (strtotime($ev['event_date']) <= time());
                        ?>
                            <tr>
                                <td>
                                    <span class="badge bg-light text-navy border font-monospace">
                                        #<?php echo $ev['event_id']; ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="../student/event_details.php?id=<?php echo $ev['event_id']; ?>" class="fw-bold text-navy text-decoration-none d-block">
                                        <?php echo htmlspecialchars($ev['event_name']); ?>
                                    </a>
                                    <small class="text-muted">Created: <?php echo date("d M Y", strtotime($ev['created_at'])); ?></small>
                                </td>
                                <td>
                                    <?php echo get_category_badge($ev['category']); ?>
                                </td>
                                <td>
                                    <span class="small text-muted"><i class="bi bi-geo-alt me-1 text-danger"></i><?php echo htmlspecialchars($ev['venue']); ?></span>
                                </td>
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
                                <td>
                                    <?php if ($is_past): ?>
                                        <span class="badge bg-secondary-subtle text-secondary rounded-pill px-2 py-1">Past</span>
                                    <?php else: ?>
                                        <span class="badge bg-success-subtle text-success rounded-pill px-2 py-1">Upcoming</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <div class="d-inline-flex gap-1">
                                        <!-- View Participants -->
                                        <a href="participants.php?event_id=<?php echo $ev['event_id']; ?>" class="btn btn-outline-secondary btn-sm" title="View Enrolled Students (<?php echo $enrolled; ?>)">
                                            <i class="bi bi-people"></i>
                                        </a>

                                        <!-- Attendance -->
                                        <a href="attendance.php?event_id=<?php echo $ev['event_id']; ?>" class="btn btn-outline-primary btn-sm" title="Mark Attendance">
                                            <i class="bi bi-check2-square"></i>
                                        </a>

                                        <!-- Edit -->
                                        <a href="edit_event.php?id=<?php echo $ev['event_id']; ?>" class="btn btn-outline-dark btn-sm" title="Edit Event Details">
                                            <i class="bi bi-pencil"></i>
                                        </a>

                                        <!-- Delete Form -->
                                        <form method="POST" action="delete_event.php" class="d-inline" onsubmit="return confirm('Are you sure you want to delete event \'<?php echo addslashes($ev['event_name']); ?>\'? This will also remove all registered student records.');">
                                            <?php echo csrf_field(); ?>
                                            <input type="hidden" name="event_id" value="<?php echo $ev['event_id']; ?>">
                                            <button type="submit" class="btn btn-outline-danger btn-sm" title="Delete Event">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center py-5">
                                <div class="py-4">
                                    <i class="bi bi-calendar-plus fs-1 text-muted mb-2 d-block"></i>
                                    <h5 class="fw-bold text-navy">No Events Created Yet</h5>
                                    <p class="text-muted small">You have not scheduled any college events yet.</p>
                                    <a href="add_event.php" class="btn btn-primary btn-sm rounded-pill">
                                        <i class="bi bi-plus-circle me-1"></i> Create First Event
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php 
mysqli_stmt_close($stmt);
require_once __DIR__ . "/../includes/footer.php"; 
?>
