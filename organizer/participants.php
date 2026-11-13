<?php
/**
 * EventEase - Participant Management Module
 * View, filter, search, and export registered students
 */

$page_title = "Participant Management | EventEase";
require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../includes/auth.php";

require_login('organizer');

$organizer_id = current_user_id();

// Parameters
$selected_event_id = filter_input(INPUT_GET, 'event_id', FILTER_VALIDATE_INT);
$status_filter = trim($_GET['status'] ?? 'all');
$export_csv = isset($_GET['export']) && $_GET['export'] === 'csv';

// Handle Status Updates (if posted)
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['update_status'])) {
    $token = $_POST['csrf_token'] ?? '';
    if (verify_csrf_token($token)) {
        $reg_id = filter_input(INPUT_POST, 'registration_id', FILTER_VALIDATE_INT);
        $new_status = trim($_POST['status'] ?? '');
        if ($reg_id && in_array($new_status, ['registered', 'attended', 'cancelled'])) {
            // Check ownership
            $check_sql = "
                SELECT r.registration_id 
                FROM registrations r 
                JOIN events e ON r.event_id = e.event_id 
                WHERE r.registration_id = ? AND e.organizer_id = ?
            ";
            $c_stmt = mysqli_prepare($conn, $check_sql);
            mysqli_stmt_bind_param($c_stmt, "ii", $reg_id, $organizer_id);
            mysqli_stmt_execute($c_stmt);
            if (mysqli_stmt_fetch($c_stmt)) {
                mysqli_stmt_close($c_stmt);
                $up_stmt = mysqli_prepare($conn, "UPDATE registrations SET status = ? WHERE registration_id = ?");
                mysqli_stmt_bind_param($up_stmt, "si", $new_status, $reg_id);
                mysqli_stmt_execute($up_stmt);
                mysqli_stmt_close($up_stmt);
                set_flash_message('success', "Participant status updated to " . ucfirst($new_status) . ".");
            } else {
                mysqli_stmt_close($c_stmt);
            }
        }
    }
    $redir_url = "participants.php?" . http_build_query([
        'event_id' => $selected_event_id ?: '',
        'status' => $status_filter
    ]);
    header("Location: " . $redir_url);
    exit();
}

// Fetch Organizer's Events for dropdown
$events_list_query = "SELECT event_id, event_name FROM events WHERE organizer_id = ? ORDER BY event_date DESC";
$el_stmt = mysqli_prepare($conn, $events_list_query);
mysqli_stmt_bind_param($el_stmt, "i", $organizer_id);
mysqli_stmt_execute($el_stmt);
$events_list = mysqli_stmt_get_result($el_stmt);

// Build Participant Query
$sql = "
    SELECT 
        r.registration_id,
        r.registration_date,
        r.status,
        u.user_id,
        u.full_name AS student_name,
        u.email AS student_email,
        e.event_id,
        e.event_name,
        e.category,
        e.venue,
        e.event_date
    FROM registrations r
    JOIN users u ON r.student_id = u.user_id
    JOIN events e ON r.event_id = e.event_id
    WHERE e.organizer_id = ?
";

$types = "i";
$params = [$organizer_id];

if ($selected_event_id && $selected_event_id > 0) {
    $sql .= " AND e.event_id = ?";
    $types .= "i";
    $params[] = $selected_event_id;
}

if ($status_filter !== 'all' && in_array($status_filter, ['registered', 'attended', 'cancelled'])) {
    $sql .= " AND r.status = ?";
    $types .= "s";
    $params[] = $status_filter;
}

$sql .= " ORDER BY r.registration_date DESC";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$participants = mysqli_stmt_get_result($stmt);

// Handle CSV Export if requested
if ($export_csv) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=eventease_participants_' . date('Ymd_His') . '.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Reg ID', 'Student Name', 'Student Email', 'Event Name', 'Category', 'Event Date', 'Venue', 'Registration Date', 'Status']);

    while ($row = mysqli_fetch_assoc($participants)) {
        fputcsv($output, [
            '#EVT-' . str_pad($row['registration_id'], 5, '0', STR_PAD_LEFT),
            $row['student_name'],
            $row['student_email'],
            $row['event_name'],
            $row['category'],
            $row['event_date'],
            $row['venue'],
            $row['registration_date'],
            ucfirst($row['status'])
        ]);
    }
    fclose($output);
    exit();
}

require_once __DIR__ . "/../includes/header.php";
?>

