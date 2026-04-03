<?php
require_once '../config/db.php';
check_login('patient');

$message = '';
$error = '';

// Handle appointment booking
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['book_appointment'])) {
    $doctor_id = (int)$_POST['doctor_id'];
    $appointment_date = $_POST['appointment_date'];
    $appointment_time = $_POST['appointment_time'];
    $reason = sanitize_input($_POST['reason']);
    $urgency = $_POST['urgency'];
    
    if (empty($doctor_id) || empty($appointment_date) || empty($appointment_time) || empty($reason)) {
        $error = "Please fill in all required fields.";
    } else {
        $patient_id = $_SESSION['user_id'];
        $appointment_id = 'APT' . str_pad(rand(1000, 9999), 4, '0', STR_PAD_LEFT);
        $appointment_datetime = $appointment_date . ' ' . $appointment_time;
        
        // Check if appointment slot is available
        $check_slot = $conn->prepare("SELECT id FROM appointments WHERE doctor_id = ? AND appointment_date = ? AND status != 'cancelled'");
        $check_slot->bind_param("is", $doctor_id, $appointment_datetime);
        $check_slot->execute();
        
        if ($check_slot->get_result()->num_rows > 0) {
            $error = "This appointment slot is already booked. Please choose a different time.";
        } else {
            // Insert appointment
            $stmt = $conn->prepare("INSERT INTO appointments (appointment_id, patient_id, doctor_id, appointment_date, reason, urgency, status, created_at) VALUES (?, ?, ?, ?, ?, ?, 'scheduled', NOW())");
            $stmt->bind_param("siisss", $appointment_id, $patient_id, $doctor_id, $appointment_datetime, $reason, $urgency);
            
            if ($stmt->execute()) {
                $message = "Appointment booked successfully!<br>Appointment ID: <strong>$appointment_id</strong><br>Please arrive 15 minutes early.";
                log_activity('appointment_booking', "Patient booked appointment: $appointment_id");
            } else {
                $error = "Failed to book appointment. Please try again.";
            }
        }
    }
}

// Get available doctors
$doctors = $conn->query("SELECT id, full_name, specialization, consultation_fee FROM doctors WHERE status = 'active' ORDER BY specialization, full_name");

