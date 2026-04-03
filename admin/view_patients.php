<?php
require_once '../config/db.php';
check_login('admin');

// Get all patients
$patients = $conn->query("
    SELECT p.*, r.full_name as registered_by_name 
    FROM patients p 
    LEFT JOIN receptionists r ON p.registered_by = r.id 
    ORDER BY p.created_at DESC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Patients - Hospital Management System</title>
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
            color: #2c5aa0;
            font-size: 2em;
        }

        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #2c5aa0;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-weight: 500;
        }

        .btn:hover {
            background: #1e3d6f;
        }

        .btn-success {
            background: #28a745;
        }

        .btn-success:hover {
            background: #218838;
        }

        .table-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .table-header {
            background: #f8f9fa;
            padding: 20px;
            border-bottom: 1px solid #eee;
        }

        .table-header h3 {
            color: #333;
            margin: 0;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
        }

        .table th,
        .table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #333;
        }

        .table tr:hover {
            background: #f8f9fa;
        }

        .badge {
            display: inline-block;
            padding: 4px 8px;
            font-size: 0.8em;
            font-weight: 500;
            border-radius: 12px;
            text-align: center;
        }

        .badge-active {
            background: #d4edda;
            color: #155724;
        }

        .badge-inactive {
            background: #f8d7da;
            color: #721c24;
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
            color: #2c5aa0;
            margin-bottom: 10px;
        }

        .stat-label {
            color: #666;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div>
                <h1>👤 View Patients</h1>
                <p>Patient records registered by receptionists</p>
            </div>
            <div>
                <a href="dashboard.php" class="btn">← Back to Dashboard</a>
            </div>
        </div>

        <?php
        $total_patients = $conn->query("SELECT COUNT(*) as count FROM patients")->fetch_assoc()['count'];
        $active_patients = $conn->query("SELECT COUNT(*) as count FROM patients WHERE status = 'active'")->fetch_assoc()['count'];
        $today_registered = $conn->query("SELECT COUNT(*) as count FROM patients WHERE DATE(created_at) = CURDATE()")->fetch_assoc()['count'];
        ?>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $total_patients; ?></div>
                <div class="stat-label">Total Patients</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $active_patients; ?></div>
                <div class="stat-label">Active Patients</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $today_registered; ?></div>
                <div class="stat-label">Registered Today</div>
            </div>
        </div>

        <div class="table-container">
            <div class="table-header">
                <h3>All Patients (<?php echo $total_patients; ?> total)</h3>
            </div>
            <table class="table">
                <thead>
                    <tr>
                        <th>Patient ID</th>
                        <th>Full Name</th>
                        <th>Phone</th>
                        <th>Email</th>
                        <th>Blood Group</th>
                        <th>Registered By</th>
                        <th>Status</th>
                        <th>Registered Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($patients->num_rows > 0): ?>
                        <?php while ($patient = $patients->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($patient['patient_id']); ?></td>
                                <td><?php echo htmlspecialchars($patient['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($patient['phone']); ?></td>
                                <td><?php echo htmlspecialchars($patient['email'] ?: '-'); ?></td>
                                <td><?php echo htmlspecialchars($patient['blood_group'] ?: '-'); ?></td>
                                <td><?php echo htmlspecialchars($patient['registered_by_name'] ?: 'Unknown'); ?></td>
                                <td>
                                    <span class="badge badge-<?php echo $patient['status']; ?>">
                                        <?php echo ucfirst($patient['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M j, Y', strtotime($patient['created_at'])); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 40px;">No patients registered yet</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>