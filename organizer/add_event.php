<?php
/**
 * EventEase - Create New College Event
 */

$page_title = "Create New Event | EventEase";
require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../includes/auth.php";

require_login('organizer');

$organizer_id = current_user_id();
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

        // Validation
        if (empty($event_name) || empty($category) || empty($venue) || empty($event_date)) {
            $error = "All required fields (Event Name, Category, Venue, Date & Capacity) must be filled.";
        } elseif (strlen($event_name) > 150) {
            $error = "Event name cannot exceed 150 characters.";
        } elseif (strlen($venue) > 150) {
            $error = "Venue name cannot exceed 150 characters.";
        } elseif ($capacity <= 0) {
            $error = "Event capacity must be at least 1 participant.";
        } elseif (strtotime($event_date) === false || strtotime($event_date) <= time()) {
            $error = "Please choose a valid future date and time for the event.";
        } else {
            // Prepared statement insertion
            $query = "
                INSERT INTO events (organizer_id, event_name, description, category, venue, event_date, capacity)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param(
                $stmt,
                "isssssi",
                $organizer_id,
                $event_name,
                $description,
                $category,
                $venue,
                $event_date,
                $capacity
            );

            if (mysqli_stmt_execute($stmt)) {
                $new_event_id = mysqli_insert_id($conn);
                mysqli_stmt_close($stmt);

                set_flash_message('success', "Event '{$event_name}' has been created successfully! Students can now view and register.");
                header("Location: manage_events.php");
                exit();
            } else {
                $error = "Failed to create event due to a database error. Please try again.";
                mysqli_stmt_close($stmt);
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
                    <li class="breadcrumb-item active text-navy fw-semibold" aria-current="page">Create New</li>
                </ol>
            </nav>
            <h2 class="fw-bold text-navy mb-0">Create College Event</h2>
        </div>
        <div class="mt-3 mt-md-0">
            <a href="manage_events.php" class="btn btn-outline-secondary rounded-pill">
                <i class="bi bi-arrow-left me-1"></i> Back to Events List
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
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-type-h1"></i></span>
                        <input type="text" name="event_name" class="form-control border-start-0" placeholder="e.g. National Level CodeCraft Hackathon 2026" maxlength="150" value="<?php echo htmlspecialchars($_POST['event_name'] ?? ''); ?>" required>
                    </div>
                </div>

                <!-- Category -->
                <div class="col-md-4">
                    <label class="form-label">Category <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-tags"></i></span>
                        <select name="category" class="form-select border-start-0" required>
                            <option value="">Select Category</option>
                            <option value="Technical" <?php echo (($_POST['category'] ?? '') === 'Technical') ? 'selected' : ''; ?>>Technical</option>
                            <option value="Cultural" <?php echo (($_POST['category'] ?? '') === 'Cultural') ? 'selected' : ''; ?>>Cultural</option>
                            <option value="Sports" <?php echo (($_POST['category'] ?? '') === 'Sports') ? 'selected' : ''; ?>>Sports</option>
                            <option value="Workshop" <?php echo (($_POST['category'] ?? '') === 'Workshop') ? 'selected' : ''; ?>>Workshop</option>
                            <option value="Seminar" <?php echo (($_POST['category'] ?? '') === 'Seminar') ? 'selected' : ''; ?>>Seminar</option>
                            <option value="Academic" <?php echo (($_POST['category'] ?? '') === 'Academic') ? 'selected' : ''; ?>>Academic</option>
                        </select>
                    </div>
                </div>

                <!-- Venue Location -->
                <div class="col-md-6">
                    <label class="form-label">Venue Location <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-geo-alt"></i></span>
                        <input type="text" name="venue" class="form-control border-start-0" placeholder="e.g. Main Auditorium / Lab 3 / Campus Ground" maxlength="150" value="<?php echo htmlspecialchars($_POST['venue'] ?? ''); ?>" required>
                    </div>
                </div>

                <!-- Event Date & Time -->
                <div class="col-md-3">
                    <label class="form-label">Event Date & Time <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-calendar-event"></i></span>
                        <input type="datetime-local" name="event_date" id="event_date" class="form-control border-start-0" value="<?php echo htmlspecialchars($_POST['event_date'] ?? ''); ?>" required>
                    </div>
                </div>

                <!-- Capacity -->
                <div class="col-md-3">
                    <label class="form-label">Max Capacity <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-people"></i></span>
                        <input type="number" name="capacity" class="form-control border-start-0" placeholder="e.g. 100" min="1" max="10000" value="<?php echo htmlspecialchars($_POST['capacity'] ?? ''); ?>" required>
                    </div>
                </div>

                <!-- Description -->
                <div class="col-12">
                    <label class="form-label">Event Description & Rules</label>
                    <textarea name="description" class="form-control" rows="5" placeholder="Provide event details, objectives, schedule, prerequisites, eligibility, and prize information..."><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                    <small class="text-muted">Formatting with line breaks is preserved on the public event page.</small>
                </div>
            </div>

            <hr class="my-4">

            <div class="d-flex justify-content-end gap-2">
                <a href="manage_events.php" class="btn btn-outline-secondary rounded-pill px-4">Cancel</a>
                <button type="submit" class="btn btn-primary rounded-pill px-4 shadow-sm">
                    <i class="bi bi-check-circle me-1"></i> Publish Event
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
