<?php
/**
 * EventEase - College Event Registration Platform
 * Database Configuration & Connection File
 */

// Base URL definition for consistent routing across all directories
if (!defined('BASE_URL')) {
    define('BASE_URL', '/EventEase/');
}

$host = "localhost";
$username = "root";
$password = "";
$database = "eventease";
$port = 3308; // XAMPP MariaDB custom port configured on this system

// Establish connection
$conn = @mysqli_connect($host, $username, $password, $database, $port);

// Fallback to default port 3306 if 3308 fails
if (!$conn) {
    $conn = @mysqli_connect($host, $username, $password, $database, 3306);
}

if (!$conn) {
    die("<div style='font-family:sans-serif;padding:30px;background:#fee2e2;color:#991b1b;border-radius:8px;max-width:600px;margin:50px auto;'>
            <h3 style='margin-top:0;'>Database Connection Error</h3>
            <p>Could not connect to MySQL server. Please ensure MySQL is running in your XAMPP Control Panel.</p>
            <p><small>Details: " . htmlspecialchars(mysqli_connect_error()) . "</small></p>
         </div>");
}

mysqli_set_charset($conn, "utf8mb4");
