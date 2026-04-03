<?php
require_once '../config/db.php';
check_login('doctor');

$message = '';
$error = '';

// Handle referral sending
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $patient_id = (int)$_POST['patient_id'];
    $specialist_id = (int)$_POST['specialist_id'];
    $referral_reason = sanitize_input($_POST['referral_reason']);
    $urgency = $_POST['urgency'];
    $notes = sanitize_input($_POST['notes']);
    $referring_doctor_id = $_SESSION['user_id'];
    
    if (empty($patient_id) || empty($specialist_id) || empty($referral_reason)) {
        $error = "Please fill in all required fields.";
    } else {
        // Generate referral ID
        do {
            $referral_id = 'REF' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
            $check = $conn->prepare("SELECT id FROM referrals WHERE referral_id = ?");
            $check->bind_param("s", $referral_id);
            $check->execute();
        } while ($check->get_result()->num_rows > 0);
        
        // Insert referral
        $stmt = $conn->prepare("INSERT INTO referrals (referral_id, patient_id, referring_doctor_id, specialist_id, referral_reason, urgency, notes, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', NOW())");
        $stmt->bind_param("siissss", $referral_id, $patient_id, $referring_doctor_id, $specialist_id, $referral_reason, $urgency, $notes);
        
        if ($stmt->execute()) {
            $message = "Referral sent successfully! Referral ID: $referral_id";
            log_activity('send_referral', "Sent referral $referral_id for patient ID $patient_id to specialist ID $specialist_id");
        } else {
            $error = "Error sending referral: " . $conn->error;
        }
    }
}

// Get patients
$patients = $conn->query("SELECT * FROM patients WHERE status = 'active' ORDER BY full_name");

// Get specialist doctors
$specialists = $conn->query("SELECT * FROM doctors WHERE status = 'active' AND specialization != 'General Practice' ORDER BY specialization, full_name");

// Get recent referrals
$recent_referrals = $conn->query("
    SELECT r.*, p.full_name as patient_name, p.patient_id, d.full_name as specialist_name, d.specialization
    FROM referrals r 
    JOIN patients p ON r.patient_id = p.id 
    JOIN doctors d ON r.specialist_id = d.id 
    WHERE r.referring_doctor_id = {$_SESSION['user_id']} 
    ORDER BY r.created_at DESC 
    LIMIT 10
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Send Referral - Doctor Dashboard</title>
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

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }

        .required {
            color: #dc3545;
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
            border-color: #4caf50;
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

        .badge-accepted {
            background: #d4edda;
            color: #155724;
        }

        .badge-completed {
            background: #cce5ff;
            color: #004085;
        }

        .urgency-high {
            color: #dc3545;
            font-weight: bold;
        }

        .urgency-medium {
            color: #ff9800;
            font-weight: bold;
        }

        .urgency-low {
            color: #4caf50;
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
                <h1>🏥 Send Patient Referral</h1>
                <p>Refer patients to specialized doctors</p>
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
            <h3 style="margin-bottom: 20px; color: #4caf50;">New Referral</h3>
            <form method="POST" action="">
                <div class="form-row">
                    <div class="form-group">
                        <label for="patient_id">Select Patient <span class="required">*</span>:</label>
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
                        <label for="specialist_id">Select Specialist <span class="required">*</span>:</label>
                        <select name="specialist_id" id="specialist_id" class="form-control" required>
                            <option value="">Choose a specialist</option>
                            <?php 
                            $current_specialization = '';
                            while ($specialist = $specialists->fetch_assoc()): 
                                if ($current_specialization != $specialist['specialization']) {
                                    if ($current_specialization != '') echo '</optgroup>';
                                    echo '<optgroup label="' . htmlspecialchars($specialist['specialization']) . '">';
                                    $current_specialization = $specialist['specialization'];
                                }
                            ?>
                                <option value="<?php echo $specialist['id']; ?>">
                                    <?php echo htmlspecialchars('Dr. ' . $specialist['full_name']); ?>
                                </option>
                            <?php endwhile; ?>
                            <?php if ($current_specialization != '') echo '</optgroup>'; ?>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="urgency">Urgency Level <span class="required">*</span>:</label>
                        <select name="urgency" id="urgency" class="form-control" required>
                            <option value="">Select urgency</option>
                            <option value="low">Low - Routine consultation</option>
                            <option value="medium">Medium - Within 1-2 weeks</option>
                            <option value="high">High - Urgent, within 24-48 hours</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="referral_reason">Reason for Referral <span class="required">*</span>:</label>
                        <select name="referral_reason" id="referral_reason" class="form-control" required>
                            <option value="">Select reason</option>
                            <option value="Second Opinion">Second Opinion</option>
                            <option value="Specialized Treatment">Specialized Treatment</option>
                            <option value="Diagnostic Consultation">Diagnostic Consultation</option>
                            <option value="Surgical Evaluation">Surgical Evaluation</option>
                            <option value="Chronic Disease Management">Chronic Disease Management</option>
                            <option value="Emergency Consultation">Emergency Consultation</option>
                            <option value="Follow-up Care">Follow-up Care</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                </div>

                <div class="form-group full-width">
                    <label for="notes">Additional Notes:</label>
                    <textarea name="notes" id="notes" class="form-control" rows="4" placeholder="Patient symptoms, test results, treatment history, specific concerns, etc."></textarea>
                </div>

                <button type="submit" class="btn">🏥 Send Referral</button>
            </form>
        </div>

        <div class="table-container">
            <h3 style="padding: 20px; margin: 0; background: #f8f9fa; border-bottom: 1px solid #e9ecef;">
                Recent Referrals
            </h3>
            <table class="table">
                <thead>
                    <tr>
                        <th>Referral ID</th>
                        <th>Patient</th>
                        <th>Specialist</th>
                        <th>Reason</th>
                        <th>Urgency</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($recent_referrals->num_rows > 0): ?>
                        <?php while ($referral = $recent_referrals->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($referral['referral_id']); ?></td>
                                <td>
                                    <?php echo htmlspecialchars($referral['patient_name']); ?>
                                    <br><small><?php echo htmlspecialchars($referral['patient_id']); ?></small>
                                </td>
                                <td>
                                    Dr. <?php echo htmlspecialchars($referral['specialist_name']); ?>
                                    <br><small><?php echo htmlspecialchars($referral['specialization']); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($referral['referral_reason']); ?></td>
                                <td>
                                    <span class="urgency-<?php echo $referral['urgency']; ?>">
                                        <?php echo ucfirst($referral['urgency']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge badge-<?php echo $referral['status']; ?>">
                                        <?php echo ucfirst($referral['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M j, Y', strtotime($referral['created_at'])); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 40px;">No referrals sent yet</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>