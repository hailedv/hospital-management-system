<?php
require_once '../config/db.php';
check_login('nurse');

$nurse_id = $_SESSION['user_id'];

// Get patients who have had vitals recorded by this nurse
$patients = $conn->query("
    SELECT DISTINCT p.*, 
           COUNT(pv.id) as vitals_count,
           MAX(pv.recorded_at) as last_vitals
    FROM patients p 
    JOIN patient_vitals pv ON p.id = pv.patient_id 
    WHERE pv.nurse_id = $nurse_id 
    GROUP BY p.id 
    ORDER BY last_vitals DESC
");

// Get all active patients for assignment
$all_patients = $conn->query("SELECT * FROM patients WHERE status = 'active' ORDER BY full_name");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assigned Patients - Nurse Dashboard</title>
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

        .patient-id {
            background: #e3f2fd;
            color: #1976d2;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.8em;
            font-weight: 600;
        }

        .blood-group {
            background: #ffebee;
            color: #c62828;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 0.8em;
            font-weight: 600;
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
                <h1>👥 Assigned Patients</h1>
                <p><?php echo htmlspecialchars($_SESSION['full_name']); ?></p>
            </div>
            <div>
                <a href="dashboard.php" class="btn">← Back to Dashboard</a>
                <a href="../logout.php" class="btn btn-danger">Logout</a>
            </div>
        </div>

        <div class="table-container">
            <h3 style="padding: 20px; margin: 0; background: #f8f9fa; border-bottom: 1px solid #e9ecef;">
                My Assigned Patients
            </h3>
            <table class="table">
                <thead>
                    <tr>
                        <th>Patient ID</th>
                        <th>Name</th>
                        <th>Phone</th>
                        <th>Blood Group</th>
                        <th>Vitals Recorded</th>
                        <th>Last Vitals</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($patients->num_rows > 0): ?>
                        <?php while ($patient = $patients->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <span class="patient-id"><?php echo htmlspecialchars($patient['patient_id']); ?></span>
                                </td>
                                <td><?php echo htmlspecialchars($patient['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($patient['phone']); ?></td>
                                <td>
                                    <?php if ($patient['blood_group']): ?>
                                        <span class="blood-group"><?php echo htmlspecialchars($patient['blood_group']); ?></span>
                                    <?php else: ?>
                                        N/A
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $patient['vitals_count']; ?> times</td>
                                <td><?php echo date('M j, Y H:i', strtotime($patient['last_vitals'])); ?></td>
                                <td>
                                    <a href="vitals.php?patient_id=<?php echo $patient['id']; ?>" class="btn btn-sm">Record Vitals</a>
                                    <a href="patient_notes.php?patient_id=<?php echo $patient['id']; ?>" class="btn btn-sm">Add Notes</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 40px;">
                                No patients assigned yet. Patients will appear here after you record their vitals.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="table-container">
            <h3 style="padding: 20px; margin: 0; background: #f8f9fa; border-bottom: 1px solid #e9ecef;">
                All Active Patients
            </h3>
            <table class="table">
                <thead>
                    <tr>
                        <th>Patient ID</th>
                        <th>Name</th>
                        <th>Phone</th>
                        <th>Blood Group</th>
                        <th>Gender</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($patient = $all_patients->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <span class="patient-id"><?php echo htmlspecialchars($patient['patient_id']); ?></span>
                            </td>
                            <td><?php echo htmlspecialchars($patient['full_name']); ?></td>
                            <td><?php echo htmlspecialchars($patient['phone']); ?></td>
                            <td>
                                <?php if ($patient['blood_group']): ?>
                                    <span class="blood-group"><?php echo htmlspecialchars($patient['blood_group']); ?></span>
                                <?php else: ?>
                                    N/A
                                <?php endif; ?>
                            </td>
                            <td><?php echo ucfirst($patient['gender']); ?></td>
                            <td>
                                <a href="vitals.php?patient_id=<?php echo $patient['id']; ?>" class="btn btn-sm">Record Vitals</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>