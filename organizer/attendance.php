<?php
/**
 * EventEase - Attendance Tracking Module
 * Real-time event check-ins, attendance counters, and batch actions
 */

$page_title = "Attendance Tracking | EventEase";
require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../includes/auth.php";

require_login('organizer');

$organizer_id = current_user_id();

// Fetch organizer's events for the dropdown
$events_stmt = mysqli_prepare($conn, "SELECT event_id, event_name, event_date FROM events WHERE organizer_id = ? ORDER BY event_date DESC");
mysqli_stmt_bind_param($events_stmt, "i", $organizer_id);
mysqli_stmt_execute($events_stmt);
$events_list = mysqli_stmt_get_result($events_stmt);

// Determine active event
$selected_event_id = filter_input(INPUT_GET, 'event_id', FILTER_VALIDATE_INT);
if (!$selected_event_id && mysqli_num_rows($events_list) > 0) {
    // Default to the first available event
    $first_event = mysqli_fetch_assoc($events_list);
    $selected_event_id = $first_event['event_id'];
    mysqli_data_seek($events_list, 0); // reset pointer
}

// Handle Attendance Actions (Toggle single, or mark all)
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($token)) {
        set_flash_message('danger', 'Security validation failed.');
    } else {
        $action = $_POST['action'] ?? '';

        // Action 1: Toggle single student attendance
        if ($action === 'toggle_attendance') {
            $reg_id = filter_input(INPUT_POST, 'registration_id', FILTER_VALIDATE_INT);
            $target_status = $_POST['target_status'] ?? 'attended';
            if ($reg_id && in_array($target_status, ['attended', 'registered'])) {
                // Ensure this registration belongs to the organizer's event
                $check_sql = "
                    SELECT r.registration_id, u.full_name 
                    FROM registrations r 
                    JOIN events e ON r.event_id = e.event_id 
                    JOIN users u ON r.student_id = u.user_id
                    WHERE r.registration_id = ? AND e.organizer_id = ?
                ";
                $c_stmt = mysqli_prepare($conn, $check_sql);
                mysqli_stmt_bind_param($c_stmt, "ii", $reg_id, $organizer_id);
                mysqli_stmt_execute($c_stmt);
                $reg_data = mysqli_fetch_assoc(mysqli_stmt_get_result($c_stmt));
                mysqli_stmt_close($c_stmt);

                if ($reg_data) {
                    $up_stmt = mysqli_prepare($conn, "UPDATE registrations SET status = ? WHERE registration_id = ?");
                    mysqli_stmt_bind_param($up_stmt, "si", $target_status, $reg_id);
                    mysqli_stmt_execute($up_stmt);
                    mysqli_stmt_close($up_stmt);

                    $label = ($target_status === 'attended') ? 'marked as Attended' : 'set to Registered';
                    set_flash_message('success', "{$reg_data['full_name']} has been {$label}.");
                }
            }
        }

        // Action 2: Batch mark all as attended
        elseif ($action === 'mark_all_attended' && $selected_event_id) {
            $batch_sql = "
                UPDATE registrations r
                JOIN events e ON r.event_id = e.event_id
                SET r.status = 'attended'
                WHERE r.event_id = ? AND e.organizer_id = ? AND r.status = 'registered'
            ";
            $b_stmt = mysqli_prepare($conn, $batch_sql);
            mysqli_stmt_bind_param($b_stmt, "ii", $selected_event_id, $organizer_id);
            mysqli_stmt_execute($b_stmt);
            $affected = mysqli_stmt_affected_rows($b_stmt);
            mysqli_stmt_close($b_stmt);

            set_flash_message('success', "Batch updated: {$affected} registered students marked as attended!");
        }
    }
    header("Location: attendance.php?event_id=" . $selected_event_id);
    exit();
}

// Fetch active event details & attendance statistics
$event_details = null;
$participants = null;

