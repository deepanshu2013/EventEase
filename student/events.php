<?php
/**
 * EventEase - Event Browsing & Search
 */

$page_title = "Browse Events | EventEase";
require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../includes/auth.php";

$student_id = is_logged_in() && current_user_role() === 'student' ? current_user_id() : 0;

// Filter params
$search_query = trim($_GET['search'] ?? '');
$category_filter = trim($_GET['category'] ?? '');

// Build query
$sql = "
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
";

$params = [$student_id];
$types = "i";

if (!empty($category_filter)) {
    $sql .= " AND e.category = ?";
    $params[] = $category_filter;
    $types .= "s";
}

if (!empty($search_query)) {
    $sql .= " AND (e.event_name LIKE ? OR e.venue LIKE ? OR e.description LIKE ?)";
    $like_search = "%" . $search_query . "%";
    $params[] = $like_search;
    $params[] = $like_search;
    $params[] = $like_search;
    $types .= "sss";
}

$sql .= " GROUP BY e.event_id ORDER BY e.event_date ASC";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$events = mysqli_stmt_get_result($stmt);

// Categories list for filter dropdown
$categories_res = mysqli_query($conn, "SELECT DISTINCT category FROM events ORDER BY category ASC");
$all_categories = [];
while ($cat_row = mysqli_fetch_assoc($categories_res)) {
    if (!empty($cat_row['category'])) {
        $all_categories[] = $cat_row['category'];
    }
}

require_once __DIR__ . "/../includes/header.php";
?>

