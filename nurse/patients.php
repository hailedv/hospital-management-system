<?php
require_once '../config/db.php';
check_login('nurse');

$page_title = 'Patients';
$role_color = '#e91e63';
$role_class = 'nurse';
$nurse_id = (int)$_SESSION['user_id'];

$patients = $conn->query("
    SELECT DISTINCT p.*,
           COUNT(pv.id) as vitals_count,
           MAX(pv.recorded_at) as last_vitals
    FROM patients p
    JOIN patient_vitals pv ON p.id = pv.patient_id
    WHERE pv.nurse_id = $nurse_id
    GROUP BY p.id
    ORDER BY last_vitals DESC
");

$all_patients = $conn->query("SELECT * FROM patients WHERE status = 'active' ORDER BY full_name");

include '../includes/header.php';
?>

<nav class="nav-menu">
    <ul>
        <li><a href="dashboard.php">Dashboard</a></li>
        <li><a href="patients.php" class="active">Patients</a></li>
        <li><a href="vitals.php">Vitals</a></li>
        <li><a href="medications.php">Medications</a></li>
        <li><a href="patient_notes.php">Nursing Notes</a></li>
    </ul>
</nav>

<div class="table-container">
    <h3>My Assigned Patients</h3>
    <table class="table">
        <thead>
            <tr><th>Patient ID</th><th>Name</th><th>Phone</th><th>Blood Group</th><th>Vitals Recorded</th><th>Last Vitals</th><th>Actions</th></tr>
        </thead>
        <tbody>
        <?php if ($patients && $patients->num_rows > 0): ?>
            <?php while ($p = $patients->fetch_assoc()): ?>
            <tr>
                <td><span style="background:#e3f2fd;color:#1976d2;padding:3px 8px;border-radius:4px;font-size:0.8em"><?= htmlspecialchars($p['patient_id']) ?></span></td>
                <td><?= htmlspecialchars($p['full_name']) ?></td>
                <td><?= htmlspecialchars($p['phone']) ?></td>
                <td><?= $p['blood_group'] ? '<span style="background:#ffebee;color:#c62828;padding:2px 6px;border-radius:3px;font-size:0.8em">'.htmlspecialchars($p['blood_group']).'</span>' : 'N/A' ?></td>
                <td><?= $p['vitals_count'] ?> times</td>
                <td><?= date('M j, Y H:i', strtotime($p['last_vitals'])) ?></td>
                <td>
                    <a href="vitals.php?patient_id=<?= $p['id'] ?>" class="btn btn-sm btn-nurse">Record Vitals</a>
                    <a href="patient_notes.php?patient_id=<?= $p['id'] ?>" class="btn btn-sm btn-nurse">Add Notes</a>
                </td>
            </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="7" style="text-align:center;padding:30px">No patients assigned yet.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<div class="table-container">
    <h3>All Active Patients</h3>
    <table class="table">
        <thead>
            <tr><th>Patient ID</th><th>Name</th><th>Phone</th><th>Blood Group</th><th>Gender</th><th>Actions</th></tr>
        </thead>
        <tbody>
        <?php while ($p = $all_patients->fetch_assoc()): ?>
            <tr>
                <td><span style="background:#e3f2fd;color:#1976d2;padding:3px 8px;border-radius:4px;font-size:0.8em"><?= htmlspecialchars($p['patient_id']) ?></span></td>
                <td><?= htmlspecialchars($p['full_name']) ?></td>
                <td><?= htmlspecialchars($p['phone']) ?></td>
                <td><?= $p['blood_group'] ? '<span style="background:#ffebee;color:#c62828;padding:2px 6px;border-radius:3px;font-size:0.8em">'.htmlspecialchars($p['blood_group']).'</span>' : 'N/A' ?></td>
                <td><?= ucfirst($p['gender']) ?></td>
                <td><a href="vitals.php?patient_id=<?= $p['id'] ?>" class="btn btn-sm btn-nurse">Record Vitals</a></td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</div>

<?php include '../includes/footer.php'; ?>
