<?php
/**
 * EventEase - Event Details & Registration Pass
 */

$page_title = "Event Details | EventEase";
require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../includes/auth.php";

$event_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$event_id || $event_id <= 0) {
    set_flash_message('danger', 'Invalid event selected.');
    header("Location: " . BASE_URL . "student/events.php");
    exit();
}

$student_id = is_logged_in() && current_user_role() === 'student' ? current_user_id() : 0;

// Fetch full event info + organizer info + capacity stats + student registration status
$query = "
    SELECT 
        e.*,
        u.full_name AS organizer_name,
        u.email AS organizer_email,
        COUNT(CASE WHEN r_all.status IN ('registered', 'attended') THEN 1 END) AS total_booked,
        r_user.registration_id,
        r_user.status AS my_status,
        r_user.registration_date AS my_registration_date
    FROM events e
    JOIN users u ON e.organizer_id = u.user_id
    LEFT JOIN registrations r_all ON e.event_id = r_all.event_id
    LEFT JOIN registrations r_user ON e.event_id = r_user.event_id AND r_user.student_id = ?
    WHERE e.event_id = ?
    GROUP BY e.event_id
";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "ii", $student_id, $event_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$event = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$event) {
    set_flash_message('danger', 'The requested event was not found.');
    header("Location: " . BASE_URL . "student/events.php");
    exit();
}

$page_title = htmlspecialchars($event['event_name']) . " | EventEase";

$capacity = (int)$event['capacity'];
$booked = (int)$event['total_booked'];
$remaining = max(0, $capacity - $booked);
$percentage = ($capacity > 0) ? min(100, round(($booked / $capacity) * 100)) : 0;
$is_full = ($remaining <= 0);
$is_past = (strtotime($event['event_date']) <= time());

require_once __DIR__ . "/../includes/header.php";
?>

