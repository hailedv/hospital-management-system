<?php
require_once '../config/db.php';
check_login('nurse');

$page_title = 'Medication Administration';
$role_color = '#e91e63';
$role_class = 'nurse';
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['administer_medication'])) {
    $prescription_id = isset($_POST['prescription_id']) ? (int)$_POST['prescription_id'] : 0;
    $administration_notes = isset($_POST['administration_notes']) ? sanitize_input($_POST['administration_notes']) : '';
    $nurse_id = (int)$_SESSION['user_id'];

    if (empty($prescription_id)) {
        $error = "Please select a prescription.";
    } else {
        $stmt = $conn->prepare("INSERT INTO patient_vitals (patient_id, nurse_id, notes)
                               SELECT patient_id, ?, CONCAT('MEDICATION ADMINISTERED - Prescription ID: ', prescription_id,
                               CASE WHEN ? != '' THEN CONCAT(' - Notes: ', ?) ELSE '' END)
                               FROM prescriptions WHERE id = ?");
        $stmt->bind_param("issi", $nurse_id, $administration_notes, $administration_notes, $prescription_id);
        if ($stmt->execute()) {
            $message = "Medication administration recorded successfully!";
            log_activity('administer_medication', "Administered medication for prescription ID $prescription_id");
        } else {
            $error = "Error recording medication administration: " . $conn->error;
        }
    }
}

$pending_prescriptions = $conn->query("
    SELECT p.*, pt.full_name as patient_name, pt.patient_id, d.full_name as doctor_name
    FROM prescriptions p
    JOIN patients pt ON p.patient_id = pt.id
    JOIN doctors d ON p.doctor_id = d.id
    WHERE p.status = 'dispensed'
    ORDER BY p.dispensed_at DESC
");

include '../includes/header.php';
?>

<nav class="nav-menu">
    <ul>
        <li><a href="dashboard.php">Dashboard</a></li>
        <li><a href="patients.php">Patients</a></li>
        <li><a href="vitals.php">Vitals</a></li>
        <li><a href="medications.php" class="active">Medications</a></li>
        <li><a href="patient_notes.php">Nursing Notes</a></li>
    </ul>
</nav>

<?php if ($message): ?>
    <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="table-container">
    <h3>💉 Dispensed Medications Ready for Administration</h3>
    <table class="table">
        <thead>
            <tr><th>Prescription ID</th><th>Patient</th><th>Doctor</th><th>Medications</th><th>Instructions</th><th>Dispensed</th><th>Actions</th></tr>
        </thead>
        <tbody>
        <?php if ($pending_prescriptions && $pending_prescriptions->num_rows > 0): ?>
            <?php while ($rx = $pending_prescriptions->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($rx['prescription_id']) ?></td>
                <td><?= htmlspecialchars($rx['patient_name']) ?><br><small><?= htmlspecialchars($rx['patient_id']) ?></small></td>
                <td><?= htmlspecialchars($rx['doctor_name']) ?></td>
                <td><?= nl2br(htmlspecialchars($rx['medications'])) ?></td>
                <td><?= htmlspecialchars($rx['instructions'] ?: 'No special instructions') ?></td>
                <td><?= $rx['dispensed_at'] ? date('M j, Y H:i', strtotime($rx['dispensed_at'])) : '-' ?></td>
                <td>
                    <form method="POST" style="display:inline">
                        <input type="hidden" name="prescription_id" value="<?= $rx['id'] ?>">
                        <button type="submit" name="administer_medication" class="btn btn-sm btn-nurse"
                                onclick="return confirm('Confirm administration for <?= htmlspecialchars($rx['patient_name']) ?>?')">
                            ✓ Administer
                        </button>
                    </form>
                </td>
            </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="7" style="text-align:center;padding:30px">No medications ready for administration</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include '../includes/footer.php'; ?>
