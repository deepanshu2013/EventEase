<?php
/**
 * EventEase - Reports & Analytics Module
 * Comprehensive college event reports, category breakdown, and turnout analytics
 */

$page_title = "Reports & Analytics | EventEase";
require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../includes/auth.php";

require_login('organizer');

$organizer_id = current_user_id();

// 1. Overall Aggregate Metrics
$agg_sql = "
    SELECT 
        COUNT(DISTINCT e.event_id) AS total_events,
        COALESCE(SUM(e.capacity), 0) AS total_capacity,
        COUNT(CASE WHEN r.status IN ('registered', 'attended') THEN 1 END) AS total_enrolled,
        COUNT(CASE WHEN r.status = 'attended' THEN 1 END) AS total_attended,
        COUNT(CASE WHEN r.status = 'cancelled' THEN 1 END) AS total_cancelled
    FROM events e
    LEFT JOIN registrations r ON e.event_id = r.event_id
    WHERE e.organizer_id = ?
";
$agg_stmt = mysqli_prepare($conn, $agg_sql);
mysqli_stmt_bind_param($agg_stmt, "i", $organizer_id);
mysqli_stmt_execute($agg_stmt);
$aggregate = mysqli_fetch_assoc(mysqli_stmt_get_result($agg_stmt));
mysqli_stmt_close($agg_stmt);

$total_events = (int)($aggregate['total_events'] ?? 0);
$total_capacity = (int)($aggregate['total_capacity'] ?? 0);
$total_enrolled = (int)($aggregate['total_enrolled'] ?? 0);
$total_attended = (int)($aggregate['total_attended'] ?? 0);
$total_cancelled = (int)($aggregate['total_cancelled'] ?? 0);

$overall_capacity_pct = ($total_capacity > 0) ? round(($total_enrolled / $total_capacity) * 100) : 0;
$overall_turnout_pct = ($total_enrolled > 0) ? round(($total_attended / $total_enrolled) * 100) : 0;
$avg_signups_per_event = ($total_events > 0) ? round($total_enrolled / $total_events, 1) : 0;

// 2. Category Performance Breakdown
$cat_sql = "
    SELECT 
        e.category,
        COUNT(DISTINCT e.event_id) AS event_count,
        COUNT(CASE WHEN r.status IN ('registered', 'attended') THEN 1 END) AS total_signups,
        COUNT(CASE WHEN r.status = 'attended' THEN 1 END) AS total_attended
    FROM events e
    LEFT JOIN registrations r ON e.event_id = r.event_id
    WHERE e.organizer_id = ?
    GROUP BY e.category
    ORDER BY total_signups DESC
";
$cat_stmt = mysqli_prepare($conn, $cat_sql);
mysqli_stmt_bind_param($cat_stmt, "i", $organizer_id);
mysqli_stmt_execute($cat_stmt);
$category_stats = mysqli_stmt_get_result($cat_stmt);

// 3. Detailed Event-by-Event Roster & Performance
$ev_rep_sql = "
    SELECT 
        e.event_id,
        e.event_name,
        e.category,
        e.venue,
        e.event_date,
        e.capacity,
        COUNT(CASE WHEN r.status IN ('registered', 'attended') THEN 1 END) AS enrolled_count,
        COUNT(CASE WHEN r.status = 'attended' THEN 1 END) AS attended_count,
        COUNT(CASE WHEN r.status = 'cancelled' THEN 1 END) AS cancelled_count
    FROM events e
    LEFT JOIN registrations r ON e.event_id = r.event_id
    WHERE e.organizer_id = ?
    GROUP BY e.event_id
    ORDER BY e.event_date ASC
";
$ev_stmt = mysqli_prepare($conn, $ev_rep_sql);
mysqli_stmt_bind_param($ev_stmt, "i", $organizer_id);
mysqli_stmt_execute($ev_stmt);
$event_reports = mysqli_stmt_get_result($ev_stmt);

require_once __DIR__ . "/../includes/header.php";
?>