<div class="container py-4">
    <!-- Breadcrumb navigation -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>index.php" class="text-decoration-none text-muted">Home</a></li>
            <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>student/events.php" class="text-decoration-none text-muted">Events</a></li>
            <li class="breadcrumb-item active text-navy fw-semibold" aria-current="page"><?php echo htmlspecialchars($event['event_name']); ?></li>
        </ol>
    </nav>

    <!-- Flash message -->
    <?php display_flash_message(); ?>

    <div class="row g-4">
        <!-- Main Event Information -->
        <div class="col-lg-8">
            <div class="card-modern p-4 mb-4">
                <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
                    <div class="d-flex align-items-center gap-2">
                        <?php echo get_category_badge($event['category']); ?>
                        <?php if ($is_past): ?>
                            <span class="badge bg-secondary rounded-pill px-3 py-1">Past Event</span>
                        <?php elseif ($is_full): ?>
                            <span class="badge bg-danger rounded-pill px-3 py-1">Housefull</span>
                        <?php else: ?>
                            <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-1">Registration Open</span>
                        <?php endif; ?>
                    </div>
                    <span class="text-muted small">
                        <i class="bi bi-clock me-1"></i> Posted on <?php echo date("d M Y", strtotime($event['created_at'])); ?>
                    </span>
                </div>

                <h2 class="fw-bold text-navy mb-3"><?php echo htmlspecialchars($event['event_name']); ?></h2>

                <div class="p-3 bg-light rounded-3 mb-4 border">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="d-flex align-items-center gap-2">
                                <div class="rounded-circle bg-purple-light text-purple p-2 d-flex align-items-center justify-content-center" style="width:40px;height:40px;">
                                    <i class="bi bi-calendar-event fs-5"></i>
                                </div>
                                <div>
                                    <small class="text-muted d-block">Date & Time</small>
                                    <strong class="text-navy"><?php echo format_event_date($event['event_date']); ?></strong>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex align-items-center gap-2">
                                <div class="rounded-circle bg-danger-subtle text-danger p-2 d-flex align-items-center justify-content-center" style="width:40px;height:40px;">
                                    <i class="bi bi-geo-alt fs-5"></i>
                                </div>
                                <div>
                                    <small class="text-muted d-block">Venue Location</small>
                                    <strong class="text-navy"><?php echo htmlspecialchars($event['venue']); ?></strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <h5 class="fw-bold text-navy mb-2">About this Event</h5>
                <div class="text-secondary mb-4" style="line-height: 1.8; white-space: pre-line;">
                    <?php echo htmlspecialchars($event['description'] ?? 'No detailed description provided for this campus event.'); ?>
                </div>

                <hr class="my-4">

                <h5 class="fw-bold text-navy mb-3">Organizer Contact Information</h5>
                <div class="d-flex align-items-center gap-3 p-3 bg-light rounded-3 border">
                    <div class="rounded-circle bg-navy text-white fw-bold d-flex align-items-center justify-content-center fs-5" style="width:48px;height:48px;">
                        <?php echo strtoupper(substr($event['organizer_name'], 0, 1)); ?>
                    </div>
                    <div>
                        <h6 class="fw-bold mb-0 text-navy"><?php echo htmlspecialchars($event['organizer_name']); ?></h6>
                        <small class="text-muted"><i class="bi bi-envelope me-1"></i><?php echo htmlspecialchars($event['organizer_email']); ?></small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Registration & Action Sidebar -->
        <div class="col-lg-4">
            <!-- Registration Status Card -->
            <div class="card-modern p-4 mb-4 sticky-top" style="top: 90px;">
                <h5 class="fw-bold text-navy mb-3">Registration Status</h5>

                <!-- Capacity Stats -->
                <div class="mb-4">
                    <div class="d-flex justify-content-between text-muted small mb-1">
                        <span>Total Capacity</span>
                        <strong class="text-navy"><?php echo $capacity; ?> Seats</strong>
                    </div>
                    <div class="d-flex justify-content-between text-muted small mb-1">
                        <span>Confirmed Registrations</span>
                        <strong class="text-purple"><?php echo $booked; ?></strong>
                    </div>
                    <div class="d-flex justify-content-between text-muted small mb-2">
                        <span>Seats Remaining</span>
                        <strong class="<?php echo $is_full ? 'text-danger' : 'text-success'; ?>"><?php echo $remaining; ?></strong>
                    </div>
                    <div class="capacity-progress">
                        <div class="progress-bar <?php echo ($percentage >= 90) ? 'bg-danger' : ''; ?>" style="width: <?php echo $percentage; ?>%"></div>
                    </div>
                </div>

                <!-- Registration Actions according to role and state -->
                <?php if (!is_logged_in()): ?>
                    <div class="alert alert-info small mb-3">
                        <i class="bi bi-info-circle me-1"></i> Please sign in to register for this event.
                    </div>
                    <a href="<?php echo BASE_URL; ?>auth/login.php" class="btn btn-primary w-100 rounded-pill py-2">
                        <i class="bi bi-box-arrow-in-right me-1"></i> Sign In to Register
                    </a>
                <?php elseif (current_user_role() === 'organizer'): ?>
                    <div class="alert alert-secondary small mb-3">
                        <i class="bi bi-person-workspace me-1"></i> You are logged in as an Event Organizer.
                    </div>
                    <a href="<?php echo BASE_URL; ?>organizer/manage_events.php" class="btn btn-navy w-100 rounded-pill py-2">
                        <i class="bi bi-kanban me-1"></i> Manage College Events
                    </a>
                <?php else: ?>
                    <!-- Student View -->
                    <?php if ($event['my_status'] === 'registered'): ?>
                        <div class="p-3 bg-success-subtle border border-success-subtle rounded-3 text-center mb-3">
                            <i class="bi bi-check-circle-fill text-success fs-2 d-block mb-1"></i>
                            <h6 class="fw-bold text-success mb-1">You Are Registered!</h6>
                            <small class="text-muted">Registered on <?php echo date("d M Y, h:i A", strtotime($event['my_registration_date'])); ?></small>
                        </div>

                        <!-- Button to open pass modal -->
                        <button type="button" class="btn btn-primary w-100 rounded-pill py-2 mb-2" data-bs-toggle="modal" data-bs-target="#passModal">
                            <i class="bi bi-qr-code me-1"></i> View Digital Pass / Ticket
                        </button>

                        <!-- Cancel registration form -->
                        <form method="POST" action="cancel_registration.php" onsubmit="return confirm('Are you sure you want to cancel your registration? Your seat will be given to another student.');">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="registration_id" value="<?php echo $event['registration_id']; ?>">
                            <input type="hidden" name="redirect_to" value="<?php echo BASE_URL; ?>student/event_details.php?id=<?php echo $event_id; ?>">
                            <button type="submit" class="btn btn-outline-danger w-100 rounded-pill btn-sm py-2">
                                <i class="bi bi-x-circle me-1"></i> Cancel My Registration
                            </button>
                        </form>

                    <?php elseif ($event['my_status'] === 'attended'): ?>
                        <div class="p-3 bg-primary-subtle border border-primary-subtle rounded-3 text-center mb-3">
                            <i class="bi bi-award-fill text-primary fs-2 d-block mb-1"></i>
                            <h6 class="fw-bold text-primary mb-1">Attendance Verified</h6>
                            <small class="text-muted">Your participation has been recorded by the organizer.</small>
                        </div>
                        <button type="button" class="btn btn-primary w-100 rounded-pill py-2" data-bs-toggle="modal" data-bs-target="#passModal">
                            <i class="bi bi-award me-1"></i> View Participation Pass
                        </button>

                    <?php elseif ($is_past): ?>
                        <button class="btn btn-secondary w-100 rounded-pill py-2" disabled>
                            Event Concluded
                        </button>
                    <?php elseif ($is_full): ?>
                        <div class="alert alert-danger small mb-3">
                            <i class="bi bi-exclamation-octagon me-1"></i> Registrations for this event are closed as all <?php echo $capacity; ?> seats have been booked.
                        </div>
                        <button class="btn btn-secondary w-100 rounded-pill py-2" disabled>
                            Housefull (Fully Booked)
                        </button>
                    <?php else: ?>
                        <!-- Ready to register -->
                        <form method="POST" action="register_event.php">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="event_id" value="<?php echo $event['event_id']; ?>">
                            <input type="hidden" name="redirect_to" value="<?php echo BASE_URL; ?>student/event_details.php?id=<?php echo $event_id; ?>">
                            <button type="submit" class="btn btn-primary w-100 rounded-pill py-2 shadow-sm">
                                <i class="bi bi-ticket-perforated me-1"></i> Register for Free
                            </button>
                        </form>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================
     Printable Digital Pass Modal
     ======================================================== -->
