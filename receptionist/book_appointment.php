<?php
require_once '../config/db.php';
check_login('receptionist');

$message = '';
$error = '';

// Handle appointment booking
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $patient_id = (int)$_POST['patient_id'];
    $doctor_id = (int)$_POST['doctor_id'];
    $appointment_date = $_POST['appointment_date'];
    $appointment_time = $_POST['appointment_time'];
    $reason = sanitize_input($_POST['reason']);
    
    if (empty($patient_id) || empty($doctor_id) || empty($appointment_date) || empty($appointment_time)) {
        $error = "Please fill in all required fields.";
    } else {
        // Check if appointment slot is available
        $check_stmt = $conn->prepare("SELECT id FROM appointments WHERE doctor_id = ? AND appointment_date = ? AND appointment_time = ? AND status != 'cancelled'");
        $check_stmt->bind_param("iss", $doctor_id, $appointment_date, $appointment_time);
        $check_stmt->execute();
        
        if ($check_stmt->get_result()->num_rows > 0) {
            $error = "This time slot is already booked. Please choose a different time.";
        } else {
            // Generate appointment ID
            do {
                $appointment_id = 'APT' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
                $check = $conn->prepare("SELECT id FROM appointments WHERE appointment_id = ?");
                $check->bind_param("s", $appointment_id);
                $check->execute();
            } while ($check->get_result()->num_rows > 0);
            
            // Insert appointment
            $stmt = $conn->prepare("INSERT INTO appointments (appointment_id, patient_id, doctor_id, appointment_date, appointment_time, reason, status, booked_by) VALUES (?, ?, ?, ?, ?, ?, 'pending', ?)");
            $booked_by = $_SESSION['user_id'];
            $stmt->bind_param("siisssi", $appointment_id, $patient_id, $doctor_id, $appointment_date, $appointment_time, $reason, $booked_by);
            
            if ($stmt->execute()) {
                $message = "Appointment booked successfully! Appointment ID: $appointment_id";
                log_activity('book_appointment', "Booked appointment $appointment_id for patient ID $patient_id with doctor ID $doctor_id");
            } else {
                $error = "Error booking appointment: " . $conn->error;
            }
        }
    }
}

// Get all active patients
$patients = $conn->query("SELECT * FROM patients WHERE status = 'active' ORDER BY full_name");

// Get all active doctors
$doctors = $conn->query("SELECT * FROM doctors WHERE status = 'active' ORDER BY full_name");

// Get today's appointments for reference
$today_appointments = $conn->query("
    SELECT a.*, p.full_name as patient_name, d.full_name as doctor_name 
    FROM appointments a 
    JOIN patients p ON a.patient_id = p.id 
    JOIN doctors d ON a.doctor_id = d.id 
    WHERE DATE(a.appointment_date) = CURDATE() 
    ORDER BY a.appointment_time ASC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Appointment - Receptionist Dashboard</title>
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
            max-width: 1200px;
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
            margin-bottom: 30px;
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
            border-color: #ff9800;
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

        .appointments-table {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
        }

        .table th,
        .table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #333;
        }

        .table tbody tr:hover {
            background: #f8f9fa;
        }

        .badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.8em;
            font-weight: 600;
        }

        .badge-pending {
            background: #fff3cd;
            color: #856404;
        }

        .badge-confirmed {
            background: #d4edda;
            color: #155724;
        }

        @media (max-width: 768px) {
            .header {
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
        <div class="header">
            <div>
                <h1>📅 Book Appointment</h1>
                <p>Schedule patient appointments with doctors</p>
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
            <h3 style="margin-bottom: 20px; color: #ff9800;">New Appointment</h3>
            <form method="POST" action="">
                <div class="form-row">
                    <div class="form-group">
                        <label for="patient_id">Select Patient:</label>
                        <select name="patient_id" id="patient_id" class="form-control" required>
                            <option value="">Choose a patient</option>
                            <?php while ($patient = $patients->fetch_assoc()): ?>
                                <option value="<?php echo $patient['id']; ?>">
                                    <?php echo htmlspecialchars($patient['patient_id'] . ' - ' . $patient['full_name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="doctor_id">Select Doctor:</label>
                        <select name="doctor_id" id="doctor_id" class="form-control" required>
                            <option value="">Choose a doctor</option>
                            <?php while ($doctor = $doctors->fetch_assoc()): ?>
                                <option value="<?php echo $doctor['id']; ?>">
                                    <?php echo htmlspecialchars('Dr. ' . $doctor['full_name'] . ' (' . $doctor['specialization'] . ')'); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="appointment_date">Appointment Date:</label>
                        <input type="date" name="appointment_date" id="appointment_date" class="form-control" 
                               min="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="appointment_time">Appointment Time:</label>
                        <select name="appointment_time" id="appointment_time" class="form-control" required>
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
                    <label for="reason">Reason for Visit:</label>
                    <textarea name="reason" id="reason" class="form-control" rows="3" 
                              placeholder="Brief description of the reason for appointment"></textarea>
                </div>

                <button type="submit" class="btn">Book Appointment</button>
            </form>
        </div>

        <div class="appointments-table">
            <h3 style="padding: 20px; margin: 0; background: #f8f9fa; border-bottom: 1px solid #e9ecef;">
                Today's Appointments
            </h3>
            <table class="table">
                <thead>
                    <tr>
                        <th>Time</th>
                        <th>Patient</th>
                        <th>Doctor</th>
                        <th>Reason</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($today_appointments->num_rows > 0): ?>
                        <?php while ($appointment = $today_appointments->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo date('H:i', strtotime($appointment['appointment_time'])); ?></td>
                                <td><?php echo htmlspecialchars($appointment['patient_name']); ?></td>
                                <td><?php echo htmlspecialchars($appointment['doctor_name']); ?></td>
                                <td><?php echo htmlspecialchars($appointment['reason'] ?: 'General consultation'); ?></td>
                                <td>
                                    <span class="badge badge-<?php echo $appointment['status']; ?>">
                                        <?php echo ucfirst($appointment['status']); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="text-align: center; padding: 40px;">No appointments for today</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>