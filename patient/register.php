<?php
require_once '../config/db.php';

$message = '';
$error = '';

// Handle patient registration
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = sanitize_input($_POST['full_name']);
    $email = sanitize_input($_POST['email']);
    $phone = sanitize_input($_POST['phone']);
    $date_of_birth = $_POST['date_of_birth'];
    $gender = $_POST['gender'];
    $blood_group = sanitize_input($_POST['blood_group']);
    $address = sanitize_input($_POST['address']);
    $emergency_contact = sanitize_input($_POST['emergency_contact']);
    $emergency_phone = sanitize_input($_POST['emergency_phone']);
    $medical_history = sanitize_input($_POST['medical_history']);
    $allergies = sanitize_input($_POST['allergies']);
    $current_medications = sanitize_input($_POST['current_medications']);
    
    // Create username and password
    $username = strtolower(str_replace(' ', '', $full_name)) . rand(100, 999);
    $password = 'patient' . rand(1000, 9999);
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Generate patient ID
    do {
        $patient_id = 'PAT' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        $check = $conn->prepare("SELECT id FROM patients WHERE patient_id = ?");
        $check->bind_param("s", $patient_id);
        $check->execute();
    } while ($check->get_result()->num_rows > 0);
    
    if (empty($full_name) || empty($phone) || empty($date_of_birth) || empty($gender)) {
        $error = "Please fill in all required fields.";
    } else {
        // Insert patient
        $stmt = $conn->prepare("INSERT INTO patients (patient_id, username, password, full_name, email, phone, date_of_birth, gender, blood_group, address, emergency_contact, emergency_phone, medical_history, allergies, current_medications, registered_by, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, 'active')");
        
        $stmt->bind_param("sssssssssssssss", $patient_id, $username, $hashed_password, $full_name, $email, $phone, $date_of_birth, $gender, $blood_group, $address, $emergency_contact, $emergency_phone, $medical_history, $allergies, $current_medications);
        
        if ($stmt->execute()) {
            $message = "Patient registered successfully!<br>Patient ID: <strong>$patient_id</strong><br>Username: <strong>$username</strong><br>Password: <strong>$password</strong><br><small>Please save these credentials for the patient.</small>";
            log_activity('register_patient', "Registered new patient: $patient_id - $full_name");
        } else {
            $error = "Error registering patient: " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Registration - Hospital Management System</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f6fa;
            color: #333;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            text-align: center;
        }

        .header h1 {
            color: #2196f3;
            font-size: 2em;
            margin-bottom: 10px;
        }

        .form-container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }

        .required {
            color: #dc3545;
        }

        .form-control {
            width: 100%;
            padding: 12px;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s;
        }

        .form-control:focus {
            outline: none;
            border-color: #2196f3;
        }

        .btn {
            display: inline-block;
            padding: 12px 30px;
            background: #2196f3;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 500;
            border: none;
            cursor: pointer;
            font-size: 16px;
        }

        .btn:hover {
            background: #1976d2;
        }

        .btn-secondary {
            background: #6c757d;
        }

        .btn-secondary:hover {
            background: #5a6268;
        }

        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .section-title {
            background: #f8f9fa;
            padding: 15px;
            margin: 20px -30px;
            font-weight: 600;
            color: #495057;
            border-left: 4px solid #2196f3;
        }

        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>👤 Patient Registration</h1>
            <p>Register new patients in the hospital management system</p>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="form-container">
            <form method="POST" action="">
                <div class="section-title">Personal Information</div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="full_name">Full Name <span class="required">*</span>:</label>
                        <input type="text" name="full_name" id="full_name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email Address:</label>
                        <input type="email" name="email" id="email" class="form-control">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="phone">Phone Number <span class="required">*</span>:</label>
                        <input type="tel" name="phone" id="phone" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="date_of_birth">Date of Birth <span class="required">*</span>:</label>
                        <input type="date" name="date_of_birth" id="date_of_birth" class="form-control" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="gender">Gender <span class="required">*</span>:</label>
                        <select name="gender" id="gender" class="form-control" required>
                            <option value="">Select Gender</option>
                            <option value="male">Male</option>
                            <option value="female">Female</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="blood_group">Blood Group:</label>
                        <select name="blood_group" id="blood_group" class="form-control">
                            <option value="">Select Blood Group</option>
                            <option value="A+">A+</option>
                            <option value="A-">A-</option>
                            <option value="B+">B+</option>
                            <option value="B-">B-</option>
                            <option value="AB+">AB+</option>
                            <option value="AB-">AB-</option>
                            <option value="O+">O+</option>
                            <option value="O-">O-</option>
                        </select>
                    </div>
                </div>

                <div class="form-group full-width">
                    <label for="address">Address:</label>
                    <textarea name="address" id="address" class="form-control" rows="3" placeholder="Complete address with city, state, postal code"></textarea>
                </div>

                <div class="section-title">Emergency Contact</div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="emergency_contact">Emergency Contact Name:</label>
                        <input type="text" name="emergency_contact" id="emergency_contact" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="emergency_phone">Emergency Contact Phone:</label>
                        <input type="tel" name="emergency_phone" id="emergency_phone" class="form-control">
                    </div>
                </div>

                <div class="section-title">Medical Information</div>

                <div class="form-group">
                    <label for="medical_history">Medical History:</label>
                    <textarea name="medical_history" id="medical_history" class="form-control" rows="3" placeholder="Previous surgeries, chronic conditions, family medical history, etc."></textarea>
                </div>

                <div class="form-group">
                    <label for="allergies">Known Allergies:</label>
                    <textarea name="allergies" id="allergies" class="form-control" rows="2" placeholder="Drug allergies, food allergies, environmental allergies, etc."></textarea>
                </div>

                <div class="form-group">
                    <label for="current_medications">Current Medications:</label>
                    <textarea name="current_medications" id="current_medications" class="form-control" rows="2" placeholder="List all current medications with dosages"></textarea>
                </div>

                <div style="text-align: center; margin-top: 30px;">
                    <button type="submit" class="btn">👤 Register Patient</button>
                    <a href="../index.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>