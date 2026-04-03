<?php
require_once '../config/db.php';
check_login('receptionist');

$page_title = 'Receptionist Dashboard';
$role_color = '#ff9800';
$role_class = 'receptionist';
$rec_id     = $_SESSION['user_id'];

$stats = [
    'registered_today' => (int)$conn->query("SELECT COUNT(*) c FROM patients WHERE DATE(created_at)=CURDATE()")->fetch_assoc()['c'],
    'appts_today'      => (int)$conn->query("SELECT COUNT(*) c FROM appointments WHERE DATE(appointment_date)=CURDATE()")->fetch_assoc()['c'],
    'pending_appts'    => (int)$conn->query("SELECT COUNT(*) c FROM appointments WHERE status='pending'")->fetch_assoc()['c'],
    'total_patients'   => (int)$conn->query("SELECT COUNT(*) c FROM patients WHERE status='active'")->fetch_assoc()['c'],
];

$recent = $conn->query("SELECT * FROM patients WHERE registered_by=$rec_id ORDER BY created_at DESC LIMIT 5");

include '../includes/header.php';
?>

<div class="dashboard-header">
    <div>
        <h1>🧑‍💼 Receptionist Dashboard</h1>
        <p><?= htmlspecialchars($_SESSION['full_name']) ?> &mdash; Shift: <?= ucfirst($_SESSION['shift'] ?? '') ?></p>
    </div>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-number"><?= $stats['registered_today'] ?></div>
        <div class="stat-label">Registered Today</div>
    </div>
    <div class="stat-card">
        <div class="stat-number"><?= $stats['appts_today'] ?></div>
        <div class="stat-label">Appointments Today</div>
    </div>
    <div class="stat-card">
        <div class="stat-number"><?= $stats['pending_appts'] ?></div>
        <div class="stat-label">Pending Appointments</div>
    </div>
    <div class="stat-card">
        <div class="stat-number"><?= $stats['total_patients'] ?></div>
        <div class="stat-label">Total Patients</div>
    </div>
</div>

<div class="features-grid">
    <div class="feature-card">
        <div class="feature-icon">👤</div>
        <h4>Register Patient</h4>
        <p>Register new patients into the system.</p>
        <a href="register_patient.php" class="btn btn-receptionist">Register</a>
    </div>
    <div class="feature-card">
        <div class="feature-icon">📅</div>
        <h4>Book Appointment</h4>
        <p>Schedule appointments with doctors.</p>
        <a href="book_appointment.php" class="btn btn-receptionist">Book</a>
    </div>
    <div class="feature-card">
        <div class="feature-icon">🔍</div>
        <h4>Search Patients</h4>
        <p>Find existing patient records.</p>
        <a href="search_patients.php" class="btn btn-receptionist">Search</a>
    </div>
</div>

<div class="table-container">
    <h3>My Recent Registrations</h3>
    <table class="table">
        <thead>
            <tr><th>Patient ID</th><th>Name</th><th>Phone</th><th>Blood Group</th><th>Registered</th></tr>
        </thead>
        <tbody>
        <?php if ($recent && $recent->num_rows > 0): ?>
            <?php while ($p = $recent->fetch_assoc()): ?>
            <tr>
                <td><span style="background:#e3f2fd;color:#1976d2;padding:3px 8px;border-radius:4px;font-size:0.8em"><?= htmlspecialchars($p['patient_id']) ?></span></td>
                <td><?= htmlspecialchars($p['full_name']) ?></td>
                <td><?= htmlspecialchars($p['phone']) ?></td>
                <td><?= htmlspecialchars($p['blood_group'] ?: '-') ?></td>
                <td><?= date('M j, H:i', strtotime($p['created_at'])) ?></td>
            </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="5" style="text-align:center;padding:30px">No registrations yet</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include '../includes/footer.php'; ?>
