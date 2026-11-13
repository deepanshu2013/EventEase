<?php
/**
 * EventEase - User Login Page
 */

$page_title = "Sign In | EventEase";
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

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($token)) {
        $error = "Security token mismatch. Please try submitting again.";
    } else {
        $email = trim($_POST["email"] ?? '');
        $password = $_POST["password"] ?? '';

        if (empty($email) || empty($password)) {
            $error = "Please enter both your email address and password.";
        } else {
            // Find user using email
            $stmt = mysqli_prepare(
                $conn,
                "SELECT user_id, full_name, email, password, role
                 FROM users
                 WHERE email = ?"
            );

            mysqli_stmt_bind_param($stmt, "s", $email);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);

            if ($user = mysqli_fetch_assoc($result)) {
                // Verify password hash
                if (password_verify($password, $user["password"])) {
                    // Regenerate session ID to prevent session fixation attacks
                    session_regenerate_id(true);

                    $_SESSION["user_id"] = $user["user_id"];
                    $_SESSION["full_name"] = $user["full_name"];
                    $_SESSION["email"] = $user["email"];
                    $_SESSION["role"] = $user["role"];

                    set_flash_message('success', "Welcome back, " . htmlspecialchars($user["full_name"]) . "!");

                    // Role-based redirect
                    if ($user["role"] === "organizer") {
                        header("Location: " . BASE_URL . "organizer/dashboard.php");
                    } else {
                        header("Location: " . BASE_URL . "student/dashboard.php");
                    }
                    exit();
                } else {
                    $error = "Invalid email address or password combination.";
                }
            } else {
                $error = "Invalid email address or password combination.";
            }

            mysqli_stmt_close($stmt);
        }
    }
}

require_once __DIR__ . "/../includes/header.php";
?>

<div class="auth-wrapper">
    <div class="auth-card-modern">
        <div class="auth-header">
            <div class="auth-logo">
                <i class="bi bi-shield-lock-fill"></i>
            </div>
            <h3 class="fw-bold text-navy mb-1">Welcome Back</h3>
            <p class="text-muted small">Access your EventEase account to continue</p>
        </div>

        <?php display_flash_message(); ?>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center mb-4" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
                <div class="small"><?php echo htmlspecialchars($error); ?></div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Quick Login Demo Buttons (Viva / Demo Friendly) -->
        <div class="mb-4 p-3 bg-light rounded-3 border">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="small fw-bold text-muted"><i class="bi bi-lightning-charge text-warning"></i> Demo Quick Login</span>
                <span class="badge bg-secondary-subtle text-dark">One-click</span>
            </div>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-outline-primary btn-sm flex-fill" onclick="quickLogin('student@test.com', 'student123')">
                    <i class="bi bi-mortarboard me-1"></i> Student Demo
                </button>
                <button type="button" class="btn btn-outline-secondary btn-sm flex-fill" onclick="quickLogin('organizer@test.com', 'organizer123')">
                    <i class="bi bi-person-badge me-1"></i> Organizer Demo
                </button>
            </div>
        </div>

        <form method="POST" action="">
            <?php echo csrf_field(); ?>

            <div class="mb-3">
                <label class="form-label">Email Address</label>
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-envelope"></i></span>
                    <input type="email" name="email" class="form-control border-start-0" placeholder="e.g. student@college.edu" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required autofocus>
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label d-flex justify-content-between">
                    <span>Password</span>
                </label>
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-key"></i></span>
                    <input type="password" name="password" class="form-control border-start-0" placeholder="Enter your account password" required>
                </div>
            </div>

            <button type="submit" class="btn btn-primary w-100 py-2 mb-3">
                <i class="bi bi-box-arrow-in-right me-1"></i> Sign In to Account
            </button>
        </form>

        <div class="text-center mt-3 pt-3 border-top">
            <p class="text-muted small mb-0">
                New to EventEase? <a href="register.php" class="text-purple fw-semibold text-decoration-none">Create an account</a>
            </p>
        </div>
    </div>
</div>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
