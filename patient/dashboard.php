<?php
require_once '../config/db.php';
check_login('patient');

$patient_id = $_SESSION['user_id'];

// Get dashboard statistics
$stats = [];
$stats['total_appointments'] = $conn->query("SELECT COUNT(*) as count FROM appointments WHERE patient_id = $patient_id")->fetch_assoc()['count'];
$stats['upcoming_appointments'] = $conn->query("SELECT COUNT(*) as count FROM appointments WHERE patient_id = $patient_id AND appointment_date >= CURDATE() AND status != 'cancelled'")->fetch_assoc()['count'];
$stats['total_prescriptions'] = $conn->query("SELECT COUNT(*) as count FROM prescriptions WHERE patient_id = $patient_id")->fetch_assoc()['count'];
$stats['pending_lab_tests'] = $conn->query("SELECT COUNT(*) as count FROM lab_tests WHERE patient_id = $patient_id AND status IN ('pending', 'in_progress')")->fetch_assoc()['count'];

// Get upcoming appointments
$upcoming_appointments = $conn->query("
    SELECT a.*, d.full_name as doctor_name, d.specialization 
    FROM appointments a 
    JOIN doctors d ON a.doctor_id = d.id 
    WHERE a.patient_id = $patient_id AND a.appointment_date >= CURDATE() AND a.status != 'cancelled'
    ORDER BY a.appointment_date ASC, a.appointment_time ASC 
    LIMIT 5
");

// Get recent lab tests
$recent_lab_tests = $conn->query("
    SELECT lt.*, d.full_name as doctor_name 
    FROM lab_tests lt 
    JOIN doctors d ON lt.doctor_id = d.id 
    WHERE lt.patient_id = $patient_id 
    ORDER BY lt.created_at DESC 
    LIMIT 5
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Dashboard - Hospital Management System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container">
        <div class="dashboard-header">
            <div>
                <h1>🧑 Patient Dashboard</h1>
                <p>Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?></p>
                <p>Patient ID: <?php echo htmlspecialchars($_SESSION['patient_id']); ?></p>
                <p>Blood Group: <?php echo htmlspecialchars($_SESSION['blood_group']); ?></p>
            </div>
            <div>
                <a href="../logout.php" class="btn btn-patient">Logout</a>
            </div>
        </div>

        <nav class="nav-menu">
            <ul>
                <li><a href="dashboard.php" class="active">Dashboard</a></li>
                <li><a href="appointments.php">My Appointments</a></li>
                <li><a href="reports.php">Medical Reports</a></li>
            </ul>
        </nav>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['total_appointments']; ?></div>
                <div class="stat-label">Total Appointments</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['upcoming_appointments']; ?></div>
                <div class="stat-label">Upcoming Appointments</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['total_prescriptions']; ?></div>
                <div class="stat-label">Total Prescriptions</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['pending_lab_tests']; ?></div>
                <div class="stat-label">Pending Lab Tests</div>
            </div>
        </div>

        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">📅</div>
                <h4>Book Appointment</h4>
                <p>Schedule new appointments with doctors online.</p>
                <a href="book_appointment.php" class="btn btn-patient">Book Appointment</a>
            </div>
            <div class="feature-card">
                <div class="feature-icon">📅</div>
                <h4>My Appointments</h4>
                <p>View your scheduled appointments and medical consultations.</p>
                <a href="appointments.php" class="btn btn-patient">View Appointments</a>
            </div>
            <div class="feature-card">
                <div class="feature-icon">📋</div>
                <h4>Medical Reports</h4>
                <p>Access your medical reports, test results, and prescriptions.</p>
                <a href="reports.php" class="btn btn-patient">View Reports</a>
            </div>
            <div class="feature-card">
                <div class="feature-icon">💳</div>
                <h4>My Bills</h4>
                <p>View and pay your medical bills and treatment charges.</p>
                <a href="bills.php" class="btn btn-patient">View Bills</a>
            </div>
            <div class="feature-card">
                <div class="feature-icon">👤</div>
                <h4>Profile Settings</h4>
                <p>Update your personal information and contact details.</p>
                <a href="profile.php" class="btn btn-patient">Update Profile</a>
            </div>
        </div>

        <div class="table-container">
            <h3 style="padding: 20px; margin: 0; background: #f8f9fa; border-bottom: 1px solid #e9ecef;">
                Upcoming Appointments
            </h3>
            <table class="table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Doctor</th>
                        <th>Specialization</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($upcoming_appointments->num_rows > 0): ?>
                        <?php while ($appointment = $upcoming_appointments->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo date('M j, Y', strtotime($appointment['appointment_date'])); ?></td>
                                <td><?php echo date('H:i', strtotime($appointment['appointment_time'])); ?></td>
                                <td>Dr. <?php echo htmlspecialchars($appointment['doctor_name']); ?></td>
                                <td><?php echo htmlspecialchars($appointment['specialization']); ?></td>
                                <td>
                                    <span class="badge badge-<?php echo $appointment['status']; ?>">
                                        <?php echo ucfirst($appointment['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($appointment['status'] == 'pending'): ?>
                                        <a href="appointments.php?action=reschedule&id=<?php echo $appointment['id']; ?>" class="btn btn-sm btn-patient">Reschedule</a>
                                    <?php else: ?>
                                        <a href="appointments.php?action=view&id=<?php echo $appointment['id']; ?>" class="btn btn-sm btn-patient">View Details</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 40px;">No upcoming appointments</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script src="../assets/js/script.js"></script>
</body>
</html>