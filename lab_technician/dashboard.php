<?php
require_once '../config/db.php';
check_login('lab_technician');

$page_title = 'Lab Technician Dashboard';
$role_color = '#673ab7';
$role_class = 'lab_technician';
$tech_id = (int)$_SESSION['user_id'];

$stats = [
    'pending'    => (int)$conn->query("SELECT COUNT(*) c FROM lab_tests WHERE assigned_to=$tech_id AND status='pending'")->fetch_assoc()['c'],
    'in_progress'=> (int)$conn->query("SELECT COUNT(*) c FROM lab_tests WHERE assigned_to=$tech_id AND status='in_progress'")->fetch_assoc()['c'],
    'completed'  => (int)$conn->query("SELECT COUNT(*) c FROM lab_tests WHERE assigned_to=$tech_id AND status='completed' AND DATE(completed_at)=CURDATE()")->fetch_assoc()['c'],
    'total'      => (int)$conn->query("SELECT COUNT(*) c FROM lab_tests WHERE assigned_to=$tech_id")->fetch_assoc()['c'],
];

$pending_tests = $conn->query("
    SELECT lt.*, p.full_name patient_name, p.patient_id, d.full_name doctor_name
    FROM lab_tests lt
    JOIN patients p ON lt.patient_id = p.id
    JOIN doctors d ON lt.doctor_id = d.id
    WHERE lt.assigned_to=$tech_id AND lt.status IN ('pending','in_progress')
    ORDER BY lt.created_at ASC LIMIT 10
");

include '../includes/header.php';
?>

<div class="dashboard-header">
    <div>
        <h1>🔬 Lab Technician Dashboard</h1>
        <p><?= htmlspecialchars($_SESSION['full_name']) ?> &mdash; License: <?= htmlspecialchars($_SESSION['license_number'] ?? 'N/A') ?> | <?= htmlspecialchars($_SESSION['specialization'] ?? '') ?></p>
    </div>
</div>

<nav class="nav-menu">
    <ul>
        <li><a href="dashboard.php" class="active">Dashboard</a></li>
        <li><a href="process_tests.php">Process Tests</a></li>
        <li><a href="test_results.php">Test Results</a></li>
    </ul>
</nav>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-number"><?= $stats['pending'] ?></div>
        <div class="stat-label">Pending Tests</div>
    </div>
    <div class="stat-card">
        <div class="stat-number"><?= $stats['in_progress'] ?></div>
        <div class="stat-label">In Progress</div>
    </div>
    <div class="stat-card">
        <div class="stat-number"><?= $stats['completed'] ?></div>
        <div class="stat-label">Completed Today</div>
    </div>
    <div class="stat-card">
        <div class="stat-number"><?= $stats['total'] ?></div>
        <div class="stat-label">Total Assigned</div>
    </div>
</div>

<div class="features-grid">
    <div class="feature-card">
        <div class="feature-icon">🧪</div>
        <h4>Process Tests</h4>
        <p>View and process assigned laboratory tests.</p>
        <a href="process_tests.php" class="btn" style="background:#673ab7;color:white">Process Tests</a>
    </div>
    <div class="feature-card">
        <div class="feature-icon">📊</div>
        <h4>Test Results</h4>
        <p>Enter results for completed tests.</p>
        <a href="test_results.php" class="btn" style="background:#673ab7;color:white">Enter Results</a>
    </div>
</div>

<div class="table-container">
    <h3>My Assigned Tests</h3>
    <table class="table">
        <thead>
            <tr><th>Test Name</th><th>Patient</th><th>Doctor</th><th>Type</th><th>Ordered</th><th>Status</th></tr>
        </thead>
        <tbody>
        <?php if ($pending_tests && $pending_tests->num_rows > 0): ?>
            <?php while ($t = $pending_tests->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($t['test_name']) ?></td>
                <td>
                    <?= htmlspecialchars($t['patient_name']) ?>
                    <br><span style="background:#e3f2fd;color:#1976d2;padding:2px 6px;border-radius:4px;font-size:0.75em"><?= htmlspecialchars($t['patient_id']) ?></span>
                </td>
                <td>Dr. <?= htmlspecialchars($t['doctor_name']) ?></td>
                <td><?= htmlspecialchars($t['test_type']) ?></td>
                <td><?= date('M j, Y H:i', strtotime($t['created_at'])) ?></td>
                <td>
                    <span class="badge badge-<?= $t['status'] === 'in_progress' ? 'confirmed' : 'pending' ?>">
                        <?= ucfirst(str_replace('_', ' ', $t['status'])) ?>
                    </span>
                </td>
            </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="6" style="text-align:center;padding:30px">No tests assigned</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include '../includes/footer.php'; ?>
