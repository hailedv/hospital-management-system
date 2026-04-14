<?php
require_once '../config/db.php';
check_login('nurse');

$page_title = 'Nurse Dashboard';
$role_color = '#e91e63';
$role_class = 'nurse';
$nurse_id   = (int)$_SESSION['user_id'];

$stats = [
    'patients' => (int)$conn->query("SELECT COUNT(DISTINCT patient_id) c FROM patient_vitals WHERE nurse_id=$nurse_id")->fetch_assoc()['c'],
    'vitals'   => (int)$conn->query("SELECT COUNT(*) c FROM patient_vitals WHERE nurse_id=$nurse_id AND DATE(recorded_at)=CURDATE()")->fetch_assoc()['c'],
];

$recent_vitals = $conn->query("
    SELECT pv.*, p.full_name patient_name
    FROM patient_vitals pv
    JOIN patients p ON pv.patient_id = p.id
    WHERE pv.nurse_id=$nurse_id
    ORDER BY pv.recorded_at DESC LIMIT 10
");

include '../includes/header.php';
?>

<div class="dashboard-header">
    <div>
        <h1>👩‍⚕️ Nurse Dashboard</h1>
        <p><?= htmlspecialchars($_SESSION['full_name']) ?> &mdash; Shift: <?= ucfirst($_SESSION['shift'] ?? '') ?></p>
    </div>
</div>

<nav class="nav-menu">
    <ul>
        <li><a href="dashboard.php" class="active">Dashboard</a></li>
        <li><a href="patients.php">Patients</a></li>
        <li><a href="vitals.php">Vitals</a></li>
        <li><a href="medications.php">Medications</a></li>
        <li><a href="patient_notes.php">Nursing Notes</a></li>
    </ul>
</nav>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-number"><?= $stats['patients'] ?></div>
        <div class="stat-label">Assigned Patients</div>
    </div>
    <div class="stat-card">
        <div class="stat-number"><?= $stats['vitals'] ?></div>
        <div class="stat-label">Vitals Recorded Today</div>
    </div>
</div>

<div class="features-grid">
    <div class="feature-card">
        <div class="feature-icon">�</div>
        <h4>Record Vitals</h4>
        <p>Record and track patient vital signs.</p>
        <a href="vitals.php" class="btn btn-nurse">Record Vitals</a>
    </div>
    <div class="feature-card">
        <div class="feature-icon">�👥</div>
        <h4>Patients</h4>
        <p>View patients assigned to your care.</p>
        <a href="patients.php" class="btn btn-nurse">View Patients</a>
    </div>
    <div class="feature-card">
        <div class="feature-icon">💉</div>
        <h4>Medications</h4>
        <p>Track and administer prescribed medications.</p>
        <a href="medications.php" class="btn btn-nurse">Medications</a>
    </div>
    <div class="feature-card">
        <div class="feature-icon">📋</div>
        <h4>Nursing Notes</h4>
        <p>Add observations and patient care notes.</p>
        <a href="patient_notes.php" class="btn btn-nurse">Add Notes</a>
    </div>
</div>

<div class="table-container">
    <h3>Recent Vitals Recorded</h3>
    <table class="table">
        <thead>
            <tr><th>Patient</th><th>Temperature</th><th>Blood Pressure</th><th>Heart Rate</th><th>Recorded At</th></tr>
        </thead>
        <tbody>
        <?php if ($recent_vitals && $recent_vitals->num_rows > 0): ?>
            <?php while ($v = $recent_vitals->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($v['patient_name']) ?></td>
                <td><?= $v['temperature'] ? $v['temperature'].'°C' : '-' ?></td>
                <td><?= ($v['blood_pressure_systolic'] && $v['blood_pressure_diastolic']) ? $v['blood_pressure_systolic'].'/'.$v['blood_pressure_diastolic'] : '-' ?></td>
                <td><?= $v['heart_rate'] ? $v['heart_rate'].' bpm' : '-' ?></td>
                <td><?= date('M j, Y H:i', strtotime($v['recorded_at'])) ?></td>
            </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="5" style="text-align:center;padding:30px">No vitals recorded yet</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include '../includes/footer.php'; ?>
