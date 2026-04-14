<?php
// Login page - handles all 8 roles: admin, doctor, nurse, receptionist, pharmacist, accountant, lab_technician, patient
require_once 'config/db.php';

$error_message = '';

// Handle login
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = sanitize_input($_POST['username']);
    $password = $_POST['password'];
    $user_type = $_POST['user_type'];
    
    if (empty($username) || empty($password) || empty($user_type)) {
        $error_message = "Please fill in all fields.";
    } else {
        // Define user tables (7 staff actors + patients)
        $tables = [
            'admin'          => 'admins',
            'doctor'         => 'doctors',
            'nurse'          => 'nurses',
            'receptionist'   => 'receptionists',
            'pharmacist'     => 'pharmacists',
            'accountant'     => 'accountants',
            'lab_technician' => 'lab_technicians',
            'patient'        => 'patients',
        ];
        
        if (isset($tables[$user_type])) {
            $table = $tables[$user_type];
            
            // Check if table exists first
            $table_check = $conn->query("SHOW TABLES LIKE '$table'");
            if ($table_check && $table_check->num_rows > 0) {
                $stmt = $conn->prepare("SELECT * FROM $table WHERE username = ? AND status = 'active'");
                if ($stmt) {
                    $stmt->bind_param("s", $username);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    
                    if ($result->num_rows == 1) {
                        $user = $result->fetch_assoc();
                        
                        if (password_verify($password, $user['password'])) {
                            // Set session
                            $_SESSION['user_id'] = $user['id'];
                            $_SESSION['user_type'] = $user_type;
                            $_SESSION['username'] = $user['username'];
                            $_SESSION['full_name'] = $user['full_name'];
                            $_SESSION['email'] = $user['email'];
                            
                            // Set additional fields
                            if ($user_type == 'doctor') {
                                $_SESSION['specialization'] = $user['specialization'];
                            } elseif ($user_type == 'nurse') {
                                $_SESSION['shift'] = $user['shift'];
                            } elseif ($user_type == 'receptionist') {
                                $_SESSION['shift'] = $user['shift'];
                            } elseif ($user_type == 'pharmacist') {
                                $_SESSION['license_number'] = $user['license_number'];
                            } elseif ($user_type == 'lab_technician') {
                                $_SESSION['license_number'] = $user['license_number'];
                                $_SESSION['specialization'] = $user['specialization'];
                            } elseif ($user_type == 'patient') {
                                $_SESSION['patient_id']  = $user['patient_id'];
                                $_SESSION['blood_group'] = $user['blood_group'];
                            }
                            
                            // Log activity
                            log_activity('login', 'User logged in successfully');
                            
                            // Redirect to dashboard
                            header("Location: $user_type/dashboard.php");
                            exit();
                        } else {
                            $error_message = "Invalid username or password.";
                        }
                    } else {
                        $error_message = "Invalid username or password.";
                    }
                    $stmt->close();
                } else {
                    $error_message = "Database query error. Please try again.";
                }
            } else {
                $error_message = "User type '$user_type' not available. Please run complete setup first.";
            }
        } else {
            $error_message = "Invalid user type.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Login - Hospital Management System</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-container {
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 400px;
        }

        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .login-header h1 {
            color: #2c5aa0;
            margin-bottom: 10px;
            font-size: 2em;
        }

        .login-header p {
            color: #666;
            font-size: 1em;
        }

        .alert {
            padding: 12px;
            margin-bottom: 20px;
            border-radius: 5px;
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
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
            border-color: #2c5aa0;
        }

        .btn-login {
            width: 100%;
            background: linear-gradient(135deg, #2c5aa0, #1e3d6f);
            color: white;
            padding: 14px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-login:hover {
            transform: translateY(-1px);
            box-shadow: 0 5px 15px rgba(44, 90, 160, 0.3);
        }

        .back-link {
            text-align: center;
            margin-top: 20px;
        }

        .back-link a {
            color: #2c5aa0;
            text-decoration: none;
            font-weight: 500;
        }

        .back-link a:hover {
            text-decoration: underline;
        }

        .user-types {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
            margin-bottom: 20px;
        }

        .user-type-card {
            padding: 15px;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }

        .user-type-card:hover {
            border-color: #2c5aa0;
            background: #f8f9ff;
        }

        .user-type-card.selected {
            border-color: #2c5aa0;
            background: #2c5aa0;
            color: white;
        }

        .user-type-icon {
            font-size: 2em;
            margin-bottom: 5px;
        }

        .user-type-name {
            font-size: 0.9em;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>🏥 Staff Login</h1>
            <p>Hospital Management System</p>
        </div>

        <?php if ($error_message): ?>
            <div class="alert"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label>Select Your Role:</label>
                <div class="user-types">
                    <div class="user-type-card" onclick="selectUserType('admin')">
                        <div class="user-type-icon">🔑</div>
                        <div class="user-type-name">Admin</div>
                    </div>
                    <div class="user-type-card" onclick="selectUserType('doctor')">
                        <div class="user-type-icon">👨‍⚕️</div>
                        <div class="user-type-name">Doctor</div>
                    </div>
                    <div class="user-type-card" onclick="selectUserType('nurse')">
                        <div class="user-type-icon">👩‍⚕️</div>
                        <div class="user-type-name">Nurse</div>
                    </div>
                    <div class="user-type-card" onclick="selectUserType('receptionist')">
                        <div class="user-type-icon">🧑‍💼</div>
                        <div class="user-type-name">Receptionist</div>
                    </div>
                    <div class="user-type-card" onclick="selectUserType('pharmacist')">
                        <div class="user-type-icon">💊</div>
                        <div class="user-type-name">Pharmacist</div>
                    </div>
                    <div class="user-type-card" onclick="selectUserType('accountant')">
                        <div class="user-type-icon">💰</div>
                        <div class="user-type-name">Accountant</div>
                    </div>
                    <div class="user-type-card" onclick="selectUserType('lab_technician')">
                        <div class="user-type-icon">🔬</div>
                        <div class="user-type-name">Lab Tech</div>
                    </div>
                    <div class="user-type-card" onclick="selectUserType('patient')">
                        <div class="user-type-icon">👤</div>
                        <div class="user-type-name">Patient</div>
                    </div>
                </div>
                <input type="hidden" name="user_type" id="user_type" required>
            </div>

            <div class="form-group">
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" class="form-control" required>
            </div>

            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" class="form-control" required>
            </div>

            <button type="submit" class="btn-login">Login</button>
        </form>

        <div class="back-link">
            <a href="index.php">← Back to Home</a>
        </div>
    </div>

    <script>
        function selectUserType(type) {
            // Remove selected class from all cards
            document.querySelectorAll('.user-type-card').forEach(card => {
                card.classList.remove('selected');
            });
            
            // Add selected class to clicked card
            event.currentTarget.classList.add('selected');
            
            // Set hidden input value
            document.getElementById('user_type').value = type;
        }
    </script>
</body>
</html>