// Get patient's upcoming appointments
$patient_id = $_SESSION['user_id'];
$upcoming_appointments = $conn->query("
    SELECT a.*, d.full_name as doctor_name, d.specialization 
    FROM appointments a 
    JOIN doctors d ON a.doctor_id = d.id 
    WHERE a.patient_id = $patient_id 
    AND a.appointment_date >= NOW() 
    AND a.status != 'cancelled'
    ORDER BY a.appointment_date ASC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Appointment - Patient Portal</title>
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
            color: #333;
        }
        
        .container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .header {
            background: white;
            padding: 25px;
            border-radius: 15px;
            text-align: center;
            margin-bottom: 30px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
        }
        
        .header h1 {
            color: #667eea;
            font-size: 2.2em;
            margin-bottom: 10px;
        }
        
        .nav-bar {
            background: white;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .nav-bar a {
            display: inline-block;
            background: #667eea;
            color: white;
            text-decoration: none;
            padding: 12px 20px;
            margin: 0 8px;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .nav-bar a:hover {
            background: #764ba2;
            transform: translateY(-2px);
        }
        
        .form-container {
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
            margin-bottom: 25px;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }
        
        .form-control {
            width: 100%;
            padding: 15px;
            border: 2px solid #e1e5e9;
            border-radius: 10px;
            font-size: 16px;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .btn {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-size: 18px;
            font-weight: 600;
            transition: all 0.3s ease;
            width: 100%;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
        }
        
        .alert {
            padding: 20px;
            margin: 20px 0;
            border-radius: 10px;
            font-weight: 500;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border-left: 5px solid #28a745;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border-left: 5px solid #dc3545;
        }
        
        .appointments-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .appointments-table th {
            background: #667eea;
            color: white;
            padding: 15px;
            text-align: left;
            font-weight: 600;
        }
        
        .appointments-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #e9ecef;
        }
        
        .appointments-table tr:hover {
            background: #f8f9fa;
        }
        
        .info-card {
            background: #e3f2fd;
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 30px;
            border-left: 5px solid #2196f3;
        }
        
        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .container {
                padding: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📅 Book Appointment</h1>
            <p>Schedule your appointment with our doctors</p>
        </div>
        
        <div class="nav-bar">
            <a href="dashboard.php">🏠 Dashboard</a>
            <a href="appointments.php">📅 My Appointments</a>
            <a href="profile.php">👤 Profile</a>
            <a href="../logout.php">🚪 Logout</a>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success">
                ✅ <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error">
                ❌ <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <div class="info-card">
            <h3>📅 Appointment Booking Guidelines</h3>
            <p><strong>Step 1:</strong> Select your preferred doctor and specialization</p>
            <p><strong>Step 2:</strong> Choose your preferred date and time</p>
            <p><strong>Step 3:</strong> Describe your symptoms or reason for visit</p>
            <p><strong>Step 4:</strong> Select urgency level</p>
            <p><strong>Note:</strong> Please arrive 15 minutes before your appointment time</p>
        </div>

        <div class="form-container">
            <h2 style="color: #667eea; margin-bottom: 25px; text-align: center;">📅 Book New Appointment</h2>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="doctor_id">👨‍⚕️ Select Doctor *</label>
                    <select id="doctor_id" name="doctor_id" class="form-control" required>
                        <option value="">Choose a doctor</option>
                        <?php if ($doctors && $doctors->num_rows > 0): ?>
                            <?php while ($doctor = $doctors->fetch_assoc()): ?>
                                <option value="<?php echo $doctor['id']; ?>">
                                    Dr. <?php echo htmlspecialchars($doctor['full_name']); ?> 
                                    (<?php echo htmlspecialchars($doctor['specialization']); ?>) 
                                    - $<?php echo number_format($doctor['consultation_fee'], 2); ?>
                                </option>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </select>
                </div>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label for="appointment_date">📅 Appointment Date *</label>
                        <input type="date" id="appointment_date" name="appointment_date" class="form-control" 
                               min="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="appointment_time">⏰ Preferred Time *</label>
                        <select id="appointment_time" name="appointment_time" class="form-control" required>
                            <option value="">Select time</option>
                            <option value="09:00:00">09:00 AM</option>
                            <option value="09:30:00">09:30 AM</option>
                            <option value="10:00:00">10:00 AM</option>
                            <option value="10:30:00">10:30 AM</option>
                            <option value="11:00:00">11:00 AM</option>
                            <option value="11:30:00">11:30 AM</option>
                            <option value="14:00:00">02:00 PM</option>
                            <option value="14:30:00">02:30 PM</option>
                            <option value="15:00:00">03:00 PM</option>
                            <option value="15:30:00">03:30 PM</option>
                            <option value="16:00:00">04:00 PM</option>
                            <option value="16:30:00">04:30 PM</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="urgency">🚨 Urgency Level</label>
                    <select id="urgency" name="urgency" class="form-control">
                        <option value="normal">Normal</option>
                        <option value="urgent">Urgent</option>
                        <option value="emergency">Emergency</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="reason">📝 Reason for Visit *</label>
                    <textarea id="reason" name="reason" class="form-control" rows="4" 
                              placeholder="Please describe your symptoms, concerns, or reason for the appointment" required></textarea>
                </div>
                
                <button type="submit" name="book_appointment" class="btn">
                    📅 Book Appointment
                </button>
            </form>
        </div>

        <div class="form-container">
            <h2 style="color: #667eea; margin-bottom: 25px; text-align: center;">📅 Your Upcoming Appointments</h2>
            
            <?php if ($upcoming_appointments && $upcoming_appointments->num_rows > 0): ?>
                <table class="appointments-table">
                    <thead>
                        <tr>
                            <th>Appointment ID</th>
                            <th>Doctor</th>
                            <th>Date & Time</th>
                            <th>Status</th>
                            <th>Urgency</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($appointment = $upcoming_appointments->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($appointment['appointment_id']); ?></td>
                                <td>
                                    Dr. <?php echo htmlspecialchars($appointment['doctor_name']); ?>
                                    <br><small><?php echo htmlspecialchars($appointment['specialization']); ?></small>
                                </td>
                                <td><?php echo date('M j, Y - g:i A', strtotime($appointment['appointment_date'])); ?></td>
                                <td>
                                    <span style="background: #28a745; color: white; padding: 4px 8px; border-radius: 4px; font-size: 0.8em;">
                                        <?php echo ucfirst($appointment['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span style="background: <?php echo $appointment['urgency'] == 'emergency' ? '#dc3545' : ($appointment['urgency'] == 'urgent' ? '#ffc107' : '#6c757d'); ?>; color: white; padding: 4px 8px; border-radius: 4px; font-size: 0.8em;">
                                        <?php echo ucfirst($appointment['urgency'] ?? 'Normal'); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="info-card">
                    <p>You have no upcoming appointments. Book your first appointment using the form above!</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>