if ($selected_event_id) {
    // 1. Event info & metric counts
    $ev_query = "
        SELECT 
            e.*,
            COUNT(CASE WHEN r.status IN ('registered', 'attended') THEN 1 END) AS total_enrolled,
            COUNT(CASE WHEN r.status = 'attended' THEN 1 END) AS total_attended,
            COUNT(CASE WHEN r.status = 'registered' THEN 1 END) AS total_pending
        FROM events e
        LEFT JOIN registrations r ON e.event_id = r.event_id
        WHERE e.event_id = ? AND e.organizer_id = ?
        GROUP BY e.event_id
    ";
    $ev_stmt = mysqli_prepare($conn, $ev_query);
    mysqli_stmt_bind_param($ev_stmt, "ii", $selected_event_id, $organizer_id);
    mysqli_stmt_execute($ev_stmt);
    $event_details = mysqli_fetch_assoc(mysqli_stmt_get_result($ev_stmt));
    mysqli_stmt_close($ev_stmt);

    // 2. Fetch attendee list for this event
    if ($event_details) {
        $p_query = "
            SELECT 
                r.registration_id,
                r.registration_date,
                r.status,
                u.user_id,
                u.full_name AS student_name,
                u.email AS student_email
            FROM registrations r
            JOIN users u ON r.student_id = u.user_id
            WHERE r.event_id = ? AND r.status IN ('registered', 'attended')
            ORDER BY u.full_name ASC
        ";
        $p_stmt = mysqli_prepare($conn, $p_query);
        mysqli_stmt_bind_param($p_stmt, "i", $selected_event_id);
        mysqli_stmt_execute($p_stmt);
        $participants = mysqli_stmt_get_result($p_stmt);
    }
}

require_once __DIR__ . "/../includes/header.php";
?>

