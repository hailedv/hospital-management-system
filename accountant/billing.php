<?php
require_once '../config/db.php';
check_login('accountant');

$message = '';
$error = '';

// Handle bill generation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['generate_bill'])) {
    $patient_id = isset($_POST['patient_id']) ? (int)$_POST['patient_id'] : 0;
    $consultation_fee = isset($_POST['consultation_fee']) ? (float)$_POST['consultation_fee'] : 0;
    $medicine_cost = isset($_POST['medicine_cost']) ? (float)$_POST['medicine_cost'] : 0;
    $lab_charges = isset($_POST['lab_charges']) ? (float)$_POST['lab_charges'] : 0;
    $other_charges = isset($_POST['other_charges']) ? (float)$_POST['other_charges'] : 0;
    $discount = isset($_POST['discount']) ? (float)$_POST['discount'] : 0;
    $description = isset($_POST['description']) ? sanitize_input($_POST['description']) : '';
    
    if (empty($patient_id)) {
        $error = "Please select a patient.";
    } elseif ($consultation_fee <= 0 && $medicine_cost <= 0 && $lab_charges <= 0 && $other_charges <= 0) {
        $error = "Please enter at least one charge amount.";
    } else {
        // Calculate totals
        $total_amount = $consultation_fee + $medicine_cost + $lab_charges + $other_charges;
        $final_amount = $total_amount - $discount;
        
        // Generate bill ID
        $bill_count = $conn->query("SELECT COUNT(*) as count FROM bills")->fetch_assoc()['count'];
        $bill_id = 'BIL' . str_pad($bill_count + 1, 4, '0', STR_PAD_LEFT);
        
        $accountant_id = $_SESSION['user_id'];
        
        // First, let's check if the description column exists
        $columns_check = $conn->query("SHOW COLUMNS FROM bills LIKE 'description'");
        
        if ($columns_check->num_rows > 0) {
            // Description column exists
            $stmt = $conn->prepare("INSERT INTO bills (bill_id, patient_id, consultation_fee, medicine_cost, lab_charges, other_charges, total_amount, discount, final_amount, description, status, generated_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?)");
            if ($stmt) {
                $stmt->bind_param("siddddddssi", $bill_id, $patient_id, $consultation_fee, $medicine_cost, $lab_charges, $other_charges, $total_amount, $discount, $final_amount, $description, $accountant_id);
            }
        } else {
            // Description column doesn't exist, insert without it
            $stmt = $conn->prepare("INSERT INTO bills (bill_id, patient_id, consultation_fee, medicine_cost, lab_charges, other_charges, total_amount, discount, final_amount, status, generated_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?)");
            if ($stmt) {
                $stmt->bind_param("siddddddsi", $bill_id, $patient_id, $consultation_fee, $medicine_cost, $lab_charges, $other_charges, $total_amount, $discount, $final_amount, $accountant_id);
            }
        }
        
        if (!$stmt) {
            $error = "Error preparing statement: " . $conn->error;
        } elseif ($stmt->execute()) {
            $message = "Bill $bill_id generated successfully! Total amount: $" . number_format($final_amount, 2);
            log_activity('generate_bill', "Generated bill $bill_id for patient ID $patient_id");
        } else {
            $error = "Error generating bill: " . $stmt->error;
        }
    }
}

// Get all patients
$patients = $conn->query("SELECT * FROM patients WHERE status = 'active' ORDER BY full_name");

