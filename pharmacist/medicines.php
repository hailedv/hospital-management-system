<?php
require_once '../config/db.php';
check_login('pharmacist');

$message = '';
$error = '';

// Handle medicine addition
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_medicine'])) {
    $medicine_name = sanitize_input($_POST['medicine_name']);
    $generic_name = sanitize_input($_POST['generic_name']);
    $manufacturer = sanitize_input($_POST['manufacturer']);
    $category = sanitize_input($_POST['category']);
    $unit_price = (float)$_POST['unit_price'];
    $stock_quantity = (int)$_POST['stock_quantity'];
    $min_stock_level = (int)$_POST['min_stock_level'];
    $expiry_date = $_POST['expiry_date'];
    
    if (empty($medicine_name) || empty($unit_price) || empty($stock_quantity)) {
        $error = "Please fill in all required fields.";
    } else {
        $stmt = $conn->prepare("INSERT INTO medicines (medicine_name, generic_name, manufacturer, category, unit_price, stock_quantity, min_stock_level, expiry_date, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active')");
        $stmt->bind_param("ssssdiis", $medicine_name, $generic_name, $manufacturer, $category, $unit_price, $stock_quantity, $min_stock_level, $expiry_date);
        
        if ($stmt->execute()) {
            $message = "Medicine added successfully!";
            log_activity('add_medicine', "Added medicine: $medicine_name");
        } else {
            $error = "Error adding medicine: " . $conn->error;
        }
    }
}

// Get all medicines
$medicines = $conn->query("SELECT * FROM medicines ORDER BY medicine_name");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Medicines - Pharmacist Dashboard</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container">
        <div class="dashboard-header">
            <div>
                <h1>💊 Manage Medicines</h1>
                <p><?php echo htmlspecialchars($_SESSION['full_name']); ?></p>
            </div>
            <div>
                <a href="dashboard.php" class="btn btn-pharmacist">← Back to Dashboard</a>
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
            <h3>Add New Medicine</h3>
            <form method="POST" action="">
                <div class="form-row">
                    <div class="form-group">
                        <label for="medicine_name">Medicine Name:</label>
                        <input type="text" name="medicine_name" id="medicine_name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="generic_name">Generic Name:</label>
                        <input type="text" name="generic_name" id="generic_name" class="form-control">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="manufacturer">Manufacturer:</label>
                        <input type="text" name="manufacturer" id="manufacturer" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="category">Category:</label>
                        <select name="category" id="category" class="form-control">
                            <option value="">Select Category</option>
                            <option value="Analgesic">Analgesic</option>
                            <option value="Antibiotic">Antibiotic</option>
                            <option value="Anti-inflammatory">Anti-inflammatory</option>
                            <option value="Antidiabetic">Antidiabetic</option>
                            <option value="Cardiovascular">Cardiovascular</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="unit_price">Unit Price:</label>
                        <input type="number" name="unit_price" id="unit_price" class="form-control" step="0.01" required>
                    </div>
                    <div class="form-group">
                        <label for="stock_quantity">Stock Quantity:</label>
                        <input type="number" name="stock_quantity" id="stock_quantity" class="form-control" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="min_stock_level">Minimum Stock Level:</label>
                        <input type="number" name="min_stock_level" id="min_stock_level" class="form-control" value="10">
                    </div>
                    <div class="form-group">
                        <label for="expiry_date">Expiry Date:</label>
                        <input type="date" name="expiry_date" id="expiry_date" class="form-control">
                    </div>
                </div>

                <button type="submit" name="add_medicine" class="btn btn-pharmacist">Add Medicine</button>
            </form>
        </div>

        <div class="table-container">
            <h3>Medicine Inventory</h3>
            <table class="table">
                <thead>
                    <tr>
                        <th>Medicine Name</th>
                        <th>Generic Name</th>
                        <th>Category</th>
                        <th>Unit Price</th>
                        <th>Stock</th>
                        <th>Min Level</th>
                        <th>Expiry Date</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($medicines->num_rows > 0): ?>
                        <?php while ($medicine = $medicines->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($medicine['medicine_name']); ?></td>
                                <td><?php echo htmlspecialchars($medicine['generic_name'] ?: 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($medicine['category'] ?: 'N/A'); ?></td>
                                <td>$<?php echo number_format($medicine['unit_price'], 2); ?></td>
                                <td>
                                    <span class="<?php echo $medicine['stock_quantity'] <= $medicine['min_stock_level'] ? 'text-danger' : ''; ?>">
                                        <?php echo $medicine['stock_quantity']; ?>
                                    </span>
                                </td>
                                <td><?php echo $medicine['min_stock_level']; ?></td>
                                <td><?php echo $medicine['expiry_date'] ? date('M j, Y', strtotime($medicine['expiry_date'])) : 'N/A'; ?></td>
                                <td>
                                    <span class="badge badge-<?php echo $medicine['status']; ?>">
                                        <?php echo ucfirst($medicine['status']); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 40px;">No medicines in inventory</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script src="../assets/js/script.js"></script>
</body>
</html>