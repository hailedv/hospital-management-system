<?php
require_once '../config/db.php';
check_login('accountant');

$message = '';
$error = '';
$selected_bill = null;

// Get bill details if bill_id is provided
if (isset($_GET['bill_id'])) {
    $bill_id = (int)$_GET['bill_id'];
    $stmt = $conn->prepare("
        SELECT b.*, p.full_name as patient_name, p.patient_id, p.phone 
        FROM bills b 
        JOIN patients p ON b.patient_id = p.id 
        WHERE b.id = ? AND b.status = 'pending'
    ");
    $stmt->bind_param("i", $bill_id);
    $stmt->execute();
    $selected_bill = $stmt->get_result()->fetch_assoc();
}

// Handle payment processing
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['process_payment'])) {
    $bill_id = isset($_POST['bill_id']) ? (int)$_POST['bill_id'] : 0;
    $payment_method = isset($_POST['payment_method']) ? sanitize_input($_POST['payment_method']) : '';
    $amount_paid = isset($_POST['amount_paid']) ? (float)$_POST['amount_paid'] : 0;
    $payment_notes = isset($_POST['payment_notes']) ? sanitize_input($_POST['payment_notes']) : '';
    
    if (empty($bill_id)) {
        $error = "Please select a bill.";
    } elseif (empty($payment_method)) {
        $error = "Please select a payment method.";
    } elseif ($amount_paid <= 0) {
        $error = "Please enter a valid payment amount.";
    } else {
        // Get bill details
        $bill_stmt = $conn->prepare("SELECT * FROM bills WHERE id = ? AND status = 'pending'");
        $bill_stmt->bind_param("i", $bill_id);
        $bill_stmt->execute();
        $bill = $bill_stmt->get_result()->fetch_assoc();
        
        if (!$bill) {
            $error = "Bill not found or already processed.";
        } elseif ($amount_paid > $bill['final_amount']) {
            $error = "Payment amount cannot exceed the bill amount.";
        } else {
            // Update bill status
            $accountant_id = $_SESSION['user_id'];
            $status = ($amount_paid >= $bill['final_amount']) ? 'paid' : 'partial';
            
            // Check if required columns exist before updating
            $columns_check = $conn->query("SHOW COLUMNS FROM bills LIKE 'amount_paid'");
            
            if ($columns_check->num_rows > 0) {
                // All payment columns exist
                $update_stmt = $conn->prepare("UPDATE bills SET status = ?, amount_paid = ?, payment_method = ?, payment_notes = ?, paid_at = NOW(), processed_by = ? WHERE id = ?");
                if ($update_stmt) {
                    $update_stmt->bind_param("sdssii", $status, $amount_paid, $payment_method, $payment_notes, $accountant_id, $bill_id);
                }
            } else {
                // Basic update without payment tracking columns
                $update_stmt = $conn->prepare("UPDATE bills SET status = ?, payment_method = ?, paid_at = NOW() WHERE id = ?");
                if ($update_stmt) {
                    $update_stmt->bind_param("ssi", $status, $payment_method, $bill_id);
                }
            }
            
            if (!$update_stmt) {
                $error = "Error preparing payment update: " . $conn->error;
            } elseif ($update_stmt->execute()) {
                $message = "Payment processed successfully! Amount: $" . number_format($amount_paid, 2);
                if ($status == 'partial') {
                    $remaining = $bill['final_amount'] - $amount_paid;
                    $message .= " (Remaining: $" . number_format($remaining, 2) . ")";
                }
                log_activity('process_payment', "Processed payment for bill {$bill['bill_id']}");
                $selected_bill = null; // Clear selection after successful payment
            } else {
                $error = "Error processing payment: " . $update_stmt->error;
            }
        }
    }
}