<div class="container py-4">
    <!-- Header & Search Controls -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
        <div>
            <span class="badge bg-purple-light text-purple px-2 py-1 rounded-pill small">Campus Discovery</span>
            <h2 class="fw-bold text-navy mb-1">Explore College Events</h2>
            <p class="text-muted small mb-0">Discover and participate in upcoming campus competitions, workshops, and fests.</p>
        </div>
        <div class="mt-3 mt-md-0">
            <span class="badge bg-light text-dark border px-3 py-2 rounded-pill">
                <i class="bi bi-calendar-check text-purple me-1"></i>
                <span id="eventCountDisplay"><?php echo mysqli_num_rows($events); ?></span> Events Available
            </span>
        </div>
    </div>

    <!-- Flash message -->
    <?php display_flash_message(); ?>

    <!-- Search and Filter Bar Card -->
    <div class="card-modern p-4 mb-4">
        <form method="GET" action="events.php" class="row g-3 align-items-end">
            <!-- Search Keyword -->
            <div class="col-md-5">
                <label class="form-label">Search Events</label>
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" class="form-control border-start-0" placeholder="Search by name, venue or keyword..." value="<?php echo htmlspecialchars($search_query); ?>" data-filter-target="#eventGrid" data-filter-item=".event-filter-card">
                </div>
            </div>

            <!-- Category Filter -->
            <div class="col-md-4">
                <label class="form-label">Category</label>
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-funnel"></i></span>
                    <select name="category" class="form-select border-start-0">
                        <option value="">All Categories</option>
                        <?php foreach ($all_categories as $cat): ?>
                            <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo ($category_filter === $cat) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- Buttons -->
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-fill">
                    <i class="bi bi-filter me-1"></i> Apply Filter
                </button>
                <?php if (!empty($search_query) || !empty($category_filter)): ?>
                    <a href="events.php" class="btn btn-outline-secondary" title="Reset Filters">
                        <i class="bi bi-x-circle"></i>
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Events Grid -->
    <div class="row g-4" id="eventGrid">
        <?php if (mysqli_num_rows($events) > 0): ?>
            <?php while ($event = mysqli_fetch_assoc($events)): 
                $capacity = (int)$event['capacity'];
                $booked = (int)$event['total_booked'];
                $remaining = max(0, $capacity - $booked);
                $percentage = ($capacity > 0) ? min(100, round(($booked / $capacity) * 100)) : 0;
                $is_full = ($remaining <= 0);
                $my_status = $event['my_status'] ?? null;
            ?>
                <div class="col-md-6 col-lg-4 event-filter-card">
                    <div class="event-card-modern card-lift">
                        <div class="event-card-header">
                            <?php echo get_category_badge($event['category']); ?>
                            <span class="badge <?php echo $is_full ? 'bg-danger-subtle text-danger' : 'bg-success-subtle text-success'; ?> rounded-pill px-2 py-1 small">
                                <i class="bi <?php echo $is_full ? 'bi-x-circle' : 'bi-check2-circle'; ?> me-1"></i>
                                <?php echo $is_full ? 'Full' : "{$remaining} Seats Open"; ?>
                            </span>
                        </div>

                        <div class="event-card-body">
                            <h5 class="event-card-title"><?php echo htmlspecialchars($event['event_name']); ?></h5>
                            <p class="event-card-desc"><?php echo htmlspecialchars($event['description'] ?? 'Join your classmates and participate in this exciting campus activity.'); ?></p>
                            
                            <div class="event-meta-row">
                                <i class="bi bi-calendar3"></i>
                                <span><?php echo format_event_date($event['event_date']); ?></span>
                            </div>
                            <div class="event-meta-row">
                                <i class="bi bi-geo-alt-fill"></i>
                                <span class="text-truncate"><?php echo htmlspecialchars($event['venue']); ?></span>
                            </div>
                            <div class="event-meta-row">
                                <i class="bi bi-person-circle"></i>
                                <span class="text-truncate">Organizer: <?php echo htmlspecialchars($event['organizer_name']); ?></span>
                            </div>

                            <!-- Capacity progress -->
                            <div class="mt-3">
                                <div class="d-flex justify-content-between text-muted small">
                                    <span>Registered</span>
                                    <span class="fw-semibold"><?php echo $booked; ?> / <?php echo $capacity; ?> (<?php echo $percentage; ?>%)</span>
                                </div>
                                <div class="capacity-progress">
                                    <div class="progress-bar <?php echo ($percentage >= 90) ? 'bg-danger' : ''; ?>" style="width: <?php echo $percentage; ?>%"></div>
                                </div>
                            </div>
                        </div>

                        <div class="event-card-footer d-flex gap-2">
                            <a href="event_details.php?id=<?php echo $event['event_id']; ?>" class="btn btn-outline-secondary w-50 btn-sm rounded-pill">
                                View Details
                            </a>

                            <?php if (!is_logged_in()): ?>
                                <a href="<?php echo BASE_URL; ?>auth/login.php" class="btn btn-primary w-50 btn-sm rounded-pill <?php echo $is_full ? 'disabled' : ''; ?>">
                                    Register
                                </a>
                            <?php elseif (current_user_role() === 'student'): ?>
                                <?php if ($my_status === 'registered'): ?>
                                    <span class="btn btn-success w-50 btn-sm rounded-pill disabled" style="opacity:1;">
                                        <i class="bi bi-check-circle me-1"></i> Registered
                                    </span>
                                <?php elseif ($my_status === 'attended'): ?>
                                    <span class="btn btn-primary w-50 btn-sm rounded-pill disabled" style="opacity:1;">
                                        <i class="bi bi-award me-1"></i> Attended
                                    </span>
                                <?php elseif ($is_full): ?>
                                    <button class="btn btn-secondary w-50 btn-sm rounded-pill" disabled>
                                        Housefull
                                    </button>
                                <?php else: ?>
                                    <form method="POST" action="register_event.php" class="w-50">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="event_id" value="<?php echo $event['event_id']; ?>">
                                        <input type="hidden" name="redirect_to" value="<?php echo BASE_URL; ?>student/events.php">
                                        <button type="submit" class="btn btn-primary w-100 btn-sm rounded-pill">
                                            Register Now
                                        </button>
                                    </form>
                                <?php endif; ?>
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
                <div class="p-5 bg-white rounded-4 border shadow-sm">
                    <i class="bi bi-search fs-1 text-muted mb-3 d-block"></i>
                    <h4 class="fw-bold text-navy">No Events Match Your Search</h4>
                    <p class="text-muted">Try adjusting your keyword filter or explore all categories.</p>
                    <a href="events.php" class="btn btn-outline-primary rounded-pill mt-2">
                        Reset All Filters
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php 
mysqli_stmt_close($stmt);
require_once __DIR__ . "/../includes/footer.php"; 
?>
