<?php
require_once '../config/db.php';
check_login('doctor');

$page_title = 'Doctor Dashboard';
$role_color = '#28a745';
$role_class = 'doctor';
$doctor_id  = (int)$_SESSION['user_id'];

$stats = [
    'patients'      => (int)$conn->query("SELECT COUNT(DISTINCT patient_id) c FROM appointments WHERE doctor_id=$doctor_id")->fetch_assoc()['c'],
    'today'         => (int)$conn->query("SELECT COUNT(*) c FROM appointments WHERE doctor_id=$doctor_id AND DATE(appointment_date)=CURDATE()")->fetch_assoc()['c'],
    'pending'       => (int)$conn->query("SELECT COUNT(*) c FROM appointments WHERE doctor_id=$doctor_id AND status='pending'")->fetch_assoc()['c'],
    'prescriptions' => (int)$conn->query("SELECT COUNT(*) c FROM prescriptions WHERE doctor_id=$doctor_id")->fetch_assoc()['c'],
];

$today_appts = $conn->query("
    SELECT a.*, p.full_name patient_name, p.phone
    FROM appointments a
    JOIN patients p ON a.patient_id = p.id
    WHERE a.doctor_id=$doctor_id AND DATE(a.appointment_date)=CURDATE()
    ORDER BY a.appointment_time ASC
");

include '../includes/header.php';
?>

<div class="dashboard-header">
    <div>
        <h1>👨‍⚕️ Doctor Dashboard</h1>
        <p>Dr. <?= htmlspecialchars($_SESSION['full_name']) ?> &mdash; <?= htmlspecialchars($_SESSION['specialization'] ?? '') ?></p>
    </div>
</div>

<nav class="nav-menu">
    <ul>
        <li><a href="dashboard.php" class="active">Dashboard</a></li>
        <li><a href="patients.php">Patients</a></li>
        <li><a href="appointments.php">Appointments</a></li>
        <li><a href="prescriptions.php">Prescriptions</a></li>
        <li><a href="medical_records.php">Medical Records</a></li>
        <li><a href="send_referral.php">Referrals</a></li>
        <li><a href="notifications.php">Notifications</a></li>
    </ul>
</nav>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-number"><?= $stats['patients'] ?></div>
        <div class="stat-label">Total Patients</div>
    </div>
    <div class="stat-card">
        <div class="stat-number"><?= $stats['today'] ?></div>
        <div class="stat-label">Today's Appointments</div>
    </div>
    <div class="stat-card">
        <div class="stat-number"><?= $stats['pending'] ?></div>
        <div class="stat-label">Pending</div>
    </div>
    <div class="stat-card">
        <div class="stat-number"><?= $stats['prescriptions'] ?></div>
        <div class="stat-label">Prescriptions</div>
    </div>
</div>

<div class="features-grid">
    <div class="feature-card">
        <div class="feature-icon">👥</div>
        <h4>My Patients</h4>
        <p>View and manage your assigned patients.</p>
        <a href="patients.php" class="btn btn-doctor">View Patients</a>
    </div>
    <div class="feature-card">
        <div class="feature-icon">💊</div>
        <h4>Prescriptions</h4>
        <p>Create and manage prescriptions.</p>
        <a href="prescriptions.php" class="btn btn-doctor">Prescriptions</a>
    </div>
    <div class="feature-card">
        <div class="feature-icon">📅</div>
        <h4>Appointments</h4>
        <p>View your schedule.</p>
        <a href="appointments.php" class="btn btn-doctor">Schedule</a>
    </div>
    <div class="feature-card">
        <div class="feature-icon">📋</div>
        <h4>Medical Records</h4>
        <p>Access patient histories.</p>
        <a href="medical_records.php" class="btn btn-doctor">Records</a>
    </div>
    <div class="feature-card">
        <div class="feature-icon">🏥</div>
        <h4>Send Referral</h4>
        <p>Refer patients to specialists.</p>
        <a href="send_referral.php" class="btn btn-doctor">Referral</a>
    </div>
    <div class="feature-card">
        <div class="feature-icon">🔔</div>
        <h4>Notifications</h4>
        <p>Incoming referrals and alerts.</p>
        <a href="notifications.php" class="btn btn-doctor">Notifications</a>
    </div>
</div>

<div class="table-container">
    <h3>Today's Appointments</h3>
    <table class="table">
        <thead>
            <tr><th>Time</th><th>Patient</th><th>Phone</th><th>Reason</th><th>Status</th><th>Action</th></tr>
        </thead>
        <tbody>
        <?php if ($today_appts && $today_appts->num_rows > 0): ?>
            <?php while ($a = $today_appts->fetch_assoc()): ?>
            <tr>
                <td><?= date('H:i', strtotime($a['appointment_time'])) ?></td>
                <td><?= htmlspecialchars($a['patient_name']) ?></td>
                <td><?= htmlspecialchars($a['phone']) ?></td>
                <td><?= htmlspecialchars($a['reason'] ?: 'General consultation') ?></td>
                <td><span class="badge badge-<?= $a['status'] ?>"><?= ucfirst($a['status']) ?></span></td>
                <td><a href="prescriptions.php?patient_id=<?= $a['patient_id'] ?>" class="btn btn-sm btn-doctor">Prescribe</a></td>
            </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="6" style="text-align:center;padding:30px">No appointments today</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include '../includes/footer.php'; ?>
