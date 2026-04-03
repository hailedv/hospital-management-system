<?php
require_once '../config/db.php';
check_login('lab_technician');

$tech_id = $_SESSION['user_id'];

// Get dashboard statistics
$stats = [];
$stats['pending_tests'] = $conn->query("SELECT COUNT(*) as count FROM lab_tests WHERE assigned_to = $tech_id AND status = 'pending'")->fetch_assoc()['count'];
$stats['in_progress_tests'] = $conn->query("SELECT COUNT(*) as count FROM lab_tests WHERE assigned_to = $tech_id AND status = 'in_progress'")->fetch_assoc()['count'];
$stats['completed_today'] = $conn->query("SELECT COUNT(*) as count FROM lab_tests WHERE assigned_to = $tech_id AND status = 'completed' AND DATE(completed_at) = CURDATE()")->fetch_assoc()['count'];
$stats['total_tests'] = $conn->query("SELECT COUNT(*) as count FROM lab_tests WHERE assigned_to = $tech_id")->fetch_assoc()['count'];

// Get pending tests
$pending_tests = $conn->query("
    SELECT lt.*, p.full_name as patient_name, p.patient_id, d.full_name as doctor_name 
    FROM lab_tests lt 
    JOIN patients p ON lt.patient_id = p.id 
    JOIN doctors d ON lt.doctor_id = d.id 
    WHERE lt.assigned_to = $tech_id AND lt.status IN ('pending', 'in_progress')
    ORDER BY lt.created_at ASC 
    LIMIT 10
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lab Technician Dashboard - Hospital Management System</title>
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
            color: #673ab7;
            font-size: 2em;
        }

        .header p {
            color: #666;
            margin: 5px 0;
        }

        .logout-btn {
            background: #dc3545;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            font-weight: 500;
        }

        .logout-btn:hover {
            background: #c82333;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }

        .stat-number {
            font-size: 2.5em;
            font-weight: bold;
            color: #673ab7;
            margin-bottom: 10px;
        }

        .stat-label {
            color: #666;
            font-size: 1.1em;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .feature-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }

        .feature-icon {
            font-size: 3em;
            margin-bottom: 15px;
        }

        .feature-card h3 {
            color: #673ab7;
            margin-bottom: 10px;
        }

        .feature-card p {
            color: #666;
            margin-bottom: 20px;
            line-height: 1.5;
        }

        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: #673ab7;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-weight: 500;
            transition: background 0.3s;
        }

        .btn:hover {
            background: #512da8;
        }

        .btn-success {
            background: #28a745;
        }

        .btn-success:hover {
            background: #218838;
        }

        .btn-info {
            background: #17a2b8;
        }

        .btn-info:hover {
            background: #138496;
        }

        .tests-section {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .tests-section h3 {
            color: #673ab7;
            margin-bottom: 20px;
        }

        .test-item {
            padding: 15px;
            border: 1px solid #eee;
            border-radius: 8px;
            margin-bottom: 15px;
            background: #f8f9fa;
        }

        .test-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .test-info {
            flex: 1;
        }

        .test-status {
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 0.8em;
            font-weight: 600;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-in_progress {
            background: #cce5ff;
            color: #004085;
        }

        .patient-id {
            background: #e3f2fd;
            color: #1976d2;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 0.8em;
            font-weight: 600;
        }

        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                text-align: center;
                gap: 15px;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .features-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div>
                <h1>🔬 Lab Technician Dashboard</h1>
                <p>Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?></p>
                <p>License: <?php echo htmlspecialchars($_SESSION['license_number']); ?> | Specialization: <?php echo htmlspecialchars($_SESSION['specialization']); ?></p>
            </div>
            <div>
                <a href="../logout.php" class="logout-btn">Logout</a>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['pending_tests']; ?></div>
                <div class="stat-label">Pending Tests</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['in_progress_tests']; ?></div>
                <div class="stat-label">In Progress</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['completed_today']; ?></div>
                <div class="stat-label">Completed Today</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['total_tests']; ?></div>
                <div class="stat-label">Total Assigned</div>
            </div>
        </div>

        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">🧪</div>
                <h3>Process Tests</h3>
                <p>View and process assigned laboratory tests and update their status.</p>
                <a href="process_tests.php" class="btn">Process Tests</a>
            </div>
            <div class="feature-card">
                <div class="feature-icon">📊</div>
                <h3>Test Results</h3>
                <p>Enter test results and generate reports for completed tests.</p>
                <a href="test_results.php" class="btn btn-success">Enter Results</a>
            </div>
            <div class="feature-card">
                <div class="feature-icon">📋</div>
                <h3>Test History</h3>
                <p>View complete history of all tests processed by you.</p>
                <a href="test_history.php" class="btn btn-info">View History</a>
            </div>
        </div>

        <div class="tests-section">
            <h3>My Assigned Tests</h3>
            <?php if ($pending_tests->num_rows > 0): ?>
                <?php while ($test = $pending_tests->fetch_assoc()): ?>
                    <div class="test-item">
                        <div class="test-header">
                            <div class="test-info">
                                <strong><?php echo htmlspecialchars($test['test_name']); ?></strong>
                                <span class="patient-id"><?php echo htmlspecialchars($test['patient_id']); ?></span>
                                <br>
                                <small>Patient: <?php echo htmlspecialchars($test['patient_name']); ?> | Doctor: Dr. <?php echo htmlspecialchars($test['doctor_name']); ?></small>
                            </div>
                            <div class="test-status status-<?php echo $test['status']; ?>">
                                <?php echo ucfirst(str_replace('_', ' ', $test['status'])); ?>
                            </div>
                        </div>
                        <div>
                            <strong>Type:</strong> <?php echo htmlspecialchars($test['test_type']); ?><br>
                            <strong>Description:</strong> <?php echo htmlspecialchars($test['description'] ?: 'No description'); ?><br>
                            <strong>Ordered:</strong> <?php echo date('M j, Y H:i', strtotime($test['created_at'])); ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p>No tests assigned to you at the moment.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>