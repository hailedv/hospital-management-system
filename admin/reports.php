<?php
require_once '../config/db.php';
check_login('admin');

// Get system statistics
$stats = [];
$stats['total_users'] = $conn->query("
    SELECT (SELECT COUNT(*) FROM admins) + 
           (SELECT COUNT(*) FROM doctors) + 
           (SELECT COUNT(*) FROM nurses) + 
           (SELECT COUNT(*) FROM receptionists) + 
           (SELECT COUNT(*) FROM pharmacists) + 
           (SELECT COUNT(*) FROM accountants) + 
           (SELECT COUNT(*) FROM patients) as total
")->fetch_assoc()['total'];

$stats['total_appointments'] = $conn->query("SELECT COUNT(*) as count FROM appointments")->fetch_assoc()['count'];
$stats['total_prescriptions'] = $conn->query("SELECT COUNT(*) as count FROM prescriptions")->fetch_assoc()['count'];
$stats['total_bills'] = $conn->query("SELECT COUNT(*) as count FROM bills")->fetch_assoc()['count'];
$stats['total_revenue'] = $conn->query("SELECT COALESCE(SUM(final_amount), 0) as total FROM bills WHERE status = 'paid'")->fetch_assoc()['total'];

// Get monthly statistics
$monthly_stats = $conn->query("
    SELECT 
        MONTH(created_at) as month,
        YEAR(created_at) as year,
        COUNT(*) as appointments
    FROM appointments 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY YEAR(created_at), MONTH(created_at)
    ORDER BY year DESC, month DESC
    LIMIT 6
");

// Get user type distribution
$user_distribution = [];
$user_distribution['doctors'] = $conn->query("SELECT COUNT(*) as count FROM doctors WHERE status = 'active'")->fetch_assoc()['count'];
$user_distribution['nurses'] = $conn->query("SELECT COUNT(*) as count FROM nurses WHERE status = 'active'")->fetch_assoc()['count'];
$user_distribution['receptionists'] = $conn->query("SELECT COUNT(*) as count FROM receptionists WHERE status = 'active'")->fetch_assoc()['count'];
$user_distribution['pharmacists'] = $conn->query("SELECT COUNT(*) as count FROM pharmacists WHERE status = 'active'")->fetch_assoc()['count'];
$user_distribution['accountants'] = $conn->query("SELECT COUNT(*) as count FROM accountants WHERE status = 'active'")->fetch_assoc()['count'];
$user_distribution['patients'] = $conn->query("SELECT COUNT(*) as count FROM patients WHERE status = 'active'")->fetch_assoc()['count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Reports - Hospital Management System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container">
        <div class="dashboard-header">
            <div>
                <h1>📊 System Reports</h1>
                <p>Comprehensive system analytics and reports</p>
            </div>
            <div>
                <a href="dashboard.php" class="btn btn-admin">← Back to Dashboard</a>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['total_users']; ?></div>
                <div class="stat-label">Total System Users</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['total_appointments']; ?></div>
                <div class="stat-label">Total Appointments</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['total_prescriptions']; ?></div>
                <div class="stat-label">Total Prescriptions</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['total_bills']; ?></div>
                <div class="stat-label">Total Bills</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">$<?php echo number_format($stats['total_revenue'], 2); ?></div>
                <div class="stat-label">Total Revenue</div>
            </div>
        </div>

        <div class="features-grid">
            <div class="feature-card">
                <h4>👥 User Distribution</h4>
                <table style="width: 100%; font-size: 0.9em;">
                    <tr><td>Doctors:</td><td><strong><?php echo $user_distribution['doctors']; ?></strong></td></tr>
                    <tr><td>Nurses:</td><td><strong><?php echo $user_distribution['nurses']; ?></strong></td></tr>
                    <tr><td>Receptionists:</td><td><strong><?php echo $user_distribution['receptionists']; ?></strong></td></tr>
                    <tr><td>Pharmacists:</td><td><strong><?php echo $user_distribution['pharmacists']; ?></strong></td></tr>
                    <tr><td>Accountants:</td><td><strong><?php echo $user_distribution['accountants']; ?></strong></td></tr>
                    <tr><td>Patients:</td><td><strong><?php echo $user_distribution['patients']; ?></strong></td></tr>
                </table>
            </div>

            <div class="feature-card">
                <h4>📈 Monthly Appointments</h4>
                <table style="width: 100%; font-size: 0.9em;">
                    <?php while ($month = $monthly_stats->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo date('M Y', mktime(0, 0, 0, $month['month'], 1, $month['year'])); ?>:</td>
                            <td><strong><?php echo $month['appointments']; ?></strong></td>
                        </tr>
                    <?php endwhile; ?>
                </table>
            </div>

            <div class="feature-card">
                <h4>💰 Financial Summary</h4>
                <?php
                $pending_amount = $conn->query("SELECT COALESCE(SUM(final_amount), 0) as total FROM bills WHERE status = 'pending'")->fetch_assoc()['total'];
                $paid_amount = $conn->query("SELECT COALESCE(SUM(final_amount), 0) as total FROM bills WHERE status = 'paid'")->fetch_assoc()['total'];
                ?>
                <table style="width: 100%; font-size: 0.9em;">
                    <tr><td>Paid Bills:</td><td><strong>$<?php echo number_format($paid_amount, 2); ?></strong></td></tr>
                    <tr><td>Pending Bills:</td><td><strong>$<?php echo number_format($pending_amount, 2); ?></strong></td></tr>
                    <tr><td>Total Revenue:</td><td><strong>$<?php echo number_format($paid_amount + $pending_amount, 2); ?></strong></td></tr>
                </table>
            </div>

            <div class="feature-card">
                <h4>🏥 System Status</h4>
                <?php
                $today_appointments = $conn->query("SELECT COUNT(*) as count FROM appointments WHERE DATE(appointment_date) = CURDATE()")->fetch_assoc()['count'];
                $pending_prescriptions = $conn->query("SELECT COUNT(*) as count FROM prescriptions WHERE status = 'pending'")->fetch_assoc()['count'];
                $low_stock = $conn->query("SELECT COUNT(*) as count FROM medicines WHERE stock_quantity <= min_stock_level")->fetch_assoc()['count'];
                ?>
                <table style="width: 100%; font-size: 0.9em;">
                    <tr><td>Today's Appointments:</td><td><strong><?php echo $today_appointments; ?></strong></td></tr>
                    <tr><td>Pending Prescriptions:</td><td><strong><?php echo $pending_prescriptions; ?></strong></td></tr>
                    <tr><td>Low Stock Medicines:</td><td><strong><?php echo $low_stock; ?></strong></td></tr>
                </table>
            </div>
        </div>

        <div class="table-container">
            <h3>Recent System Activity</h3>
            <table class="table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>User Type</th>
                        <th>Action</th>
                        <th>Description</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $recent_activities = $conn->query("SELECT * FROM activity_logs ORDER BY created_at DESC LIMIT 15");
                    while ($activity = $recent_activities->fetch_assoc()):
                    ?>
                        <tr>
                            <td><?php echo date('M j, H:i', strtotime($activity['created_at'])); ?></td>
                            <td><?php echo ucfirst($activity['user_type']); ?></td>
                            <td><?php echo ucfirst($activity['action']); ?></td>
                            <td><?php echo htmlspecialchars($activity['description']); ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script src="../assets/js/script.js"></script>
</body>
</html>