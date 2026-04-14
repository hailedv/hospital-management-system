<?php
require_once '../config/db.php';
check_login('patient');

$page_title = 'My Bills';
$role_color = '#007bff';
$role_class = 'patient';
$patient_id = (int)$_SESSION['user_id'];

$bills = $conn->query("
    SELECT b.*, a.full_name as generated_by_name
    FROM bills b
    LEFT JOIN accountants a ON b.generated_by = a.id
    WHERE b.patient_id = $patient_id
    ORDER BY b.created_at DESC
");

$totals = $conn->query("
    SELECT
        COUNT(*) as total_bills,
        COALESCE(SUM(final_amount), 0) as total_amount,
        COALESCE(SUM(CASE WHEN status='paid' THEN final_amount ELSE 0 END), 0) as paid_amount,
        COALESCE(SUM(CASE WHEN status='pending' THEN final_amount ELSE 0 END), 0) as outstanding_amount
    FROM bills WHERE patient_id = $patient_id
")->fetch_assoc();

include '../includes/header.php';
?>

<div class="dashboard-header">
    <div>
        <h1>💳 My Bills</h1>
        <p><?= htmlspecialchars($_SESSION['full_name']) ?></p>
    </div>
</div>

<nav class="nav-menu">
    <ul>
        <li><a href="dashboard.php">Dashboard</a></li>
        <li><a href="appointments.php">My Appointments</a></li>
        <li><a href="book_appointment.php">Book Appointment</a></li>
        <li><a href="bills.php" class="active">My Bills</a></li>
        <li><a href="profile.php">Profile</a></li>
    </ul>
</nav>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-number"><?= $totals['total_bills'] ?></div>
        <div class="stat-label">Total Bills</div>
    </div>
    <div class="stat-card">
        <div class="stat-number">$<?= number_format($totals['total_amount'], 2) ?></div>
        <div class="stat-label">Total Amount</div>
    </div>
    <div class="stat-card">
        <div class="stat-number">$<?= number_format($totals['paid_amount'], 2) ?></div>
        <div class="stat-label">Amount Paid</div>
    </div>
    <div class="stat-card">
        <div class="stat-number" style="color:#dc3545">$<?= number_format($totals['outstanding_amount'], 2) ?></div>
        <div class="stat-label">Outstanding</div>
    </div>
</div>

<div class="table-container">
    <h3>My Medical Bills</h3>
    <table class="table">
        <thead>
            <tr><th>Bill ID</th><th>Date</th><th>Services</th><th>Total</th><th>Discount</th><th>Final</th><th>Status</th><th>Paid On</th></tr>
        </thead>
        <tbody>
        <?php if ($bills && $bills->num_rows > 0): ?>
            <?php while ($b = $bills->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($b['bill_id']) ?></td>
                <td><?= date('M j, Y', strtotime($b['created_at'])) ?></td>
                <td style="font-size:0.85em">
                    <?php if ($b['consultation_fee'] > 0) echo 'Consult: $'.number_format($b['consultation_fee'],2).'<br>'; ?>
                    <?php if ($b['medicine_cost'] > 0)    echo 'Medicine: $'.number_format($b['medicine_cost'],2).'<br>'; ?>
                    <?php if ($b['lab_charges'] > 0)      echo 'Lab: $'.number_format($b['lab_charges'],2).'<br>'; ?>
                    <?php if ($b['other_charges'] > 0)    echo 'Other: $'.number_format($b['other_charges'],2); ?>
                </td>
                <td>$<?= number_format($b['total_amount'], 2) ?></td>
                <td>$<?= number_format($b['discount'], 2) ?></td>
                <td><strong>$<?= number_format($b['final_amount'], 2) ?></strong></td>
                <td><span class="badge badge-<?= $b['status'] ?>"><?= ucfirst($b['status']) ?></span></td>
                <td><?= $b['paid_at'] ? date('M j, Y', strtotime($b['paid_at'])) : '-' ?></td>
            </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="8" style="text-align:center;padding:30px">No bills found</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<?php if ($totals['outstanding_amount'] > 0): ?>
<div style="background:#fff3cd;border:1px solid #ffeaa7;padding:16px;border-radius:8px;margin-top:16px;color:#856404">
    You have outstanding bills totaling <strong>$<?= number_format($totals['outstanding_amount'], 2) ?></strong>. Please contact the billing department to arrange payment.
</div>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>
