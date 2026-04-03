<?php
require_once '../config/db.php';
check_login('doctor');

$doctor_id = $_SESSION['user_id'];

// Get doctor's patients (patients who have appointments with this doctor)
$patients = $conn->query("
    SELECT DISTINCT p.*, 
           COUNT(a.id) as total_appointments,
           MAX(a.appointment_date) as last_appointment,
           COUNT(pr.id) as total_prescriptions
    FROM patients p 
    LEFT JOIN appointments a ON p.id = a.patient_id AND a.doctor_id = $doctor_id
    LEFT JOIN prescriptions pr ON p.id = pr.patient_id AND pr.doctor_id = $doctor_id
    WHERE a.id IS NOT NULL
    GROUP BY p.id 
    ORDER BY last_appointment DESC, p.full_name ASC
");

// Get all patients for search/assignment
$all_patients = $conn->query("
    SELECT p.*, 
           COUNT(a.id) as appointment_count
    FROM patients p 
    LEFT JOIN appointments a ON p.id = a.patient_id 
    WHERE p.status = 'active'
    GROUP BY p.id 
    ORDER BY p.full_name ASC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Patients - Doctor Dashboard</title>
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
            color: #2196f3;
            font-size: 2em;
        }

        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #2196f3;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-weight: 500;
            border: none;
            cursor: pointer;
        }

        .btn:hover {
            background: #1976d2;
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

        .btn-success {
            background: #28a745;
        }

        .btn-success:hover {
            background: #218838;
        }

        .search-container {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        .search-row {
            display: flex;
            gap: 15px;
            align-items: end;
        }

        .form-group {
            flex: 1;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
        }

        .form-control {
            width: 100%;
            padding: 10px;
            border: 2px solid #e1e5e9;
            border-radius: 5px;
            font-size: 14px;
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

        .stats-badge {
            background: #e8f5e8;
            color: #2e7d32;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 0.8em;
        }

        .tabs {
            display: flex;
            background: white;
            border-radius: 10px 10px 0 0;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 0;
        }

        .tab {
            flex: 1;
            padding: 15px 20px;
            background: #f8f9fa;
            border: none;
            cursor: pointer;
            font-weight: 600;
            transition: background 0.3s;
        }

        .tab.active {
            background: white;
            color: #2196f3;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        @media (max-width: 768px) {
            .dashboard-header {
                flex-direction: column;
                text-align: center;
                gap: 15px;
            }

            .search-row {
                flex-direction: column;
            }

            .tabs {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="dashboard-header">
            <div>
                <h1>👥 My Patients</h1>
                <p>Dr. <?php echo htmlspecialchars($_SESSION['full_name']); ?></p>
            </div>
            <div>
                <a href="dashboard.php" class="btn">← Back to Dashboard</a>
                <a href="../logout.php" class="btn btn-danger">Logout</a>
            </div>
        </div>

        <div class="search-container">
            <div class="search-row">
                <div class="form-group">
                    <label for="search_patient">Search Patients:</label>
                    <input type="text" id="search_patient" class="form-control" placeholder="Search by name, patient ID, or phone..." onkeyup="searchPatients()">
                </div>
                <div class="form-group">
                    <button class="btn btn-success" onclick="showAllPatients()">Show All Patients</button>
                </div>
            </div>
        </div>

        <div class="tabs">
            <button class="tab active" onclick="showTab('my-patients')">My Patients</button>
            <button class="tab" onclick="showTab('all-patients')">All Patients</button>
        </div>

        <div id="my-patients" class="tab-content active">
            <div class="table-container">
                <h3 style="padding: 20px; margin: 0; background: #f8f9fa; border-bottom: 1px solid #e9ecef;">
                    Patients Under My Care
                </h3>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Patient ID</th>
                            <th>Name</th>
                            <th>Phone</th>
                            <th>Blood Group</th>
                            <th>Age</th>
                            <th>Appointments</th>
                            <th>Prescriptions</th>
                            <th>Last Visit</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($patients->num_rows > 0): ?>
                            <?php while ($patient = $patients->fetch_assoc()): ?>
                                <?php 
                                $age = $patient['date_of_birth'] ? 
                                    floor((time() - strtotime($patient['date_of_birth'])) / (365.25 * 24 * 60 * 60)) : 'N/A';
                                ?>
                                <tr>
                                    <td>
                                        <span class="patient-id"><?php echo htmlspecialchars($patient['patient_id']); ?></span>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($patient['full_name']); ?></strong>
                                        <br><small><?php echo ucfirst($patient['gender']); ?></small>
                                    </td>
                                    <td><?php echo htmlspecialchars($patient['phone']); ?></td>
                                    <td>
                                        <?php if ($patient['blood_group']): ?>
                                            <span class="blood-group"><?php echo htmlspecialchars($patient['blood_group']); ?></span>
                                        <?php else: ?>
                                            N/A
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $age; ?></td>
                                    <td><span class="stats-badge"><?php echo $patient['total_appointments']; ?></span></td>
                                    <td><span class="stats-badge"><?php echo $patient['total_prescriptions']; ?></span></td>
                                    <td>
                                        <?php if ($patient['last_appointment']): ?>
                                            <?php echo date('M j, Y', strtotime($patient['last_appointment'])); ?>
                                        <?php else: ?>
                                            Never
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="patient_details.php?id=<?php echo $patient['id']; ?>" class="btn btn-sm">View Details</a>
                                        <a href="prescriptions.php?patient_id=<?php echo $patient['id']; ?>" class="btn btn-sm btn-success">Prescribe</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" style="text-align: center; padding: 40px;">
                                    No patients assigned yet. Patients will appear here after appointments are scheduled.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div id="all-patients" class="tab-content">
            <div class="table-container">
                <h3 style="padding: 20px; margin: 0; background: #f8f9fa; border-bottom: 1px solid #e9ecef;">
                    All Hospital Patients
                </h3>
                <table class="table" id="all-patients-table">
                    <thead>
                        <tr>
                            <th>Patient ID</th>
                            <th>Name</th>
                            <th>Phone</th>
                            <th>Blood Group</th>
                            <th>Age</th>
                            <th>Total Appointments</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($patient = $all_patients->fetch_assoc()): ?>
                            <?php 
                            $age = $patient['date_of_birth'] ? 
                                floor((time() - strtotime($patient['date_of_birth'])) / (365.25 * 24 * 60 * 60)) : 'N/A';
                            ?>
                            <tr>
                                <td>
                                    <span class="patient-id"><?php echo htmlspecialchars($patient['patient_id']); ?></span>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($patient['full_name']); ?></strong>
                                    <br><small><?php echo ucfirst($patient['gender']); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($patient['phone']); ?></td>
                                <td>
                                    <?php if ($patient['blood_group']): ?>
                                        <span class="blood-group"><?php echo htmlspecialchars($patient['blood_group']); ?></span>
                                    <?php else: ?>
                                        N/A
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $age; ?></td>
                                <td><span class="stats-badge"><?php echo $patient['appointment_count']; ?></span></td>
                                <td>
                                    <a href="patient_details.php?id=<?php echo $patient['id']; ?>" class="btn btn-sm">View Details</a>
                                    <a href="prescriptions.php?patient_id=<?php echo $patient['id']; ?>" class="btn btn-sm btn-success">Prescribe</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        function showTab(tabName) {
            // Hide all tab contents
            const contents = document.querySelectorAll('.tab-content');
            contents.forEach(content => content.classList.remove('active'));
            
            // Remove active class from all tabs
            const tabs = document.querySelectorAll('.tab');
            tabs.forEach(tab => tab.classList.remove('active'));
            
            // Show selected tab content
            document.getElementById(tabName).classList.add('active');
            
            // Add active class to clicked tab
            event.target.classList.add('active');
        }

        function searchPatients() {
            const searchTerm = document.getElementById('search_patient').value.toLowerCase();
            const table = document.getElementById('all-patients-table');
            const rows = table.getElementsByTagName('tr');
            
            for (let i = 1; i < rows.length; i++) {
                const row = rows[i];
                const text = row.textContent.toLowerCase();
                
                if (text.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            }
        }

        function showAllPatients() {
            document.getElementById('search_patient').value = '';
            searchPatients();
            showTab('all-patients');
        }
    </script>
</body>
</html>