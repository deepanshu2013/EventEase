<?php
require_once "config/database.php";

$accounts = [
    ['email' => 'organizer@test.com', 'password' => 'organizer123', 'expected_role' => 'organizer'],
    ['email' => 'student@test.com', 'password' => 'student123', 'expected_role' => 'student'],
];

foreach ($accounts as $acc) {
    $stmt = mysqli_prepare($conn, "SELECT user_id, full_name, role, password FROM users WHERE email = ?");
    mysqli_stmt_bind_param($stmt, "s", $acc['email']);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($res);
    mysqli_stmt_close($stmt);

    if ($user && password_verify($acc['password'], $user['password'])) {
        echo "[OK] {$user['full_name']} ({$acc['email']}) logged in as {$user['role']}\n";
    } else {
        echo "[FAIL] Could not verify {$acc['email']}\n";
    }
}
