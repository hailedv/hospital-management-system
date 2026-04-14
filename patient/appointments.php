<?php
// Patient Appointments - full appointment history for the logged-in patient using shared layout
require_once '../config/db.php';
check_login('patient');

$page_title = 'My Appointments';
$role_color = '#007bff';
$role_class = 'patient';
$patient_id = (int)$_SESSION['user_id'];

$appointments = $conn->query("
    SELECT a.*, d.full_name doctor_name, d.specialization
    FROM appointments a
    JOIN doctors d ON a.doctor_id = d.id
    WHERE a.patient_id = $patient_id
    ORDER BY a.appointment_date DESC, a.appointment_time DESC
");

include '../includes/header.php';
?>

<div class="dashboard-header">
    <div>
        <h1>📅 My Appointments</h1>
        <p><?= htmlspecialchars($_SESSION['full_name']) ?></p>
    </div>
</div>

<nav class="nav-menu">
    <ul>
        <li><a href="dashboard.php">Dashboard</a></li>
        <li><a href="appointments.php" class="active">My Appointments</a></li>
        <li><a href="book_appointment.php">Book Appointment</a></li>
        <li><a href="bills.php">My Bills</a></li>
        <li><a href="profile.php">Profile</a></li>
    </ul>
</nav>

<div class="table-container">
    <h3>All Appointments</h3>
    <table class="table">
        <thead>
            <tr><th>Appointment ID</th><th>Date</th><th>Time</th><th>Doctor</th><th>Specialization</th><th>Reason</th><th>Status</th></tr>
        </thead>
        <tbody>
        <?php if ($appointments && $appointments->num_rows > 0): ?>
            <?php while ($a = $appointments->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($a['appointment_id']) ?></td>
                <td><?= date('M j, Y', strtotime($a['appointment_date'])) ?></td>
                <td><?= date('H:i', strtotime($a['appointment_time'])) ?></td>
                <td>Dr. <?= htmlspecialchars($a['doctor_name']) ?></td>
                <td><?= htmlspecialchars($a['specialization']) ?></td>
                <td><?= htmlspecialchars($a['reason'] ?: 'General consultation') ?></td>
                <td><span class="badge badge-<?= $a['status'] ?>"><?= ucfirst($a['status']) ?></span></td>
            </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="7" style="text-align:center;padding:30px">No appointments found</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include '../includes/footer.php'; ?>
