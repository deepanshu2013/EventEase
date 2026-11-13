<?php
/**
 * EventEase - Common Footer Component
 */
if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../config/database.php';
}
?>
</main>

<footer class="footer-custom">
    <div class="container">
        <div class="row g-4 mb-4">
            <!-- Brand & Info -->
            <div class="col-lg-4 col-md-6">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <span class="d-inline-flex align-items-center justify-content-center bg-white text-navy rounded-circle p-1" style="width:36px;height:36px;">
                        <i class="bi bi-calendar2-event-fill text-purple fs-5"></i>
                    </span>
                    <h4 class="text-white fw-bold mb-0">EventEase</h4>
                </div>
                <p class="text-secondary mb-3 pe-lg-3">
                    A comprehensive college event organization and registration platform. Built to empower students to discover and register for campus activities effortlessly while providing organizers robust management tools.
                </p>
                <div class="d-flex gap-2">
                    <span class="badge bg-secondary-subtle text-light border border-secondary px-3 py-2">
                        <i class="bi bi-mortarboard me-1"></i> MCA Minor Project
                    </span>
                    <span class="badge bg-secondary-subtle text-light border border-secondary px-3 py-2">
                        <i class="bi bi-shield-check me-1"></i> Secure Core PHP
                    </span>
                </div>
            </div>

            <!-- Quick Links -->
            <div class="col-lg-2 col-md-6 col-6">
                <h5>Platform</h5>
                <ul class="list-unstyled">
                    <li><a href="<?php echo BASE_URL; ?>index.php">Home</a></li>
                    <li><a href="<?php echo BASE_URL; ?>index.php#events">Upcoming Events</a></li>
                    <li><a href="<?php echo BASE_URL; ?>index.php#categories">Categories</a></li>
                    <li><a href="<?php echo BASE_URL; ?>index.php#why-us">Key Features</a></li>
                    <li><a href="<?php echo BASE_URL; ?>auth/login.php">User Login</a></li>
                </ul>
            </div>

            <!-- Event Categories -->
            <div class="col-lg-3 col-md-6 col-6">
                <h5>Categories</h5>
                <ul class="list-unstyled">
                    <li><a href="<?php echo BASE_URL; ?>student/events.php?category=Technical"><i class="bi bi-code-slash me-1"></i> Technical & Coding</a></li>
                    <li><a href="<?php echo BASE_URL; ?>student/events.php?category=Cultural"><i class="bi bi-palette me-1"></i> Cultural & Arts</a></li>
                    <li><a href="<?php echo BASE_URL; ?>student/events.php?category=Sports"><i class="bi bi-trophy me-1"></i> Sports & Athletics</a></li>
                    <li><a href="<?php echo BASE_URL; ?>student/events.php?category=Workshop"><i class="bi bi-tools me-1"></i> Skill Workshops</a></li>
                    <li><a href="<?php echo BASE_URL; ?>student/events.php?category=Seminar"><i class="bi bi-mic me-1"></i> Seminars & Talks</a></li>
                </ul>
            </div>

            <!-- System Info & Technology -->
            <div class="col-lg-3 col-md-6">
                <h5>Tech Stack</h5>
                <p class="text-secondary small mb-2">Designed strictly with Core PHP, MySQLi, Bootstrap 5, and modern JavaScript.</p>
                <div class="p-3 rounded-3" style="background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.08);">
                    <div class="d-flex align-items-center justify-content-between text-secondary small mb-1">
                        <span>Architecture:</span>
                        <strong class="text-white">Modular MVC/Procedural</strong>
                    </div>
                    <div class="d-flex align-items-center justify-content-between text-secondary small mb-1">
                        <span>Database:</span>
                        <strong class="text-white">MySQL (Port <?php echo $port ?? 3308; ?>)</strong>
                    </div>
                    <div class="d-flex align-items-center justify-content-between text-secondary small">
                        <span>Environment:</span>
                        <strong class="text-white">Apache (XAMPP)</strong>
                    </div>
                </div>
            </div>
        </div>

        <!-- Copyright bar -->
        <div class="footer-bottom d-flex flex-column flex-md-row justify-content-between align-items-center gap-2">
            <p class="mb-0 text-secondary small">
                &copy; <?php echo date("Y"); ?> <strong>EventEase</strong>. College Event Registration Platform. All Rights Reserved.
            </p>
            <p class="mb-0 text-secondary small">
                Developed for MCA Minor Project Presentation &bull; Designed with <i class="bi bi-heart-fill text-danger mx-1"></i>
            </p>
        </div>
    </div>
</footer>

<!-- Bootstrap 5 JavaScript Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Custom App JavaScript -->
<script src="<?php echo BASE_URL; ?>assets/js/script.js"></script>

</body>
</html>
