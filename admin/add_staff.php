<?php
require_once '../config/db.php';
check_login('admin');

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $role      = $_POST['role'] ?? '';
    $full_name = trim($_POST['full_name'] ?? '');
    $username  = trim($_POST['username'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $phone     = trim($_POST['phone'] ?? '');
    $password  = password_hash($_POST['password'] ?? '123456', PASSWORD_DEFAULT);
    $extra     = trim($_POST['extra'] ?? '');

    $tables = [
        'doctor'       => 'doctors',
        'nurse'        => 'nurses',
        'receptionist' => 'receptionists',
        'pharmacist'   => 'pharmacists',
        'accountant'   => 'accountants',
        'admin'        => 'admins',
    ];

    if (!isset($tables[$role])) {
        $error = "Invalid role selected.";
    } else {
        $table = $tables[$role];

        // Build extra column based on role
        $extra_col = '';
        $extra_val = '';
        if ($role === 'doctor')       { $extra_col = ', specialization'; $extra_val = ", '$extra'"; }
        elseif ($role === 'nurse')    { $extra_col = ', shift';          $extra_val = ", '$extra'"; }
        elseif ($role === 'receptionist') { $extra_col = ', shift';     $extra_val = ", '$extra'"; }
        elseif ($role === 'pharmacist')   { $extra_col = ', license_number'; $extra_val = ", '$extra'"; }

        $sql = "INSERT INTO $table (full_name, username, email, phone, password$extra_col, status)
                VALUES (?, ?, ?, ?, ?$extra_val, 'active')";

        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("sssss", $full_name, $username, $email, $phone, $password);
            if ($stmt->execute()) {
                $message = "Staff member added successfully!";
                log_activity('add_staff', "Added $role: $username");
            } else {
                $error = "Error: " . $conn->error;
            }
        } else {
            $error = "Query error: " . $conn->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Staff - Hospital Management System</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: #f5f6fa; color: #333; }
        .container { max-width: 600px; margin: 40px auto; padding: 20px; }
        .card { background: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); padding: 30px; }
        h1 { color: #2c5aa0; margin-bottom: 20px; }
        .form-group { margin-bottom: 16px; }
        label { display: block; margin-bottom: 6px; font-weight: 500; }
        input, select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; font-size: 0.95em; }
        input:focus, select:focus { outline: none; border-color: #2c5aa0; }
        .btn { display: inline-block; padding: 10px 20px; border: none; border-radius: 6px; cursor: pointer; font-size: 1em; text-decoration: none; }
        .btn-primary { background: #28a745; color: white; }
        .btn-back { background: #6c757d; color: white; }
        .alert-success { background: #d4edda; color: #155724; padding: 12px; border-radius: 6px; margin-bottom: 16px; }
        .alert-error   { background: #f8d7da; color: #721c24; padding: 12px; border-radius: 6px; margin-bottom: 16px; }
        .actions { display: flex; gap: 10px; margin-top: 10px; }
    </style>
</head>
<body>
<div class="container">
    <div class="card">
        <h1>➕ Add New Staff</h1>

        <?php if ($message): ?>
            <div class="alert-success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>Role</label>
                <select name="role" required onchange="updateExtraField(this.value)">
                    <option value="">-- Select Role --</option>
                    <option value="doctor">Doctor</option>
                    <option value="nurse">Nurse</option>
                    <option value="receptionist">Receptionist</option>
                    <option value="pharmacist">Pharmacist</option>
                    <option value="accountant">Accountant</option>
                    <option value="admin">Admin</option>
                </select>
            </div>
            <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="full_name" required>
            </div>
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" required>
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" required>
            </div>
            <div class="form-group">
                <label>Phone</label>
                <input type="text" name="phone">
            </div>
            <div class="form-group" id="extra_field" style="display:none;">
                <label id="extra_label">Extra Info</label>
                <input type="text" name="extra" id="extra_input">
            </div>
            <div class="form-group">
                <label>Password (default: 123456)</label>
                <input type="password" name="password" placeholder="Leave blank for 123456" value="123456">
            </div>
            <div class="actions">
                <button type="submit" class="btn btn-primary">Add Staff</button>
                <a href="manage_users.php" class="btn btn-back">← Back</a>
            </div>
        </form>
    </div>
</div>
<script>
function updateExtraField(role) {
    const wrap  = document.getElementById('extra_field');
    const label = document.getElementById('extra_label');
    const input = document.getElementById('extra_input');
    const map = {
        doctor: 'Specialization',
        nurse: 'Shift (morning/evening/night)',
        receptionist: 'Shift (morning/evening/night)',
        pharmacist: 'License Number'
    };
    if (map[role]) {
        label.textContent = map[role];
        input.placeholder = map[role];
        wrap.style.display = 'block';
    } else {
        wrap.style.display = 'none';
    }
}
</script>
</body>
</html>
