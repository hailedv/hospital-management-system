<?php
require_once '../config/db.php';
check_login('nurse');

$page_title = 'Nursing Notes';
$role_color = '#e91e63';
$role_class = 'nurse';
$message = '';
$error = '';
$patient = null;

if (isset($_GET['patient_id'])) {
    $pid = (int)$_GET['patient_id'];
    $stmt = $conn->prepare("SELECT * FROM patients WHERE id = ?");
    $stmt->bind_param("i", $pid);
    $stmt->execute();
    $patient = $stmt->get_result()->fetch_assoc();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_note'])) {
    $patient_id   = isset($_POST['patient_id']) ? (int)$_POST['patient_id'] : 0;
    $notes        = isset($_POST['notes'])     ? sanitize_input($_POST['notes'])     : '';
    $note_type    = isset($_POST['note_type']) ? sanitize_input($_POST['note_type']) : '';
    $nurse_id     = (int)$_SESSION['user_id'];

    if (empty($patient_id))  { $error = "Please select a patient."; }
    elseif (empty($note_type)) { $error = "Please select a note type."; }
    elseif (empty($notes))   { $error = "Please enter your nursing notes."; }
    else {
        $note_content = "[$note_type] " . $notes;
        $stmt = $conn->prepare("INSERT INTO patient_vitals (patient_id, nurse_id, notes) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $patient_id, $nurse_id, $note_content);
        if ($stmt->execute()) {
            $message = "Nursing note added successfully!";
            log_activity('add_nursing_note', "Added nursing note for patient ID $patient_id");
        } else {
            $error = "Error adding note: " . $conn->error;
        }
    }
}

$patients = $conn->query("SELECT * FROM patients WHERE status = 'active' ORDER BY full_name");

$recent_notes = null;
if ($patient) {
    $pid = (int)$patient['id'];
    $recent_notes = $conn->query("
        SELECT pv.notes, pv.recorded_at, n.full_name as nurse_name
        FROM patient_vitals pv
        LEFT JOIN nurses n ON pv.nurse_id = n.id
        WHERE pv.patient_id = $pid AND pv.notes IS NOT NULL AND pv.notes != ''
        ORDER BY pv.recorded_at DESC LIMIT 10
    ");
}

include '../includes/header.php';
?>

<nav class="nav-menu">
    <ul>
        <li><a href="dashboard.php">Dashboard</a></li>
        <li><a href="patients.php">Patients</a></li>
        <li><a href="vitals.php">Vitals</a></li>
        <li><a href="medications.php">Medications</a></li>
        <li><a href="patient_notes.php" class="active">Nursing Notes</a></li>
    </ul>
</nav>

<?php if ($message): ?>
    <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="table-container" style="padding:0">
    <h3>📋 Add Nursing Note</h3>
    <div style="padding:24px">
        <form method="POST">
            <div class="form-group">
                <label>Select Patient:</label>
                <select name="patient_id" class="form-control" required onchange="window.location.href='patient_notes.php?patient_id='+this.value">
                    <option value="">Choose a patient</option>
                    <?php while ($p = $patients->fetch_assoc()): ?>
                        <option value="<?= $p['id'] ?>" <?= ($patient && $patient['id'] == $p['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($p['patient_id'] . ' - ' . $p['full_name']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <?php if ($patient): ?>
                <div style="background:#e3f2fd;padding:12px;border-radius:6px;margin-bottom:16px">
                    <strong><?= htmlspecialchars($patient['full_name']) ?></strong>
                    &nbsp;|&nbsp; ID: <?= htmlspecialchars($patient['patient_id']) ?>
                    &nbsp;|&nbsp; Phone: <?= htmlspecialchars($patient['phone']) ?>
                </div>
                <div class="form-group">
                    <label>Note Type:</label>
                    <select name="note_type" class="form-control" required>
                        <option value="">Select note type</option>
                        <?php foreach (['Assessment','Care Plan','Medication','Observation','Patient Education','Discharge Planning','General'] as $t): ?>
                            <option value="<?= $t ?>"><?= $t ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Nursing Notes:</label>
                    <textarea name="notes" class="form-control" rows="5" required placeholder="Enter detailed nursing observations..."></textarea>
                </div>
                <button type="submit" name="add_note" class="btn btn-nurse">Add Note</button>
            <?php endif; ?>
        </form>
    </div>
</div>

<?php if ($patient && $recent_notes && $recent_notes->num_rows > 0): ?>
<div class="table-container" style="margin-top:20px">
    <h3>Recent Notes for <?= htmlspecialchars($patient['full_name']) ?></h3>
    <?php while ($note = $recent_notes->fetch_assoc()): ?>
        <div style="padding:15px;border-bottom:1px solid #eee">
            <div style="display:flex;justify-content:space-between;margin-bottom:8px">
                <div>
                    <?php
                    if (preg_match('/^\[([^\]]+)\]/', $note['notes'], $m)) {
                        echo '<span style="background:#e91e63;color:white;padding:2px 8px;border-radius:4px;font-size:0.8em">'.htmlspecialchars($m[1]).'</span>';
                        $note_content = preg_replace('/^\[([^\]]+)\]\s*/', '', $note['notes']);
                    } else {
                        $note_content = $note['notes'];
                    }
                    ?>
                    <span style="margin-left:8px;font-weight:600"><?= htmlspecialchars($note['nurse_name'] ?: 'Unknown Nurse') ?></span>
                </div>
                <span style="color:#666;font-size:0.9em"><?= date('M j, Y H:i', strtotime($note['recorded_at'])) ?></span>
            </div>
            <div><?= nl2br(htmlspecialchars($note_content)) ?></div>
        </div>
    <?php endwhile; ?>
</div>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>
