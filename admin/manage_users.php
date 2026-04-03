<?php
require_once '../config/db.php';
check_login('admin');

$user_type = $_GET['type'] ?? 'all';
$message = '';

// Handle user status updates
if ($_POST['action'] ?? '' == 'update_status') {
    $user_id = (int)$_POST['user_id'];
    $table = $_POST['table'];
    $new_status = $_POST['status'] == 'active' ? 'inactive' : 'active';
    
    $stmt = $conn->prepare("UPDATE $table SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $new_status, $user_id);
    
    if ($stmt->execute()) {
        $message = "User status updated successfully!";
        log_activity('update_user_status', "Updated $table user ID $user_id to $new_status");
    } else {
        $message = "Error updating user status.";
    }
}

// Get users based on type with error handling
$users = [];

try {
    if ($user_type == 'all' || $user_type == 'doctor') {
        $result = $conn->query("SELECT 'doctor' as type, id, username, full_name, email, phone, specialization, status FROM doctors ORDER BY full_name");
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $users[] = $row;
            }
        }
    }
    
    if ($user_type == 'all' || $user_type == 'nurse') {
        $result = $conn->query("SELECT 'nurse' as type, id, username, full_name, email, phone, shift as specialization, status FROM nurses ORDER BY full_name");
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $users[] = $row;
            }
        }
    }
    
    if ($user_type == 'all' || $user_type == 'staff') {
        // Receptionists
        $result = $conn->query("SELECT 'receptionist' as type, id, username, full_name, email, phone, shift as specialization, status FROM receptionists ORDER BY full_name");
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $users[] = $row;
            }
        }
        
        // Pharmacists
        $result = $conn->query("SELECT 'pharmacist' as type, id, username, full_name, email, phone, license_number as specialization, status FROM pharmacists ORDER BY full_name");
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $users[] = $row;
            }
        }
        
        // Accountants
        $result = $conn->query("SELECT 'accountant' as type, id, username, full_name, email, phone, '' as specialization, status FROM accountants ORDER BY full_name");
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $users[] = $row;
            }
        }
    }
    
    if ($user_type == 'all' || $user_type == 'admin') {
        $result = $conn->query("SELECT 'admin' as type, id, username, full_name, email, phone, '' as specialization, status FROM admins ORDER BY full_name");
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $users[] = $row;
            }
        }
    }
} catch (Exception $e) {
    $message = "Error loading users: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Staff - Hospital Management System</title>
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

        .alert {
            padding: 12px;
            margin-bottom: 20px;
            border-radius: 5px;
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .nav-menu {
            background: white;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .nav-menu ul {
            list-style: none;
            display: flex;
            padding: 0;
            margin: 0;
        }

        .nav-menu li {
            flex: 1;
        }

        .nav-menu a {
            display: block;
            padding: 15px 20px;
            text-decoration: none;
            color: #666;
            text-align: center;
            border-right: 1px solid #eee;
            transition: all 0.3s;
        }

        .nav-menu a:hover,
        .nav-menu a.active {
            background: #2c5aa0;
            color: white;
        }

        .nav-menu li:last-child a {
            border-right: none;
        }

        .btn {
            display: inline-block;
            padding: 8px 16px;
            text-decoration: none;
            border-radius: 5px;
            font-weight: 500;
            text-align: center;
            cursor: pointer;
            border: none;
            transition: all 0.3s;
            font-size: 0.9em;
        }

        .btn-admin {
            background: #dc3545;
            color: white;
        }

        .btn-admin:hover {
            background: #c82333;
        }

        .btn-doctor {
            background: #28a745;
            color: white;
        }

        .btn-doctor:hover {
            background: #218838;
        }

        .btn-accountant {
            background: #6c757d;
            color: white;
        }

        .btn-accountant:hover {
            background: #545b62;
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 0.8em;
        }

        .table-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .table-container h3 {
            padding: 20px;
            margin: 0;
            background: #f8f9fa;
            border-bottom: 1px solid #e9ecef;
            color: #333;
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
            font-size: 0.75em;
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

        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                text-align: center;
                gap: 15px;
            }

            .nav-menu ul {
                flex-direction: column;
            }

            .nav-menu a {
                border-right: none;
                border-bottom: 1px solid #eee;
            }

            .table-container {
                overflow-x: auto;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div>
                <h1>👥 Manage Staff</h1>
                <p>Manage system users and their access</p>
            </div>
            <div>
                <a href="add_staff.php" class="btn btn-doctor" style="margin-right: 10px;">➕ Add New Staff</a>
                <a href="dashboard.php" class="btn btn-admin">← Back to Dashboard</a>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="alert"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <?php
        // Get statistics
        $total_doctors = $conn->query("SELECT COUNT(*) as count FROM doctors WHERE status = 'active'")->fetch_assoc()['count'] ?? 0;
        $total_nurses = $conn->query("SELECT COUNT(*) as count FROM nurses WHERE status = 'active'")->fetch_assoc()['count'] ?? 0;
        $total_staff = $conn->query("SELECT COUNT(*) as count FROM receptionists WHERE status = 'active'")->fetch_assoc()['count'] ?? 0;
        $total_staff += $conn->query("SELECT COUNT(*) as count FROM pharmacists WHERE status = 'active'")->fetch_assoc()['count'] ?? 0;
        $total_staff += $conn->query("SELECT COUNT(*) as count FROM accountants WHERE status = 'active'")->fetch_assoc()['count'] ?? 0;
        ?>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $total_doctors; ?></div>
                <div class="stat-label">Active Doctors</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $total_nurses; ?></div>
                <div class="stat-label">Active Nurses</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $total_staff; ?></div>
                <div class="stat-label">Other Staff</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo count($users); ?></div>
                <div class="stat-label">Total Shown</div>
            </div>
        </div>

        <div class="nav-menu">
            <ul>
                <li><a href="?type=all" <?php echo $user_type == 'all' ? 'class="active"' : ''; ?>>All Users</a></li>
                <li><a href="?type=doctor" <?php echo $user_type == 'doctor' ? 'class="active"' : ''; ?>>Doctors</a></li>
                <li><a href="?type=nurse" <?php echo $user_type == 'nurse' ? 'class="active"' : ''; ?>>Nurses</a></li>
                <li><a href="?type=staff" <?php echo $user_type == 'staff' ? 'class="active"' : ''; ?>>Other Staff</a></li>
                <li><a href="?type=admin" <?php echo $user_type == 'admin' ? 'class="active"' : ''; ?>>Admins</a></li>
            </ul>
        </div>

        <div class="table-container">
            <h3>System Users (<?php echo count($users); ?> users)</h3>
            <table class="table">
                <thead>
                    <tr>
                        <th>Type</th>
                        <th>Name</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Details</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($users) > 0): ?>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo ucfirst($user['type']); ?></td>
                                <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><?php echo htmlspecialchars($user['phone']); ?></td>
                                <td><?php echo htmlspecialchars($user['specialization'] ?: '-'); ?></td>
                                <td>
                                    <span class="badge badge-<?php echo $user['status']; ?>">
                                        <?php echo ucfirst($user['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="update_status">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        <input type="hidden" name="table" value="<?php echo $user['type']; ?>s">
                                        <input type="hidden" name="status" value="<?php echo $user['status']; ?>">
                                        <button type="submit" class="btn btn-sm <?php echo $user['status'] == 'active' ? 'btn-accountant' : 'btn-doctor'; ?>">
                                            <?php echo $user['status'] == 'active' ? 'Deactivate' : 'Activate'; ?>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 40px;">No users found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>