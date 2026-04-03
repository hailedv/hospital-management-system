<?php
require_once '../config/db.php';
check_login('pharmacist');

$page_title = 'Pharmacist Dashboard';
$role_color = '#9c27b0';
$role_class = 'pharmacist';

$stats = [
    'pending'    => (int)$conn->query("SELECT COUNT(*) c FROM prescriptions WHERE status='pending'")->fetch_assoc()['c'],
    'dispensed'  => (int)$conn->query("SELECT COUNT(*) c FROM prescriptions WHERE status='dispensed' AND DATE(dispensed_at)=CURDATE()")->fetch_assoc()['c'],
    'low_stock'  => (int)$conn->query("SELECT COUNT(*) c FROM medicines WHERE stock_quantity<=min_stock_level AND status='active'")->fetch_assoc()['c'],
    'medicines'  => (int)$conn->query("SELECT COUNT(*) c FROM medicines WHERE status='active'")->fetch_assoc()['c'],
];

$pending = $conn->query("
    SELECT pr.*, p.full_name patient_name, d.full_name doctor_name
    FROM prescriptions pr
    JOIN patients p ON pr.patient_id = p.id
    JOIN doctors d ON pr.doctor_id = d.id
    WHERE pr.status='pending'
    ORDER BY pr.created_at DESC LIMIT 10
");

include '../includes/header.php';
?>

<div class="dashboard-header">
    <div>
        <h1>💊 Pharmacist Dashboard</h1>
        <p><?= htmlspecialchars($_SESSION['full_name']) ?> &mdash; License: <?= htmlspecialchars($_SESSION['license_number'] ?? 'N/A') ?></p>
    </div>
</div>

<nav class="nav-menu">
    <ul>
        <li><a href="dashboard.php" class="active">Dashboard</a></li>
        <li><a href="issue_medicine.php">Issue Medicine</a></li>
        <li><a href="stock_reports.php">Stock Reports</a></li>
        <li><a href="expiry_alerts.php">Expiry Alerts</a></li>
    </ul>
</nav>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-number"><?= $stats['pending'] ?></div>
        <div class="stat-label">Pending Prescriptions</div>
    </div>
    <div class="stat-card">
        <div class="stat-number"><?= $stats['dispensed'] ?></div>
        <div class="stat-label">Dispensed Today</div>
    </div>
    <div class="stat-card">
        <div class="stat-number"><?= $stats['low_stock'] ?></div>
        <div class="stat-label">Low Stock</div>
    </div>
    <div class="stat-card">
        <div class="stat-number"><?= $stats['medicines'] ?></div>
        <div class="stat-label">Total Medicines</div>
    </div>
</div>

<div class="features-grid">
    <div class="feature-card">
        <div class="feature-icon">📋</div>
        <h4>Issue Medicine</h4>
        <p>Dispense prescribed medicines to patients.</p>
        <a href="issue_medicine.php" class="btn btn-pharmacist">Issue Medicine</a>
    </div>
    <div class="feature-card">
        <div class="feature-icon">📊</div>
        <h4>Stock Reports</h4>
        <p>Inventory reports and stock analytics.</p>
        <a href="stock_reports.php" class="btn btn-pharmacist">Stock Reports</a>
    </div>
    <div class="feature-card">
        <div class="feature-icon">⚠️</div>
        <h4>Expiry Alerts</h4>
        <p>Monitor medicine expiry dates.</p>
        <a href="expiry_alerts.php" class="btn btn-pharmacist">Expiry Alerts</a>
    </div>
</div>

<div class="table-container">
    <h3>Pending Prescriptions</h3>
    <table class="table">
        <thead>
            <tr><th>Prescription ID</th><th>Patient</th><th>Doctor</th><th>Medications</th><th>Date</th><th>Action</th></tr>
        </thead>
        <tbody>
        <?php if ($pending && $pending->num_rows > 0): ?>
            <?php while ($p = $pending->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($p['prescription_id']) ?></td>
                <td><?= htmlspecialchars($p['patient_name']) ?></td>
                <td>Dr. <?= htmlspecialchars($p['doctor_name']) ?></td>
                <td><?= htmlspecialchars(substr($p['medications'], 0, 60)) ?>...</td>
                <td><?= date('M j, Y', strtotime($p['created_at'])) ?></td>
                <td><a href="issue_medicine.php?prescription_id=<?= $p['id'] ?>" class="btn btn-sm btn-pharmacist">Dispense</a></td>
            </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="6" style="text-align:center;padding:30px">No pending prescriptions</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include '../includes/footer.php'; ?>