<?php if ($student_id && ($event['my_status'] === 'registered' || $event['my_status'] === 'attended')): ?>
<div class="modal fade" id="passModal" tabindex="-1" aria-labelledby="passModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header bg-navy text-white border-0 py-3">
                <h5 class="modal-title fw-bold" id="passModalLabel">
                    <i class="bi bi-ticket-detailed me-2 text-warning"></i> College Event Pass
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 bg-light" id="printablePassArea">
                <div class="ticket-card">
                    <div class="text-center mb-3">
                        <span class="badge bg-purple text-white px-3 py-1 rounded-pill small mb-2">OFFICIAL ENTRY PASS</span>
                        <h4 class="fw-bold text-navy mb-0"><?php echo htmlspecialchars($event['event_name']); ?></h4>
                        <small class="text-muted"><?php echo htmlspecialchars($event['category']); ?> &bull; EventEase Platform</small>
                    </div>

                    <hr class="my-3">

                    <div class="row g-2 small mb-3">
                        <div class="col-6">
                            <span class="text-muted d-block">Attendee Name</span>
                            <strong class="text-navy"><?php echo htmlspecialchars(current_user_name()); ?></strong>
                        </div>
                        <div class="col-6">
                            <span class="text-muted d-block">Pass ID</span>
                            <strong class="text-navy">#EVT-<?php echo str_pad($event['registration_id'], 5, '0', STR_PAD_LEFT); ?></strong>
                        </div>
                        <div class="col-6 mt-2">
                            <span class="text-muted d-block">Event Date</span>
                            <strong class="text-navy"><?php echo format_event_date_short($event['event_date']); ?></strong>
                        </div>
                        <div class="col-6 mt-2">
                            <span class="text-muted d-block">Entry Status</span>
                            <strong class="text-success"><?php echo strtoupper($event['my_status']); ?></strong>
                        </div>
                        <div class="col-12 mt-2">
                            <span class="text-muted d-block">Venue Location</span>
                            <strong class="text-navy"><?php echo htmlspecialchars($event['venue']); ?></strong>
                        </div>
                    </div>

                    <div class="p-3 bg-light rounded-3 text-center border">
                        <div class="font-monospace fw-bold fs-5 letter-spacing-2 text-navy mb-1">
                            * REG-<?php echo $event['registration_id']; ?>-<?php echo $event['event_id']; ?>-OK *
                        </div>
                        <small class="text-muted">Present this pass at venue entrance for attendance verification</small>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 bg-light p-3">
                <button type="button" class="btn btn-outline-secondary rounded-pill btn-sm" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary rounded-pill btn-sm" onclick="printPass('printablePassArea')">
                    <i class="bi bi-printer me-1"></i> Print / Save PDF
                </button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