<div class="container py-4">
    <!-- Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
        <div>
            <span class="badge bg-purple text-white px-2 py-1 rounded-pill small">Auditing & Analytics</span>
            <h2 class="fw-bold text-navy mb-1">Reports & Platform Analytics</h2>
            <p class="text-muted small mb-0">Quantitative analysis of student engagement, category distribution, and attendance turnout.</p>
        </div>
        <div class="mt-3 mt-md-0 d-flex gap-2">
            <button type="button" class="btn btn-outline-secondary rounded-pill" onclick="window.print()">
                <i class="bi bi-printer me-1"></i> Print / Export Report
            </button>
            <a href="dashboard.php" class="btn btn-primary rounded-pill">
                <i class="bi bi-speedometer2 me-1"></i> Back to Dashboard
            </a>
        </div>
    </div>

    <!-- Aggregate Analytics Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-3 col-6">
            <div class="dashboard-widget text-center">
                <div class="widget-value text-navy"><?php echo $total_events; ?></div>
                <div class="widget-title">Total Events Hosted</div>
                <small class="text-muted">Organized by department</small>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="dashboard-widget text-center">
                <div class="widget-value text-purple"><?php echo $total_enrolled; ?></div>
                <div class="widget-title">Total Registrations</div>
                <small class="text-muted">Avg <?php echo $avg_signups_per_event; ?> per event</small>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="dashboard-widget text-center">
                <div class="widget-value text-success"><?php echo $overall_turnout_pct; ?>%</div>
                <div class="widget-title">Turnout Ratio</div>
                <small class="text-muted"><?php echo $total_attended; ?> verified attendees</small>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="dashboard-widget text-center">
                <div class="widget-value text-warning"><?php echo $overall_capacity_pct; ?>%</div>
                <div class="widget-title">Capacity Fill Rate</div>
                <small class="text-muted"><?php echo $total_enrolled; ?> of <?php echo $total_capacity; ?> seats</small>
            </div>
        </div>
    </div>

    <!-- Category Breakdown Table -->
    <div class="card-modern p-4 mb-4">
        <h5 class="fw-bold text-navy mb-3"><i class="bi bi-pie-chart-fill text-purple me-2"></i>Performance by Event Category</h5>
        <div class="table-responsive">
            <table class="table table-modern align-middle mb-0">
                <thead>
                    <tr>
                        <th>Category</th>
                        <th>Events Count</th>
                        <th>Total Signups</th>
                        <th>Turnout (Attended)</th>
                        <th>Engagement Share</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($category_stats) > 0): ?>
                        <?php while ($cat = mysqli_fetch_assoc($category_stats)): 
                            $share = ($total_enrolled > 0) ? round(($cat['total_signups'] / $total_enrolled) * 100) : 0;
                        ?>
                            <tr>
                                <td><?php echo get_category_badge($cat['category']); ?></td>
                                <td><strong><?php echo $cat['event_count']; ?></strong> events</td>
                                <td><span class="fw-bold text-navy"><?php echo $cat['total_signups']; ?></span> students</td>
                                <td>
                                    <span class="text-success fw-semibold"><?php echo $cat['total_attended']; ?></span>
                                    <small class="text-muted">(<?php echo ($cat['total_signups'] > 0) ? round(($cat['total_attended'] / $cat['total_signups']) * 100) : 0; ?>%)</small>
                                </td>
                                <td style="min-width: 150px;">
                                    <div class="d-flex justify-content-between small text-muted mb-1">
                                        <span>Share</span>
                                        <strong><?php echo $share; ?>%</strong>
                                    </div>
                                    <div class="capacity-progress">
                                        <div class="progress-bar bg-primary" style="width: <?php echo $share; ?>%"></div>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="5" class="text-center text-muted py-3">No category data available.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Comprehensive Event Performance Audit Table -->
    <div class="card-modern p-4">
        <h5 class="fw-bold text-navy mb-3"><i class="bi bi-file-earmark-bar-graph text-purple me-2"></i>Detailed Event Audit Report</h5>
        <div class="table-responsive">
            <table class="table table-modern align-middle mb-0">
                <thead>
                    <tr>
                        <th>Event</th>
                        <th>Date & Venue</th>
                        <th>Capacity</th>
                        <th>Enrolled</th>
                        <th>Attended</th>
                        <th>Cancelled</th>
                        <th>Turnout</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($event_reports) > 0): ?>
                        <?php while ($ev = mysqli_fetch_assoc($event_reports)): 
                            $cap = (int)$ev['capacity'];
                            $enr = (int)$ev['enrolled_count'];
                            $att = (int)$ev['attended_count'];
                            $cnc = (int)$ev['cancelled_count'];
                            $turnout = ($enr > 0) ? round(($att / $enr) * 100) : 0;
                            $cap_pct = ($cap > 0) ? round(($enr / $cap) * 100) : 0;
                            $is_past = (strtotime($ev['event_date']) <= time());
                        ?>
                            <tr>
                                <td>
                                    <strong class="text-navy"><?php echo htmlspecialchars($ev['event_name']); ?></strong>
                                    <div><?php echo get_category_badge($ev['category']); ?></div>
                                </td>
                                <td>
                                    <div class="small fw-semibold text-navy"><?php echo format_event_date_short($ev['event_date']); ?></div>
                                    <small class="text-muted"><i class="bi bi-geo-alt me-1"></i><?php echo htmlspecialchars($ev['venue']); ?></small>
                                </td>
                                <td><strong><?php echo $cap; ?></strong></td>
                                <td>
                                    <span class="fw-bold text-primary"><?php echo $enr; ?></span>
                                    <small class="text-muted">(<?php echo $cap_pct; ?>%)</small>
                                </td>
                                <td><span class="fw-bold text-success"><?php echo $att; ?></span></td>
                                <td><span class="text-secondary"><?php echo $cnc; ?></span></td>
                                <td>
                                    <span class="badge <?php echo ($turnout >= 70) ? 'bg-success' : 'bg-secondary'; ?> rounded-pill">
                                        <?php echo $turnout; ?>%
                                    </span>
                                </td>
                                <td>
                                    <?php if ($cap_pct >= 100): ?>
                                        <span class="badge bg-danger rounded-pill">Full</span>
                                    <?php elseif ($is_past): ?>
                                        <span class="badge bg-secondary-subtle text-secondary rounded-pill">Concluded</span>
                                    <?php else: ?>
                                        <span class="badge bg-success-subtle text-success rounded-pill">Open</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="8" class="text-center py-4 text-muted">No events recorded.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php 
mysqli_stmt_close($ev_stmt);
mysqli_stmt_close($cat_stmt);
require_once __DIR__ . "/../includes/footer.php"; 
?>
