<?php
require_once '../config/db.php';
check_login('patient');

$message = '';
$error = '';
$patient_id = $_SESSION['user_id'];

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $full_name = isset($_POST['full_name']) ? sanitize_input($_POST['full_name']) : '';
    $phone = isset($_POST['phone']) ? sanitize_input($_POST['phone']) : '';
    $email = isset($_POST['email']) ? sanitize_input($_POST['email']) : '';
    $address = isset($_POST['address']) ? sanitize_input($_POST['address']) : '';
    $emergency_contact = isset($_POST['emergency_contact']) ? sanitize_input($_POST['emergency_contact']) : '';
    $emergency_phone = isset($_POST['emergency_phone']) ? sanitize_input($_POST['emergency_phone']) : '';
    
    if (empty($full_name) || empty($phone)) {
        $error = "Please fill in all required fields.";
    } else {
        $stmt = $conn->prepare("UPDATE patients SET full_name = ?, phone = ?, email = ?, address = ?, emergency_contact = ?, emergency_phone = ? WHERE id = ?");
        $stmt->bind_param("ssssssi", $full_name, $phone, $email, $address, $emergency_contact, $emergency_phone, $patient_id);
        
        if ($stmt->execute()) {
            $message = "Profile updated successfully!";
            $_SESSION['full_name'] = $full_name; // Update session
        } else {
            $error = "Error updating profile: " . $conn->error;
        }
    }
}

// Get current patient data
$patient = $conn->query("SELECT * FROM patients WHERE id = $patient_id")->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Settings - Patient Dashboard</title>
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

        .dashboard-header {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .dashboard-header h1 {
            color: #2196f3;
            font-size: 2em;
        }

        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #2196f3;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-weight: 500;
            border: none;
            cursor: pointer;
        }

        .btn:hover {
            background: #1976d2;
        }

        .btn-danger {
            background: #dc3545;
        }

        .btn-danger:hover {
            background: #c82333;
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
            border-color: #2196f3;
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

        .info-section {
            background: #e3f2fd;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .readonly-field {
            background: #f8f9fa;
            color: #6c757d;
        }

        @media (max-width: 768px) {
            .dashboard-header {
                flex-direction: column;
                text-align: center;
                gap: 15px;
            }

            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="dashboard-header">
            <div>
                <h1>👤 Profile Settings</h1>
                <p><?php echo htmlspecialchars($_SESSION['full_name']); ?></p>
            </div>
            <div>
                <a href="dashboard.php" class="btn">← Back to Dashboard</a>
                <a href="../logout.php" class="btn btn-danger">Logout</a>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="form-container">
            <h3 style="margin-bottom: 20px; color: #2196f3;">Update Your Profile</h3>
            
            <div class="info-section">
                <h4>Account Information</h4>
                <p><strong>Patient ID:</strong> <?php echo htmlspecialchars($patient['patient_id']); ?></p>
                <p><strong>Date of Birth:</strong> <?php echo date('M j, Y', strtotime($patient['date_of_birth'])); ?></p>
                <p><strong>Gender:</strong> <?php echo ucfirst($patient['gender']); ?></p>
                <p><strong>Blood Group:</strong> <?php echo $patient['blood_group'] ?: 'Not specified'; ?></p>
                <p><strong>Registration Date:</strong> <?php echo date('M j, Y', strtotime($patient['created_at'])); ?></p>
            </div>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="full_name">Full Name: <span style="color: red;">*</span></label>
                    <input type="text" name="full_name" id="full_name" class="form-control" 
                           value="<?php echo htmlspecialchars($patient['full_name']); ?>" required>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="phone">Phone Number: <span style="color: red;">*</span></label>
                        <input type="tel" name="phone" id="phone" class="form-control" 
                               value="<?php echo htmlspecialchars($patient['phone']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email Address:</label>
                        <input type="email" name="email" id="email" class="form-control" 
                               value="<?php echo htmlspecialchars($patient['email']); ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="address">Address:</label>
                    <textarea name="address" id="address" class="form-control" rows="3"><?php echo htmlspecialchars($patient['address']); ?></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="emergency_contact">Emergency Contact Name:</label>
                        <input type="text" name="emergency_contact" id="emergency_contact" class="form-control" 
                               value="<?php echo htmlspecialchars($patient['emergency_contact']); ?>">
                    </div>
                    <div class="form-group">
                        <label for="emergency_phone">Emergency Contact Phone:</label>
                        <input type="tel" name="emergency_phone" id="emergency_phone" class="form-control" 
                               value="<?php echo htmlspecialchars($patient['emergency_phone']); ?>">
                    </div>
                </div>

                <button type="submit" name="update_profile" class="btn">Update Profile</button>
            </form>

            <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee;">
                <h4 style="color: #666; margin-bottom: 10px;">Need to update other information?</h4>
                <p style="color: #666;">To update your date of birth, gender, or blood group, please contact the reception desk or call the hospital directly.</p>
            </div>
        </div>
    </div>
</body>
</html>