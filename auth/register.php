<?php
/**
 * EventEase - User Registration Page
 */

$page_title = "Register | EventEase";
require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../includes/auth.php";

// If already logged in, redirect directly to dashboard
if (is_logged_in()) {
    if (current_user_role() === 'organizer') {
        header("Location: " . BASE_URL . "organizer/dashboard.php");
    } else {
        header("Location: " . BASE_URL . "student/dashboard.php");
    }
    exit();
}

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($token)) {
        $error = "Security token mismatch. Please try submitting again.";
    } else {
        $full_name = trim($_POST["full_name"] ?? '');
        $email = trim($_POST["email"] ?? '');
        $password = $_POST["password"] ?? '';
        $confirm_password = $_POST["confirm_password"] ?? '';
        $role = trim($_POST["role"] ?? 'student');

        // Whitelist allowed roles
        if (!in_array($role, ['student', 'organizer'])) {
            $role = 'student';
        }

        // Validate empty fields
        if (empty($full_name) || empty($email) || empty($password) || empty($confirm_password)) {
            $error = "All fields are required. Please fill out the complete registration form.";
        }
        // Validate full name length
        elseif (strlen($full_name) < 3 || strlen($full_name) > 100) {
            $error = "Full name must be between 3 and 100 characters.";
        }
        // Validate email format
        elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Please provide a valid college or personal email address.";
        }
        // Validate password minimum length
        elseif (strlen($password) < 8) {
            $error = "Password must be at least 8 characters long for security.";
        }
        // Verify password match
        elseif ($password !== $confirm_password) {
            $error = "Passwords do not match. Please re-enter matching passwords.";
        } else {
            // Check whether email already exists
            $check = mysqli_prepare($conn, "SELECT user_id FROM users WHERE email = ?");
            mysqli_stmt_bind_param($check, "s", $email);
            mysqli_stmt_execute($check);
            mysqli_stmt_store_result($check);

            if (mysqli_stmt_num_rows($check) > 0) {
                $error = "An account with this email address already exists. Please sign in.";
            } else {
                // Secure password hashing
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                $query = mysqli_prepare(
                    $conn,
                    "INSERT INTO users (full_name, email, password, role) VALUES (?, ?, ?, ?)"
                );
                mysqli_stmt_bind_param($query, "ssss", $full_name, $email, $hashed_password, $role);

                if (mysqli_stmt_execute($query)) {
                    $new_user_id = mysqli_insert_id($conn);
                    mysqli_stmt_close($query);
                    mysqli_stmt_close($check);

                    // Automatically start session for immediate access
                    session_regenerate_id(true);
                    $_SESSION["user_id"] = $new_user_id;
                    $_SESSION["full_name"] = $full_name;
                    $_SESSION["email"] = $email;
                    $_SESSION["role"] = $role;

                    set_flash_message('success', "Welcome to EventEase, {$full_name}! Your account has been registered successfully.");

                    if ($role === 'organizer') {
                        header("Location: " . BASE_URL . "organizer/dashboard.php");
                    } else {
                        header("Location: " . BASE_URL . "student/dashboard.php");
                    }
                    exit();
                } else {
                    $error = "Registration failed due to a server error. Please try again.";
                }
                mysqli_stmt_close($query);
            }
            mysqli_stmt_close($check);
        }
    }
}

require_once __DIR__ . "/../includes/header.php";
?>

<div class="auth-wrapper">
    <div class="auth-card-modern" style="max-width: 520px;">
        <div class="auth-header">
            <div class="auth-logo">
                <i class="bi bi-person-plus-fill"></i>
            </div>
            <h3 class="fw-bold text-navy mb-1">Create an Account</h3>
            <p class="text-muted small">Join EventEase to discover and organize college events</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center mb-4" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
                <div class="small"><?php echo htmlspecialchars($error); ?></div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <form method="POST" action="" id="registerForm">
            <?php echo csrf_field(); ?>

            <!-- Role Selector -->
            <label class="form-label d-block">Select Your Role</label>
            <div class="auth-role-selector">
                <div class="auth-role-option">
                    <input type="radio" name="role" id="role_student" value="student" <?php echo (($_POST['role'] ?? 'student') === 'student') ? 'checked' : ''; ?>>
                    <label for="role_student">
                        <i class="bi bi-mortarboard me-1"></i> Student
                    </label>
                </div>
                <div class="auth-role-option">
                    <input type="radio" name="role" id="role_organizer" value="organizer" <?php echo (($_POST['role'] ?? '') === 'organizer') ? 'checked' : ''; ?>>
                    <label for="role_organizer">
                        <i class="bi bi-person-workspace me-1"></i> Organizer
                    </label>
                </div>
            </div>

            <!-- Full Name -->
            <div class="mb-3">
                <label class="form-label">Full Name</label>
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-person"></i></span>
                    <input type="text" name="full_name" class="form-control border-start-0" placeholder="e.g. Rahul Sharma" value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>" required>
                </div>
            </div>

            <!-- Email Address -->
            <div class="mb-3">
                <label class="form-label">Email Address</label>
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-envelope"></i></span>
                    <input type="email" name="email" class="form-control border-start-0" placeholder="e.g. rahul@college.edu" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                </div>
            </div>

            <!-- Password -->
            <div class="row g-2 mb-3">
                <div class="col-sm-6">
                    <label class="form-label">Password</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-lock"></i></span>
                        <input type="password" name="password" id="reg_password" class="form-control border-start-0" placeholder="Min 8 characters" minlength="8" required>
                    </div>
                </div>
                <div class="col-sm-6">
                    <label class="form-label">Confirm Password</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-shield-check"></i></span>
                        <input type="password" name="confirm_password" id="reg_confirm" class="form-control border-start-0" placeholder="Repeat password" minlength="8" required>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary w-100 py-2 mb-3">
                <i class="bi bi-check-circle me-1"></i> Register Free Account
            </button>
        </form>

        <div class="text-center mt-3 pt-3 border-top">
            <p class="text-muted small mb-0">
                Already registered? <a href="login.php" class="text-purple fw-semibold text-decoration-none">Sign in here</a>
            </p>
        </div>
    </div>
</div>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
