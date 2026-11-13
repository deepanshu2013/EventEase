<?php
/**
 * EventEase - Student Online Event Registration Handler
 * Handles transactional, concurrency-safe event registration
 */

require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../includes/auth.php";

require_login('student');

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: " . BASE_URL . "student/events.php");
    exit();
}

$token = $_POST['csrf_token'] ?? '';
if (!verify_csrf_token($token)) {
    set_flash_message('danger', 'Invalid security token. Please try submitting again.');
    header("Location: " . BASE_URL . "student/events.php");
    exit();
}

$student_id = current_user_id();
$event_id = filter_input(INPUT_POST, 'event_id', FILTER_VALIDATE_INT);
$redirect_to = $_POST['redirect_to'] ?? BASE_URL . "student/dashboard.php";

if (!$event_id || $event_id <= 0) {
    set_flash_message('danger', 'Invalid event selected for registration.');
    header("Location: " . BASE_URL . "student/events.php");
    exit();
}

// Transaction with Row-Level Locking for Concurrency Protection
mysqli_begin_transaction($conn);

try {
    // 1. Fetch event details and lock row
    $query = "SELECT event_id, event_name, capacity, event_date FROM events WHERE event_id = ? FOR UPDATE";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $event_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $event = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if (!$event) {
        throw new Exception("The selected event does not exist.");
    }

    // 2. Check if event is in the past
    if (strtotime($event['event_date']) <= time()) {
        throw new Exception("Registration is closed because this event has already taken place or started.");
    }

    // 3. Check existing registration for this student and event
    $query = "SELECT registration_id, status FROM registrations WHERE student_id = ? AND event_id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "ii", $student_id, $event_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $existing = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if ($existing && in_array($existing['status'], ['registered', 'attended'])) {
        throw new Exception("You are already registered for '" . htmlspecialchars($event['event_name']) . "'.");
    }

    // 4. Server-side capacity validation
    $query = "SELECT COUNT(*) AS total_booked FROM registrations WHERE event_id = ? AND status IN ('registered', 'attended')";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $event_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $count_row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if ($count_row['total_booked'] >= $event['capacity']) {
        throw new Exception("Registration failed: '" . htmlspecialchars($event['event_name']) . "' has reached its maximum capacity of {$event['capacity']} participants.");
    }

    // 5. Insert new registration or reactivate a previously cancelled one
    if ($existing && $existing['status'] === 'cancelled') {
        $update_query = "UPDATE registrations SET status = 'registered', registration_date = CURRENT_TIMESTAMP WHERE registration_id = ?";
        $stmt = mysqli_prepare($conn, $update_query);
        mysqli_stmt_bind_param($stmt, "i", $existing['registration_id']);
    } else {
        $insert_query = "INSERT INTO registrations (student_id, event_id, status) VALUES (?, ?, 'registered')";
        $stmt = mysqli_prepare($conn, $insert_query);
        mysqli_stmt_bind_param($stmt, "ii", $student_id, $event_id);
    }

    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("Database error while processing your registration. Please try again.");
    }
    mysqli_stmt_close($stmt);

    // Commit transaction
    mysqli_commit($conn);
    set_flash_message('success', "Congratulations! You have successfully registered for '" . htmlspecialchars($event['event_name']) . "'.");

} catch (Exception $e) {
    mysqli_rollback($conn);
    set_flash_message('danger', $e->getMessage());
}

header("Location: " . $redirect_to);
exit();
