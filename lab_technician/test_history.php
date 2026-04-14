<?php
require_once '../config/db.php';
check_login('lab_technician');

$page_title = 'Test History';
$role_color = '#673ab7';
$role_class = 'lab_technician';
$tech_id = (int)$_SESSION['user_id'];

$tests = $conn->query("
    SELECT lt.*, p.full_name patient_name, p.patient_id, d.full_name doctor_name
    FROM lab_tests lt
    JOIN patients p ON lt.patient_id = p.id
    JOIN doctors d ON lt.doctor_id = d.id
    WHERE lt.assigned_to = $tech_id
    ORDER BY lt.created_at DESC
");

include '../includes/header.php';
?>

<div class="dashboard-header">
    <div>
        <h1>📋 Test History</h1>
        <p><?= htmlspecialchars($_SESSION['full_name']) ?></p>
    </div>
</div>

<nav class="nav-menu">
    <ul>
        <li><a href="dashboard.php">Dashboard</a></li>
        <li><a href="process_tests.php">Process Tests</a></li>
        <li><a href="test_results.php">Test Results</a></li>
        <li><a href="test_history.php" class="active">Test History</a></li>
    </ul>
</nav>

<div class="table-container">
    <h3>All Assigned Tests</h3>
    <table class="table">
        <thead>
            <tr><th>Test ID</th><th>Test Name</th><th>Patient</th><th>Doctor</th><th>Type</th><th>Status</th><th>Result</th><th>Ordered</th><th>Completed</th></tr>
        </thead>
        <tbody>
        <?php if ($tests && $tests->num_rows > 0): ?>
            <?php while ($t = $tests->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($t['test_id']) ?></td>
                <td><?= htmlspecialchars($t['test_name']) ?></td>
                <td>
                    <?= htmlspecialchars($t['patient_name']) ?>
                    <br><span style="background:#e3f2fd;color:#1976d2;padding:2px 6px;border-radius:4px;font-size:0.75em"><?= htmlspecialchars($t['patient_id']) ?></span>
                </td>
                <td>Dr. <?= htmlspecialchars($t['doctor_name']) ?></td>
                <td><?= htmlspecialchars($t['test_type']) ?></td>
                <td>
                    <span class="badge badge-<?= $t['status'] === 'in_progress' ? 'confirmed' : $t['status'] ?>">
                        <?= ucfirst(str_replace('_', ' ', $t['status'])) ?>
                    </span>
                </td>
                <td><?= $t['result'] ? htmlspecialchars(substr($t['result'], 0, 60)).'...' : '<em style="color:#999">-</em>' ?></td>
                <td><?= date('M j, Y', strtotime($t['created_at'])) ?></td>
                <td><?= $t['completed_at'] ? date('M j, Y', strtotime($t['completed_at'])) : '-' ?></td>
            </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="9" style="text-align:center;padding:30px">No test history found</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include '../includes/footer.php'; ?>
