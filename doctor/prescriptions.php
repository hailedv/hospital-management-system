<?php
require_once '../config/db.php';
check_login('doctor');

$message = '';
$error = '';
$doctor_id = $_SESSION['user_id'];

// Handle prescription creation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_prescription'])) {
    $patient_id = isset($_POST['patient_id']) ? (int)$_POST['patient_id'] : 0;
    $medications = isset($_POST['medications']) ? sanitize_input($_POST['medications']) : '';
    $instructions = isset($_POST['instructions']) ? sanitize_input($_POST['instructions']) : '';
    
    if (empty($patient_id)) {
        $error = "Please select a patient.";
    } elseif (empty($medications)) {
        $error = "Please enter medications.";
    } else {
        // Generate prescription ID
        $prescription_count = $conn->query("SELECT COUNT(*) as count FROM prescriptions")->fetch_assoc()['count'];
        $prescription_id = 'PRE' . str_pad($prescription_count + 1, 4, '0', STR_PAD_LEFT);
        
        $stmt = $conn->prepare("INSERT INTO prescriptions (prescription_id, patient_id, doctor_id, medications, instructions, status) VALUES (?, ?, ?, ?, ?, 'pending')");
        $stmt->bind_param("siiss", $prescription_id, $patient_id, $doctor_id, $medications, $instructions);
        
        if ($stmt->execute()) {
            $message = "Prescription $prescription_id created successfully!";
            log_activity('create_prescription', "Created prescription $prescription_id for patient ID $patient_id");
        } else {
            $error = "Error creating prescription: " . $conn->error;
        }
    }
}

// Get selected patient info if patient_id is provided
$selected_patient = null;
if (isset($_GET['patient_id'])) {
    $patient_id = (int)$_GET['patient_id'];
    $stmt = $conn->prepare("SELECT * FROM patients WHERE id = ?");
    $stmt->bind_param("i", $patient_id);
    $stmt->execute();
    $selected_patient = $stmt->get_result()->fetch_assoc();
}

// Get all patients for dropdown
$patients = $conn->query("SELECT * FROM patients WHERE status = 'active' ORDER BY full_name");