// Get recent bills
$recent_bills = $conn->query("
    SELECT b.*, p.full_name as patient_name, p.patient_id 
    FROM bills b 
    JOIN patients p ON b.patient_id = p.id 
    ORDER BY b.created_at DESC 
    LIMIT 15
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Billing Management - Accountant Dashboard</title>
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

        .badge-cancelled {
            background: #f8d7da;
            color: #721c24;
        }

        .total-section {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-top: 20px;
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
                <h1>💳 Billing Management</h1>
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

        <div class="form-container">
            <h3 style="margin-bottom: 20px; color: #ff9800;">Generate New Bill</h3>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="patient_id">Select Patient:</label>
                    <select name="patient_id" id="patient_id" class="form-control" required>
                        <option value="">Choose a patient</option>
                        <?php while ($patient = $patients->fetch_assoc()): ?>
                            <option value="<?php echo $patient['id']; ?>">
                                <?php echo htmlspecialchars($patient['patient_id'] . ' - ' . $patient['full_name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="consultation_fee">Consultation Fee ($):</label>
                        <input type="number" name="consultation_fee" id="consultation_fee" class="form-control" step="0.01" placeholder="0.00" onchange="calculateTotal()">
                    </div>
                    <div class="form-group">
                        <label for="medicine_cost">Medicine Cost ($):</label>
                        <input type="number" name="medicine_cost" id="medicine_cost" class="form-control" step="0.01" placeholder="0.00" onchange="calculateTotal()">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="lab_charges">Lab Charges ($):</label>
                        <input type="number" name="lab_charges" id="lab_charges" class="form-control" step="0.01" placeholder="0.00" onchange="calculateTotal()">
                    </div>
                    <div class="form-group">
                        <label for="other_charges">Other Charges ($):</label>
                        <input type="number" name="other_charges" id="other_charges" class="form-control" step="0.01" placeholder="0.00" onchange="calculateTotal()">
                    </div>
                </div>

                <div class="form-group">
                    <label for="discount">Discount ($):</label>
                    <input type="number" name="discount" id="discount" class="form-control" step="0.01" placeholder="0.00" onchange="calculateTotal()">
                </div>

                <div class="form-group">
                    <label for="description">Description/Notes:</label>
                    <textarea name="description" id="description" class="form-control" rows="3" placeholder="Additional notes or description for this bill"></textarea>
                </div>

                <div class="total-section">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <strong>Total Amount: $<span id="total_amount">0.00</span></strong><br>
                            <strong style="color: #ff9800; font-size: 1.2em;">Final Amount: $<span id="final_amount">0.00</span></strong>
                        </div>
                        <button type="submit" name="generate_bill" class="btn">Generate Bill</button>
                    </div>
                </div>
            </form>
        </div>

        <div class="table-container">
            <h3 style="padding: 20px; margin: 0; background: #f8f9fa; border-bottom: 1px solid #e9ecef;">
                Recent Bills
            </h3>
            <table class="table">
                <thead>
                    <tr>
                        <th>Bill ID</th>
                        <th>Patient</th>
                        <th>Consultation</th>
                        <th>Medicine</th>
                        <th>Lab</th>
                        <th>Other</th>
                        <th>Discount</th>
                        <th>Final Amount</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($recent_bills->num_rows > 0): ?>
                        <?php while ($bill = $recent_bills->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($bill['bill_id']); ?></td>
                                <td>
                                    <?php echo htmlspecialchars($bill['patient_name']); ?>
                                    <br><small><?php echo htmlspecialchars($bill['patient_id']); ?></small>
                                </td>
                                <td>$<?php echo number_format($bill['consultation_fee'], 2); ?></td>
                                <td>$<?php echo number_format($bill['medicine_cost'], 2); ?></td>
                                <td>$<?php echo number_format($bill['lab_charges'], 2); ?></td>
                                <td>$<?php echo number_format($bill['other_charges'], 2); ?></td>
                                <td>$<?php echo number_format($bill['discount'], 2); ?></td>
                                <td><strong>$<?php echo number_format($bill['final_amount'], 2); ?></strong></td>
                                <td>
                                    <span class="badge badge-<?php echo $bill['status']; ?>">
                                        <?php echo ucfirst($bill['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M j, Y', strtotime($bill['created_at'])); ?></td>
                                <td>
                                    <?php if ($bill['status'] == 'pending'): ?>
                                        <a href="payments.php?bill_id=<?php echo $bill['id']; ?>" class="btn btn-sm">Process Payment</a>
                                    <?php else: ?>
                                        <button class="btn btn-sm" onclick="alert('Bill already processed')">View</button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="11" style="text-align: center; padding: 40px;">No bills found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        function calculateTotal() {
            const consultation = parseFloat(document.getElementById('consultation_fee').value) || 0;
            const medicine = parseFloat(document.getElementById('medicine_cost').value) || 0;
            const lab = parseFloat(document.getElementById('lab_charges').value) || 0;
            const other = parseFloat(document.getElementById('other_charges').value) || 0;
            const discount = parseFloat(document.getElementById('discount').value) || 0;
            
            const total = consultation + medicine + lab + other;
            const final = total - discount;
            
            document.getElementById('total_amount').textContent = total.toFixed(2);
            document.getElementById('final_amount').textContent = final.toFixed(2);
        }
    </script>
</body>
</html>