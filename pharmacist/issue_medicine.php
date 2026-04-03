<?php
require_once '../config/db.php';
check_login('pharmacist');

$page_title = 'Issue Medicine';
$role_color = '#9c27b0';
$role_class = 'pharmacist';

$message = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['prescription_id'])) {
    $prescription_id = (int)$_POST['prescription_id'];
    $pharmacist_id   = $_SESSION['user_id'];

    $stmt = $conn->prepare("UPDATE prescriptions SET status='dispensed', dispensed_by=?, dispensed_at=NOW() WHERE id=? AND status='pending'");
    $stmt->bind_param("ii", $pharmacist_id, $prescription_id);

    if ($stmt->execute() && $stmt->affected_rows > 0) {
        $message = "Medicine dispensed successfully.";
        log_activity('dispense_medicine', "Dispensed prescription ID $prescription_id");
    } else {
        $error = "Could not dispense — prescription may already be processed.";
    }
}

$pending = $conn->query("
    SELECT p.id, p.prescription_id, p.medications, p.instructions, p.created_at,
           COALESCE(pt.full_name,'Unknown') patient_name,
           COALESCE(pt.patient_id,'N/A') patient_code,
           COALESCE(d.full_name,'Unknown') doctor_name
    FROM prescriptions p
    LEFT JOIN patients pt ON p.patient_id = pt.id
    LEFT JOIN doctors d ON p.doctor_id = d.id
    WHERE p.status='pending'
    ORDER BY p.created_at DESC
");

$dispensed = $conn->query("
    SELECT p.prescription_id, p.dispensed_at,
           COALESCE(pt.full_name,'Unknown') patient_name,
           COALESCE(d.full_name,'Unknown') doctor_name,
           COALESCE(ph.full_name,'Unknown') pharmacist_name
    FROM prescriptions p
    LEFT JOIN patients pt ON p.patient_id = pt.id
    LEFT JOIN doctors d ON p.doctor_id = d.id
    LEFT JOIN pharmacists ph ON p.dispensed_by = ph.id
    WHERE p.status='dispensed'
    ORDER BY p.dispensed_at DESC LIMIT 15
");

include '../includes/header.php';
?>

<div class="dashboard-header">
    <div>
        <h1>💊 Issue Medicine</h1>
        <p><?= htmlspecialchars($_SESSION['full_name']) ?></p>
    </div>
    <a href="dashboard.php" class="btn btn-pharmacist">← Dashboard</a>
</div>

<?php if ($message): ?>
    <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="table-container">
    <h3>Pending Prescriptions</h3>
    <table class="table">
        <thead>
            <tr><th>Prescription ID</th><th>Patient</th><th>Doctor</th><th>Medications</th><th>Instructions</th><th>Date</th><th>Action</th></tr>
        </thead>
        <tbody>
        <?php if ($pending && $pending->num_rows > 0): ?>
            <?php while ($rx = $pending->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($rx['prescription_id']) ?></td>
                <td><?= htmlspecialchars($rx['patient_name']) ?><br><small><?= htmlspecialchars($rx['patient_code']) ?></small></td>
                <td><?= htmlspecialchars($rx['doctor_name']) ?></td>
                <td><?= htmlspecialchars(substr($rx['medications'], 0, 80)) ?>...</td>
                <td><?= htmlspecialchars($rx['instructions'] ?: '-') ?></td>
                <td><?= date('M j, Y', strtotime($rx['created_at'])) ?></td>
                <td>
                    <form method="POST">
                        <input type="hidden" name="prescription_id" value="<?= $rx['id'] ?>">
                        <button type="submit" class="btn btn-sm btn-pharmacist" onclick="return confirm('Dispense this medicine?')">Dispense</button>
                    </form>
                </td>
            </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="7" style="text-align:center;padding:30px">No pending prescriptions</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<div class="table-container">
    <h3>Recently Dispensed</h3>
    <table class="table">
        <thead>
            <tr><th>Prescription ID</th><th>Patient</th><th>Doctor</th><th>Dispensed By</th><th>Dispensed At</th></tr>
        </thead>
        <tbody>
        <?php if ($dispensed && $dispensed->num_rows > 0): ?>
            <?php while ($rx = $dispensed->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($rx['prescription_id']) ?></td>
                <td><?= htmlspecialchars($rx['patient_name']) ?></td>
                <td><?= htmlspecialchars($rx['doctor_name']) ?></td>
                <td><?= htmlspecialchars($rx['pharmacist_name']) ?></td>
                <td><?= date('M j, Y H:i', strtotime($rx['dispensed_at'])) ?></td>
            </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="5" style="text-align:center;padding:30px">No medicines dispensed yet</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include '../includes/footer.php'; ?>
