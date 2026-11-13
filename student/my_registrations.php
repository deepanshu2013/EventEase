<?php
/**
 * EventEase - Student My Registrations & History
 */

$page_title = "My Registrations | EventEase";
require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../includes/auth.php";

require_login('student');

$student_id = current_user_id();
$filter_tab = $_GET['tab'] ?? 'all';

// Query registrations with event details
$sql = "
    SELECT 
        r.registration_id,
        r.registration_date,
        r.status,
        e.event_id,
        e.event_name,
        e.category,
        e.venue,
        e.event_date,
        u.full_name AS organizer_name
    FROM registrations r
    JOIN events e ON r.event_id = e.event_id
    JOIN users u ON e.organizer_id = u.user_id
    WHERE r.student_id = ?
";

if ($filter_tab === 'active') {
    $sql .= " AND r.status = 'registered'";
} elseif ($filter_tab === 'attended') {
    $sql .= " AND r.status = 'attended'";
} elseif ($filter_tab === 'cancelled') {
    $sql .= " AND r.status = 'cancelled'";
}

$sql .= " ORDER BY r.registration_date DESC";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $student_id);
mysqli_stmt_execute($stmt);
$registrations = mysqli_stmt_get_result($stmt);

// Counts for tab badges
$count_query = "
    SELECT 
        COUNT(*) AS total_all,
        COUNT(CASE WHEN status = 'registered' THEN 1 END) AS total_active,
        COUNT(CASE WHEN status = 'attended' THEN 1 END) AS total_attended,
        COUNT(CASE WHEN status = 'cancelled' THEN 1 END) AS total_cancelled
    FROM registrations
    WHERE student_id = ?
";
$count_stmt = mysqli_prepare($conn, $count_query);
mysqli_stmt_bind_param($count_stmt, "i", $student_id);
mysqli_stmt_execute($count_stmt);
$counts = mysqli_fetch_assoc(mysqli_stmt_get_result($count_stmt));
mysqli_stmt_close($count_stmt);

require_once __DIR__ . "/../includes/header.php";
?>

<div class="container py-4">
    <!-- Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
        <div>
            <span class="badge bg-purple-light text-purple px-2 py-1 rounded-pill small">Participation Tracking</span>
            <h2 class="fw-bold text-navy mb-1">My Event Registrations</h2>
            <p class="text-muted small mb-0">Track your registration status, access entry passes, or manage cancellations.</p>
        </div>
        <div class="mt-3 mt-md-0">
            <a href="events.php" class="btn btn-primary rounded-pill">
                <i class="bi bi-plus-circle me-1"></i> Register for More Events
            </a>
        </div>
    </div>

    <!-- Flash message -->
    <?php display_flash_message(); ?>

    <!-- Filter Tabs -->
    <div class="card-modern mb-4 p-2 bg-light">
        <ul class="nav nav-pills nav-fill gap-2">
            <li class="nav-item">
                <a class="nav-link rounded-pill py-2 <?php echo ($filter_tab === 'all') ? 'active bg-primary text-white' : 'text-muted'; ?>" href="my_registrations.php?tab=all">
                    All Registrations <span class="badge bg-white text-dark ms-1"><?php echo (int)($counts['total_all'] ?? 0); ?></span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link rounded-pill py-2 <?php echo ($filter_tab === 'active') ? 'active bg-primary text-white' : 'text-muted'; ?>" href="my_registrations.php?tab=active">
                    <i class="bi bi-check-circle me-1"></i> Active <span class="badge bg-white text-dark ms-1"><?php echo (int)($counts['total_active'] ?? 0); ?></span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link rounded-pill py-2 <?php echo ($filter_tab === 'attended') ? 'active bg-primary text-white' : 'text-muted'; ?>" href="my_registrations.php?tab=attended">
                    <i class="bi bi-award me-1"></i> Attended <span class="badge bg-white text-dark ms-1"><?php echo (int)($counts['total_attended'] ?? 0); ?></span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link rounded-pill py-2 <?php echo ($filter_tab === 'cancelled') ? 'active bg-primary text-white' : 'text-muted'; ?>" href="my_registrations.php?tab=cancelled">
                    <i class="bi bi-x-circle me-1"></i> Cancelled <span class="badge bg-white text-dark ms-1"><?php echo (int)($counts['total_cancelled'] ?? 0); ?></span>
                </a>
            </li>
        </ul>
    </div>

    <!-- Registrations Table / List -->
    <div class="card-modern overflow-hidden">
        <div class="table-responsive">
            <table class="table table-modern align-middle mb-0">
                <thead>
                    <tr>
                        <th>Pass ID</th>
                        <th>Event Details</th>
                        <th>Category</th>
                        <th>Date & Time</th>
                        <th>Venue</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($registrations) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($registrations)): ?>
                            <tr>
                                <td>
                                    <span class="badge bg-light text-navy border font-monospace">
                                        #EVT-<?php echo str_pad($row['registration_id'], 4, '0', STR_PAD_LEFT); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="event_details.php?id=<?php echo $row['event_id']; ?>" class="fw-bold text-navy text-decoration-none d-block">
                                        <?php echo htmlspecialchars($row['event_name']); ?>
                                    </a>
                                    <small class="text-muted">Registered: <?php echo date("d M Y", strtotime($row['registration_date'])); ?></small>
                                </td>
                                <td>
                                    <?php echo get_category_badge($row['category']); ?>
                                </td>
                                <td>
                                    <div class="small fw-semibold text-navy"><?php echo format_event_date_short($row['event_date']); ?></div>
                                    <small class="text-muted"><?php echo date("h:i A", strtotime($row['event_date'])); ?></small>
                                </td>
                                <td>
                                    <span class="small text-muted"><i class="bi bi-geo-alt me-1 text-danger"></i><?php echo htmlspecialchars($row['venue']); ?></span>
                                </td>
                                <td>
                                    <?php echo get_status_badge($row['status']); ?>
                                </td>
                                <td class="text-end">
                                    <div class="d-inline-flex gap-1">
                                        <!-- View Details link -->
                                        <a href="event_details.php?id=<?php echo $row['event_id']; ?>" class="btn btn-outline-secondary btn-sm rounded-pill" title="View Event Page">
                                            <i class="bi bi-eye"></i>
                                        </a>

                                        <!-- Cancel action (only if currently registered) -->
                                        <?php if ($row['status'] === 'registered'): ?>
                                            <form method="POST" action="cancel_registration.php" class="d-inline" onsubmit="return confirm('Are you sure you want to cancel your registration for \'<?php echo addslashes($row['event_name']); ?>\'?');">
                                                <?php echo csrf_field(); ?>
                                                <input type="hidden" name="registration_id" value="<?php echo $row['registration_id']; ?>">
                                                <input type="hidden" name="redirect_to" value="<?php echo BASE_URL; ?>student/my_registrations.php?tab=<?php echo htmlspecialchars($filter_tab); ?>">
                                                <button type="submit" class="btn btn-outline-danger btn-sm rounded-pill" title="Cancel Registration">
                                                    <i class="bi bi-x-circle"></i> Cancel
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center py-5">
                                <div class="py-4">
                                    <i class="bi bi-ticket-perforated fs-1 text-muted mb-2 d-block"></i>
                                    <h5 class="fw-bold text-navy">No Registrations Found</h5>
                                    <p class="text-muted small">You do not have any event registrations matching this filter.</p>
                                    <a href="events.php" class="btn btn-outline-primary btn-sm rounded-pill">
                                        Explore Campus Events
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
