<?php
require_once '../config/db.php';
check_login('pharmacist');

// Get date range from form or default to current month
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');

// Stock statistics
$stock_stats = $conn->query("
    SELECT 
        COUNT(*) as total_medicines,
        SUM(stock_quantity) as total_stock,
        SUM(CASE WHEN stock_quantity <= min_stock_level THEN 1 ELSE 0 END) as low_stock_count,
        SUM(CASE WHEN stock_quantity = 0 THEN 1 ELSE 0 END) as out_of_stock_count,
        AVG(unit_price) as avg_price,
        SUM(stock_quantity * unit_price) as total_inventory_value
    FROM medicines 
    WHERE status = 'active'
")->fetch_assoc();

// Low stock medicines
$low_stock = $conn->query("
    SELECT * FROM medicines 
    WHERE stock_quantity <= min_stock_level AND status = 'active'
    ORDER BY stock_quantity ASC
");

// Top medicines by value
$top_by_value = $conn->query("
    SELECT *, (stock_quantity * unit_price) as total_value
    FROM medicines 
    WHERE status = 'active'
    ORDER BY total_value DESC 
    LIMIT 10
");

// Category-wise stock
$category_stock = $conn->query("
    SELECT 
        COALESCE(category, 'Uncategorized') as category,
        COUNT(*) as medicine_count,
        SUM(stock_quantity) as total_quantity,
        SUM(stock_quantity * unit_price) as category_value
    FROM medicines 
    WHERE status = 'active'
    GROUP BY category
    ORDER BY category_value DESC
");

// Recent stock movements (simulated - in real system you'd have stock_movements table)
$recent_dispensed = $conn->query("
    SELECT p.*, pt.full_name as patient_name, d.full_name as doctor_name
    FROM prescriptions p
    JOIN patients pt ON p.patient_id = pt.id
    JOIN doctors d ON p.doctor_id = d.id
    WHERE p.status = 'dispensed' AND DATE(p.dispensed_at) BETWEEN '$start_date' AND '$end_date'
    ORDER BY p.dispensed_at DESC
    LIMIT 15
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stock Reports - Pharmacist Dashboard</title>
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

        .stat-label {
            color: #666;
            font-size: 0.9em;
        }

        .alert-low {
            color: #ff9800;
        }

        .alert-critical {
            color: #f44336;
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

        .filter-container {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        .filter-row {
            display: flex;
            gap: 20px;
            align-items: end;
        }

        .form-group {
            flex: 1;
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

            .filter-row {
                flex-direction: column;
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
                <h1>📊 Stock Reports & Analytics</h1>
                <p><?php echo htmlspecialchars($_SESSION['full_name']); ?></p>
            </div>
            <div>
                <a href="dashboard.php" class="btn">← Back to Dashboard</a>
                <a href="../logout.php" class="btn btn-danger">Logout</a>
            </div>
        </div>

        <div class="filter-container">
            <form method="GET" action="">
                <div class="filter-row">
                    <div class="form-group">
                        <label>Start Date:</label>
                        <input type="date" name="start_date" class="form-control" value="<?php echo $start_date; ?>">
                    </div>
                    <div class="form-group">
                        <label>End Date:</label>
                        <input type="date" name="end_date" class="form-control" value="<?php echo $end_date; ?>">
                    </div>
                    <div class="form-group">
                        <button type="submit" class="btn">Generate Report</button>
                    </div>
                </div>
            </form>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $stock_stats['total_medicines']; ?></div>
                <div class="stat-label">Total Medicines</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format($stock_stats['total_stock']); ?></div>
                <div class="stat-label">Total Stock Units</div>
            </div>
            <div class="stat-card">
                <div class="stat-number alert-low"><?php echo $stock_stats['low_stock_count']; ?></div>
                <div class="stat-label">Low Stock Items</div>
            </div>
            <div class="stat-card">
                <div class="stat-number alert-critical"><?php echo $stock_stats['out_of_stock_count']; ?></div>
                <div class="stat-label">Out of Stock</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">$<?php echo number_format($stock_stats['total_inventory_value'], 2); ?></div>
                <div class="stat-label">Inventory Value</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">$<?php echo number_format($stock_stats['avg_price'], 2); ?></div>
                <div class="stat-label">Average Price</div>
            </div>
        </div>

        <div class="table-container">
            <h3 style="padding: 20px; margin: 0; background: #f8f9fa; border-bottom: 1px solid #e9ecef;">
                ⚠️ Low Stock Alert
            </h3>
            <table class="table">
                <thead>
                    <tr>
                        <th>Medicine Name</th>
                        <th>Current Stock</th>
                        <th>Min Level</th>
                        <th>Unit Price</th>
                        <th>Category</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($low_stock->num_rows > 0): ?>
                        <?php while ($medicine = $low_stock->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($medicine['medicine_name']); ?></td>
                                <td class="<?php echo $medicine['stock_quantity'] == 0 ? 'alert-critical' : 'alert-low'; ?>">
                                    <?php echo $medicine['stock_quantity']; ?>
                                </td>
                                <td><?php echo $medicine['min_stock_level']; ?></td>
                                <td>$<?php echo number_format($medicine['unit_price'], 2); ?></td>
                                <td><?php echo htmlspecialchars($medicine['category'] ?: 'N/A'); ?></td>
                                <td>
                                    <?php if ($medicine['stock_quantity'] == 0): ?>
                                        <span style="color: #f44336; font-weight: bold;">OUT OF STOCK</span>
                                    <?php else: ?>
                                        <span style="color: #ff9800; font-weight: bold;">LOW STOCK</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 40px; color: #4caf50;">
                                ✅ All medicines are adequately stocked
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="table-container">
            <h3 style="padding: 20px; margin: 0; background: #f8f9fa; border-bottom: 1px solid #e9ecef;">
                💰 Top Medicines by Inventory Value
            </h3>
            <table class="table">
                <thead>
                    <tr>
                        <th>Medicine Name</th>
                        <th>Stock Quantity</th>
                        <th>Unit Price</th>
                        <th>Total Value</th>
                        <th>Category</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($medicine = $top_by_value->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($medicine['medicine_name']); ?></td>
                            <td><?php echo number_format($medicine['stock_quantity']); ?></td>
                            <td>$<?php echo number_format($medicine['unit_price'], 2); ?></td>
                            <td><strong>$<?php echo number_format($medicine['total_value'], 2); ?></strong></td>
                            <td><?php echo htmlspecialchars($medicine['category'] ?: 'N/A'); ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <div class="table-container">
            <h3 style="padding: 20px; margin: 0; background: #f8f9fa; border-bottom: 1px solid #e9ecef;">
                📈 Category-wise Stock Analysis
            </h3>
            <table class="table">
                <thead>
                    <tr>
                        <th>Category</th>
                        <th>Medicine Count</th>
                        <th>Total Quantity</th>
                        <th>Category Value</th>
                        <th>Avg Value per Medicine</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($category = $category_stock->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($category['category']); ?></td>
                            <td><?php echo $category['medicine_count']; ?></td>
                            <td><?php echo number_format($category['total_quantity']); ?></td>
                            <td>$<?php echo number_format($category['category_value'], 2); ?></td>
                            <td>$<?php echo number_format($category['category_value'] / $category['medicine_count'], 2); ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>