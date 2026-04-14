<?php
require_once '../config/db.php';
check_login('lab_technician');

$page_title = 'Enter Test Results';
$role_color = '#673ab7';
$role_class = 'lab_technician';
$message = '';
$error = '';
$test = null;
$tech_id = (int)$_SESSION['user_id'];

if (isset($_GET['test_id'])) {
    $test_id = (int)$_GET['test_id'];
    $stmt = $conn->prepare("
        SELECT lt.*, p.full_name patient_name, p.patient_id, d.full_name doctor_name
        FROM lab_tests lt
        JOIN patients p ON lt.patient_id = p.id
        JOIN doctors d ON lt.doctor_id = d.id
        WHERE lt.id=? AND lt.assigned_to=? AND lt.status='in_progress'
    ");
    $stmt->bind_param("ii", $test_id, $tech_id);
    $stmt->execute();
    $test = $stmt->get_result()->fetch_assoc();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $test_id = (int)$_POST['test_id'];
    $result  = sanitize_input($_POST['result'] ?? '');

    if (empty($test_id) || empty($result)) {
        $error = "Please fill in all required fields.";
    } else {
        $stmt = $conn->prepare("UPDATE lab_tests SET result=?, status='completed', completed_at=NOW() WHERE id=? AND assigned_to=?");
        $stmt->bind_param("sii", $result, $test_id, $tech_id);
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            $message = "Test results submitted successfully!";
            log_activity('complete_test', "Completed test ID $test_id");
            $test = null;
        } else {
            $error = "Error submitting results.";
        }
    }
}

include '../includes/header.php';
?>

<nav class="nav-menu">
    <ul>
        <li><a href="dashboard.php">Dashboard</a></li>
        <li><a href="process_tests.php">Process Tests</a></li>
        <li><a href="test_results.php" class="active">Test Results</a></li>
        <li><a href="test_history.php">Test History</a></li>
    </ul>
</nav>

<?php if ($message): ?>
    <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<?php if ($test): ?>
<div class="table-container" style="padding:0">
    <h3>Enter Results for: <?= htmlspecialchars($test['test_name']) ?></h3>
    <div style="padding:24px">
        <div style="background:#f0f4ff;padding:14px;border-radius:6px;margin-bottom:20px;font-size:0.9em">
            <strong>Test ID:</strong> <?= htmlspecialchars($test['test_id']) ?> &nbsp;|&nbsp;
            <strong>Patient:</strong> <?= htmlspecialchars($test['patient_name']) ?> (<?= htmlspecialchars($test['patient_id']) ?>) &nbsp;|&nbsp;
            <strong>Doctor:</strong> Dr. <?= htmlspecialchars($test['doctor_name']) ?> &nbsp;|&nbsp;
            <strong>Type:</strong> <?= htmlspecialchars($test['test_type']) ?>
        </div>
        <form method="POST">
            <input type="hidden" name="test_id" value="<?= $test['id'] ?>">
            <div class="form-group">
                <label>Test Results <span style="color:red">*</span></label>
                <textarea name="result" class="form-control" rows="10" required placeholder="Enter detailed test results..."></textarea>
            </div>
            <button type="submit" class="btn btn-doctor">Submit Results</button>
        </form>
    </div>
</div>
<?php else: ?>
<div class="table-container" style="padding:24px;text-align:center">
    <p>No test selected. <a href="process_tests.php">Go to Process Tests</a> to pick a test in progress.</p>
</div>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>
    $test_id = (int)$_GET['test_id'];
    $stmt = $conn->prepare("
        SELECT lt.*, p.full_name as patient_name, p.patient_id, d.full_name as doctor_name 
        FROM lab_tests lt 
        JOIN patients p ON lt.patient_id = p.id 
        JOIN doctors d ON lt.doctor_id = d.id 
        WHERE lt.id = ? AND lt.assigned_to = ? AND lt.status = 'in_progress'
    ");
    $tech_id = $_SESSION['user_id'];
    $stmt->bind_param("ii", $test_id, $tech_id);
    $stmt->execute();
    $test = $stmt->get_result()->fetch_assoc();
}

// Handle result submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $test_id = (int)$_POST['test_id'];
    $result = sanitize_input($_POST['result']);
    
    if (empty($test_id) || empty($result)) {
        $error = "Please fill in all required fields.";
    } else {
        $stmt = $conn->prepare("UPDATE lab_tests SET result = ?, status = 'completed', completed_at = NOW() WHERE id = ? AND assigned_to = ?");
        $tech_id = $_SESSION['user_id'];
        $stmt->bind_param("sii", $result, $test_id, $tech_id);
        
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            $message = "Test results submitted successfully!";
            log_activity('complete_test', "Completed test ID $test_id");
            $test = null; // Clear test data
        } else {
            $error = "Error submitting results.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enter Test Results - Lab Technician Dashboard</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container">
        <div class="dashboard-header">
            <div>
                <h1>📋 Enter Test Results</h1>
                <p><?php echo htmlspecialchars($_SESSION['full_name']); ?></p>
            </div>
            <div>
                <a href="process_tests.php" class="btn btn-lab">← Back to Tests</a>
                <a href="dashboard.php" class="btn btn-lab">Dashboard</a>
                <a href="../logout.php" class="btn btn-danger">Logout</a>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($test): ?>
            <div class="form-container">
                <h3>Test Information</h3>
                <div class="test-info">
                    <p><strong>Test ID:</strong> <?php echo htmlspecialchars($test['test_id']); ?></p>
                    <p><strong>Patient:</strong> <?php echo htmlspecialchars($test['patient_name']); ?> (<?php echo htmlspecialchars($test['patient_id']); ?>)</p>
                    <p><strong>Doctor:</strong> <?php echo htmlspecialchars($test['doctor_name']); ?></p>
                    <p><strong>Test Name:</strong> <?php echo htmlspecialchars($test['test_name']); ?></p>
                    <p><strong>Test Type:</strong> <?php echo htmlspecialchars($test['test_type']); ?></p>
                    <p><strong>Description:</strong> <?php echo htmlspecialchars($test['description'] ?: 'No description'); ?></p>
                </div>

                <form method="POST" action="">
                    <input type="hidden" name="test_id" value="<?php echo $test['id']; ?>">
                    
                    <div class="form-group">
                        <label for="result">Test Results:</label>
                        <textarea name="result" id="result" class="form-control" rows="10" required 
                                  placeholder="Enter detailed test results here..."></textarea>
                    </div>

                    <button type="submit" class="btn btn-success">Submit Results</button>
                </form>
            </div>
        <?php else: ?>
            <div class="info-container">
                <h3>No Test Selected</h3>
                <p>Please select a test from the <a href="process_tests.php">Process Tests</a> page to enter results.</p>
            </div>
        <?php endif; ?>
    </div>

    <script src="../assets/js/script.js"></script>
</body>
</html>