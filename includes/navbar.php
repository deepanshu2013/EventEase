<?php
/**
 * EventEase - Common Navigation Bar Component
 */
if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../config/database.php';
}
if (!function_exists('is_logged_in')) {
    require_once __DIR__ . '/auth.php';
}

$current_script = basename($_SERVER['PHP_SELF']);
$logged_in = is_logged_in();
$user_role = current_user_role();
$user_name = current_user_name();
$user_email = current_user_email();
?>
<nav class="navbar navbar-expand-lg navbar-custom sticky-top">
    <div class="container">
        <!-- Brand Logo -->
        <a class="navbar-brand" href="<?php echo BASE_URL; ?>index.php">
            <span class="d-inline-flex align-items-center justify-content-center bg-white text-navy rounded-circle p-1" style="width:34px;height:34px;">
                <i class="bi bi-calendar2-event-fill text-purple fs-6"></i>
            </span>
            <span>EventEase</span>
            <?php if ($logged_in): ?>
                <span class="brand-badge ms-1"><?php echo ucfirst($user_role); ?></span>
            <?php endif; ?>
        </a>

        <!-- Mobile Toggle Button -->
        <button class="navbar-toggler border-0 text-white" type="button" data-bs-toggle="collapse" data-bs-target="#eventEaseNavbar" aria-controls="eventEaseNavbar" aria-expanded="false" aria-label="Toggle navigation">
            <i class="bi bi-list fs-2 text-white"></i>
        </button>

        <!-- Navbar Menu -->
        <div class="collapse navbar-collapse" id="eventEaseNavbar">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0 ms-lg-3">
                <li class="nav-item">
                    <a class="nav-link <?php echo ($current_script === 'index.php') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>index.php">
                        <i class="bi bi-house me-1"></i> Home
                    </a>
                </li>

                <?php if ($logged_in && $user_role === 'student'): ?>
                    <!-- Student Specific Links -->
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($current_script === 'dashboard.php') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>student/dashboard.php">
                            <i class="bi bi-speedometer2 me-1"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($current_script === 'events.php' || $current_script === 'event_details.php') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>student/events.php">
                            <i class="bi bi-compass me-1"></i> Browse Events
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($current_script === 'my_registrations.php') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>student/my_registrations.php">
                            <i class="bi bi-ticket-perforated me-1"></i> My Registrations
                        </a>
                    </li>

                <?php elseif ($logged_in && $user_role === 'organizer'): ?>
                    <!-- Organizer Specific Links -->
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($current_script === 'dashboard.php') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>organizer/dashboard.php">
                            <i class="bi bi-speedometer2 me-1"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($current_script === 'add_event.php') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>organizer/add_event.php">
                            <i class="bi bi-plus-circle me-1"></i> Create Event
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($current_script === 'manage_events.php') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>organizer/manage_events.php">
                            <i class="bi bi-kanban me-1"></i> Manage Events
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($current_script === 'participants.php') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>organizer/participants.php">
                            <i class="bi bi-people me-1"></i> Participants
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($current_script === 'attendance.php') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>organizer/attendance.php">
                            <i class="bi bi-check2-square me-1"></i> Attendance
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($current_script === 'reports.php') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>organizer/reports.php">
                            <i class="bi bi-bar-chart-line me-1"></i> Reports
                        </a>
                    </li>

                <?php else: ?>
                    <!-- Guest Links -->
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo BASE_URL; ?>index.php#events">
                            <i class="bi bi-calendar-week me-1"></i> Upcoming Events
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo BASE_URL; ?>index.php#categories">
                            <i class="bi bi-grid me-1"></i> Categories
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo BASE_URL; ?>index.php#why-us">
                            <i class="bi bi-stars me-1"></i> Why EventEase
                        </a>
                    </li>
                <?php endif; ?>
            </ul>

            <!-- Right-hand User Controls -->
            <div class="d-flex align-items-center gap-2 mt-3 mt-lg-0">
                <?php if ($logged_in): ?>
                    <div class="dropdown">
                        <button class="btn btn-navy text-white dropdown-toggle d-flex align-items-center gap-2 px-3 py-2 border border-secondary" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <div class="rounded-circle bg-purple-light text-purple fw-bold d-inline-flex align-items-center justify-content-center" style="width:28px;height:28px;font-size:0.8rem;">
                                <?php echo strtoupper(substr($user_name, 0, 1)); ?>
                            </div>
                            <span class="d-none d-sm-inline fw-semibold"><?php echo htmlspecialchars($user_name); ?></span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 py-2">
                            <li class="px-3 py-1">
                                <small class="text-muted d-block">Signed in as</small>
                                <span class="fw-bold text-dark d-block text-truncate" style="max-width:190px;"><?php echo htmlspecialchars($user_email); ?></span>
                            </li>
                            <li><hr class="dropdown-divider my-2"></li>
                            <li>
                                <a class="dropdown-item" href="<?php echo ($user_role === 'organizer') ? BASE_URL . 'organizer/dashboard.php' : BASE_URL . 'student/dashboard.php'; ?>">
                                    <i class="bi bi-speedometer2 me-2 text-muted"></i> Dashboard
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="<?php echo ($user_role === 'organizer') ? BASE_URL . 'student/profile.php' : BASE_URL . 'student/profile.php'; ?>">
                                    <i class="bi bi-person-gear me-2 text-muted"></i> Profile Settings
                                </a>
                            </li>
                            <li><hr class="dropdown-divider my-2"></li>
                            <li>
                                <a class="dropdown-item text-danger fw-semibold" href="<?php echo BASE_URL; ?>auth/logout.php">
                                    <i class="bi bi-box-arrow-right me-2"></i> Sign Out
                                </a>
                            </li>
                        </ul>
                    </div>
                <?php else: ?>
                    <a href="<?php echo BASE_URL; ?>auth/login.php" class="btn btn-outline-light px-3 py-2">
                        <i class="bi bi-box-arrow-in-right me-1"></i> Sign In
                    </a>
                    <a href="<?php echo BASE_URL; ?>auth/register.php" class="btn btn-primary px-3 py-2">
                        <i class="bi bi-person-plus me-1"></i> Register Free
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>
