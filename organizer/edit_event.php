<?php
/**
 * EventEase - Edit Existing Event
 */

$page_title = "Edit Event | EventEase";
require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../includes/auth.php";

require_login('organizer');

$organizer_id = current_user_id();
$event_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$event_id || $event_id <= 0) {
    set_flash_message('danger', 'Invalid event selected for editing.');
    header("Location: manage_events.php");
    exit();
}

// Fetch event and verify ownership
$query = "
    SELECT 
        e.*,
        COUNT(CASE WHEN r.status IN ('registered', 'attended') THEN 1 END) AS enrolled_count
    FROM events e
    LEFT JOIN registrations r ON e.event_id = r.event_id
    WHERE e.event_id = ? AND e.organizer_id = ?
    GROUP BY e.event_id
";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "ii", $event_id, $organizer_id);
mysqli_stmt_execute($stmt);
$event = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$event) {
    set_flash_message('danger', 'Event not found or you do not have permission to modify it.');
    header("Location: manage_events.php");
    exit();
}

$enrolled_count = (int)$event['enrolled_count'];
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($token)) {
        $error = "Security token validation failed. Please try submitting again.";
    } else {
        $event_name  = trim($_POST['event_name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $category    = trim($_POST['category'] ?? '');
        $venue       = trim($_POST['venue'] ?? '');
        $event_date  = trim($_POST['event_date'] ?? '');
        $capacity    = (int)($_POST['capacity'] ?? 0);

        if (empty($event_name) || empty($category) || empty($venue) || empty($event_date)) {
            $error = "All required fields must be filled.";
        } elseif (strlen($event_name) > 150) {
            $error = "Event name cannot exceed 150 characters.";
        } elseif (strlen($venue) > 150) {
            $error = "Venue name cannot exceed 150 characters.";
        } elseif ($capacity <= 0) {
            $error = "Event capacity must be at least 1 participant.";
        } elseif ($capacity < $enrolled_count) {
            $error = "Cannot set capacity to {$capacity}. There are already {$enrolled_count} active registered students.";
        } elseif (strtotime($event_date) === false) {
            $error = "Please provide a valid event date and time.";
        } else {
            $update_sql = "
                UPDATE events 
                SET event_name = ?, description = ?, category = ?, venue = ?, event_date = ?, capacity = ?
                WHERE event_id = ? AND organizer_id = ?
            ";
            $update_stmt = mysqli_prepare($conn, $update_sql);
            mysqli_stmt_bind_param(
                $update_stmt,
                "sssssiii",
                $event_name,
                $description,
                $category,
                $venue,
                $event_date,
                $capacity,
                $event_id,
                $organizer_id
            );

            if (mysqli_stmt_execute($update_stmt)) {
                mysqli_stmt_close($update_stmt);
                set_flash_message('success', "Event '{$event_name}' was updated successfully!");
                header("Location: manage_events.php");
                exit();
            } else {
                $error = "Failed to update event. Database error occurred.";
                mysqli_stmt_close($update_stmt);
            }
        }
    }
}

require_once __DIR__ . "/../includes/header.php";
?>

<div class="container py-4">
    <!-- Breadcrumb & Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="dashboard.php" class="text-decoration-none text-muted">Organizer</a></li>
                    <li class="breadcrumb-item"><a href="manage_events.php" class="text-decoration-none text-muted">Events</a></li>
                    <li class="breadcrumb-item active text-navy fw-semibold" aria-current="page">Edit Event #<?php echo $event['event_id']; ?></li>
                </ol>
            </nav>
            <h2 class="fw-bold text-navy mb-0">Edit Event Details</h2>
        </div>
        <div class="mt-3 mt-md-0 d-flex gap-2">
            <a href="participants.php?event_id=<?php echo $event['event_id']; ?>" class="btn btn-outline-primary rounded-pill">
                <i class="bi bi-people me-1"></i> View Registered Students (<?php echo $enrolled_count; ?>)
            </a>
            <a href="manage_events.php" class="btn btn-outline-secondary rounded-pill">
                <i class="bi bi-arrow-left me-1"></i> Back to List
            </a>
        </div>
    </div>

    <!-- Flash message -->
    <?php display_flash_message(); ?>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center mb-4" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
            <div><?php echo htmlspecialchars($error); ?></div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card-modern p-4 p-md-5">
        <form method="POST" action="">
            <?php echo csrf_field(); ?>

            <div class="row g-4">
                <!-- Event Name -->
                <div class="col-md-8">
                    <label class="form-label">Event Name <span class="text-danger">*</span></label>
                    <input type="text" name="event_name" class="form-control" maxlength="150" value="<?php echo htmlspecialchars($_POST['event_name'] ?? $event['event_name']); ?>" required>
                </div>

                <!-- Category -->
                <div class="col-md-4">
                    <label class="form-label">Category <span class="text-danger">*</span></label>
                    <?php $current_cat = $_POST['category'] ?? $event['category']; ?>
                    <select name="category" class="form-select" required>
                        <option value="Technical" <?php echo ($current_cat === 'Technical') ? 'selected' : ''; ?>>Technical</option>
                        <option value="Cultural" <?php echo ($current_cat === 'Cultural') ? 'selected' : ''; ?>>Cultural</option>
                        <option value="Sports" <?php echo ($current_cat === 'Sports') ? 'selected' : ''; ?>>Sports</option>
                        <option value="Workshop" <?php echo ($current_cat === 'Workshop') ? 'selected' : ''; ?>>Workshop</option>
                        <option value="Seminar" <?php echo ($current_cat === 'Seminar') ? 'selected' : ''; ?>>Seminar</option>
                        <option value="Academic" <?php echo ($current_cat === 'Academic') ? 'selected' : ''; ?>>Academic</option>
                    </select>
                </div>

                <!-- Venue Location -->
                <div class="col-md-6">
                    <label class="form-label">Venue Location <span class="text-danger">*</span></label>
                    <input type="text" name="venue" class="form-control" maxlength="150" value="<?php echo htmlspecialchars($_POST['venue'] ?? $event['venue']); ?>" required>
                </div>

                <!-- Event Date & Time -->
                <div class="col-md-3">
                    <label class="form-label">Event Date & Time <span class="text-danger">*</span></label>
                    <?php 
                        $dt_val = $_POST['event_date'] ?? date('Y-m-d\TH:i', strtotime($event['event_date']));
                    ?>
                    <input type="datetime-local" name="event_date" class="form-control" value="<?php echo htmlspecialchars($dt_val); ?>" required>
                </div>

                <!-- Capacity -->
                <div class="col-md-3">
                    <label class="form-label">Max Capacity <span class="text-danger">*</span></label>
                    <input type="number" name="capacity" class="form-control" min="<?php echo max(1, $enrolled_count); ?>" max="10000" value="<?php echo htmlspecialchars($_POST['capacity'] ?? $event['capacity']); ?>" required>
                    <small class="text-muted">Minimum capacity: <?php echo max(1, $enrolled_count); ?> (enrolled)</small>
                </div>

                <!-- Description -->
                <div class="col-12">
                    <label class="form-label">Event Description & Rules</label>
                    <textarea name="description" class="form-control" rows="5"><?php echo htmlspecialchars($_POST['description'] ?? $event['description']); ?></textarea>
                </div>
            </div>

            <hr class="my-4">

            <div class="d-flex justify-content-end gap-2">
                <a href="manage_events.php" class="btn btn-outline-secondary rounded-pill px-4">Cancel</a>
                <button type="submit" class="btn btn-primary rounded-pill px-4 shadow-sm">
                    <i class="bi bi-save me-1"></i> Update Event
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
