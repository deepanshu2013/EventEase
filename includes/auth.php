<?php
/**
 * EventEase - College Event Registration Platform
 * Authentication, Session & Security Helpers
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . "/../config/database.php";

/**
 * Check if a user is currently logged in
 */
function is_logged_in() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Get current user's ID
 */
function current_user_id() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Get current user's full name
 */
function current_user_name() {
    return $_SESSION['full_name'] ?? 'Guest';
}

/**
 * Get current user's email
 */
function current_user_email() {
    return $_SESSION['email'] ?? '';
}

/**
 * Get current user's role
 */
function current_user_role() {
    return $_SESSION['role'] ?? null;
}

/**
 * Enforce authentication and role permissions
 * Redirects unauthorized users appropriately
 */
function require_login($allowed_role = null) {
    if (!is_logged_in()) {
        set_flash_message('warning', 'Please log in to access this page.');
        header("Location: " . BASE_URL . "auth/login.php");
        exit();
    }

    if ($allowed_role !== null) {
        $user_role = current_user_role();
        if ($user_role !== $allowed_role) {
            set_flash_message('danger', 'Access denied. You do not have permission to view that page.');
            if ($user_role === 'organizer') {
                header("Location: " . BASE_URL . "organizer/dashboard.php");
            } else {
                header("Location: " . BASE_URL . "student/dashboard.php");
            }
            exit();
        }
    }
}

/**
 * Generate CSRF Token for form security
 */
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Render a hidden CSRF input field
 */
function csrf_field() {
    $token = generate_csrf_token();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
}

/**
 * Verify submitted CSRF token
 */
function verify_csrf_token($token) {
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Set a session flash alert message
 */
function set_flash_message($type, $message) {
    $_SESSION['flash_message'] = [
        'type' => $type, // success, danger, warning, info
        'text' => $message
    ];
}

/**
 * Display flash message if exists and clear it
 */
function display_flash_message() {
    if (isset($_SESSION['flash_message'])) {
        $type = htmlspecialchars($_SESSION['flash_message']['type']);
        $text = htmlspecialchars($_SESSION['flash_message']['text']);
        unset($_SESSION['flash_message']);

        $icon = 'bi-info-circle';
        if ($type === 'success') $icon = 'bi-check-circle-fill';
        if ($type === 'danger')  $icon = 'bi-exclamation-triangle-fill';
        if ($type === 'warning') $icon = 'bi-exclamation-circle-fill';

        echo "
        <div class='alert alert-{$type} alert-dismissible fade show d-flex align-items-center shadow-sm' role='alert'>
            <i class='bi {$icon} me-2 fs-5'></i>
            <div>{$text}</div>
            <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
        </div>";
    }
}

/**
 * Format datetime string into user-friendly format
 */
function format_event_date($datetime) {
    if (!$datetime) return 'TBA';
    $timestamp = strtotime($datetime);
    return date("D, d M Y | h:i A", $timestamp);
}

/**
 * Format date for short badge
 */
function format_event_date_short($datetime) {
    if (!$datetime) return 'TBA';
    $timestamp = strtotime($datetime);
    return date("d M Y", $timestamp);
}

/**
 * Get category badge HTML
 */
function get_category_badge($category) {
    $colors = [
        'Technical' => 'badge-cat-tech',
        'Cultural'  => 'badge-cat-cult',
        'Sports'    => 'badge-cat-sport',
        'Workshop'  => 'badge-cat-work',
        'Seminar'   => 'badge-cat-sem',
        'Academic'  => 'badge-cat-acad'
    ];
    $cls = $colors[$category] ?? 'badge-cat-gen';
    return "<span class='badge {$cls}'>" . htmlspecialchars($category) . "</span>";
}

/**
 * Get registration status badge HTML
 */
function get_status_badge($status) {
    switch (strtolower($status)) {
        case 'registered':
            return '<span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 rounded-pill"><i class="bi bi-check-circle me-1"></i> Registered</span>';
        case 'attended':
            return '<span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-2 rounded-pill"><i class="bi bi-award me-1"></i> Attended</span>';
        case 'cancelled':
            return '<span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-3 py-2 rounded-pill"><i class="bi bi-x-circle me-1"></i> Cancelled</span>';
        default:
            return '<span class="badge bg-light text-dark px-3 py-2 rounded-pill">' . htmlspecialchars(ucfirst($status)) . '</span>';
    }
}
