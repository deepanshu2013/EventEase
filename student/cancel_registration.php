<?php
/**
 * EventEase - Cancel Event Registration Handler
 */

require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../includes/auth.php";

require_login('student');

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: " . BASE_URL . "student/my_registrations.php");
    exit();
}

$token = $_POST['csrf_token'] ?? '';
if (!verify_csrf_token($token)) {
    set_flash_message('danger', 'Security validation failed. Please try again.');
    header("Location: " . BASE_URL . "student/my_registrations.php");
    exit();
}

$student_id = current_user_id();
$registration_id = filter_input(INPUT_POST, 'registration_id', FILTER_VALIDATE_INT);
$redirect_to = $_POST['redirect_to'] ?? BASE_URL . "student/my_registrations.php";

if (!$registration_id || $registration_id <= 0) {
    set_flash_message('danger', 'Invalid registration identifier.');
    header("Location: " . BASE_URL . "student/my_registrations.php");
    exit();
}

// Find registration and verify ownership
$query = "
    SELECT r.registration_id, r.status, e.event_name, e.event_date 
    FROM registrations r
    JOIN events e ON r.event_id = e.event_id
    WHERE r.registration_id = ? AND r.student_id = ?
";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "ii", $registration_id, $student_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$reg = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$reg) {
    set_flash_message('danger', 'Registration record not found or permission denied.');
    header("Location: " . BASE_URL . "student/my_registrations.php");
    exit();
}

if ($reg['status'] === 'attended') {
    set_flash_message('warning', 'You cannot cancel registration for an event you have already attended.');
    header("Location: " . BASE_URL . "student/my_registrations.php");
    exit();
}

if ($reg['status'] === 'cancelled') {
    set_flash_message('info', 'This registration is already cancelled.');
    header("Location: " . BASE_URL . "student/my_registrations.php");
    exit();
}

// Update status to cancelled
$update_query = "UPDATE registrations SET status = 'cancelled' WHERE registration_id = ?";
$stmt = mysqli_prepare($conn, $update_query);
mysqli_stmt_bind_param($stmt, "i", $registration_id);

if (mysqli_stmt_execute($stmt)) {
    set_flash_message('success', "Your registration for '" . htmlspecialchars($reg['event_name']) . "' has been cancelled successfully. Your seat has been released.");
} else {
    set_flash_message('danger', 'Unable to cancel registration. Please try again.');
}

mysqli_stmt_close($stmt);

header("Location: " . $redirect_to);
exit();