// Get pending bills
$pending_bills = $conn->query("
    SELECT b.*, p.full_name as patient_name, p.patient_id 
    FROM bills b 
    JOIN patients p ON b.patient_id = p.id 
    WHERE b.status IN ('pending', 'partial') 
    ORDER BY b.created_at DESC
");

// Get recent payments
$recent_payments = $conn->query("
    SELECT b.*, p.full_name as patient_name, p.patient_id 
    FROM bills b 
    JOIN patients p ON b.patient_id = p.id 
    WHERE b.status IN ('paid', 'partial') AND b.paid_at IS NOT NULL 
    ORDER BY b.paid_at DESC 
    LIMIT 10
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Processing - Accountant Dashboard</title>
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

        .form-container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 20px;
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

        .bill-info {
            background: #e3f2fd;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
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

        @media (max-width: 768px) {
            .dashboard-header {
                flex-direction: column;
                text-align: center;
                gap: 15px;
            }

            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="dashboard-header">
            <div>
                <h1>💰 Payment Processing</h1>
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

        <?php if ($selected_bill): ?>
            <div class="form-container">
                <h3 style="margin-bottom: 20px; color: #ff9800;">Process Payment</h3>
                
                <div class="bill-info">
                    <h4>Bill Information:</h4>
                    <p><strong>Bill ID:</strong> <?php echo htmlspecialchars($selected_bill['bill_id']); ?></p>
                    <p><strong>Patient:</strong> <?php echo htmlspecialchars($selected_bill['patient_name']); ?> (<?php echo htmlspecialchars($selected_bill['patient_id']); ?>)</p>
                    <p><strong>Phone:</strong> <?php echo htmlspecialchars($selected_bill['phone']); ?></p>
                    <p><strong>Total Amount:</strong> $<?php echo number_format($selected_bill['total_amount'], 2); ?></p>
                    <p><strong>Discount:</strong> $<?php echo number_format($selected_bill['discount'], 2); ?></p>
                    <p><strong>Final Amount:</strong> <span style="color: #ff9800; font-weight: bold; font-size: 1.2em;">$<?php echo number_format($selected_bill['final_amount'], 2); ?></span></p>
                </div>

                <form method="POST" action="">
                    <input type="hidden" name="bill_id" value="<?php echo $selected_bill['id']; ?>">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="payment_method">Payment Method:</label>
                            <select name="payment_method" id="payment_method" class="form-control" required>
                                <option value="">Select payment method</option>
                                <option value="cash">Cash</option>
                                <option value="credit_card">Credit Card</option>
                                <option value="debit_card">Debit Card</option>
                                <option value="bank_transfer">Bank Transfer</option>
                                <option value="insurance">Insurance</option>
                                <option value="check">Check</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="amount_paid">Amount Paid ($):</label>
                            <input type="number" name="amount_paid" id="amount_paid" class="form-control" 
                                   step="0.01" max="<?php echo $selected_bill['final_amount']; ?>" 
                                   value="<?php echo $selected_bill['final_amount']; ?>" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="payment_notes">Payment Notes:</label>
                        <textarea name="payment_notes" id="payment_notes" class="form-control" rows="3" 
                                  placeholder="Transaction ID, reference number, or additional notes"></textarea>
                    </div>

                    <button type="submit" name="process_payment" class="btn">Process Payment</button>
                    <a href="payments.php" class="btn" style="background: #6c757d; margin-left: 10px;">Cancel</a>
                </form>
            </div>
        <?php endif; ?>

        <div class="table-container">
            <h3 style="padding: 20px; margin: 0; background: #f8f9fa; border-bottom: 1px solid #e9ecef;">
                Pending Bills
            </h3>
            <table class="table">
                <thead>
                    <tr>
                        <th>Bill ID</th>
                        <th>Patient</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($pending_bills->num_rows > 0): ?>
                        <?php while ($bill = $pending_bills->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($bill['bill_id']); ?></td>
                                <td>
                                    <?php echo htmlspecialchars($bill['patient_name']); ?>
                                    <br><small><?php echo htmlspecialchars($bill['patient_id']); ?></small>
                                </td>
                                <td>
                                    $<?php echo number_format($bill['final_amount'], 2); ?>
                                    <?php if ($bill['status'] == 'partial'): ?>
                                        <br><small>Paid: $<?php echo number_format(isset($bill['amount_paid']) ? $bill['amount_paid'] : 0, 2); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge badge-<?php echo $bill['status']; ?>">
                                        <?php echo ucfirst($bill['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M j, Y', strtotime($bill['created_at'])); ?></td>
                                <td>
                                    <a href="payments.php?bill_id=<?php echo $bill['id']; ?>" class="btn btn-sm">Process Payment</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 40px;">No pending bills</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="table-container">
            <h3 style="padding: 20px; margin: 0; background: #f8f9fa; border-bottom: 1px solid #e9ecef;">
                Recent Payments
            </h3>
            <table class="table">
                <thead>
                    <tr>
                        <th>Bill ID</th>
                        <th>Patient</th>
                        <th>Amount Paid</th>
                        <th>Payment Method</th>
                        <th>Status</th>
                        <th>Payment Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($recent_payments->num_rows > 0): ?>
                        <?php while ($payment = $recent_payments->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($payment['bill_id']); ?></td>
                                <td>
                                    <?php echo htmlspecialchars($payment['patient_name']); ?>
                                    <br><small><?php echo htmlspecialchars($payment['patient_id']); ?></small>
                                </td>
                                <td>$<?php echo number_format(isset($payment['amount_paid']) ? $payment['amount_paid'] : $payment['final_amount'], 2); ?></td>
                                <td><?php echo ucfirst(str_replace('_', ' ', $payment['payment_method'])); ?></td>
                                <td>
                                    <span class="badge badge-<?php echo $payment['status']; ?>">
                                        <?php echo ucfirst($payment['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M j, Y H:i', strtotime($payment['paid_at'])); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 40px;">No recent payments</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>