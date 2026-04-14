<?php
// Nurse Vitals - record and submit patient vital signs using shared layout
require_once '../config/db.php';
check_login('nurse');

$page_title = 'Record Patient Vitals';
$role_color = '#e91e63';
$role_class = 'nurse';
$nurse_id = (int)$_SESSION['user_id'];
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['record_vitals'])) {
    $patient_id = isset($_POST['patient_id']) ? (int)$_POST['patient_id'] : 0;
    $temperature = !empty($_POST['temperature']) ? (float)$_POST['temperature'] : null;
    $bp_systolic = !empty($_POST['bp_systolic']) ? (int)$_POST['bp_systolic'] : null;
    $bp_diastolic = !empty($_POST['bp_diastolic']) ? (int)$_POST['bp_diastolic'] : null;
    $heart_rate = !empty($_POST['heart_rate']) ? (int)$_POST['heart_rate'] : null;
    $respiratory_rate = !empty($_POST['respiratory_rate']) ? (int)$_POST['respiratory_rate'] : null;
    $oxygen_saturation = !empty($_POST['oxygen_saturation']) ? (float)$_POST['oxygen_saturation'] : null;
    $weight = !empty($_POST['weight']) ? (float)$_POST['weight'] : null;
    $height = !empty($_POST['height']) ? (float)$_POST['height'] : null;
    $notes = isset($_POST['notes']) ? sanitize_input($_POST['notes']) : '';

    if (empty($patient_id)) {
        $error = "Please select a patient.";
    } elseif (is_null($temperature) && is_null($bp_systolic) && is_null($heart_rate) && is_null($respiratory_rate) && is_null($oxygen_saturation) && is_null($weight) && is_null($height)) {
        $error = "Please enter at least one vital sign measurement.";
    } else {
        $stmt = $conn->prepare("INSERT INTO patient_vitals (patient_id, nurse_id, temperature, blood_pressure_systolic, blood_pressure_diastolic, heart_rate, respiratory_rate, oxygen_saturation, weight, height, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iidiiidddds", $patient_id, $nurse_id, $temperature, $bp_systolic, $bp_diastolic, $heart_rate, $respiratory_rate, $oxygen_saturation, $weight, $height, $notes);
        if ($stmt->execute()) {
            $message = "Patient vitals recorded successfully!";
            log_activity('record_vitals', "Recorded vitals for patient ID $patient_id");
        } else {
            $error = "Error recording vitals: " . $conn->error;
        }
    }
}

$patients = $conn->query("SELECT * FROM patients WHERE status = 'active' ORDER BY full_name");

include '../includes/header.php';
?>

<nav class="nav-menu">
    <ul>
        <li><a href="dashboard.php">Dashboard</a></li>
        <li><a href="patients.php">Patients</a></li>
        <li><a href="vitals.php" class="active">Vitals</a></li>
        <li><a href="medications.php">Medications</a></li>
        <li><a href="patient_notes.php">Nursing Notes</a></li>
    </ul>
</nav>

<?php if ($message): ?>
    <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="table-container" style="padding:0">
    <h3>📊 Record Vital Signs</h3>
    <div style="padding:24px">
        <form method="POST">
            <div class="form-group">
                <label>Select Patient:</label>
                <select name="patient_id" class="form-control" required>
                    <option value="">Choose a patient</option>
                    <?php while ($p = $patients->fetch_assoc()): ?>
                        <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['patient_id'] . ' - ' . $p['full_name']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
                <div class="form-group">
                    <label>Temperature (°C):</label>
                    <input type="number" name="temperature" class="form-control" step="0.1" placeholder="36.5">
                </div>
                <div class="form-group">
                    <label>Heart Rate (bpm):</label>
                    <input type="number" name="heart_rate" class="form-control" placeholder="72">
                </div>
                <div class="form-group">
                    <label>BP Systolic:</label>
                    <input type="number" name="bp_systolic" class="form-control" placeholder="120">
                </div>
                <div class="form-group">
                    <label>BP Diastolic:</label>
                    <input type="number" name="bp_diastolic" class="form-control" placeholder="80">
                </div>
                <div class="form-group">
                    <label>Respiratory Rate (per min):</label>
                    <input type="number" name="respiratory_rate" class="form-control" placeholder="16">
                </div>
                <div class="form-group">
                    <label>Oxygen Saturation (%):</label>
                    <input type="number" name="oxygen_saturation" class="form-control" step="0.1" placeholder="98.5">
                </div>
                <div class="form-group">
                    <label>Weight (kg):</label>
                    <input type="number" name="weight" class="form-control" step="0.1" placeholder="70.5">
                </div>
                <div class="form-group">
                    <label>Height (cm):</label>
                    <input type="number" name="height" class="form-control" step="0.1" placeholder="175.0">
                </div>
            </div>
            <div class="form-group">
                <label>Notes:</label>
                <textarea name="notes" class="form-control" rows="3" placeholder="Additional observations"></textarea>
            </div>
            <button type="submit" name="record_vitals" class="btn btn-nurse">Record Vitals</button>
        </form>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
    $patient_id = isset($_POST['patient_id']) ? (int)$_POST['patient_id'] : 0;
    $temperature = !empty($_POST['temperature']) ? (float)$_POST['temperature'] : null;
    $bp_systolic = !empty($_POST['bp_systolic']) ? (int)$_POST['bp_systolic'] : null;
    $bp_diastolic = !empty($_POST['bp_diastolic']) ? (int)$_POST['bp_diastolic'] : null;
    $heart_rate = !empty($_POST['heart_rate']) ? (int)$_POST['heart_rate'] : null;
    $respiratory_rate = !empty($_POST['respiratory_rate']) ? (int)$_POST['respiratory_rate'] : null;
    $oxygen_saturation = !empty($_POST['oxygen_saturation']) ? (float)$_POST['oxygen_saturation'] : null;
    $weight = !empty($_POST['weight']) ? (float)$_POST['weight'] : null;
    $height = !empty($_POST['height']) ? (float)$_POST['height'] : null;
    $notes = isset($_POST['notes']) ? sanitize_input($_POST['notes']) : '';
    
    if (empty($patient_id)) {
        $error = "Please select a patient.";
    } elseif (is_null($temperature) && is_null($bp_systolic) && is_null($heart_rate) && is_null($respiratory_rate) && is_null($oxygen_saturation) && is_null($weight) && is_null($height)) {
        $error = "Please enter at least one vital sign measurement.";
    } else {
        $stmt = $conn->prepare("INSERT INTO patient_vitals (patient_id, nurse_id, temperature, blood_pressure_systolic, blood_pressure_diastolic, heart_rate, respiratory_rate, oxygen_saturation, weight, height, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iidiiidddds", $patient_id, $nurse_id, $temperature, $bp_systolic, $bp_diastolic, $heart_rate, $respiratory_rate, $oxygen_saturation, $weight, $height, $notes);
        
        if ($stmt->execute()) {
            $message = "Patient vitals recorded successfully!";
            log_activity('record_vitals', "Recorded vitals for patient ID $patient_id");
        } else {
            $error = "Error recording vitals: " . $conn->error;
        }
    }
}

