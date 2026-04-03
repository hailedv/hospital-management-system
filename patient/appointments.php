<?php
require_once '../config/db.php';
check_login('patient');

$patient_id = $_SESSION['user_id'];

// Get patient's appointments
$appointments = $conn->query("
    SELECT a.*, d.full_name as doctor_name, d.specialization 
    FROM appointments a 
    JOIN doctors d ON a.doctor_id = d.id 
    WHERE a.patient_id = $patient_id 
    ORDER BY a.appointment_date DESC, a.appointment_time DESC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Appointments - Patient Dashboard</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container">
        <div class="dashboard-header">
            <div>
                <h1>📅 My Appointments</h1>
                <p><?php echo htmlspecialchars($_SESSION['full_name']); ?></p>
            </div>
            <div>
                <a href="dashboard.php" class="btn btn-patient">← Back to Dashboard</a>
                <a href="../logout.php" class="btn btn-danger">Logout</a>
            </div>
        </div>

        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Appointment ID</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Doctor</th>
                        <th>Specialization</th>
                        <th>Reason</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($appointments->num_rows > 0): ?>
                        <?php while ($appointment = $appointments->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($appointment['appointment_id']); ?></td>
                                <td><?php echo date('M j, Y', strtotime($appointment['appointment_date'])); ?></td>
                                <td><?php echo date('H:i', strtotime($appointment['appointment_time'])); ?></td>
                                <td><?php echo htmlspecialchars($appointment['doctor_name']); ?></td>
                                <td><?php echo htmlspecialchars($appointment['specialization']); ?></td>
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
                            <td colspan="7" style="text-align: center; padding: 40px;">No appointments found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script src="../assets/js/script.js"></script>
</body>
</html>