<?php
// Patient Dashboard - appointment stats, upcoming schedule, and quick links using shared layout
require_once '../config/db.php';
check_login('patient');

$page_title = 'Patient Dashboard';
$role_color = '#007bff';
$role_class = 'patient';
$patient_id = (int)$_SESSION['user_id'];

$stats = [
    'total_appointments'    => (int)$conn->query("SELECT COUNT(*) c FROM appointments WHERE patient_id=$patient_id")->fetch_assoc()['c'],
    'upcoming_appointments' => (int)$conn->query("SELECT COUNT(*) c FROM appointments WHERE patient_id=$patient_id AND appointment_date>=CURDATE() AND status!='cancelled'")->fetch_assoc()['c'],
    'total_prescriptions'   => (int)$conn->query("SELECT COUNT(*) c FROM prescriptions WHERE patient_id=$patient_id")->fetch_assoc()['c'],
    'pending_lab_tests'     => (int)$conn->query("SELECT COUNT(*) c FROM lab_tests WHERE patient_id=$patient_id AND status IN ('pending','in_progress')")->fetch_assoc()['c'],
];

$upcoming = $conn->query("
    SELECT a.*, d.full_name doctor_name, d.specialization
    FROM appointments a
    JOIN doctors d ON a.doctor_id = d.id
    WHERE a.patient_id=$patient_id AND a.appointment_date>=CURDATE() AND a.status!='cancelled'
    ORDER BY a.appointment_date ASC, a.appointment_time ASC LIMIT 5
");

include '../includes/header.php';
?>

<div class="dashboard-header">
    <div>
        <h1>🧑 Patient Dashboard</h1>
        <p>Welcome, <?= htmlspecialchars($_SESSION['full_name']) ?></p>
        <p>Patient ID: <?= htmlspecialchars($_SESSION['patient_id'] ?? '') ?> &nbsp;|&nbsp; Blood Group: <?= htmlspecialchars($_SESSION['blood_group'] ?? 'N/A') ?></p>
    </div>
</div>

<nav class="nav-menu">
    <ul>
        <li><a href="dashboard.php" class="active">Dashboard</a></li>
        <li><a href="appointments.php">My Appointments</a></li>
        <li><a href="book_appointment.php">Book Appointment</a></li>
        <li><a href="bills.php">My Bills</a></li>
        <li><a href="profile.php">Profile</a></li>
    </ul>
</nav>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-number"><?= $stats['total_appointments'] ?></div>
        <div class="stat-label">Total Appointments</div>
    </div>
    <div class="stat-card">
        <div class="stat-number"><?= $stats['upcoming_appointments'] ?></div>
        <div class="stat-label">Upcoming Appointments</div>
    </div>
    <div class="stat-card">
        <div class="stat-number"><?= $stats['total_prescriptions'] ?></div>
        <div class="stat-label">Prescriptions</div>
    </div>
    <div class="stat-card">
        <div class="stat-number"><?= $stats['pending_lab_tests'] ?></div>
        <div class="stat-label">Pending Lab Tests</div>
    </div>
</div>

<div class="features-grid">
    <div class="feature-card">
        <div class="feature-icon">📅</div>
        <h4>Book Appointment</h4>
        <p>Schedule a new appointment with a doctor.</p>
        <a href="book_appointment.php" class="btn btn-patient">Book Now</a>
    </div>
    <div class="feature-card">
        <div class="feature-icon">📋</div>
        <h4>My Appointments</h4>
        <p>View all your scheduled appointments.</p>
        <a href="appointments.php" class="btn btn-patient">View Appointments</a>
    </div>
    <div class="feature-card">
        <div class="feature-icon">💳</div>
        <h4>My Bills</h4>
        <p>View your medical bills and payment status.</p>
        <a href="bills.php" class="btn btn-patient">View Bills</a>
    </div>
    <div class="feature-card">
        <div class="feature-icon">👤</div>
        <h4>Profile Settings</h4>
        <p>Update your personal information.</p>
        <a href="profile.php" class="btn btn-patient">Update Profile</a>
    </div>
</div>

<div class="table-container">
    <h3>Upcoming Appointments</h3>
    <table class="table">
        <thead>
            <tr><th>Date</th><th>Time</th><th>Doctor</th><th>Specialization</th><th>Status</th></tr>
        </thead>
        <tbody>
        <?php if ($upcoming && $upcoming->num_rows > 0): ?>
            <?php while ($a = $upcoming->fetch_assoc()): ?>
            <tr>
                <td><?= date('M j, Y', strtotime($a['appointment_date'])) ?></td>
                <td><?= date('H:i', strtotime($a['appointment_time'])) ?></td>
                <td>Dr. <?= htmlspecialchars($a['doctor_name']) ?></td>
                <td><?= htmlspecialchars($a['specialization']) ?></td>
                <td><span class="badge badge-<?= $a['status'] ?>"><?= ucfirst($a['status']) ?></span></td>
            </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="5" style="text-align:center;padding:30px">No upcoming appointments</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include '../includes/footer.php'; ?>
