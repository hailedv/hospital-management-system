<?php
require_once '../config/db.php';
check_login('accountant');

$page_title = 'Insurance Claims';
$role_color = '#607d8b';
$role_class = 'accountant';

$message = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_claim'])) {
    $company = trim($_POST['insurance_company'] ?? '');
    $policy  = trim($_POST['policy_number'] ?? '');
    $amount  = floatval($_POST['claim_amount'] ?? 0);
    $notes   = trim($_POST['claim_notes'] ?? '');

    if (empty($company)) {
        $error = 'Please enter the insurance company name.';
    } elseif (empty($policy)) {
        $error = 'Please enter the policy number.';
    } elseif ($amount <= 0) {
        $error = 'Please enter a valid claim amount.';
    } else {
        $claim_id = 'INS' . str_pad(rand(1000, 9999), 4, '0', STR_PAD_LEFT);
        $message  = "Claim $claim_id submitted — $company | Policy: $policy | Amount: $" . number_format($amount, 2);
        log_activity('submit_insurance_claim', "Claim $claim_id for $company");
    }
}

include '../includes/header.php';
?>

<div class="dashboard-header">
    <div>
        <h1>📋 Insurance Claims</h1>
        <p><?= htmlspecialchars($_SESSION['full_name']) ?></p>
    </div>
    <a href="dashboard.php" class="btn btn-accountant">← Dashboard</a>
</div>

<nav class="nav-menu">
    <ul>
        <li><a href="dashboard.php">Dashboard</a></li>
        <li><a href="billing.php">Billing</a></li>
        <li><a href="payments.php">Payments</a></li>
        <li><a href="insurance.php" class="active">Insurance</a></li>
        <li><a href="reports.php">Reports</a></li>
    </ul>
</nav>

<?php if ($message): ?>
    <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div style="background:white;padding:30px;border-radius:10px;box-shadow:0 2px 8px rgba(0,0,0,0.1);max-width:700px">
    <h3 style="margin-bottom:20px;color:#607d8b">Submit New Insurance Claim</h3>
    <form method="POST">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">
            <div class="form-group">
                <label>Insurance Company *</label>
                <input type="text" name="insurance_company" class="form-control" placeholder="e.g. Blue Cross" value="<?= htmlspecialchars($_POST['insurance_company'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label>Policy Number *</label>
                <input type="text" name="policy_number" class="form-control" placeholder="e.g. BC123456" value="<?= htmlspecialchars($_POST['policy_number'] ?? '') ?>" required>
            </div>
        </div>
        <div class="form-group">
            <label>Claim Amount ($) *</label>
            <input type="number" name="claim_amount" class="form-control" step="0.01" min="0.01" placeholder="500.00" value="<?= htmlspecialchars($_POST['claim_amount'] ?? '') ?>" required>
        </div>
        <div class="form-group">
            <label>Additional Notes</label>
            <textarea name="claim_notes" class="form-control" rows="3" placeholder="Diagnosis codes, treatment details..."><?= htmlspecialchars($_POST['claim_notes'] ?? '') ?></textarea>
        </div>
        <button type="submit" name="submit_claim" class="btn btn-accountant" style="width:100%;padding:12px;font-size:1em">Submit Claim</button>
    </form>
</div>

<?php include '../includes/footer.php'; ?>
