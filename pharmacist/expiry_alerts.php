<?php
require_once '../config/db.php';
check_login('pharmacist');

$message = '';
$error = '';

// Handle medicine disposal
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['dispose_medicine'])) {
    $medicine_id = (int)$_POST['medicine_id'];
    $disposal_reason = sanitize_input($_POST['disposal_reason']);
    
    if ($medicine_id && $disposal_reason) {
        // In a real system, you'd move to a disposal_log table and update stock
        $stmt = $conn->prepare("UPDATE medicines SET status = 'expired', stock_quantity = 0 WHERE id = ?");
        $stmt->bind_param("i", $medicine_id);
        
        if ($stmt->execute()) {
            $message = "Medicine marked as expired and removed from active stock.";
            log_activity('dispose_expired_medicine', "Disposed expired medicine ID $medicine_id: $disposal_reason");
        } else {
            $error = "Error disposing medicine: " . $conn->error;
        }
    }
}

// Get medicines expiring soon (within 30 days)
$expiring_soon = $conn->query("
    SELECT * FROM medicines 
    WHERE expiry_date IS NOT NULL 
    AND expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
    AND status = 'active'
    ORDER BY expiry_date ASC
");

// Get expired medicines
$expired_medicines = $conn->query("
    SELECT * FROM medicines 
    WHERE expiry_date IS NOT NULL 
    AND expiry_date < CURDATE()
    AND status = 'active'
    ORDER BY expiry_date ASC
");

// Get medicines without expiry dates
$no_expiry = $conn->query("
    SELECT * FROM medicines 
    WHERE expiry_date IS NULL 
    AND status = 'active'
    ORDER BY medicine_name
");

// Calculate statistics
$expiry_stats = $conn->query("
    SELECT 
        COUNT(CASE WHEN expiry_date < CURDATE() THEN 1 END) as expired_count,
        COUNT(CASE WHEN expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY) THEN 1 END) as expiring_week,
        COUNT(CASE WHEN expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 1 END) as expiring_month,
        COUNT(CASE WHEN expiry_date IS NULL THEN 1 END) as no_expiry_count,
        SUM(CASE WHEN expiry_date < CURDATE() THEN stock_quantity * unit_price ELSE 0 END) as expired_value
    FROM medicines 
    WHERE status = 'active'
")->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Expiry Alerts - Pharmacist Dashboard</title>
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

        .btn-warning {
            background: #ff9800;
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 0.9em;
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
            margin-bottom: 5px;
        }

        .stat-label {
            color: #666;
            font-size: 0.9em;
        }

        .stat-expired {
            color: #f44336;
        }

        .stat-warning {
            color: #ff9800;
        }

        .stat-info {
            color: #2196f3;
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
        }

        .table tbody tr:hover {
            background: #f8f9fa;
        }

        .expired-row {
            background: #ffebee !important;
        }

        .expiring-row {
            background: #fff3e0 !important;
        }

        .days-left {
            font-weight: bold;
        }

        .days-expired {
            color: #f44336;
        }

        .days-warning {
            color: #ff9800;
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
            margin: 15% auto;
            padding: 20px;
            border-radius: 10px;
            width: 80%;
            max-width: 500px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-control {
            width: 100%;
            padding: 10px;
            border: 2px solid #e1e5e9;
            border-radius: 5px;
        }

        @media (max-width: 768px) {
            .dashboard-header {
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
        <div class="dashboard-header">
            <div>
                <h1>⚠️ Medicine Expiry Management</h1>
                <p><?php echo htmlspecialchars($_SESSION['full_name']); ?></p>
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

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number stat-expired"><?php echo $expiry_stats['expired_count']; ?></div>
                <div class="stat-label">Expired Medicines</div>
            </div>
            <div class="stat-card">
                <div class="stat-number stat-warning"><?php echo $expiry_stats['expiring_week']; ?></div>
                <div class="stat-label">Expiring This Week</div>
            </div>
            <div class="stat-card">
                <div class="stat-number stat-warning"><?php echo $expiry_stats['expiring_month']; ?></div>
                <div class="stat-label">Expiring This Month</div>
            </div>
            <div class="stat-card">
                <div class="stat-number stat-info"><?php echo $expiry_stats['no_expiry_count']; ?></div>
                <div class="stat-label">No Expiry Date Set</div>
            </div>
            <div class="stat-card">
                <div class="stat-number stat-expired">$<?php echo number_format($expiry_stats['expired_value'], 2); ?></div>
                <div class="stat-label">Expired Stock Value</div>
            </div>
        </div>

        <?php if ($expired_medicines->num_rows > 0): ?>
            <div class="table-container">
                <h3 style="padding: 20px; margin: 0; background: #ffebee; border-bottom: 1px solid #e9ecef; color: #f44336;">
                    🚨 EXPIRED MEDICINES - Immediate Action Required
                </h3>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Medicine Name</th>
                            <th>Expiry Date</th>
                            <th>Days Expired</th>
                            <th>Stock Quantity</th>
                            <th>Unit Price</th>
                            <th>Total Loss</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($medicine = $expired_medicines->fetch_assoc()): ?>
                            <?php 
                            $days_expired = (strtotime('now') - strtotime($medicine['expiry_date'])) / (60 * 60 * 24);
                            $total_loss = $medicine['stock_quantity'] * $medicine['unit_price'];
                            ?>
                            <tr class="expired-row">
                                <td><?php echo htmlspecialchars($medicine['medicine_name']); ?></td>
                                <td><?php echo date('M j, Y', strtotime($medicine['expiry_date'])); ?></td>
                                <td class="days-left days-expired"><?php echo floor($days_expired); ?> days ago</td>
                                <td><?php echo $medicine['stock_quantity']; ?></td>
                                <td>$<?php echo number_format($medicine['unit_price'], 2); ?></td>
                                <td class="days-expired">$<?php echo number_format($total_loss, 2); ?></td>
                                <td>
                                    <button class="btn btn-danger btn-sm" onclick="openDisposeModal(<?php echo $medicine['id']; ?>, '<?php echo htmlspecialchars($medicine['medicine_name']); ?>')">
                                        Dispose
                                    </button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <div class="table-container">
            <h3 style="padding: 20px; margin: 0; background: #fff3e0; border-bottom: 1px solid #e9ecef; color: #ff9800;">
                ⏰ Medicines Expiring Soon (Next 30 Days)
            </h3>
            <table class="table">
                <thead>
                    <tr>
                        <th>Medicine Name</th>
                        <th>Expiry Date</th>
                        <th>Days Left</th>
                        <th>Stock Quantity</th>
                        <th>Category</th>
                        <th>Unit Price</th>
                        <th>Priority</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($expiring_soon->num_rows > 0): ?>
                        <?php while ($medicine = $expiring_soon->fetch_assoc()): ?>
                            <?php 
                            $days_left = (strtotime($medicine['expiry_date']) - strtotime('now')) / (60 * 60 * 24);
                            $priority = $days_left <= 7 ? 'HIGH' : ($days_left <= 14 ? 'MEDIUM' : 'LOW');
                            $priority_color = $days_left <= 7 ? '#f44336' : ($days_left <= 14 ? '#ff9800' : '#4caf50');
                            ?>
                            <tr class="expiring-row">
                                <td><?php echo htmlspecialchars($medicine['medicine_name']); ?></td>
                                <td><?php echo date('M j, Y', strtotime($medicine['expiry_date'])); ?></td>
                                <td class="days-left days-warning"><?php echo floor($days_left); ?> days</td>
                                <td><?php echo $medicine['stock_quantity']; ?></td>
                                <td><?php echo htmlspecialchars($medicine['category'] ?: 'N/A'); ?></td>
                                <td>$<?php echo number_format($medicine['unit_price'], 2); ?></td>
                                <td style="color: <?php echo $priority_color; ?>; font-weight: bold;">
                                    <?php echo $priority; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 40px; color: #4caf50;">
                                ✅ No medicines expiring in the next 30 days
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($no_expiry->num_rows > 0): ?>
            <div class="table-container">
                <h3 style="padding: 20px; margin: 0; background: #e3f2fd; border-bottom: 1px solid #e9ecef; color: #1976d2;">
                    📅 Medicines Without Expiry Dates
                </h3>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Medicine Name</th>
                            <th>Stock Quantity</th>
                            <th>Category</th>
                            <th>Unit Price</th>
                            <th>Manufacturer</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($medicine = $no_expiry->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($medicine['medicine_name']); ?></td>
                                <td><?php echo $medicine['stock_quantity']; ?></td>
                                <td><?php echo htmlspecialchars($medicine['category'] ?: 'N/A'); ?></td>
                                <td>$<?php echo number_format($medicine['unit_price'], 2); ?></td>
                                <td><?php echo htmlspecialchars($medicine['manufacturer'] ?: 'N/A'); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Dispose Medicine Modal -->
    <div id="disposeModal" class="modal">
        <div class="modal-content">
            <h3>Dispose Expired Medicine</h3>
            <form method="POST">
                <input type="hidden" id="dispose_medicine_id" name="medicine_id">
                <div class="form-group">
                    <label>Medicine:</label>
                    <input type="text" id="dispose_medicine_name" class="form-control" readonly>
                </div>
                <div class="form-group">
                    <label>Disposal Reason:</label>
                    <select name="disposal_reason" class="form-control" required>
                        <option value="">Select reason</option>
                        <option value="Expired - Safe disposal">Expired - Safe disposal</option>
                        <option value="Expired - Return to supplier">Expired - Return to supplier</option>
                        <option value="Damaged packaging">Damaged packaging</option>
                        <option value="Quality concerns">Quality concerns</option>
                        <option value="Recall notice">Recall notice</option>
                    </select>
                </div>
                <div style="text-align: right;">
                    <button type="button" class="btn" style="background: #6c757d;" onclick="closeDisposeModal()">Cancel</button>
                    <button type="submit" name="dispose_medicine" class="btn btn-danger">Dispose Medicine</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openDisposeModal(medicineId, medicineName) {
            document.getElementById('dispose_medicine_id').value = medicineId;
            document.getElementById('dispose_medicine_name').value = medicineName;
            document.getElementById('disposeModal').style.display = 'block';
        }

        function closeDisposeModal() {
            document.getElementById('disposeModal').style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('disposeModal');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }
    </script>
</body>
</html>