<?php
require_once '../config/db.php';
check_login('doctor');

$doctor_id = $_SESSION['user_id'];

// Get today's date
$today = date('Y-m-d');

// Get doctor's appointments
$appointments = $conn->query("
    SELECT a.*, p.full_name as patient_name, p.patient_id, p.phone, r.full_name as booked_by_name
    FROM appointments a
    JOIN patients p ON a.patient_id = p.id
    LEFT JOIN receptionists r ON a.booked_by = r.id
    WHERE a.doctor_id = $doctor_id
    ORDER BY a.appointment_date DESC, a.appointment_time ASC
");

// Get today's appointments
$today_appointments = $conn->query("
    SELECT a.*, p.full_name as patient_name, p.patient_id, p.phone
    FROM appointments a
    JOIN patients p ON a.patient_id = p.id
    WHERE a.doctor_id = $doctor_id AND a.appointment_date = '$today'
    ORDER BY a.appointment_time ASC
");

// Get appointment statistics
$stats = $conn->query("
    SELECT 
        COUNT(*) as total_appointments,
        SUM(CASE WHEN appointment_date = '$today' THEN 1 ELSE 0 END) as today_appointments,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_appointments,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_appointments
    FROM appointments 
    WHERE doctor_id = $doctor_id
")->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Appointments - Doctor Dashboard</title>
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
            color: #4caf50;
            font-size: 2em;
        }

        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #4caf50;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-weight: 500;
            border: none;
            cursor: pointer;
        }

        .btn:hover {
            background: #45a049;
        }

        .btn-danger {
            background: #dc3545;
        }

        .btn-danger:hover {
            background: #c82333;
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 0.9em;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }

        .stat-number {
            font-size: 2em;
            font-weight: bold;
            color: #4caf50;
            margin-bottom: 5px;
        }

        .stat-label {
            color: #666;
            font-size: 0.9em;
        }

        .table-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
            margin-bottom: 30px;
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
            background: #cce5ff;
            color: #004085;
        }

        .badge-completed {
            background: #d4edda;
            color: #155724;
        }

        .badge-cancelled {
            background: #f8d7da;
            color: #721c24;
        }

        .today-highlight {
            background: #e8f5e8 !important;
        }

        @media (max-width: 768px) {
            .dashboard-header {
                flex-direction: column;
                text-align: center;
                gap: 15px;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="dashboard-header">
            <div>
                <h1>📅 My Appointments</h1>
                <p><?php echo htmlspecialchars($_SESSION['full_name']); ?></p>
                <p>Today: <?php echo date('l, F j, Y'); ?></p>
            </div>
            <div>
                <a href="dashboard.php" class="btn">← Back to Dashboard</a>
                <a href="../logout.php" class="btn btn-danger">Logout</a>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['total_appointments']; ?></div>
                <div class="stat-label">Total Appointments</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['today_appointments']; ?></div>
                <div class="stat-label">Today's Appointments</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['pending_appointments']; ?></div>
                <div class="stat-label">Pending</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['completed_appointments']; ?></div>
                <div class="stat-label">Completed</div>
            </div>
        </div>

        <div class="table-container">
            <h3 style="padding: 20px; margin: 0; background: #f8f9fa; border-bottom: 1px solid #e9ecef;">
                Today's Schedule - <?php echo date('F j, Y'); ?>
            </h3>
            <table class="table">
                <thead>
                    <tr>
                        <th>Time</th>
                        <th>Patient</th>
                        <th>Contact</th>
                        <th>Reason</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($today_appointments->num_rows > 0): ?>
                        <?php while ($appointment = $today_appointments->fetch_assoc()): ?>
                            <tr class="today-highlight">
                                <td><strong><?php echo date('g:i A', strtotime($appointment['appointment_time'])); ?></strong></td>
                                <td>
                                    <?php echo htmlspecialchars($appointment['patient_name']); ?>
                                    <br><small><?php echo htmlspecialchars($appointment['patient_id']); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($appointment['phone']); ?></td>
                                <td><?php echo htmlspecialchars($appointment['reason'] ?: 'General consultation'); ?></td>
                                <td>
                                    <span class="badge badge-<?php echo $appointment['status']; ?>">
                                        <?php echo ucfirst($appointment['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($appointment['status'] == 'confirmed'): ?>
                                        <a href="patient_details.php?patient_id=<?php echo $appointment['patient_id']; ?>" class="btn btn-sm">View Patient</a>
                                    <?php else: ?>
                                        <button class="btn btn-sm" onclick="alert('Appointment not confirmed yet')">View Patient</button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 40px;">No appointments scheduled for today</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="table-container">
            <h3 style="padding: 20px; margin: 0; background: #f8f9fa; border-bottom: 1px solid #e9ecef;">
                All Appointments
            </h3>
            <table class="table">
                <thead>
                    <tr>
                        <th>Appointment ID</th>
                        <th>Date & Time</th>
                        <th>Patient</th>
                        <th>Contact</th>
                        <th>Reason</th>
                        <th>Status</th>
                        <th>Booked By</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($appointments->num_rows > 0): ?>
                        <?php while ($appointment = $appointments->fetch_assoc()): ?>
                            <tr class="<?php echo ($appointment['appointment_date'] == $today) ? 'today-highlight' : ''; ?>">
                                <td><?php echo htmlspecialchars($appointment['appointment_id']); ?></td>
                                <td>
                                    <?php echo date('M j, Y', strtotime($appointment['appointment_date'])); ?>
                                    <br><strong><?php echo date('g:i A', strtotime($appointment['appointment_time'])); ?></strong>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($appointment['patient_name']); ?>
                                    <br><small><?php echo htmlspecialchars($appointment['patient_id']); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($appointment['phone']); ?></td>
                                <td><?php echo htmlspecialchars($appointment['reason'] ?: 'General consultation'); ?></td>
                                <td>
                                    <span class="badge badge-<?php echo $appointment['status']; ?>">
                                        <?php echo ucfirst($appointment['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($appointment['booked_by_name'] ?: 'Online'); ?></td>
                                <td>
                                    <a href="patient_details.php?patient_id=<?php echo $appointment['patient_id']; ?>" class="btn btn-sm">View Patient</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 40px;">No appointments found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>