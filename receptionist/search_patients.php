<?php
require_once '../config/db.php';
check_login('receptionist');

$search_results = null;
$search_term = '';

// Handle search
if ($_SERVER['REQUEST_METHOD'] == 'POST' || isset($_GET['search'])) {
    $search_term = isset($_POST['search_term']) ? sanitize_input($_POST['search_term']) : sanitize_input($_GET['search']);
    
    if (!empty($search_term)) {
        $search_query = "
            SELECT p.*, r.full_name as registered_by_name 
            FROM patients p 
            LEFT JOIN receptionists r ON p.registered_by = r.id 
            WHERE p.patient_id LIKE ? 
               OR p.full_name LIKE ? 
               OR p.phone LIKE ? 
               OR p.email LIKE ? 
            ORDER BY p.created_at DESC
        ";
        
        $search_param = "%$search_term%";
        $stmt = $conn->prepare($search_query);
        $stmt->bind_param("ssss", $search_param, $search_param, $search_param, $search_param);
        $stmt->execute();
        $search_results = $stmt->get_result();
    }
}

// Get recent patients if no search
if (!$search_results) {
    $search_results = $conn->query("
        SELECT p.*, r.full_name as registered_by_name 
        FROM patients p 
        LEFT JOIN receptionists r ON p.registered_by = r.id 
        ORDER BY p.created_at DESC 
        LIMIT 20
    ");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Patients - Receptionist Dashboard</title>
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
            color: #ff9800;
            font-size: 2em;
        }

        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #ff9800;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-weight: 500;
            border: none;
            cursor: pointer;
        }

        .btn:hover {
            background: #f57c00;
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

        .search-container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }

        .search-form {
            display: flex;
            gap: 15px;
            align-items: end;
        }

        .form-group {
            flex: 1;
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
            border-color: #ff9800;
        }

        .results-container {
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

        .search-info {
            padding: 20px;
            background: #f8f9fa;
            border-bottom: 1px solid #e9ecef;
            color: #666;
        }

        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                text-align: center;
                gap: 15px;
            }

            .search-form {
                flex-direction: column;
            }

            .table {
                font-size: 0.9em;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div>
                <h1>🔍 Search Patients</h1>
                <p>Find and view patient records</p>
            </div>
            <div>
                <a href="dashboard.php" class="btn">← Back to Dashboard</a>
                <a href="../logout.php" class="btn btn-danger">Logout</a>
            </div>
        </div>

        <div class="search-container">
            <h3 style="margin-bottom: 20px; color: #ff9800;">Patient Search</h3>
            <form method="POST" action="" class="search-form">
                <div class="form-group">
                    <label for="search_term">Search by Patient ID, Name, Phone, or Email:</label>
                    <input type="text" name="search_term" id="search_term" class="form-control" 
                           value="<?php echo htmlspecialchars($search_term); ?>" 
                           placeholder="Enter patient ID, name, phone number, or email">
                </div>
                <button type="submit" class="btn">Search</button>
            </form>
        </div>

        <div class="results-container">
            <div class="search-info">
                <?php if ($search_term): ?>
                    Search results for: <strong>"<?php echo htmlspecialchars($search_term); ?>"</strong> 
                    (<?php echo $search_results->num_rows; ?> results found)
                <?php else: ?>
                    Showing recent patients (<?php echo $search_results->num_rows; ?> records)
                <?php endif; ?>
            </div>
            
            <table class="table">
                <thead>
                    <tr>
                        <th>Patient ID</th>
                        <th>Name</th>
                        <th>Phone</th>
                        <th>Email</th>
                        <th>Blood Group</th>
                        <th>Registered By</th>
                        <th>Date Registered</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($search_results->num_rows > 0): ?>
                        <?php while ($patient = $search_results->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <span class="patient-id"><?php echo htmlspecialchars($patient['patient_id']); ?></span>
                                </td>
                                <td><?php echo htmlspecialchars($patient['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($patient['phone']); ?></td>
                                <td><?php echo htmlspecialchars($patient['email'] ?: 'N/A'); ?></td>
                                <td>
                                    <?php if ($patient['blood_group']): ?>
                                        <span class="blood-group"><?php echo htmlspecialchars($patient['blood_group']); ?></span>
                                    <?php else: ?>
                                        N/A
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($patient['registered_by_name'] ?: 'Unknown'); ?></td>
                                <td><?php echo date('M j, Y', strtotime($patient['created_at'])); ?></td>
                                <td>
                                    <a href="book_appointment.php?patient_id=<?php echo $patient['id']; ?>" class="btn btn-sm">Book Appointment</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 40px;">
                                <?php if ($search_term): ?>
                                    No patients found matching "<?php echo htmlspecialchars($search_term); ?>"
                                <?php else: ?>
                                    No patients registered yet
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>