<div class="container py-4">
    <!-- Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
        <div>
            <span class="badge bg-purple text-white px-2 py-1 rounded-pill small">Check-in Terminal</span>
            <h2 class="fw-bold text-navy mb-1">Event Attendance Tracking</h2>
            <p class="text-muted small mb-0">Record participant turnout, toggle attendance status, and compute real-time attendance ratios.</p>
        </div>
        <div class="mt-3 mt-md-0 d-flex gap-2">
            <a href="participants.php<?php echo $selected_event_id ? '?event_id=' . $selected_event_id : ''; ?>" class="btn btn-outline-secondary rounded-pill">
                <i class="bi bi-people me-1"></i> Roster View
            </a>
            <a href="dashboard.php" class="btn btn-outline-primary rounded-pill">
                <i class="bi bi-speedometer2 me-1"></i> Dashboard
            </a>
        </div>
    </div>

    <!-- Flash message -->
    <?php display_flash_message(); ?>

    <!-- Event Selection Bar -->
    <div class="card-modern p-3 mb-4">
        <form method="GET" action="attendance.php" class="row g-2 align-items-center">
            <div class="col-md-7">
                <label class="form-label small text-muted mb-1">Select Event for Attendance</label>
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-calendar-event"></i></span>
                    <select name="event_id" class="form-select border-start-0" onchange="this.form.submit()">
                        <?php if (mysqli_num_rows($events_list) === 0): ?>
                            <option value="">No events created yet</option>
                        <?php else: ?>
                            <?php 
                            mysqli_data_seek($events_list, 0);
                            while ($ev = mysqli_fetch_assoc($events_list)): ?>
                                <option value="<?php echo $ev['event_id']; ?>" <?php echo ($selected_event_id == $ev['event_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($ev['event_name']); ?> (<?php echo date("d M Y", strtotime($ev['event_date'])); ?>)
                                </option>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </select>
                </div>
            </div>
            <div class="col-md-5 text-md-end pt-md-3">
                <?php if ($event_details && (int)$event_details['total_pending'] > 0): ?>
                    <form method="POST" action="" class="d-inline" onsubmit="return confirm('Mark ALL pending registered students as attended?');">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="action" value="mark_all_attended">
                        <button type="submit" class="btn btn-outline-success btn-sm rounded-pill">
                            <i class="bi bi-check-all me-1"></i> Mark All as Attended
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <?php if ($event_details): 
        $enrolled = (int)$event_details['total_enrolled'];
        $attended = (int)$event_details['total_attended'];
        $pending  = (int)$event_details['total_pending'];
        $turnout_rate = ($enrolled > 0) ? round(($attended / $enrolled) * 100) : 0;
    ?>
        <!-- Event Statistics Header Card -->
        <div class="card border-0 shadow-sm rounded-4 mb-4 text-white p-4" style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 60%, #312e81 100%);">
            <div class="row align-items-center g-3">
                <div class="col-lg-6">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <?php echo get_category_badge($event_details['category']); ?>
                        <span class="badge bg-white text-dark small"><i class="bi bi-geo-alt me-1"></i><?php echo htmlspecialchars($event_details['venue']); ?></span>
                    </div>
                    <h3 class="fw-bold mb-1"><?php echo htmlspecialchars($event_details['event_name']); ?></h3>
                    <small class="text-light opacity-75"><i class="bi bi-calendar3 me-1"></i><?php echo format_event_date($event_details['event_date']); ?></small>
                </div>
                <div class="col-lg-6">
                    <div class="row g-2 text-center">
                        <div class="col-4">
                            <div class="p-2 rounded-3" style="background: rgba(255, 255, 255, 0.1);">
                                <h4 class="fw-bold mb-0 text-white"><?php echo $enrolled; ?></h4>
                                <small class="text-light opacity-75">Enrolled</small>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="p-2 rounded-3" style="background: rgba(34, 197, 94, 0.2);">
                                <h4 class="fw-bold mb-0 text-success"><?php echo $attended; ?></h4>
                                <small class="text-light opacity-75">Attended</small>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="p-2 rounded-3" style="background: rgba(234, 179, 8, 0.2);">
                                <h4 class="fw-bold mb-0 text-warning"><?php echo $turnout_rate; ?>%</h4>
                                <small class="text-light opacity-75">Turnout</small>
                            </div>
                        </div>
                    </div>
                    <div class="capacity-progress mt-3">
                        <div class="progress-bar bg-success" style="width: <?php echo $turnout_rate; ?>%"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Live Search for on-campus verification -->
        <div class="card-modern p-3 mb-3">
            <div class="input-group">
                <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
                <input type="text" class="form-control border-start-0" placeholder="Type student name to quickly find attendee..." data-filter-target="#attendanceTable" data-filter-item="tbody tr">
            </div>
        </div>

        <!-- Attendee Check-In Table -->
        <div class="card-modern overflow-hidden">
            <div class="table-responsive">
                <table class="table table-modern align-middle mb-0" id="attendanceTable">
                    <thead>
                        <tr>
                            <th>Pass ID</th>
                            <th>Student Attendee</th>
                            <th>Email Address</th>
                            <th>Enrolled Date</th>
                            <th>Current Status</th>
                            <th class="text-end">Mark Attendance</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($participants && mysqli_num_rows($participants) > 0): ?>
                            <?php while ($row = mysqli_fetch_assoc($participants)): 
                                $is_attended = ($row['status'] === 'attended');
                            ?>
                                <tr>
                                    <td>
                                        <span class="badge bg-light text-navy border font-monospace">
                                            #EVT-<?php echo str_pad($row['registration_id'], 4, '0', STR_PAD_LEFT); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="rounded-circle bg-purple-light text-purple fw-bold d-flex align-items-center justify-content-center" style="width:34px;height:34px;font-size:0.8rem;">
                                                <?php echo strtoupper(substr($row['student_name'], 0, 1)); ?>
                                            </div>
                                            <span class="fw-bold text-navy"><?php echo htmlspecialchars($row['student_name']); ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <small class="text-muted"><?php echo htmlspecialchars($row['student_email']); ?></small>
                                    </td>
                                    <td>
                                        <small class="text-muted"><?php echo date("d M Y", strtotime($row['registration_date'])); ?></small>
                                    </td>
                                    <td>
                                        <?php if ($is_attended): ?>
                                            <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-1">
                                                <i class="bi bi-check-circle-fill me-1"></i> Attended
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle rounded-pill px-3 py-1">
                                                <i class="bi bi-clock me-1"></i> Registered (Pending)
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <form method="POST" action="" class="d-inline">
                                            <?php echo csrf_field(); ?>
                                            <input type="hidden" name="action" value="toggle_attendance">
                                            <input type="hidden" name="registration_id" value="<?php echo $row['registration_id']; ?>">
                                            
                                            <?php if ($is_attended): ?>
                                                <input type="hidden" name="target_status" value="registered">
                                                <button type="submit" class="btn btn-outline-warning btn-sm rounded-pill" title="Revoke Attendance">
                                                    <i class="bi bi-arrow-counterclockwise me-1"></i> Mark Absent
                                                </button>
                                            <?php else: ?>
                                                <input type="hidden" name="target_status" value="attended">
                                                <button type="submit" class="btn btn-success btn-sm rounded-pill px-3 shadow-sm">
                                                    <i class="bi bi-check2-circle me-1"></i> Check In (Present)
                                                </button>
                                            <?php endif; ?>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center py-5">
                                    <div class="py-4">
                                        <i class="bi bi-person-x fs-1 text-muted mb-2 d-block"></i>
                                        <h5 class="fw-bold text-navy">No Enrolled Students</h5>
                                        <p class="text-muted small">No students are currently registered for this event.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php else: ?>
        <div class="text-center py-5 bg-white rounded-4 border shadow-sm">
            <i class="bi bi-calendar-x fs-1 text-muted mb-2 d-block"></i>
            <h4 class="fw-bold text-navy">No Event Selected</h4>
            <p class="text-muted">Please select an event from the dropdown above to manage attendance.</p>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
