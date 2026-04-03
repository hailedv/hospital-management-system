<?php
require_once '../config/db.php';
check_login('doctor');

$patient = null;
$appointments = null;
$prescriptions = null;
$vitals = null;

if (isset($_GET['id'])) {
    $patient_id = (int)$_GET['id'];
    
    // Get patient details
    $stmt = $conn->prepare("SELECT * FROM patients WHERE id = ?");
    $stmt->bind_param("i", $patient_id);
    $stmt->execute();
    $patient = $stmt->get_result()->fetch_assoc();
    
    if ($patient) {
        // Get appointments with this doctor
        $doctor_id = $_SESSION['user_id'];
        $appointments = $conn->query("
            SELECT * FROM appointments 
            WHERE patient_id = $patient_id AND doctor_id = $doctor_id 
            ORDER BY appointment_date DESC
        ");
        
        // Get prescriptions by this doctor
        $prescriptions = $conn->query("
            SELECT * FROM prescriptions 
            WHERE patient_id = $patient_id AND doctor_id = $doctor_id 
            ORDER BY created_at DESC
        ");
        
        // Get recent vitals
        $vitals = $conn->query("
            SELECT pv.*, n.full_name as nurse_name 
            FROM patient_vitals pv 
            LEFT JOIN nurses n ON pv.nurse_id = n.id 
            WHERE pv.patient_id = $patient_id 
            ORDER BY pv.recorded_at DESC 
            LIMIT 5
        ");
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Details - Doctor Dashboard</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container">
        <div class="dashboard-header">
            <div>
                <h1>👤 Patient Details</h1>
                <p>Dr. <?php echo htmlspecialchars($_SESSION['full_name']); ?></p>
            </div>
            <div>
                <a href="patients.php" class="btn btn-doctor">← Back to Patients</a>
                <a href="../logout.php" class="btn btn-danger">Logout</a>
            </div>
        </div>

        <?php if ($patient): ?>
            <div class="patient-info-container">
                <h3>Patient Information</h3>
                <div class="patient-details">
                    <p><strong>Patient ID:</strong> <?php echo htmlspecialchars($patient['patient_id']); ?></p>
                    <p><strong>Name:</strong> <?php echo htmlspecialchars($patient['full_name']); ?></p>
                    <p><strong>Phone:</strong> <?php echo htmlspecialchars($patient['phone']); ?></p>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($patient['email'] ?: 'N/A'); ?></p>
                    <p><strong>Date of Birth:</strong> <?php echo date('M j, Y', strtotime($patient['date_of_birth'])); ?></p>
                    <p><strong>Gender:</strong> <?php echo ucfirst($patient['gender']); ?></p>
                    <p><strong>Blood Group:</strong> <?php echo htmlspecialchars($patient['blood_group'] ?: 'N/A'); ?></p>
                    <p><strong>Address:</strong> <?php echo htmlspecialchars($patient['address'] ?: 'N/A'); ?></p>
                    <p><strong>Emergency Contact:</strong> <?php echo htmlspecialchars($patient['emergency_contact'] ?: 'N/A'); ?></p>
                    <p><strong>Emergency Phone:</strong> <?php echo htmlspecialchars($patient['emergency_phone'] ?: 'N/A'); ?></p>
                </div>
                
                <div class="actions">
                    <a href="prescribe.php?patient_id=<?php echo $patient['id']; ?>" class="btn btn-doctor">Create Prescription</a>
                </div>
            </div>

            <div class="table-container">
                <h3>Recent Vital Signs</h3>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Temperature</th>
                            <th>Blood Pressure</th>
                            <th>Heart Rate</th>
                            <th>Recorded By</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($vitals->num_rows > 0): ?>
                            <?php while ($vital = $vitals->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo date('M j, Y H:i', strtotime($vital['recorded_at'])); ?></td>
                                    <td><?php echo $vital['temperature'] ? $vital['temperature'] . '°C' : '-'; ?></td>
                                    <td>
                                        <?php 
                                        if ($vital['blood_pressure_systolic'] && $vital['blood_pressure_diastolic']) {
                                            echo $vital['blood_pressure_systolic'] . '/' . $vital['blood_pressure_diastolic'];
                                        } else {
                                            echo '-';
                                        }
                                        ?>
                                    </td>
                                    <td><?php echo $vital['heart_rate'] ? $vital['heart_rate'] . ' bpm' : '-'; ?></td>
                                    <td><?php echo htmlspecialchars($vital['nurse_name'] ?: 'Unknown'); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" style="text-align: center; padding: 40px;">No vital signs recorded</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        <?php else: ?>
            <div class="error-container">
                <h3>Patient Not Found</h3>
                <p>The requested patient could not be found or you don't have access to view this patient.</p>
            </div>
        <?php endif; ?>
    </div>

    <script src="../assets/js/script.js"></script>
</body>
</html>