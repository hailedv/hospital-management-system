<?php
// Patient Profile - update personal contact and emergency information using shared layout
require_once '../config/db.php';
check_login('patient');

$page_title = 'Profile Settings';
$role_color = '#007bff';
$role_class = 'patient';
$message = '';
$error = '';
$patient_id = (int)$_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $full_name         = sanitize_input($_POST['full_name'] ?? '');
    $phone             = sanitize_input($_POST['phone'] ?? '');
    $email             = sanitize_input($_POST['email'] ?? '');
    $address           = sanitize_input($_POST['address'] ?? '');
    $emergency_contact = sanitize_input($_POST['emergency_contact'] ?? '');
    $emergency_phone   = sanitize_input($_POST['emergency_phone'] ?? '');

    if (empty($full_name) || empty($phone)) {
        $error = "Full name and phone are required.";
    } else {
        $stmt = $conn->prepare("UPDATE patients SET full_name=?, phone=?, email=?, address=?, emergency_contact=?, emergency_phone=? WHERE id=?");
        $stmt->bind_param("ssssssi", $full_name, $phone, $email, $address, $emergency_contact, $emergency_phone, $patient_id);
        if ($stmt->execute()) {
            $message = "Profile updated successfully!";
            $_SESSION['full_name'] = $full_name;
        } else {
            $error = "Error updating profile: " . $conn->error;
        }
    }
}

$stmt = $conn->prepare("SELECT * FROM patients WHERE id=?");
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$patient = $stmt->get_result()->fetch_assoc();

include '../includes/header.php';
?>

<div class="dashboard-header">
    <div>
        <h1>👤 Profile Settings</h1>
        <p><?= htmlspecialchars($_SESSION['full_name']) ?></p>
    </div>
</div>

<nav class="nav-menu">
    <ul>
        <li><a href="dashboard.php">Dashboard</a></li>
        <li><a href="appointments.php">My Appointments</a></li>
        <li><a href="book_appointment.php">Book Appointment</a></li>
        <li><a href="bills.php">My Bills</a></li>
        <li><a href="profile.php" class="active">Profile</a></li>
    </ul>
</nav>

<?php if ($message): ?>
    <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="table-container" style="padding:0">
    <h3>Update Your Profile</h3>
    <div style="padding:24px">
        <div style="background:#e3f2fd;padding:16px;border-radius:6px;margin-bottom:20px">
            <strong>Patient ID:</strong> <?= htmlspecialchars($patient['patient_id']) ?> &nbsp;|&nbsp;
            <strong>DOB:</strong> <?= date('M j, Y', strtotime($patient['date_of_birth'])) ?> &nbsp;|&nbsp;
            <strong>Gender:</strong> <?= ucfirst($patient['gender']) ?> &nbsp;|&nbsp;
            <strong>Blood Group:</strong> <?= $patient['blood_group'] ?: 'Not specified' ?>
        </div>
        <form method="POST">
            <div class="form-group">
                <label>Full Name <span style="color:red">*</span></label>
                <input type="text" name="full_name" class="form-control" value="<?= htmlspecialchars($patient['full_name']) ?>" required>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
                <div class="form-group">
                    <label>Phone <span style="color:red">*</span></label>
                    <input type="tel" name="phone" class="form-control" value="<?= htmlspecialchars($patient['phone']) ?>" required>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($patient['email'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Emergency Contact</label>
                    <input type="text" name="emergency_contact" class="form-control" value="<?= htmlspecialchars($patient['emergency_contact'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Emergency Phone</label>
                    <input type="tel" name="emergency_phone" class="form-control" value="<?= htmlspecialchars($patient['emergency_phone'] ?? '') ?>">
                </div>
            </div>
            <div class="form-group">
                <label>Address</label>
                <textarea name="address" class="form-control" rows="3"><?= htmlspecialchars($patient['address'] ?? '') ?></textarea>
            </div>
            <button type="submit" name="update_profile" class="btn btn-patient">Update Profile</button>
        </form>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
