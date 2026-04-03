<?php
require_once '../config/db.php';
check_login('receptionist');

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = sanitize_input($_POST['full_name']);
    $email = sanitize_input($_POST['email']);
    $phone = sanitize_input($_POST['phone']);
    $date_of_birth = $_POST['date_of_birth'];
    $gender = $_POST['gender'];
    $blood_group = $_POST['blood_group'];
    $address = sanitize_input($_POST['address']);
    $emergency_contact = sanitize_input($_POST['emergency_contact']);
    $emergency_phone = sanitize_input($_POST['emergency_phone']);
    
    // Validate required fields
    if (empty($full_name) || empty($phone) || empty($date_of_birth) || empty($gender)) {
        $error = "Please fill in all required fields.";
    } else {
        try {
            // Generate unique patient ID
            do {
                $patient_id = 'PAT' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
                $check = $conn->prepare("SELECT id FROM patients WHERE patient_id = ?");
                $check->bind_param("s", $patient_id);
                $check->execute();
            } while ($check->get_result()->num_rows > 0);
            
            // Insert patient
            $stmt = $conn->prepare("INSERT INTO patients (patient_id, full_name, email, phone, date_of_birth, gender, blood_group, address, emergency_contact, emergency_phone, registered_by, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')");
            $registered_by = $_SESSION['user_id'];
            $stmt->bind_param("ssssssssssi", $patient_id, $full_name, $email, $phone, $date_of_birth, $gender, $blood_group, $address, $emergency_contact, $emergency_phone, $registered_by);
            
            if ($stmt->execute()) {
                $message = "Patient registered successfully! Patient ID: $patient_id";
                log_activity('register_patient', "Registered new patient: $full_name (ID: $patient_id)");
                
                // Clear form
                $_POST = [];
            } else {
                $error = "Error registering patient: " . $conn->error;
            }
        } catch (Exception $e) {
            $error = "Error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Patient - Hospital Management System</title>
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
            max-width: 900px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 {
            color: #ff9800;
            font-size: 2em;
        }

        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #ff9800;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-weight: 500;
            border: none;
            cursor: pointer;
        }

        .btn:hover {
            background: #f57c00;
        }

        .btn-success {
            background: #28a745;
        }

        .btn-success:hover {
            background: #218838;
        }

        .form-container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .alert {
            padding: 12px;
            margin-bottom: 20px;
            border-radius: 5px;
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

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
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
            border-color: #ff9800;
        }

        .form-section {
            margin-bottom: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .form-section h3 {
            color: #ff9800;
            margin-bottom: 20px;
            border-bottom: 2px solid #ff9800;
            padding-bottom: 10px;
        }

        .gender-options {
            display: flex;
            gap: 20px;
        }

        .gender-option {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .gender-option input[type="radio"] {
            width: 20px;
            height: 20px;
        }

        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                text-align: center;
                gap: 15px;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .gender-options {
                flex-direction: column;
                gap: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div>
                <h1>👤 Register New Patient</h1>
                <p>Add new patient to the hospital system</p>
            </div>
            <div>
                <a href="dashboard.php" class="btn">← Back to Dashboard</a>
            </div>
        </div>

        <div class="form-container">
            <?php if ($message): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-section">
                    <h3>Personal Information</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="full_name">Full Name *</label>
                            <input type="text" id="full_name" name="full_name" class="form-control" required value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>">
                        </div>

                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" class="form-control" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                        </div>

                        <div class="form-group">
                            <label for="phone">Phone Number *</label>
                            <input type="tel" id="phone" name="phone" class="form-control" required value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                        </div>

                        <div class="form-group">
                            <label for="date_of_birth">Date of Birth *</label>
                            <input type="date" id="date_of_birth" name="date_of_birth" class="form-control" required value="<?php echo htmlspecialchars($_POST['date_of_birth'] ?? ''); ?>">
                        </div>

                        <div class="form-group">
                            <label>Gender *</label>
                            <div class="gender-options">
                                <div class="gender-option">
                                    <input type="radio" id="male" name="gender" value="male" <?php echo ($_POST['gender'] ?? '') == 'male' ? 'checked' : ''; ?> required>
                                    <label for="male">Male</label>
                                </div>
                                <div class="gender-option">
                                    <input type="radio" id="female" name="gender" value="female" <?php echo ($_POST['gender'] ?? '') == 'female' ? 'checked' : ''; ?> required>
                                    <label for="female">Female</label>
                                </div>
                                <div class="gender-option">
                                    <input type="radio" id="other" name="gender" value="other" <?php echo ($_POST['gender'] ?? '') == 'other' ? 'checked' : ''; ?> required>
                                    <label for="other">Other</label>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="blood_group">Blood Group</label>
                            <select id="blood_group" name="blood_group" class="form-control">
                                <option value="">Select Blood Group</option>
                                <option value="A+" <?php echo ($_POST['blood_group'] ?? '') == 'A+' ? 'selected' : ''; ?>>A+</option>
                                <option value="A-" <?php echo ($_POST['blood_group'] ?? '') == 'A-' ? 'selected' : ''; ?>>A-</option>
                                <option value="B+" <?php echo ($_POST['blood_group'] ?? '') == 'B+' ? 'selected' : ''; ?>>B+</option>
                                <option value="B-" <?php echo ($_POST['blood_group'] ?? '') == 'B-' ? 'selected' : ''; ?>>B-</option>
                                <option value="AB+" <?php echo ($_POST['blood_group'] ?? '') == 'AB+' ? 'selected' : ''; ?>>AB+</option>
                                <option value="AB-" <?php echo ($_POST['blood_group'] ?? '') == 'AB-' ? 'selected' : ''; ?>>AB-</option>
                                <option value="O+" <?php echo ($_POST['blood_group'] ?? '') == 'O+' ? 'selected' : ''; ?>>O+</option>
                                <option value="O-" <?php echo ($_POST['blood_group'] ?? '') == 'O-' ? 'selected' : ''; ?>>O-</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h3>Contact Information</h3>
                    <div class="form-group">
                        <label for="address">Address</label>
                        <textarea id="address" name="address" class="form-control" rows="3" placeholder="Full address"><?php echo htmlspecialchars($_POST['address'] ?? ''); ?></textarea>
                    </div>

                    <div class="form-grid">
                        <div class="form-group">
                            <label for="emergency_contact">Emergency Contact Name</label>
                            <input type="text" id="emergency_contact" name="emergency_contact" class="form-control" value="<?php echo htmlspecialchars($_POST['emergency_contact'] ?? ''); ?>">
                        </div>

                        <div class="form-group">
                            <label for="emergency_phone">Emergency Contact Phone</label>
                            <input type="tel" id="emergency_phone" name="emergency_phone" class="form-control" value="<?php echo htmlspecialchars($_POST['emergency_phone'] ?? ''); ?>">
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-success">Register Patient</button>
            </form>
        </div>
    </div>
</body>
</html>