// Get doctor's recent prescriptions
$recent_prescriptions = $conn->query("
    SELECT p.*, pt.full_name as patient_name, pt.patient_id 
    FROM prescriptions p 
    JOIN patients pt ON p.patient_id = pt.id 
    WHERE p.doctor_id = $doctor_id 
    ORDER BY p.created_at DESC 
    LIMIT 15
");

// Get common medications for quick selection
$common_medications = [
    'Amoxicillin 500mg' => 'Take 1 capsule 3 times daily for 7-10 days',
    'Paracetamol 500mg' => 'Take 1-2 tablets every 4-6 hours as needed (max 8 tablets/day)',
    'Ibuprofen 400mg' => 'Take 1 tablet 2-3 times daily with food (max 3 tablets/day)',
    'Omeprazole 20mg' => 'Take 1 capsule daily before breakfast',
    'Metformin 500mg' => 'Take 1 tablet twice daily with meals',
    'Lisinopril 10mg' => 'Take 1 tablet daily at the same time',
    'Atorvastatin 20mg' => 'Take 1 tablet daily at bedtime',
    'Aspirin 75mg' => 'Take 1 tablet daily after food',
    'Amlodipine 5mg' => 'Take 1 tablet daily',
    'Cetirizine 10mg' => 'Take 1 tablet daily for allergies'
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Prescriptions - Doctor Dashboard</title>
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

        .btn-sm {
            padding: 6px 12px;
            font-size: 0.9em;
        }

        .btn-success {
            background: #28a745;
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

        .patient-info {
            background: #e3f2fd;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .medication-templates {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .medication-btn {
            display: inline-block;
            padding: 8px 12px;
            background: #e9ecef;
            color: #495057;
            border: none;
            border-radius: 4px;
            margin: 3px;
            cursor: pointer;
            font-size: 0.9em;
        }

        .medication-btn:hover {
            background: #dee2e6;
        }

        .table-container {
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
                <h1>💊 Manage Prescriptions</h1>
                <p>Dr. <?php echo htmlspecialchars($_SESSION['full_name']); ?></p>
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
            <h3 style="margin-bottom: 20px; color: #2196f3;">Create New Prescription</h3>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="patient_id">Select Patient:</label>
                    <select name="patient_id" id="patient_id" class="form-control" required onchange="loadPatientInfo()">
                        <option value="">Choose a patient</option>
                        <?php while ($patient = $patients->fetch_assoc()): ?>
                            <option value="<?php echo $patient['id']; ?>" 
                                    <?php echo ($selected_patient && $selected_patient['id'] == $patient['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($patient['patient_id'] . ' - ' . $patient['full_name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <?php if ($selected_patient): ?>
                    <div class="patient-info">
                        <h4>Patient Information:</h4>
                        <div class="form-row">
                            <div>
                                <p><strong>Name:</strong> <?php echo htmlspecialchars($selected_patient['full_name']); ?></p>
                                <p><strong>Patient ID:</strong> <?php echo htmlspecialchars($selected_patient['patient_id']); ?></p>
                                <p><strong>Phone:</strong> <?php echo htmlspecialchars($selected_patient['phone']); ?></p>
                            </div>
                            <div>
                                <p><strong>Age:</strong> 
                                    <?php 
                                    if ($selected_patient['date_of_birth']) {
                                        $age = floor((time() - strtotime($selected_patient['date_of_birth'])) / (365.25 * 24 * 60 * 60));
                                        echo $age . ' years';
                                    } else {
                                        echo 'N/A';
                                    }
                                    ?>
                                </p>
                                <p><strong>Gender:</strong> <?php echo ucfirst($selected_patient['gender']); ?></p>
                                <p><strong>Blood Group:</strong> <?php echo htmlspecialchars($selected_patient['blood_group'] ?: 'N/A'); ?></p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="medication-templates">
                    <h4 style="margin-bottom: 10px;">Common Medications (Click to Add):</h4>
                    <?php foreach ($common_medications as $medication => $dosage): ?>
                        <button type="button" class="medication-btn" 
                                onclick="addMedication('<?php echo htmlspecialchars($medication); ?>', '<?php echo htmlspecialchars($dosage); ?>')">
                            <?php echo htmlspecialchars($medication); ?>
                        </button>
                    <?php endforeach; ?>
                </div>

                <div class="form-group">
                    <label for="medications">Medications & Dosage:</label>
                    <textarea name="medications" id="medications" class="form-control" rows="6" required 
                              placeholder="Enter medications with dosage instructions. Example:&#10;Amoxicillin 500mg - Take 1 capsule 3 times daily for 7 days&#10;Paracetamol 500mg - Take 1 tablet when needed for pain (max 4 per day)"></textarea>
                </div>

                <div class="form-group">
                    <label for="instructions">Special Instructions:</label>
                    <textarea name="instructions" id="instructions" class="form-control" rows="4" 
                              placeholder="Additional instructions for the patient (e.g., take with food, avoid alcohol, etc.)"></textarea>
                </div>

                <button type="submit" name="create_prescription" class="btn btn-success">Create Prescription</button>
            </form>
        </div>

        <div class="table-container">
            <h3 style="padding: 20px; margin: 0; background: #f8f9fa; border-bottom: 1px solid #e9ecef;">
                My Recent Prescriptions
            </h3>
            <table class="table">
                <thead>
                    <tr>
                        <th>Prescription ID</th>
                        <th>Patient</th>
                        <th>Medications</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($recent_prescriptions->num_rows > 0): ?>
                        <?php while ($prescription = $recent_prescriptions->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($prescription['prescription_id']); ?></td>
                                <td>
                                    <?php echo htmlspecialchars($prescription['patient_name']); ?>
                                    <br><small><?php echo htmlspecialchars($prescription['patient_id']); ?></small>
                                </td>
                                <td>
                                    <?php 
                                    $medications = explode("\n", $prescription['medications']);
                                    echo htmlspecialchars($medications[0]);
                                    if (count($medications) > 1) {
                                        echo "<br><small>+" . (count($medications) - 1) . " more...</small>";
                                    }
                                    ?>
                                </td>
                                <td>
                                    <span class="badge badge-<?php echo $prescription['status']; ?>">
                                        <?php echo ucfirst($prescription['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M j, Y H:i', strtotime($prescription['created_at'])); ?></td>
                                <td>
                                    <button class="btn btn-sm" onclick="viewPrescription('<?php echo htmlspecialchars($prescription['prescription_id']); ?>')">
                                        View Details
                                    </button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 40px;">No prescriptions created yet</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        function loadPatientInfo() {
            const patientId = document.getElementById('patient_id').value;
            if (patientId) {
                window.location.href = 'prescriptions.php?patient_id=' + patientId;
            }
        }

        function addMedication(medication, dosage) {
            const medicationsTextarea = document.getElementById('medications');
            const currentText = medicationsTextarea.value;
            const newMedication = medication + ' - ' + dosage;
            
            if (currentText.trim() === '') {
                medicationsTextarea.value = newMedication;
            } else {
                medicationsTextarea.value = currentText + '\n' + newMedication;
            }
            
            medicationsTextarea.focus();
        }

        function viewPrescription(prescriptionId) {
            alert('Prescription Details: ' + prescriptionId + '\n\nThis would open a detailed view of the prescription in a full implementation.');
        }
    </script>
</body>
</html>