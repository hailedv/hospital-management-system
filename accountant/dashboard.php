<?php
require_once '../config/db.php';
check_login('accountant');

$page_title = 'Accountant Dashboard';
$role_color = '#607d8b';
$role_class = 'accountant';

$stats = [
    'total_bills'  => (int)$conn->query("SELECT COUNT(*) c FROM bills WHERE status!='cancelled'")->fetch_assoc()['c'],
    'pending'      => (int)$conn->query("SELECT COUNT(*) c FROM bills WHERE status='pending'")->fetch_assoc()['c'],
    'paid_today'   => (int)$conn->query("SELECT COUNT(*) c FROM bills WHERE status='paid' AND DATE(paid_at)=CURDATE()")->fetch_assoc()['c'],
    'revenue'      => (float)$conn->query("SELECT COALESCE(SUM(total_amount),0) t FROM bills WHERE status='paid'")->fetch_assoc()['t'],
];

$recent_bills = $conn->query("
    SELECT b.*, p.full_name patient_name
    FROM bills b
    JOIN patients p ON b.patient_id = p.id
    ORDER BY b.created_at DESC LIMIT 10
");

include '../includes/header.php';
?>

<div class="dashboard-header">
    <div>
        <h1>💰 Accountant Dashboard</h1>
        <p><?= htmlspecialchars($_SESSION['full_name']) ?></p>
    </div>
</div>

<nav class="nav-menu">
    <ul>
        <li><a href="dashboard.php" class="active">Dashboard</a></li>
        <li><a href="billing.php">Billing</a></li>
        <li><a href="payments.php">Payments</a></li>
        <li><a href="insurance.php">Insurance</a></li>
        <li><a href="reports.php">Reports</a></li>
    </ul>
</nav>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-number"><?= $stats['total_bills'] ?></div>
        <div class="stat-label">Total Bills</div>
    </div>
    <div class="stat-card">
        <div class="stat-number"><?= $stats['pending'] ?></div>
        <div class="stat-label">Pending Payments</div>
    </div>
    <div class="stat-card">
        <div class="stat-number"><?= $stats['paid_today'] ?></div>
        <div class="stat-label">Paid Today</div>
    </div>
    <div class="stat-card">
        <div class="stat-number">$<?= number_format($stats['revenue'], 2) ?></div>
        <div class="stat-label">Total Revenue</div>
    </div>
</div>

<div class="features-grid">
    <div class="feature-card">
        <div class="feature-icon">💳</div>
        <h4>Billing</h4>
        <p>Create and manage patient bills.</p>
        <a href="billing.php" class="btn btn-accountant">Billing</a>
    </div>
    <div class="feature-card">
        <div class="feature-icon">💰</div>
        <h4>Payments</h4>
        <p>Record and manage payments.</p>
        <a href="payments.php" class="btn btn-accountant">Payments</a>
    </div>
    <div class="feature-card">
        <div class="feature-icon">📋</div>
        <h4>Insurance</h4>
        <p>Manage insurance claims.</p>
        <a href="insurance.php" class="btn btn-accountant">Insurance</a>
    </div>
    <div class="feature-card">
        <div class="feature-icon">📊</div>
        <h4>Reports</h4>
        <p>Financial and revenue reports.</p>
        <a href="reports.php" class="btn btn-accountant">Reports</a>
    </div>
</div>

<div class="table-container">
    <h3>Recent Bills</h3>
    <table class="table">
        <thead>
            <tr><th>Bill ID</th><th>Patient</th><th>Amount</th><th>Status</th><th>Date</th><th>Action</th></tr>
        </thead>
        <tbody>
        <?php if ($recent_bills && $recent_bills->num_rows > 0): ?>
            <?php while ($b = $recent_bills->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($b['bill_id']) ?></td>
                <td><?= htmlspecialchars($b['patient_name']) ?></td>
                <td>$<?= number_format($b['total_amount'], 2) ?></td>
                <td><span class="badge badge-<?= $b['status'] ?>"><?= ucfirst($b['status']) ?></span></td>
                <td><?= date('M j, Y', strtotime($b['created_at'])) ?></td>
                <td>
                    <?php if ($b['status'] === 'pending'): ?>
                        <a href="payments.php?bill_id=<?= $b['id'] ?>" class="btn btn-sm btn-accountant">Pay</a>
                    <?php else: ?>
                        <a href="billing.php?view=<?= $b['id'] ?>" class="btn btn-sm btn-accountant">View</a>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="6" style="text-align:center;padding:30px">No bills found</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include '../includes/footer.php'; ?>
