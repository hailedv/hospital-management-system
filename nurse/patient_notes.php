<?php
require_once '../config/db.php';
check_login('nurse');

$message = '';
$error = '';
$patient = null;

// Get patient info if patient_id is provided
if (isset($_GET['patient_id'])) {
    $patient_id = (int)$_GET['patient_id'];
    $stmt = $conn->prepare("SELECT * FROM patients WHERE id = ?");
    $stmt->bind_param("i", $patient_id);
    $stmt->execute();
    $patient = $stmt->get_result()->fetch_assoc();
}

// Handle note submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_note'])) {
    $patient_id = isset($_POST['patient_id']) ? (int)$_POST['patient_id'] : 0;
    $notes = isset($_POST['notes']) ? sanitize_input($_POST['notes']) : '';
    $note_type = isset($_POST['note_type']) ? sanitize_input($_POST['note_type']) : '';
    
    if (empty($patient_id)) {
        $error = "Please select a patient.";
    } elseif (empty($note_type)) {
        $error = "Please select a note type.";
    } elseif (empty($notes)) {
        $error = "Please enter your nursing notes.";
    } else {
        // For this demo, we'll store notes in the patient_vitals table with a special note
        $nurse_id = $_SESSION['user_id'];
        $note_content = "[$note_type] " . $notes;
        
        $stmt = $conn->prepare("INSERT INTO patient_vitals (patient_id, nurse_id, notes) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $patient_id, $nurse_id, $note_content);
        
        if ($stmt->execute()) {
            $message = "Nursing note added successfully!";
            log_activity('add_nursing_note', "Added nursing note for patient ID $patient_id");
        } else {
            $error = "Error adding note: " . $conn->error;
        }
    }
}

// Get all patients for dropdown
$patients = $conn->query("SELECT * FROM patients WHERE status = 'active' ORDER BY full_name");

// Get recent notes for the selected patient
$recent_notes = null;
if ($patient) {
    $recent_notes = $conn->query("
        SELECT pv.notes, pv.recorded_at, n.full_name as nurse_name 
        FROM patient_vitals pv 
        LEFT JOIN nurses n ON pv.nurse_id = n.id 
        WHERE pv.patient_id = {$patient['id']} AND pv.notes IS NOT NULL AND pv.notes != ''
        ORDER BY pv.recorded_at DESC 
        LIMIT 10
    ");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nursing Notes - Nurse Dashboard</title>
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

        .patient-info {
            background: #e3f2fd;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .notes-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .note-item {
            padding: 15px;
            border-bottom: 1px solid #eee;
        }

        .note-item:last-child {
            border-bottom: none;
        }

        .note-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .note-type {
            background: #e91e63;
            color: white;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 0.8em;
            font-weight: 600;
        }

        .note-time {
            color: #666;
            font-size: 0.9em;
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
                <h1>📋 Nursing Notes</h1>
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
            <h3 style="margin-bottom: 20px; color: #e91e63;">Add Nursing Note</h3>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="patient_id">Select Patient:</label>
                    <select name="patient_id" id="patient_id" class="form-control" required onchange="selectPatient()">
                        <option value="">Choose a patient</option>
                        <?php while ($p = $patients->fetch_assoc()): ?>
                            <option value="<?php echo $p['id']; ?>" <?php echo ($patient && $patient['id'] == $p['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($p['patient_id'] . ' - ' . $p['full_name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <?php if ($patient): ?>
                    <div class="patient-info">
                        <h4>Patient Information:</h4>
                        <p><strong>Name:</strong> <?php echo htmlspecialchars($patient['full_name']); ?></p>
                        <p><strong>Patient ID:</strong> <?php echo htmlspecialchars($patient['patient_id']); ?></p>
                        <p><strong>Phone:</strong> <?php echo htmlspecialchars($patient['phone']); ?></p>
                    </div>

                    <div class="form-group">
                        <label for="note_type">Note Type:</label>
                        <select name="note_type" id="note_type" class="form-control" required>
                            <option value="">Select note type</option>
                            <option value="Assessment">Assessment</option>
                            <option value="Care Plan">Care Plan</option>
                            <option value="Medication">Medication</option>
                            <option value="Observation">Observation</option>
                            <option value="Patient Education">Patient Education</option>
                            <option value="Discharge Planning">Discharge Planning</option>
                            <option value="General">General</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="notes">Nursing Notes:</label>
                        <textarea name="notes" id="notes" class="form-control" rows="6" required 
                                  placeholder="Enter detailed nursing observations, care provided, patient response, etc."></textarea>
                    </div>

                    <button type="submit" name="add_note" class="btn" onclick="return validateNoteForm(this.form)">Add Note</button>
                <?php endif; ?>
            </form>
        </div>

        <?php if ($patient && $recent_notes && $recent_notes->num_rows > 0): ?>
            <div class="notes-container">
                <h3 style="padding: 20px; margin: 0; background: #f8f9fa; border-bottom: 1px solid #e9ecef;">
                    Recent Notes for <?php echo htmlspecialchars($patient['full_name']); ?>
                </h3>
                <?php while ($note = $recent_notes->fetch_assoc()): ?>
                    <div class="note-item">
                        <div class="note-header">
                            <div>
                                <?php 
                                // Extract note type from the note content
                                if (preg_match('/^\[([^\]]+)\]/', $note['notes'], $matches)) {
                                    echo '<span class="note-type">' . htmlspecialchars($matches[1]) . '</span>';
                                    $note_content = preg_replace('/^\[([^\]]+)\]\s*/', '', $note['notes']);
                                } else {
                                    $note_content = $note['notes'];
                                }
                                ?>
                                <span style="margin-left: 10px; font-weight: 600;">
                                    <?php echo htmlspecialchars($note['nurse_name'] ?: 'Unknown Nurse'); ?>
                                </span>
                            </div>
                            <div class="note-time">
                                <?php echo date('M j, Y H:i', strtotime($note['recorded_at'])); ?>
                            </div>
                        </div>
                        <div class="note-content">
                            <?php echo nl2br(htmlspecialchars($note_content)); ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Function to handle patient selection
        function selectPatient() {
            const patientSelect = document.getElementById('patient_id');
            if (patientSelect.value) {
                // Redirect to the same page with patient_id parameter
                window.location.href = 'patient_notes.php?patient_id=' + patientSelect.value;
            }
        }

        // Form validation for note submission
        function validateNoteForm(form) {
            const patientId = document.getElementById('patient_id').value;
            const noteType = document.getElementById('note_type').value;
            const notes = document.getElementById('notes').value;
            
            if (!patientId) {
                alert('Please select a patient');
                return false;
            }
            
            if (!noteType) {
                alert('Please select a note type');
                return false;
            }
            
            if (!notes.trim()) {
                alert('Please enter your nursing notes');
                return false;
            }
            
            return true;
        }
    </script>
</body>
</html>