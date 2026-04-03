<?php
require_once '../config/db.php';
check_login('accountant');

$page_title = 'Financial Reports';
$role_color = '#607d8b';
$role_class = 'accountant';

$start = $_GET['start_date'] ?? date('Y-m-01');
$end   = $_GET['end_date']   ?? date('Y-m-t');

$rev_stmt = $conn->prepare("
    SELECT COUNT(*) total_bills,
           SUM(CASE WHEN status='paid' THEN 1 ELSE 0 END) paid_bills,
           SUM(CASE WHEN status='pending' THEN 1 ELSE 0 END) pending_bills,
           SUM(total_amount) gross_revenue,
           SUM(discount) total_discounts,
           SUM(final_amount) net_revenue,
           SUM(CASE WHEN status='paid' THEN final_amount ELSE 0 END) collected_revenue,
           SUM(CASE WHEN status IN('pending','partial') THEN final_amount - COALESCE(amount_paid,0) ELSE 0 END) outstanding_revenue
    FROM bills WHERE DATE(created_at) BETWEEN ? AND ?
");
$rev_stmt->bind_param("ss", $start, $end);
$rev_stmt->execute();
$stats = $rev_stmt->get_result()->fetch_assoc();

$daily_stmt = $conn->prepare("
    SELECT DATE(created_at) date, COUNT(*) bills_count,
           SUM(final_amount) daily_revenue,
           SUM(CASE WHEN status='paid' THEN final_amount ELSE 0 END) collected_revenue
    FROM bills WHERE DATE(created_at) BETWEEN ? AND ?
    GROUP BY DATE(created_at) ORDER BY DATE(created_at) DESC LIMIT 30
");
$daily_stmt->bind_param("ss", $start, $end);
$daily_stmt->execute();
$daily = $daily_stmt->get_result();

$top_stmt = $conn->prepare("
    SELECT p.patient_id, p.full_name,
           COUNT(b.id) total_bills,
           SUM(b.final_amount) total_spent,
           SUM(CASE WHEN b.status='paid' THEN b.final_amount ELSE 0 END) amount_paid
    FROM bills b JOIN patients p ON b.patient_id=p.id
    WHERE DATE(b.created_at) BETWEEN ? AND ?
    GROUP BY b.patient_id ORDER BY total_spent DESC LIMIT 10
");
$top_stmt->bind_param("ss", $start, $end);
$top_stmt->execute();
$top_patients = $top_stmt->get_result();

include '../includes/header.php';
?>

<div class="dashboard-header">
    <div>
        <h1>📊 Financial Reports</h1>
        <p><?= htmlspecialchars($_SESSION['full_name']) ?></p>
    </div>
    <a href="dashboard.php" class="btn btn-accountant">← Dashboard</a>
</div>

<nav class="nav-menu">
    <ul>
        <li><a href="dashboard.php">Dashboard</a></li>
        <li><a href="billing.php">Billing</a></li>
        <li><a href="payments.php">Payments</a></li>
        <li><a href="insurance.php">Insurance</a></li>
        <li><a href="reports.php" class="active">Reports</a></li>
    </ul>
</nav>

<div style="background:white;padding:20px;border-radius:10px;box-shadow:0 2px 8px rgba(0,0,0,0.1);margin-bottom:20px">
    <form method="GET" style="display:flex;gap:15px;align-items:flex-end;flex-wrap:wrap">
        <div class="form-group" style="margin:0">
            <label>Start Date</label>
            <input type="date" name="start_date" class="form-control" value="<?= $start ?>">
        </div>
        <div class="form-group" style="margin:0">
            <label>End Date</label>
            <input type="date" name="end_date" class="form-control" value="<?= $end ?>">
        </div>
        <button type="submit" class="btn btn-accountant">Generate</button>
    </form>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-number"><?= $stats['total_bills'] ?? 0 ?></div>
        <div class="stat-label">Total Bills</div>
    </div>
    <div class="stat-card">
        <div class="stat-number">$<?= number_format($stats['gross_revenue'] ?? 0, 2) ?></div>
        <div class="stat-label">Gross Revenue</div>
    </div>
    <div class="stat-card">
        <div class="stat-number">$<?= number_format($stats['collected_revenue'] ?? 0, 2) ?></div>
        <div class="stat-label">Collected</div>
    </div>
    <div class="stat-card">
        <div class="stat-number">$<?= number_format($stats['outstanding_revenue'] ?? 0, 2) ?></div>
        <div class="stat-label">Outstanding</div>
    </div>
</div>

<div class="table-container">
    <h3>Daily Revenue Trend</h3>
    <table class="table">
        <thead>
            <tr><th>Date</th><th>Bills</th><th>Total Revenue</th><th>Collected</th><th>Collection Rate</th></tr>
        </thead>
        <tbody>
        <?php while ($d = $daily->fetch_assoc()):
            $rate = $d['daily_revenue'] > 0 ? ($d['collected_revenue'] / $d['daily_revenue']) * 100 : 0;
        ?>
            <tr>
                <td><?= date('M j, Y', strtotime($d['date'])) ?></td>
                <td><?= $d['bills_count'] ?></td>
                <td>$<?= number_format($d['daily_revenue'], 2) ?></td>
                <td style="color:#28a745;font-weight:600">$<?= number_format($d['collected_revenue'], 2) ?></td>
                <td><?= number_format($rate, 1) ?>%</td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</div>

<div class="table-container">
    <h3>Top Patients by Revenue</h3>
    <table class="table">
        <thead>
            <tr><th>Patient ID</th><th>Name</th><th>Bills</th><th>Total</th><th>Paid</th><th>Outstanding</th></tr>
        </thead>
        <tbody>
        <?php while ($p = $top_patients->fetch_assoc()):
            $outstanding = $p['total_spent'] - $p['amount_paid'];
        ?>
            <tr>
                <td><?= htmlspecialchars($p['patient_id']) ?></td>
                <td><?= htmlspecialchars($p['full_name']) ?></td>
                <td><?= $p['total_bills'] ?></td>
                <td>$<?= number_format($p['total_spent'], 2) ?></td>
                <td style="color:#28a745;font-weight:600">$<?= number_format($p['amount_paid'], 2) ?></td>
                <td style="color:<?= $outstanding > 0 ? '#dc3545' : '#28a745' ?>;font-weight:600">$<?= number_format($outstanding, 2) ?></td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</div>

<?php include '../includes/footer.php'; ?>
