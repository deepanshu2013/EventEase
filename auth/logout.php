<?php
/**
 * EventEase - User Logout Script
 */

require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../includes/auth.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Clear all session variables
$_SESSION = array();

// If it's desired to kill the session cookie, also delete the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Destroy session
session_destroy();

// Start fresh session just for the flash message
session_start();
set_flash_message('info', 'You have been logged out successfully. Have a great day!');

header("Location: " . BASE_URL . "auth/login.php");
exit();
