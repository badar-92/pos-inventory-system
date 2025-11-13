<?php
include 'includes/config.php';
include 'includes/auth.php';

// Check if user is logged in
checkAuthentication();

if (!isset($_GET['transaction_id'])) {
    header("Location: pos.php");
    exit;
}

$transaction_id = $_GET['transaction_id'];

// Get transaction details
$sql = "SELECT t.*, u.full_name as cashier_name
        FROM transactions t
        LEFT JOIN users u ON t.cashier_id = u.user_id
        WHERE t.transaction_id = :transaction_id";
$stmt = $pdo->prepare($sql);
$stmt->bindParam(':transaction_id', $transaction_id, PDO::PARAM_STR);
$stmt->execute();
$transaction = $stmt->fetch(PDO::FETCH_ASSOC);

// Get transaction items
$sql = "SELECT ti.*, p.product_name, p.is_open_product
        FROM transaction_items ti
        LEFT JOIN products p ON ti.product_id = p.product_id
        WHERE ti.transaction_id = :transaction_id";
$stmt = $pdo->prepare($sql);
$stmt->bindParam(':transaction_id', $transaction_id, PDO::PARAM_STR);
$stmt->execute();
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Store details
$store_name = "Badar Mart";
$store_address = "123 Main Street, City, State";
$store_phone = "(555) 123-4567";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>POS System - Receipt</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        body {
            font-family: 'Courier New', monospace;
            font-size: 14px;
            line-height: 1.4;
        }
        .receipt-80mm {
            width: 80mm;
            margin: 0 auto;
            padding: 10px;
            border: 1px solid #ccc;
        }
        .receipt-header {
            text-align: center;
            margin-bottom: 15px;
            border-bottom: 1px dashed #ccc;
            padding-bottom: 10px;
        }
        .receipt-header h1 {
            font-size: 18px;
            margin: 0;
            font-weight: bold;
        }
        .receipt-details {
            margin-bottom: 15px;
            border-bottom: 1px dashed #ccc;
            padding-bottom: 10px;
        }
        .receipt-details p {
            margin: 5px 0;
        }
        .receipt-items {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        .receipt-items th {
            border-bottom: 1px solid #000;
            text-align: left;
            padding: 5px 0;
        }
        .receipt-items td {
            padding: 5px 0;
            vertical-align: top;
        }
        .receipt-items tfoot tr:first-child td {
            border-top: 1px solid #000;
            padding-top: 10px;
        }
        .receipt-items tfoot tr:last-child td {
            border-top: 1px double #000;
            padding-top: 10px;
            font-weight: bold;
        }
        .receipt-footer {
            text-align: center;
            margin-top: 15px;
            border-top: 1px dashed #ccc;
            padding-top: 10px;
        }
        .receipt-actions {
            margin-top: 20px;
            text-align: center;
        }
        .btn {
            display: inline-block;
            margin: 5px;
            padding: 8px 15px;
            background: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            cursor: pointer;
        }
        @media print {
            .no-print {
                display: none;
            }
            body {
                font-size: 12px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="receipt receipt-80mm">
            <div class="receipt-header">
                <h1><?php echo $store_name; ?></h1>
                <p><?php echo $store_address; ?></p>
                <p><?php echo $store_phone; ?></p>
            </div>
            
            <div class="receipt-details">
                <p><strong>Transaction ID:</strong> <span><?php echo $transaction['transaction_id']; ?></span></p>
                <p><strong>Date:</strong> <span><?php echo $transaction['transaction_date']; ?></span></p>
                <p><strong>Cashier:</strong> <span><?php echo $transaction['cashier_name']; ?></span></p>
                <p><strong>Customer:</strong> <span><?php echo $transaction['customer_name']; ?></span></p>
            </div>
            
            <table class="receipt-items">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Qty</th>
                        <th>Price</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): 
                        $itemTotal = $item['price'] * $item['quantity'];
                        if ($item['is_open_product'] && $item['weight_kg'] > 0) {
                            $itemTotal = $item['price']; // For open products, price is already the total
                        }
                    ?>
                    <tr>
                        <td>
                            <?php echo $item['product_name']; ?>
                            <?php if ($item['is_open_product'] && $item['weight_kg'] > 0): ?>
                            <br><small>(<?php echo number_format($item['weight_kg'], 3); ?> kg)</small>
                            <?php endif; ?>
                        </td>
                        <td><?php echo $item['quantity']; ?></td>
                        <td>
                            <?php if ($item['is_open_product'] && $item['weight_kg'] > 0): ?>
                            $<?php echo number_format($item['price'] / $item['weight_kg'], 2); ?>/kg
                            <?php else: ?>
                            $<?php echo number_format($item['price'], 2); ?>
                            <?php endif; ?>
                        </td>
                        <td>$<?php echo number_format($itemTotal, 2); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3">Subtotal</td>
                        <td>$<?php echo number_format($transaction['subtotal'], 2); ?></td>
                    </tr>
                    <?php if ($transaction['discount_amount'] > 0): ?>
                    <tr>
                        <td colspan="3">Discount</td>
                        <td>-$<?php echo number_format($transaction['discount_amount'], 2); ?></td>
                    </tr>
                    <?php endif; ?>
                    <tr>
                        <td colspan="3">Tax (<?php echo $transaction['tax_rate']; ?>%)</td>
                        <td>$<?php echo number_format($transaction['tax_amount'], 2); ?></td>
                    </tr>
                    <tr>
                        <td colspan="3">Total</td>
                        <td>$<?php echo number_format($transaction['total_amount'], 2); ?></td>
                    </tr>
                    <?php if ($transaction['payment_method'] === 'Cash'): ?>
                    <tr>
                        <td colspan="3">Cash Given</td>
                        <td>$<?php echo number_format($transaction['cash_given'], 2); ?></td>
                    </tr>
                    <tr>
                        <td colspan="3">Change</td>
                        <td>$<?php echo number_format($transaction['change_amount'], 2); ?></td>
                    </tr>
                    <?php endif; ?>
                    <tr>
                        <td colspan="3">Payment Method</td>
                        <td><?php echo $transaction['payment_method']; ?></td>
                    </tr>
                </tfoot>
            </table>
            
            <div class="receipt-footer">
                <p>Thank you for your business!</p>
            </div>
            
            <div class="no-print receipt-actions">
                <button onclick="window.print()" class="btn"><i class="fas fa-print"></i> Print Receipt</button>
                <a href="pos.php" class="btn"><i class="fas fa-cash-register"></i> New Sale</a>
                <a href="dashboard.php" class="btn"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            </div>
        </div>
    </div>
</body>
</html>