<div class="container py-4">
    <!-- Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
        <div>
            <span class="badge bg-purple text-white px-2 py-1 rounded-pill small">Student Roster</span>
            <h2 class="fw-bold text-navy mb-1">Participant Management</h2>
            <p class="text-muted small mb-0">View student signups, update enrollment status, and download attendance rosters.</p>
        </div>
        <div class="mt-3 mt-md-0 d-flex gap-2">
            <!-- CSV Export Button -->
            <a href="participants.php?<?php echo http_build_query(array_merge($_GET, ['export' => 'csv'])); ?>" class="btn btn-outline-success rounded-pill">
                <i class="bi bi-file-earmark-spreadsheet me-1"></i> Export to CSV
            </a>
            <a href="attendance.php<?php echo $selected_event_id ? '?event_id=' . $selected_event_id : ''; ?>" class="btn btn-navy rounded-pill">
                <i class="bi bi-check2-square me-1"></i> Attendance Mode
            </a>
        </div>
    </div>

    <!-- Flash message -->
    <?php display_flash_message(); ?>

    <!-- Filter and Search Bar -->
    <div class="card-modern p-4 mb-4">
        <form method="GET" action="participants.php" class="row g-3 align-items-end">
            <!-- Event Dropdown -->
            <div class="col-md-5">
                <label class="form-label">Filter by Event</label>
                <select name="event_id" class="form-select" onchange="this.form.submit()">
                    <option value="">All My Events</option>
                    <?php 
                    mysqli_data_seek($events_list, 0);
                    while ($ev = mysqli_fetch_assoc($events_list)): ?>
                        <option value="<?php echo $ev['event_id']; ?>" <?php echo ($selected_event_id == $ev['event_id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($ev['event_name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <!-- Status Dropdown -->
            <div class="col-md-3">
                <label class="form-label">Registration Status</label>
                <select name="status" class="form-select" onchange="this.form.submit()">
                    <option value="all" <?php echo ($status_filter === 'all') ? 'selected' : ''; ?>>All Statuses</option>
                    <option value="registered" <?php echo ($status_filter === 'registered') ? 'selected' : ''; ?>>Registered</option>
                    <option value="attended" <?php echo ($status_filter === 'attended') ? 'selected' : ''; ?>>Attended</option>
                    <option value="cancelled" <?php echo ($status_filter === 'cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                </select>
            </div>

            <!-- Instant Text Search Input -->
            <div class="col-md-4">
                <label class="form-label">Live Search Roster</label>
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
                    <input type="text" class="form-control border-start-0" placeholder="Type student name or email..." data-filter-target="#participantsTable" data-filter-item="tbody tr">
                </div>
            </div>
        </form>
    </div>

    <!-- Participants Table -->
    <div class="card-modern overflow-hidden">
        <div class="table-responsive">
            <table class="table table-modern align-middle mb-0" id="participantsTable">
                <thead>
                    <tr>
                        <th>Pass ID</th>
                        <th>Student Attendee</th>
                        <th>Event Name</th>
                        <th>Registered On</th>
                        <th>Status</th>
                        <th class="text-end">Manage Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($participants) > 0): ?>
                        <?php while ($p = mysqli_fetch_assoc($participants)): ?>
                            <tr>
                                <td>
                                    <span class="badge bg-light text-navy border font-monospace">
                                        #EVT-<?php echo str_pad($p['registration_id'], 4, '0', STR_PAD_LEFT); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="rounded-circle bg-purple-light text-purple fw-bold d-flex align-items-center justify-content-center" style="width:34px;height:34px;font-size:0.8rem;">
                                            <?php echo strtoupper(substr($p['student_name'], 0, 1)); ?>
                                        </div>
                                        <div>
                                            <div class="fw-bold text-navy"><?php echo htmlspecialchars($p['student_name']); ?></div>
                                            <small class="text-muted"><i class="bi bi-envelope me-1"></i><?php echo htmlspecialchars($p['student_email']); ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="fw-semibold text-navy d-block text-truncate" style="max-width: 200px;">
                                        <?php echo htmlspecialchars($p['event_name']); ?>
                                    </span>
                                    <small class="text-muted"><?php echo get_category_badge($p['category']); ?></small>
                                </td>
                                <td>
                                    <div class="small fw-semibold text-navy"><?php echo date("d M Y", strtotime($p['registration_date'])); ?></div>
                                    <small class="text-muted"><?php echo date("h:i A", strtotime($p['registration_date'])); ?></small>
                                </td>
                                <td>
                                    <?php echo get_status_badge($p['status']); ?>
                                </td>
                                <td class="text-end">
                                    <!-- Status Update Dropdown Form -->
                                    <form method="POST" action="" class="d-inline-flex gap-1 align-items-center justify-content-end">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="update_status" value="1">
                                        <input type="hidden" name="registration_id" value="<?php echo $p['registration_id']; ?>">
                                        <select name="status" class="form-select form-select-sm py-1" style="width: auto; font-size: 0.8rem;" onchange="this.form.submit()">
                                            <option value="registered" <?php echo ($p['status'] === 'registered') ? 'selected' : ''; ?>>Registered</option>
                                            <option value="attended" <?php echo ($p['status'] === 'attended') ? 'selected' : ''; ?>>Attended</option>
                                            <option value="cancelled" <?php echo ($p['status'] === 'cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                                        </select>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center py-5">
                                <div class="py-4">
                                    <i class="bi bi-people fs-1 text-muted mb-2 d-block"></i>
                                    <h5 class="fw-bold text-navy">No Participants Found</h5>
                                    <p class="text-muted small">No student registrations match the selected event or status filter.</p>
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
