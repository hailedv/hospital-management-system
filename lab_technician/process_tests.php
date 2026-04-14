<?php
require_once '../config/db.php';
check_login('lab_technician');

$page_title = 'Process Lab Tests';
$role_color = '#673ab7';
$role_class = 'lab_technician';
$message = '';
$error = '';
$tech_id = (int)$_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $test_id = (int)$_POST['test_id'];
    $action  = $_POST['action'];

    if ($action == 'start') {
        $stmt = $conn->prepare("UPDATE lab_tests SET status='in_progress', assigned_to=?, sample_collected_at=NOW() WHERE id=? AND status='pending'");
        $stmt->bind_param("ii", $tech_id, $test_id);
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            $message = "Test started successfully!";
            log_activity('start_test', "Started processing test ID $test_id");
        } else {
            $error = "Error starting test or test already processed.";
        }
    }
}

$pending_tests = $conn->query("
    SELECT lt.*, p.full_name patient_name, p.patient_id, d.full_name doctor_name
    FROM lab_tests lt
    JOIN patients p ON lt.patient_id = p.id
    JOIN doctors d ON lt.doctor_id = d.id
    WHERE lt.status='pending'
    ORDER BY lt.created_at ASC
");

$in_progress = $conn->query("
    SELECT lt.*, p.full_name patient_name, p.patient_id, d.full_name doctor_name
    FROM lab_tests lt
    JOIN patients p ON lt.patient_id = p.id
    JOIN doctors d ON lt.doctor_id = d.id
    WHERE lt.status='in_progress' AND lt.assigned_to=$tech_id
    ORDER BY lt.sample_collected_at ASC
");

include '../includes/header.php';
?>

<nav class="nav-menu">
    <ul>
        <li><a href="dashboard.php">Dashboard</a></li>
        <li><a href="process_tests.php" class="active">Process Tests</a></li>
        <li><a href="test_results.php">Test Results</a></li>
        <li><a href="test_history.php">Test History</a></li>
    </ul>
</nav>

<?php if ($message): ?>
    <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="table-container">
    <h3>Pending Tests</h3>
    <table class="table">
        <thead>
            <tr><th>Test ID</th><th>Patient</th><th>Doctor</th><th>Test Name</th><th>Type</th><th>Ordered</th><th>Action</th></tr>
        </thead>
        <tbody>
        <?php if ($pending_tests && $pending_tests->num_rows > 0): ?>
            <?php while ($t = $pending_tests->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($t['test_id']) ?></td>
                <td><?= htmlspecialchars($t['patient_name']) ?><br><small><?= htmlspecialchars($t['patient_id']) ?></small></td>
                <td>Dr. <?= htmlspecialchars($t['doctor_name']) ?></td>
                <td><?= htmlspecialchars($t['test_name']) ?></td>
                <td><?= htmlspecialchars($t['test_type']) ?></td>
                <td><?= date('M j, Y', strtotime($t['created_at'])) ?></td>
                <td>
                    <form method="POST" style="display:inline">
                        <input type="hidden" name="test_id" value="<?= $t['id'] ?>">
                        <input type="hidden" name="action" value="start">
                        <button type="submit" class="btn btn-sm" style="background:#673ab7;color:white">Start Processing</button>
                    </form>
                </td>
            </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="7" style="text-align:center;padding:30px">No pending tests</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<div class="table-container" style="margin-top:20px">
    <h3>Tests In Progress</h3>
    <table class="table">
        <thead>
            <tr><th>Test ID</th><th>Patient</th><th>Test Name</th><th>Started At</th><th>Action</th></tr>
        </thead>
        <tbody>
        <?php if ($in_progress && $in_progress->num_rows > 0): ?>
            <?php while ($t = $in_progress->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($t['test_id']) ?></td>
                <td><?= htmlspecialchars($t['patient_name']) ?><br><small><?= htmlspecialchars($t['patient_id']) ?></small></td>
                <td><?= htmlspecialchars($t['test_name']) ?></td>
                <td><?= date('M j, Y H:i', strtotime($t['sample_collected_at'])) ?></td>
                <td><a href="test_results.php?test_id=<?= $t['id'] ?>" class="btn btn-sm btn-doctor">Enter Results</a></td>
            </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="5" style="text-align:center;padding:30px">No tests in progress</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include '../includes/footer.php'; ?>
    $test_id = (int)$_POST['test_id'];
    $action = $_POST['action'];
    
    if ($action == 'start') {
        $stmt = $conn->prepare("UPDATE lab_tests SET status = 'in_progress', assigned_to = ?, sample_collected_at = NOW() WHERE id = ? AND status = 'pending'");
        $tech_id = $_SESSION['user_id'];
        $stmt->bind_param("ii", $tech_id, $test_id);
        
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            $message = "Test started successfully!";
            log_activity('start_test', "Started processing test ID $test_id");
        } else {
            $error = "Error starting test or test already processed.";
        }
    }
}

// Get pending tests
$pending_tests = $conn->query("
    SELECT lt.*, p.full_name as patient_name, p.patient_id, d.full_name as doctor_name 
    FROM lab_tests lt 
    JOIN patients p ON lt.patient_id = p.id 
    JOIN doctors d ON lt.doctor_id = d.id 
    WHERE lt.status = 'pending' 
    ORDER BY lt.created_at ASC
");

// Get tests in progress by this technician
$in_progress_tests = $conn->query("
    SELECT lt.*, p.full_name as patient_name, p.patient_id, d.full_name as doctor_name 
    FROM lab_tests lt 
    JOIN patients p ON lt.patient_id = p.id 
    JOIN doctors d ON lt.doctor_id = d.id 
    WHERE lt.status = 'in_progress' AND lt.assigned_to = {$_SESSION['user_id']}
    ORDER BY lt.sample_collected_at ASC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Process Tests - Lab Technician Dashboard</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container">
        <div class="dashboard-header">
            <div>
                <h1>🔬 Process Lab Tests</h1>
                <p><?php echo htmlspecialchars($_SESSION['full_name']); ?></p>
            </div>
            <div>
                <a href="dashboard.php" class="btn btn-lab">← Back to Dashboard</a>
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
            <h3>Pending Tests</h3>
            <table class="table">
                <thead>
                    <tr>
                        <th>Test ID</th>
                        <th>Patient</th>
                        <th>Doctor</th>
                        <th>Test Name</th>
                        <th>Test Type</th>
                        <th>Description</th>
                        <th>Ordered Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($pending_tests->num_rows > 0): ?>
                        <?php while ($test = $pending_tests->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($test['test_id']); ?></td>
                                <td>
                                    <?php echo htmlspecialchars($test['patient_name']); ?>
                                    <br><small><?php echo htmlspecialchars($test['patient_id']); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($test['doctor_name']); ?></td>
                                <td><?php echo htmlspecialchars($test['test_name']); ?></td>
                                <td><?php echo htmlspecialchars($test['test_type']); ?></td>
                                <td><?php echo htmlspecialchars($test['description'] ?: 'No description'); ?></td>
                                <td><?php echo date('M j, Y', strtotime($test['created_at'])); ?></td>
                                <td>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="test_id" value="<?php echo $test['id']; ?>">
                                        <input type="hidden" name="action" value="start">
                                        <button type="submit" class="btn btn-sm btn-lab">Start Processing</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 40px;">No pending tests</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="table-container">
            <h3>Tests in Progress</h3>
            <table class="table">
                <thead>
                    <tr>
                        <th>Test ID</th>
                        <th>Patient</th>
                        <th>Test Name</th>
                        <th>Started At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($in_progress_tests->num_rows > 0): ?>
                        <?php while ($test = $in_progress_tests->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($test['test_id']); ?></td>
                                <td>
                                    <?php echo htmlspecialchars($test['patient_name']); ?>
                                    <br><small><?php echo htmlspecialchars($test['patient_id']); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($test['test_name']); ?></td>
                                <td><?php echo date('M j, Y H:i', strtotime($test['sample_collected_at'])); ?></td>
                                <td>
                                    <a href="test_results.php?test_id=<?php echo $test['id']; ?>" class="btn btn-sm btn-success">Enter Results</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="text-align: center; padding: 40px;">No tests in progress</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script src="../assets/js/script.js"></script>
</body>
</html>