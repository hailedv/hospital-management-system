<?php
require_once '../config/db.php';
check_login('patient');

$page_title = 'Medical Reports';
$role_color = '#007bff';
$role_class = 'patient';
$patient_id = (int)$_SESSION['user_id'];

$prescriptions = $conn->query("
    SELECT pr.*, d.full_name doctor_name, d.specialization
    FROM prescriptions pr
    JOIN doctors d ON pr.doctor_id = d.id
    WHERE pr.patient_id = $patient_id
    ORDER BY pr.created_at DESC
");

$lab_tests = $conn->query("
    SELECT lt.*, d.full_name doctor_name
    FROM lab_tests lt
    JOIN doctors d ON lt.doctor_id = d.id
    WHERE lt.patient_id = $patient_id
    ORDER BY lt.created_at DESC
");

include '../includes/header.php';
?>

<div class="dashboard-header">
    <div>
        <h1>📋 Medical Reports</h1>
        <p><?= htmlspecialchars($_SESSION['full_name']) ?></p>
    </div>
</div>

<nav class="nav-menu">
    <ul>
        <li><a href="dashboard.php">Dashboard</a></li>
        <li><a href="appointments.php">My Appointments</a></li>
        <li><a href="book_appointment.php">Book Appointment</a></li>
        <li><a href="bills.php">My Bills</a></li>
        <li><a href="profile.php">Profile</a></li>
    </ul>
</nav>

<div class="table-container">
    <h3>My Prescriptions</h3>
    <table class="table">
        <thead>
            <tr><th>Prescription ID</th><th>Doctor</th><th>Medications</th><th>Instructions</th><th>Status</th><th>Date</th></tr>
        </thead>
        <tbody>
        <?php if ($prescriptions && $prescriptions->num_rows > 0): ?>
            <?php while ($rx = $prescriptions->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($rx['prescription_id']) ?></td>
                <td>Dr. <?= htmlspecialchars($rx['doctor_name']) ?><br><small><?= htmlspecialchars($rx['specialization']) ?></small></td>
                <td><?= nl2br(htmlspecialchars($rx['medications'])) ?></td>
                <td><?= htmlspecialchars($rx['instructions'] ?: '-') ?></td>
                <td><span class="badge badge-<?= $rx['status'] ?>"><?= ucfirst($rx['status']) ?></span></td>
                <td><?= date('M j, Y', strtotime($rx['created_at'])) ?></td>
            </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="6" style="text-align:center;padding:30px">No prescriptions found</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<div class="table-container" style="margin-top:20px">
    <h3>My Lab Tests</h3>
    <table class="table">
        <thead>
            <tr><th>Test ID</th><th>Test Name</th><th>Type</th><th>Doctor</th><th>Status</th><th>Result</th><th>Date</th></tr>
        </thead>
        <tbody>
        <?php if ($lab_tests && $lab_tests->num_rows > 0): ?>
            <?php while ($t = $lab_tests->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($t['test_id']) ?></td>
                <td><?= htmlspecialchars($t['test_name']) ?></td>
                <td><?= htmlspecialchars($t['test_type']) ?></td>
                <td>Dr. <?= htmlspecialchars($t['doctor_name']) ?></td>
                <td><span class="badge badge-<?= $t['status'] === 'in_progress' ? 'confirmed' : $t['status'] ?>"><?= ucfirst(str_replace('_',' ',$t['status'])) ?></span></td>
                <td><?= $t['result'] ? htmlspecialchars(substr($t['result'], 0, 80)).'...' : '<em style="color:#999">Pending</em>' ?></td>
                <td><?= date('M j, Y', strtotime($t['created_at'])) ?></td>
            </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="7" style="text-align:center;padding:30px">No lab tests found</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include '../includes/footer.php'; ?>
