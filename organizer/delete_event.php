<?php
/**
 * EventEase - Delete College Event Handler
 */

require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../includes/auth.php";

require_login('organizer');

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: manage_events.php");
    exit();
}

$token = $_POST['csrf_token'] ?? '';
if (!verify_csrf_token($token)) {
    set_flash_message('danger', 'Security verification failed. Please try again.');
    header("Location: manage_events.php");
    exit();
}

$organizer_id = current_user_id();
$event_id = filter_input(INPUT_POST, 'event_id', FILTER_VALIDATE_INT);

if (!$event_id || $event_id <= 0) {
    set_flash_message('danger', 'Invalid event selected for deletion.');
    header("Location: manage_events.php");
    exit();
}

// Fetch event name first
$check_stmt = mysqli_prepare($conn, "SELECT event_name FROM events WHERE event_id = ? AND organizer_id = ?");
mysqli_stmt_bind_param($check_stmt, "ii", $event_id, $organizer_id);
mysqli_stmt_execute($check_stmt);
$res = mysqli_stmt_get_result($check_stmt);
$ev = mysqli_fetch_assoc($res);
mysqli_stmt_close($check_stmt);

if (!$ev) {
    set_flash_message('danger', 'Event not found or permission denied.');
    header("Location: manage_events.php");
    exit();
}

// Delete event (foreign key cascading will automatically remove dependent registrations)
$del_stmt = mysqli_prepare($conn, "DELETE FROM events WHERE event_id = ? AND organizer_id = ?");
mysqli_stmt_bind_param($del_stmt, "ii", $event_id, $organizer_id);

if (mysqli_stmt_execute($del_stmt)) {
    set_flash_message('success', "Event '{$ev['event_name']}' has been permanently removed.");
} else {
    set_flash_message('danger', 'Unable to delete event. Database error occurred.');
}

mysqli_stmt_close($del_stmt);

header("Location: manage_events.php");
exit();