// Get all patients
$patients = $conn->query("SELECT * FROM patients WHERE status = 'active' ORDER BY full_name");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Record Patient Vitals - Nurse Dashboard</title>
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
            border-color: #e91e63;
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
                <h1>📊 Record Patient Vitals</h1>
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
            <h3 style="margin-bottom: 20px; color: #e91e63;">Record Vital Signs</h3>
            <form method="POST" action="">
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

                <div class="form-row">
                    <div class="form-group">
                        <label for="temperature">Temperature (°C):</label>
                        <input type="number" name="temperature" id="temperature" class="form-control" step="0.1" placeholder="36.5">
                    </div>
                    <div class="form-group">
                        <label for="heart_rate">Heart Rate (bpm):</label>
                        <input type="number" name="heart_rate" id="heart_rate" class="form-control" placeholder="72">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="bp_systolic">Blood Pressure Systolic:</label>
                        <input type="number" name="bp_systolic" id="bp_systolic" class="form-control" placeholder="120">
                    </div>
                    <div class="form-group">
                        <label for="bp_diastolic">Blood Pressure Diastolic:</label>
                        <input type="number" name="bp_diastolic" id="bp_diastolic" class="form-control" placeholder="80">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="respiratory_rate">Respiratory Rate (per min):</label>
                        <input type="number" name="respiratory_rate" id="respiratory_rate" class="form-control" placeholder="16">
                    </div>
                    <div class="form-group">
                        <label for="oxygen_saturation">Oxygen Saturation (%):</label>
                        <input type="number" name="oxygen_saturation" id="oxygen_saturation" class="form-control" step="0.1" placeholder="98.5">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="weight">Weight (kg):</label>
                        <input type="number" name="weight" id="weight" class="form-control" step="0.1" placeholder="70.5">
                    </div>
                    <div class="form-group">
                        <label for="height">Height (cm):</label>
                        <input type="number" name="height" id="height" class="form-control" step="0.1" placeholder="175.0">
                    </div>
                </div>

                <div class="form-group">
                    <label for="notes">Notes:</label>
                    <textarea name="notes" id="notes" class="form-control" rows="3" placeholder="Any additional observations or notes"></textarea>
                </div>

                <button type="submit" name="record_vitals" class="btn" onclick="return validateVitalsForm(this.form)">Record Vitals</button>
            </form>
        </div>
    </div>

    <script>
        // Form validation for vitals recording
        function validateVitalsForm(form) {
            const patientId = document.getElementById('patient_id').value;
            const temperature = document.getElementById('temperature').value;
            const heartRate = document.getElementById('heart_rate').value;
            const bpSystolic = document.getElementById('bp_systolic').value;
            const bpDiastolic = document.getElementById('bp_diastolic').value;
            const respiratoryRate = document.getElementById('respiratory_rate').value;
            const oxygenSaturation = document.getElementById('oxygen_saturation').value;
            const weight = document.getElementById('weight').value;
            const height = document.getElementById('height').value;
            
            if (!patientId) {
                alert('Please select a patient');
                return false;
            }
            
            // Check if at least one vital sign is entered
            if (!temperature && !heartRate && !bpSystolic && !respiratoryRate && !oxygenSaturation && !weight && !height) {
                alert('Please enter at least one vital sign measurement');
                return false;
            }
            
            // Validate blood pressure - if one is entered, both should be entered
            if ((bpSystolic && !bpDiastolic) || (!bpSystolic && bpDiastolic)) {
                alert('Please enter both systolic and diastolic blood pressure values');
                return false;
            }
            
            return true;
        }
    </script>
</body>
</html>