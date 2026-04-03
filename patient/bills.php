<?php
require_once '../config/db.php';
check_login('patient');

$patient_id = $_SESSION['user_id'];

// Get patient's bills
$bills = $conn->query("
    SELECT b.*, a.full_name as generated_by_name 
    FROM bills b 
    LEFT JOIN accountants a ON b.generated_by = a.id 
    WHERE b.patient_id = $patient_id 
    ORDER BY b.created_at DESC
");

// Calculate totals
$totals = $conn->query("
    SELECT 
        COUNT(*) as total_bills,
        SUM(final_amount) as total_amount,
        SUM(CASE WHEN status = 'paid' THEN final_amount ELSE 0 END) as paid_amount,
        SUM(CASE WHEN status IN ('pending', 'partial') THEN final_amount - COALESCE(amount_paid, 0) ELSE 0 END) as outstanding_amount
    FROM bills 
    WHERE patient_id = $patient_id
")->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Bills - Patient Dashboard</title>
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
            color: #2196f3;
            margin-bottom: 5px;
        }

        .stat-label {
            color: #666;
            font-size: 0.9em;
        }

        .table-container {
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

        .badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.8em;
            font-weight: 600;
        }

        .badge-pending {
            background: #fff3cd;
            color: #856404;
        }

        .badge-paid {
            background: #d4edda;
            color: #155724;
        }

        .badge-partial {
            background: #cce5ff;
            color: #004085;
        }

        .outstanding {
            color: #dc3545;
            font-weight: 600;
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
                <h1>💳 My Bills</h1>
                <p><?php echo htmlspecialchars($_SESSION['full_name']); ?></p>
            </div>
            <div>
                <a href="dashboard.php" class="btn">← Back to Dashboard</a>
                <a href="../logout.php" class="btn btn-danger">Logout</a>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $totals['total_bills']; ?></div>
                <div class="stat-label">Total Bills</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">$<?php echo number_format($totals['total_amount'], 2); ?></div>
                <div class="stat-label">Total Amount</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">$<?php echo number_format($totals['paid_amount'], 2); ?></div>
                <div class="stat-label">Amount Paid</div>
            </div>
            <div class="stat-card">
                <div class="stat-number outstanding">$<?php echo number_format($totals['outstanding_amount'], 2); ?></div>
                <div class="stat-label">Outstanding</div>
            </div>
        </div>

        <div class="table-container">
            <h3 style="padding: 20px; margin: 0; background: #f8f9fa; border-bottom: 1px solid #e9ecef;">
                My Medical Bills
            </h3>
            <table class="table">
                <thead>
                    <tr>
                        <th>Bill ID</th>
                        <th>Date</th>
                        <th>Services</th>
                        <th>Total Amount</th>
                        <th>Discount</th>
                        <th>Final Amount</th>
                        <th>Status</th>
                        <th>Payment Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($bills->num_rows > 0): ?>
                        <?php while ($bill = $bills->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($bill['bill_id']); ?></td>
                                <td><?php echo date('M j, Y', strtotime($bill['created_at'])); ?></td>
                                <td>
                                    <?php if ($bill['consultation_fee'] > 0): ?>
                                        Consultation: $<?php echo number_format($bill['consultation_fee'], 2); ?><br>
                                    <?php endif; ?>
                                    <?php if ($bill['medicine_cost'] > 0): ?>
                                        Medicine: $<?php echo number_format($bill['medicine_cost'], 2); ?><br>
                                    <?php endif; ?>
                                    <?php if ($bill['lab_charges'] > 0): ?>
                                        Lab Tests: $<?php echo number_format($bill['lab_charges'], 2); ?><br>
                                    <?php endif; ?>
                                    <?php if ($bill['other_charges'] > 0): ?>
                                        Other: $<?php echo number_format($bill['other_charges'], 2); ?>
                                    <?php endif; ?>
                                </td>
                                <td>$<?php echo number_format($bill['total_amount'], 2); ?></td>
                                <td>$<?php echo number_format($bill['discount'], 2); ?></td>
                                <td><strong>$<?php echo number_format($bill['final_amount'], 2); ?></strong></td>
                                <td>
                                    <span class="badge badge-<?php echo $bill['status']; ?>">
                                        <?php echo ucfirst($bill['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($bill['paid_at']): ?>
                                        <?php echo date('M j, Y', strtotime($bill['paid_at'])); ?>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 40px;">No bills found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($totals['outstanding_amount'] > 0): ?>
            <div style="background: #fff3cd; border: 1px solid #ffeaa7; padding: 20px; border-radius: 10px; margin-top: 20px;">
                <h4 style="color: #856404; margin-bottom: 10px;">💡 Payment Information</h4>
                <p style="color: #856404;">You have outstanding bills totaling $<?php echo number_format($totals['outstanding_amount'], 2); ?>. Please contact the billing department to arrange payment.</p>
                <p style="color: #856404; margin-top: 10px;"><strong>Payment Methods:</strong> Cash, Credit Card, Bank Transfer, Insurance</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>