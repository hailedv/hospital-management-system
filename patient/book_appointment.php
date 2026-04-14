<?php
require_once '../config/db.php';
check_login('patient');

$page_title = 'Book Appointment';
$role_color = '#007bff';
$role_class = 'patient';
$message = '';
$error = '';
$patient_id = (int)$_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['book_appointment'])) {
    $doctor_id        = (int)$_POST['doctor_id'];
    $appointment_date = $_POST['appointment_date'];
    $appointment_time = $_POST['appointment_time'];
    $reason           = sanitize_input($_POST['reason'] ?? '');
    $urgency          = sanitize_input($_POST['urgency'] ?? 'normal');

    if (empty($doctor_id) || empty($appointment_date) || empty($appointment_time) || empty($reason)) {
        $error = "Please fill in all required fields.";
    } else {
        // Generate unique appointment ID
        do {
            $appointment_id = 'APT' . str_pad(rand(1000, 9999), 4, '0', STR_PAD_LEFT);
            $chk = $conn->prepare("SELECT id FROM appointments WHERE appointment_id=?");
            $chk->bind_param("s", $appointment_id);
            $chk->execute();
        } while ($chk->get_result()->num_rows > 0);

        // Check slot availability
        $slot_chk = $conn->prepare("SELECT id FROM appointments WHERE doctor_id=? AND appointment_date=? AND appointment_time=? AND status!='cancelled'");
        $slot_chk->bind_param("iss", $doctor_id, $appointment_date, $appointment_time);
        $slot_chk->execute();

        if ($slot_chk->get_result()->num_rows > 0) {
            $error = "This appointment slot is already booked. Please choose a different time.";
        } else {
            $stmt = $conn->prepare("INSERT INTO appointments (appointment_id, patient_id, doctor_id, appointment_date, appointment_time, reason, status, booked_by) VALUES (?, ?, ?, ?, ?, ?, 'pending', ?)");
            $stmt->bind_param("siisssi", $appointment_id, $patient_id, $doctor_id, $appointment_date, $appointment_time, $reason, $patient_id);
            if ($stmt->execute()) {
                $message = "Appointment booked! ID: <strong>$appointment_id</strong>. Please arrive 15 minutes early.";
                log_activity('book_appointment', "Patient booked appointment: $appointment_id");
            } else {
                $error = "Failed to book appointment. Please try again.";
            }
        }
    }
}

$doctors = $conn->query("SELECT id, full_name, specialization, consultation_fee FROM doctors WHERE status='active' ORDER BY specialization, full_name");

$upcoming = $conn->query("
    SELECT a.*, d.full_name doctor_name, d.specialization
    FROM appointments a
    JOIN doctors d ON a.doctor_id = d.id
    WHERE a.patient_id=$patient_id AND a.appointment_date>=CURDATE() AND a.status!='cancelled'
    ORDER BY a.appointment_date ASC LIMIT 5
");

include '../includes/header.php';
?>

<div class="dashboard-header">
    <div>
        <h1>📅 Book Appointment</h1>
        <p><?= htmlspecialchars($_SESSION['full_name']) ?></p>
    </div>
</div>

<nav class="nav-menu">
    <ul>
        <li><a href="dashboard.php">Dashboard</a></li>
        <li><a href="appointments.php">My Appointments</a></li>
        <li><a href="book_appointment.php" class="active">Book Appointment</a></li>
        <li><a href="bills.php">My Bills</a></li>
        <li><a href="profile.php">Profile</a></li>
    </ul>
</nav>

<?php if ($message): ?>
    <div class="alert alert-success"><?= $message ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="table-container" style="padding:0">
    <h3>Book New Appointment</h3>
    <div style="padding:24px">
        <form method="POST">
            <div class="form-group">
                <label>Select Doctor <span style="color:red">*</span></label>
                <select name="doctor_id" class="form-control" required>
                    <option value="">Choose a doctor</option>
                    <?php while ($d = $doctors->fetch_assoc()): ?>
                        <option value="<?= $d['id'] ?>">
                            Dr. <?= htmlspecialchars($d['full_name']) ?> (<?= htmlspecialchars($d['specialization']) ?>) — $<?= number_format($d['consultation_fee'], 2) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
                <div class="form-group">
                    <label>Appointment Date <span style="color:red">*</span></label>
                    <input type="date" name="appointment_date" class="form-control" min="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="form-group">
                    <label>Preferred Time <span style="color:red">*</span></label>
                    <select name="appointment_time" class="form-control" required>
                        <option value="">Select time</option>
                        <?php
                        $times = ['09:00:00'=>'09:00 AM','09:30:00'=>'09:30 AM','10:00:00'=>'10:00 AM','10:30:00'=>'10:30 AM',
                                  '11:00:00'=>'11:00 AM','11:30:00'=>'11:30 AM','14:00:00'=>'02:00 PM','14:30:00'=>'02:30 PM',
                                  '15:00:00'=>'03:00 PM','15:30:00'=>'03:30 PM','16:00:00'=>'04:00 PM','16:30:00'=>'04:30 PM'];
                        foreach ($times as $val => $lbl): ?>
                            <option value="<?= $val ?>"><?= $lbl ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label>Urgency</label>
                <select name="urgency" class="form-control">
                    <option value="normal">Normal</option>
                    <option value="urgent">Urgent</option>
                    <option value="emergency">Emergency</option>
                </select>
            </div>
            <div class="form-group">
                <label>Reason for Visit <span style="color:red">*</span></label>
                <textarea name="reason" class="form-control" rows="4" required placeholder="Describe your symptoms or reason for the appointment"></textarea>
            </div>
            <button type="submit" name="book_appointment" class="btn btn-patient">Book Appointment</button>
        </form>
    </div>
</div>

<?php if ($upcoming && $upcoming->num_rows > 0): ?>
<div class="table-container" style="margin-top:20px">
    <h3>Your Upcoming Appointments</h3>
    <table class="table">
        <thead>
            <tr><th>ID</th><th>Doctor</th><th>Date & Time</th><th>Status</th></tr>
        </thead>
        <tbody>
        <?php while ($a = $upcoming->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($a['appointment_id']) ?></td>
                <td>Dr. <?= htmlspecialchars($a['doctor_name']) ?><br><small><?= htmlspecialchars($a['specialization']) ?></small></td>
                <td><?= date('M j, Y', strtotime($a['appointment_date'])) ?> at <?= date('H:i', strtotime($a['appointment_time'])) ?></td>
                <td><span class="badge badge-<?= $a['status'] ?>"><?= ucfirst($a['status']) ?></span></td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>
