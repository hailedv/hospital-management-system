<?php
require_once '../config/db.php';
check_login('doctor');

$doctor_id = $_SESSION['user_id'];

// Handle referral actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $referral_id = (int)$_POST['referral_id'];
    $action = $_POST['action'];
    $response_notes = sanitize_input($_POST['response_notes'] ?? '');
    
    if ($action == 'accept') {
        $stmt = $conn->prepare("UPDATE referrals SET status = 'accepted', response_notes = ?, responded_at = NOW() WHERE id = ? AND specialist_id = ?");
        $stmt->bind_param("sii", $response_notes, $referral_id, $doctor_id);
        if ($stmt->execute()) {
            $message = "Referral accepted successfully!";
        }
    } elseif ($action == 'complete') {
        $stmt = $conn->prepare("UPDATE referrals SET status = 'completed', response_notes = ?, completed_at = NOW() WHERE id = ? AND specialist_id = ?");
        $stmt->bind_param("sii", $response_notes, $referral_id, $doctor_id);
        if ($stmt->execute()) {
            $message = "Referral marked as completed!";
        }
    }
}

// Get pending referrals for this doctor
$pending_referrals = $conn->query("
    SELECT r.*, p.full_name as patient_name, p.patient_id, p.phone, p.date_of_birth, p.blood_group,
           d.full_name as referring_doctor_name
    FROM referrals r 
    JOIN patients p ON r.patient_id = p.id 
    JOIN doctors d ON r.referring_doctor_id = d.id 
    WHERE r.specialist_id = $doctor_id AND r.status = 'pending'
    ORDER BY 
        CASE r.urgency 
            WHEN 'high' THEN 1 
            WHEN 'medium' THEN 2 
            WHEN 'low' THEN 3 
        END, r.created_at ASC
");

// Get accepted referrals
$accepted_referrals = $conn->query("
    SELECT r.*, p.full_name as patient_name, p.patient_id, p.phone,
           d.full_name as referring_doctor_name
    FROM referrals r 
    JOIN patients p ON r.patient_id = p.id 
    JOIN doctors d ON r.referring_doctor_id = d.id 
    WHERE r.specialist_id = $doctor_id AND r.status = 'accepted'
    ORDER BY r.responded_at DESC
");

// Get completed referrals
$completed_referrals = $conn->query("
    SELECT r.*, p.full_name as patient_name, p.patient_id,
           d.full_name as referring_doctor_name
    FROM referrals r 
    JOIN patients p ON r.patient_id = p.id 
    JOIN doctors d ON r.referring_doctor_id = d.id 
    WHERE r.specialist_id = $doctor_id AND r.status = 'completed'
    ORDER BY r.completed_at DESC
    LIMIT 10
");

// Get referral statistics
$stats = [];
$stats['pending'] = $conn->query("SELECT COUNT(*) as count FROM referrals WHERE specialist_id = $doctor_id AND status = 'pending'")->fetch_assoc()['count'];
$stats['accepted'] = $conn->query("SELECT COUNT(*) as count FROM referrals WHERE specialist_id = $doctor_id AND status = 'accepted'")->fetch_assoc()['count'];
$stats['completed'] = $conn->query("SELECT COUNT(*) as count FROM referrals WHERE specialist_id = $doctor_id AND status = 'completed'")->fetch_assoc()['count'];
$stats['urgent'] = $conn->query("SELECT COUNT(*) as count FROM referrals WHERE specialist_id = $doctor_id AND status = 'pending' AND urgency = 'high'")->fetch_assoc()['count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Referral Notifications - Doctor Dashboard</title>
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
            max-width: 1400px;
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
            padding: 8px 16px;
            background: #4caf50;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-weight: 500;
            border: none;
            cursor: pointer;
            font-size: 14px;
        }

        .btn:hover {
            background: #45a049;
        }

        .btn-danger {
            background: #dc3545;
        }

        .btn-warning {
            background: #ff9800;
        }

        .btn-sm {
            padding: 4px 8px;
            font-size: 12px;
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

        .stat-number.urgent {
            color: #dc3545;
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

        .referral-details {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            margin: 5px 0;
            font-size: 0.9em;
        }

        .patient-info {
            background: #e3f2fd;
            padding: 8px;
            border-radius: 4px;
            margin: 5px 0;
            font-size: 0.85em;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }

        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 20px;
            border-radius: 10px;
            width: 80%;
            max-width: 600px;
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .close:hover {
            color: black;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
        }

        .form-control {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        @media (max-width: 768px) {
            .header {
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
        <div class="header">
            <div>
                <h1>🔔 Referral Notifications</h1>
                <p>Dr. <?php echo htmlspecialchars($_SESSION['full_name']); ?> - <?php echo htmlspecialchars($_SESSION['specialization']); ?></p>
            </div>
            <div>
                <a href="dashboard.php" class="btn">← Back to Dashboard</a>
                <a href="../logout.php" class="btn btn-danger">Logout</a>
            </div>
        </div>

        <?php if (isset($message)): ?>
            <div style="background: #d4edda; color: #155724; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['pending']; ?></div>
                <div class="stat-label">Pending Referrals</div>
            </div>
            <div class="stat-card">
                <div class="stat-number urgent"><?php echo $stats['urgent']; ?></div>
                <div class="stat-label">Urgent Referrals</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['accepted']; ?></div>
                <div class="stat-label">Accepted Referrals</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['completed']; ?></div>
                <div class="stat-label">Completed Referrals</div>
            </div>
        </div>

        <div class="table-container">
            <h3 style="padding: 20px; margin: 0; background: #f8f9fa; border-bottom: 1px solid #e9ecef;">
                Pending Referrals (<?php echo $stats['pending']; ?>)
            </h3>
            <table class="table">
                <thead>
                    <tr>
                        <th>Referral Details</th>
                        <th>Patient Information</th>
                        <th>Referring Doctor</th>
                        <th>Urgency</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($pending_referrals->num_rows > 0): ?>
                        <?php while ($referral = $pending_referrals->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($referral['referral_id']); ?></strong>
                                    <div class="referral-details">
                                        <strong>Reason:</strong> <?php echo htmlspecialchars($referral['referral_reason']); ?><br>
                                        <?php if ($referral['notes']): ?>
                                            <strong>Notes:</strong> <?php echo htmlspecialchars($referral['notes']); ?>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($referral['patient_name']); ?></strong><br>
                                    <div class="patient-info">
                                        ID: <?php echo htmlspecialchars($referral['patient_id']); ?><br>
                                        Phone: <?php echo htmlspecialchars($referral['phone']); ?><br>
                                        DOB: <?php echo date('M j, Y', strtotime($referral['date_of_birth'])); ?><br>
                                        Blood: <?php echo htmlspecialchars($referral['blood_group'] ?: 'Unknown'); ?>
                                    </div>
                                </td>
                                <td>Dr. <?php echo htmlspecialchars($referral['referring_doctor_name']); ?></td>
                                <td>
                                    <span class="urgency-<?php echo $referral['urgency']; ?>">
                                        <?php echo ucfirst($referral['urgency']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M j, Y', strtotime($referral['created_at'])); ?></td>
                                <td>
                                    <button class="btn btn-sm" onclick="acceptReferral(<?php echo $referral['id']; ?>)">Accept</button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 40px;">No pending referrals</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="table-container">
            <h3 style="padding: 20px; margin: 0; background: #f8f9fa; border-bottom: 1px solid #e9ecef;">
                Accepted Referrals (<?php echo $stats['accepted']; ?>)
            </h3>
            <table class="table">
                <thead>
                    <tr>
                        <th>Referral ID</th>
                        <th>Patient</th>
                        <th>Reason</th>
                        <th>Referring Doctor</th>
                        <th>Accepted Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($accepted_referrals->num_rows > 0): ?>
                        <?php while ($referral = $accepted_referrals->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($referral['referral_id']); ?></td>
                                <td>
                                    <?php echo htmlspecialchars($referral['patient_name']); ?>
                                    <br><small><?php echo htmlspecialchars($referral['patient_id']); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($referral['referral_reason']); ?></td>
                                <td>Dr. <?php echo htmlspecialchars($referral['referring_doctor_name']); ?></td>
                                <td><?php echo date('M j, Y', strtotime($referral['responded_at'])); ?></td>
                                <td>
                                    <button class="btn btn-sm btn-warning" onclick="completeReferral(<?php echo $referral['id']; ?>)">Complete</button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 40px;">No accepted referrals</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal for accepting referral -->
    <div id="acceptModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h3>Accept Referral</h3>
            <form method="POST" action="">
                <input type="hidden" name="referral_id" id="accept_referral_id">
                <input type="hidden" name="action" value="accept">
                <div class="form-group">
                    <label for="response_notes">Response Notes (Optional):</label>
                    <textarea name="response_notes" id="response_notes" class="form-control" rows="3" placeholder="Any notes for the referring doctor..."></textarea>
                </div>
                <button type="submit" class="btn">Accept Referral</button>
                <button type="button" class="btn btn-danger" onclick="closeModal()">Cancel</button>
            </form>
        </div>
    </div>

    <!-- Modal for completing referral -->
    <div id="completeModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h3>Complete Referral</h3>
            <form method="POST" action="">
                <input type="hidden" name="referral_id" id="complete_referral_id">
                <input type="hidden" name="action" value="complete">
                <div class="form-group">
                    <label for="completion_notes">Completion Notes:</label>
                    <textarea name="response_notes" id="completion_notes" class="form-control" rows="4" placeholder="Summary of consultation, diagnosis, treatment plan, recommendations..." required></textarea>
                </div>
                <button type="submit" class="btn">Mark as Complete</button>
                <button type="button" class="btn btn-danger" onclick="closeModal()">Cancel</button>
            </form>
        </div>
    </div>

    <script>
        function acceptReferral(referralId) {
            document.getElementById('accept_referral_id').value = referralId;
            document.getElementById('acceptModal').style.display = 'block';
        }

        function completeReferral(referralId) {
            document.getElementById('complete_referral_id').value = referralId;
            document.getElementById('completeModal').style.display = 'block';
        }

        function closeModal() {
            document.getElementById('acceptModal').style.display = 'none';
            document.getElementById('completeModal').style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                closeModal();
            }
        }
    </script>
</body>
</html>