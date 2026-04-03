<?php
require_once '../config/db.php';
check_login('nurse');

$message = '';
$error = '';

// Handle medication administration
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['administer_medication'])) {
    $prescription_id = isset($_POST['prescription_id']) ? (int)$_POST['prescription_id'] : 0;
    $administration_notes = isset($_POST['administration_notes']) ? sanitize_input($_POST['administration_notes']) : '';
    
    if (empty($prescription_id)) {
        $error = "Please select a prescription.";
    } else {
        // Create a medication administration record
        $nurse_id = $_SESSION['user_id'];
        $administered_at = date('Y-m-d H:i:s');
        
        // For this demo, we'll create a simple medication_administration table entry
        // In a real system, you'd have a dedicated medication_administration table
        $stmt = $conn->prepare("INSERT INTO patient_vitals (patient_id, nurse_id, notes) 
                               SELECT patient_id, ?, CONCAT('MEDICATION ADMINISTERED - Prescription ID: ', prescription_id, 
                               CASE WHEN ? != '' THEN CONCAT(' - Notes: ', ?) ELSE '' END) 
                               FROM prescriptions WHERE id = ?");
        $stmt->bind_param("issi", $nurse_id, $administration_notes, $administration_notes, $prescription_id);
        
        if ($stmt->execute()) {
            $message = "Medication administration recorded successfully!";
            log_activity('administer_medication', "Administered medication for prescription ID $prescription_id");
        } else {
            $error = "Error recording medication administration: " . $conn->error;
        }
    }
}

// Get pending prescriptions for patients
$pending_prescriptions = $conn->query("
    SELECT p.*, pt.full_name as patient_name, pt.patient_id, d.full_name as doctor_name 
    FROM prescriptions p 
    JOIN patients pt ON p.patient_id = pt.id 
    JOIN doctors d ON p.doctor_id = d.id 
    WHERE p.status = 'dispensed' 
    ORDER BY p.dispensed_at DESC
");

// Get recent medication administrations (simulated)
$recent_medications = $conn->query("
    SELECT p.*, pt.full_name as patient_name, pt.patient_id, d.full_name as doctor_name 
    FROM prescriptions p 
    JOIN patients pt ON p.patient_id = pt.id 
    JOIN doctors d ON p.doctor_id = d.id 
    WHERE p.status = 'dispensed' 
    ORDER BY p.dispensed_at DESC 
    LIMIT 10
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medication Administration - Nurse Dashboard</title>
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
            color: #e91e63;
            font-size: 2em;
        }

        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #e91e63;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-weight: 500;
            border: none;
            cursor: pointer;
        }

        .btn:hover {
            background: #c2185b;
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

        .badge-dispensed {
            background: #d4edda;
            color: #155724;
        }

        @media (max-width: 768px) {
            .dashboard-header {
                flex-direction: column;
                text-align: center;
                gap: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="dashboard-header">
            <div>
                <h1>💉 Medication Administration</h1>
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

        <div class="table-container">
            <h3 style="padding: 20px; margin: 0; background: #f8f9fa; border-bottom: 1px solid #e9ecef;">
                Dispensed Medications Ready for Administration
            </h3>
            <table class="table">
                <thead>
                    <tr>
                        <th>Prescription ID</th>
                        <th>Patient</th>
                        <th>Doctor</th>
                        <th>Medications</th>
                        <th>Instructions</th>
                        <th>Dispensed Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($pending_prescriptions->num_rows > 0): ?>
                        <?php while ($prescription = $pending_prescriptions->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($prescription['prescription_id']); ?></td>
                                <td>
                                    <?php echo htmlspecialchars($prescription['patient_name']); ?>
                                    <br><small><?php echo htmlspecialchars($prescription['patient_id']); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($prescription['doctor_name']); ?></td>
                                <td><?php echo nl2br(htmlspecialchars($prescription['medications'])); ?></td>
                                <td><?php echo htmlspecialchars($prescription['instructions'] ?: 'No special instructions'); ?></td>
                                <td><?php echo $prescription['dispensed_at'] ? date('M j, Y H:i', strtotime($prescription['dispensed_at'])) : 'Not dispensed'; ?></td>
                                <td>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="prescription_id" value="<?php echo $prescription['id']; ?>">
                                        
                                        <!-- Quick administration -->
                                        <button type="submit" name="administer_medication" class="btn btn-sm" 
                                                onclick="return confirm('Confirm medication administration for <?php echo htmlspecialchars($prescription['patient_name']); ?>?')">
                                            ✓ Administer
                                        </button>
                                        
                                        <!-- Detailed administration with notes -->
                                        <button type="button" class="btn btn-sm" style="background: #17a2b8; margin-left: 5px;" 
                                                onclick="showAdministrationForm(<?php echo $prescription['id']; ?>)">
                                            📝 Add Notes
                                        </button>
                                    </form>
                                    
                                    <!-- Hidden form for detailed administration -->
                                    <div id="admin-form-<?php echo $prescription['id']; ?>" style="display: none; margin-top: 10px; padding: 10px; background: #f8f9fa; border-radius: 5px;">
                                        <form method="POST">
                                            <input type="hidden" name="prescription_id" value="<?php echo $prescription['id']; ?>">
                                            <div style="margin-bottom: 10px;">
                                                <label style="display: block; font-weight: 600; margin-bottom: 5px;">Administration Notes:</label>
                                                <textarea name="administration_notes" rows="3" style="width: 100%; padding: 5px;" 
                                                          placeholder="Patient response, side effects, administration time, etc."></textarea>
                                            </div>
                                            <button type="submit" name="administer_medication" class="btn btn-sm">Record Administration</button>
                                            <button type="button" class="btn btn-sm" style="background: #6c757d;" 
                                                    onclick="hideAdministrationForm(<?php echo $prescription['id']; ?>)">Cancel</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 40px;">No medications ready for administration</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="table-container">
            <h3 style="padding: 20px; margin: 0; background: #f8f9fa; border-bottom: 1px solid #e9ecef;">
                Medication Schedule Overview
            </h3>
            <div style="padding: 20px;">
                <p><strong>Morning Medications:</strong> 8:00 AM - 10:00 AM</p>
                <p><strong>Afternoon Medications:</strong> 2:00 PM - 4:00 PM</p>
                <p><strong>Evening Medications:</strong> 8:00 PM - 10:00 PM</p>
                <br>
                <p><em>Note: Always verify patient identity and medication details before administration.</em></p>
                <p><em>Document any adverse reactions or patient concerns immediately.</em></p>
            </div>
        </div>
    </div>

    <script>
        function showAdministrationForm(prescriptionId) {
            document.getElementById('admin-form-' + prescriptionId).style.display = 'block';
        }
        
        function hideAdministrationForm(prescriptionId) {
            document.getElementById('admin-form-' + prescriptionId).style.display = 'none';
        }
    </script>
</body>
</html>