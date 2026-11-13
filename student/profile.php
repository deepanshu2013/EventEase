<?php
/**
 * EventEase - User Profile & Security Settings
 */

$page_title = "Profile Settings | EventEase";
require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../includes/auth.php";

require_login(); // Logged-in user of any role

$user_id = current_user_id();
$user_role = current_user_role();

// Fetch current user details
$stmt = mysqli_prepare($conn, "SELECT user_id, full_name, email, password, role, created_at FROM users WHERE user_id = ?");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

$profile_error = "";
$profile_success = "";
$password_error = "";
$password_success = "";

// Handle Form Submissions
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($token)) {
        set_flash_message('danger', 'Security verification failed. Please try again.');
        header("Location: profile.php");
        exit();
    }

    $action = $_POST['action'] ?? '';

    // Action 1: Update Name
    if ($action === 'update_profile') {
        $new_name = trim($_POST['full_name'] ?? '');
        if (empty($new_name) || strlen($new_name) < 3 || strlen($new_name) > 100) {
            $profile_error = "Full name must be between 3 and 100 characters.";
        } else {
            $update_stmt = mysqli_prepare($conn, "UPDATE users SET full_name = ? WHERE user_id = ?");
            mysqli_stmt_bind_param($update_stmt, "si", $new_name, $user_id);
            if (mysqli_stmt_execute($update_stmt)) {
                $_SESSION['full_name'] = $new_name;
                $user['full_name'] = $new_name;
                $profile_success = "Profile details updated successfully!";
            } else {
                $profile_error = "Unable to update profile. Please try again.";
            }
            mysqli_stmt_close($update_stmt);
        }
    }

    // Action 2: Change Password
    elseif ($action === 'change_password') {
        $current_pass = $_POST['current_password'] ?? '';
        $new_pass = $_POST['new_password'] ?? '';
        $confirm_pass = $_POST['confirm_password'] ?? '';

        if (empty($current_pass) || empty($new_pass) || empty($confirm_pass)) {
            $password_error = "All password fields are required.";
        } elseif (!password_verify($current_pass, $user['password'])) {
            $password_error = "The current password you entered is incorrect.";
        } elseif (strlen($new_pass) < 8) {
            $password_error = "New password must be at least 8 characters long.";
        } elseif ($new_pass !== $confirm_pass) {
            $password_error = "New password and confirmation password do not match.";
        } else {
            $hashed = password_hash($new_pass, PASSWORD_DEFAULT);
            $pass_stmt = mysqli_prepare($conn, "UPDATE users SET password = ? WHERE user_id = ?");
            mysqli_stmt_bind_param($pass_stmt, "si", $hashed, $user_id);
            if (mysqli_stmt_execute($pass_stmt)) {
                $user['password'] = $hashed;
                $password_success = "Password changed successfully! Keep your new password secure.";
            } else {
                $password_error = "Failed to update password. Please try again.";
            }
            mysqli_stmt_close($pass_stmt);
        }
    }
}

require_once __DIR__ . "/../includes/header.php";
?>

<div class="container py-4">
    <!-- Header -->
    <div class="mb-4">
        <span class="badge bg-purple-light text-purple px-2 py-1 rounded-pill small">Account Settings</span>
        <h2 class="fw-bold text-navy mb-1">User Profile & Security</h2>
        <p class="text-muted small mb-0">Manage your personal credentials and security preferences.</p>
    </div>

    <!-- Flash message -->
    <?php display_flash_message(); ?>

    <div class="row g-4">
        <!-- Profile Summary Card -->
        <div class="col-lg-4">
            <div class="card-modern p-4 text-center">
                <div class="rounded-circle bg-navy text-white fw-bold d-inline-flex align-items-center justify-content-center fs-2 mb-3 shadow-sm" style="width:80px;height:80px;">
                    <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
                </div>
                <h4 class="fw-bold text-navy mb-1"><?php echo htmlspecialchars($user['full_name']); ?></h4>
                <p class="text-muted small mb-2"><?php echo htmlspecialchars($user['email']); ?></p>

                <div class="mb-3">
                    <?php if ($user['role'] === 'organizer'): ?>
                        <span class="badge bg-purple text-white px-3 py-2 rounded-pill">
                            <i class="bi bi-person-workspace me-1"></i> Faculty Organizer
                        </span>
                    <?php else: ?>
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-2 rounded-pill">
                            <i class="bi bi-mortarboard me-1"></i> College Student
                        </span>
                    <?php endif; ?>
                </div>

                <hr class="my-3">

                <div class="text-start small text-muted">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Account ID:</span>
                        <strong class="text-navy">#USR-<?php echo str_pad($user['user_id'], 4, '0', STR_PAD_LEFT); ?></strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Member Since:</span>
                        <strong class="text-navy"><?php echo date("d M Y", strtotime($user['created_at'])); ?></strong>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>Status:</span>
                        <span class="text-success fw-bold"><i class="bi bi-check-circle-fill me-1"></i>Active</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit Profile & Security Forms -->
        <div class="col-lg-8">
            <!-- Form 1: General Info -->
            <div class="card-modern p-4 mb-4">
                <h5 class="fw-bold text-navy mb-3"><i class="bi bi-person me-2 text-purple"></i>Personal Information</h5>

                <?php if (!empty($profile_error)): ?>
                    <div class="alert alert-danger small py-2"><?php echo htmlspecialchars($profile_error); ?></div>
                <?php endif; ?>
                <?php if (!empty($profile_success)): ?>
                    <div class="alert alert-success small py-2"><?php echo htmlspecialchars($profile_success); ?></div>
                <?php endif; ?>

                <form method="POST" action="">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="action" value="update_profile">

                    <div class="mb-3">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="full_name" class="form-control" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Email Address</label>
                        <input type="email" class="form-control bg-light" value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
                        <small class="text-muted">Email address is permanently associated with your college identity and cannot be changed.</small>
                    </div>

                    <button type="submit" class="btn btn-primary rounded-pill px-4">
                        Save Profile Changes
                    </button>
                </form>
            </div>

            <!-- Form 2: Change Password -->
            <div class="card-modern p-4">
                <h5 class="fw-bold text-navy mb-3"><i class="bi bi-shield-lock me-2 text-purple"></i>Change Password</h5>

                <?php if (!empty($password_error)): ?>
                    <div class="alert alert-danger small py-2"><?php echo htmlspecialchars($password_error); ?></div>
                <?php endif; ?>
                <?php if (!empty($password_success)): ?>
                    <div class="alert alert-success small py-2"><?php echo htmlspecialchars($password_success); ?></div>
                <?php endif; ?>

                <form method="POST" action="">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="action" value="change_password">

                    <div class="mb-3">
                        <label class="form-label">Current Password</label>
                        <input type="password" name="current_password" class="form-control" placeholder="Enter your current password" required>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">New Password</label>
                            <input type="password" name="new_password" class="form-control" placeholder="Min 8 characters" minlength="8" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Confirm New Password</label>
                            <input type="password" name="confirm_password" class="form-control" placeholder="Confirm new password" minlength="8" required>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-navy rounded-pill px-4">
                        Update Password
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
