<?php
// Admin Dashboard - system overview, staff stats, and navigation menu
require_once '../config/db.php';
check_login('admin');

$page_title  = 'Admin Dashboard';
$role_color  = '#2c5aa0';
$role_class  = 'admin';

$stats = [];
foreach (['doctors','nurses','patients','appointments'] as $t) {
    $r = $conn->query("SHOW TABLES LIKE '$t'");
    $stats[$t] = ($r && $r->num_rows > 0)
        ? (int)$conn->query("SELECT COUNT(*) c FROM $t")->fetch_assoc()['c']
        : 0;
}

include '../includes/header.php';
?>

<div class="dashboard-header">
    <div>
        <h1>🔑 Admin Dashboard</h1>
        <p>Welcome, <?= htmlspecialchars($_SESSION['full_name']) ?></p>
    </div>
</div>

<nav class="nav-menu">
    <ul>
        <li><a href="dashboard.php" class="active">Dashboard</a></li>
        <li><a href="add_staff.php">Add Staff</a></li>
        <li><a href="manage_users.php">Manage Users</a></li>
        <li><a href="view_patients.php">View Patients</a></li>
        <li><a href="reports.php">Reports</a></li>
    </ul>
</nav>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-number"><?= $stats['doctors'] ?></div>
        <div class="stat-label">Doctors</div>
    </div>
    <div class="stat-card">
        <div class="stat-number"><?= $stats['nurses'] ?></div>
        <div class="stat-label">Nurses</div>
    </div>
    <div class="stat-card">
        <div class="stat-number"><?= $stats['patients'] ?></div>
        <div class="stat-label">Patients</div>
    </div>
    <div class="stat-card">
        <div class="stat-number"><?= $stats['appointments'] ?></div>
        <div class="stat-label">Appointments</div>
    </div>
</div>

<div class="features-grid">
    <div class="feature-card">
        <div class="feature-icon">➕</div>
        <h4>Add Staff</h4>
        <p>Register new doctors, nurses, pharmacists and other staff.</p>
        <a href="add_staff.php" class="btn btn-doctor">Add Staff</a>
    </div>
    <div class="feature-card">
        <div class="feature-icon">👥</div>
        <h4>Manage Users</h4>
        <p>View, activate or deactivate system users.</p>
        <a href="manage_users.php" class="btn btn-admin">Manage Users</a>
    </div>
    <div class="feature-card">
        <div class="feature-icon">👤</div>
        <h4>View Patients</h4>
        <p>Browse all registered patients.</p>
        <a href="view_patients.php" class="btn btn-patient">View Patients</a>
    </div>
    <div class="feature-card">
        <div class="feature-icon">📊</div>
        <h4>Reports</h4>
        <p>System-wide operational reports.</p>
        <a href="reports.php" class="btn btn-accountant">Reports</a>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
