<?php
include 'includes/config.php';
include 'includes/auth.php';

// Check if user is logged in
checkAuthentication();

if (!isset($_GET['return_id'])) {
    header("Location: return_exchange.php");
    exit;
}

$return_id = intval($_GET['return_id']); // Convert to integer

// Get return details
$sql = "SELECT r.*, u.full_name as cashier_name
        FROM returns r
        LEFT JOIN users u ON r.cashier_id = u.user_id
        WHERE r.return_id = :return_id";
$stmt = $pdo->prepare($sql);
$stmt->bindParam(':return_id', $return_id, PDO::PARAM_INT);
$stmt->execute();
$return = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$return) {
    header("Location: return_exchange.php");
    exit;
}

// Get return items
$sql = "SELECT ri.*, p.product_name, p.is_open_product
        FROM return_items ri
        LEFT JOIN products p ON ri.product_id = p.product_id
        WHERE ri.return_id = :return_id";
$stmt = $pdo->prepare($sql);
$stmt->bindParam(':return_id', $return_id, PDO::PARAM_INT);
$stmt->execute();
$return_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get exchange transaction and items if exists
$exchange_transaction = null;
$exchange_items = [];
if (!empty($return['exchange_transaction_id'])) {
    // Get exchange transaction details
    $sql = "SELECT * FROM transactions WHERE transaction_id = :transaction_id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':transaction_id', $return['exchange_transaction_id'], PDO::PARAM_STR);
    $stmt->execute();
    $exchange_transaction = $stmt->fetch(PDO::FETCH_ASSOC);

    // Get exchange transaction items
    $sql = "SELECT ti.*, p.product_name, p.is_open_product
            FROM transaction_items ti
            LEFT JOIN products p ON ti.product_id = p.product_id
            WHERE ti.transaction_id = :transaction_id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':transaction_id', $return['exchange_transaction_id'], PDO::PARAM_STR);
    $stmt->execute();
    $exchange_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get original transaction details
$sql = "SELECT * FROM transactions WHERE transaction_id = :transaction_id";
$stmt = $pdo->prepare($sql);
$stmt->bindParam(':transaction_id', $return['original_transaction_id'], PDO::PARAM_STR);
$stmt->execute();
$original_transaction = $stmt->fetch(PDO::FETCH_ASSOC);

// Store details
$store_name = "Badar Mart";
$store_address = "123 Main Street, City, State";
$store_phone = "(555) 123-4567";

// Calculate net amount details
$net_amount = $return['net_amount'];
$is_refundable = $net_amount > 0;
$is_payable = $net_amount < 0;
$amount_label = $is_refundable ? "Amount to be Returned" : ($is_payable ? "Amount Payable" : "Settled");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>POS System - Return Receipt</title>
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
        .return-badge {
            background: #ff4444;
            color: white;
            padding: 5px 10px;
            border-radius: 3px;
            font-weight: bold;
            margin-bottom: 10px;
            text-align: center;
        }
        .exchange-badge {
            background: #4CAF50;
            color: white;
            padding: 5px 10px;
            border-radius: 3px;
            font-weight: bold;
            margin-bottom: 10px;
            text-align: center;
        }
        .amount-section {
            margin: 15px 0;
            padding: 10px;
            border: 1px solid #000;
            border-radius: 5px;
            text-align: center;
        }
        .refundable {
            background: #e8f5e8;
            border-color: #4CAF50;
        }
        .payable {
            background: #ffebee;
            border-color: #f44336;
        }
        .settled {
            background: #fff3e0;
            border-color: #ff9800;
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
            <?php if ($return['return_type'] == 'exchange'): ?>
                <div class="exchange-badge">EXCHANGE RECEIPT</div>
            <?php else: ?>
                <div class="return-badge">RETURN RECEIPT</div>
            <?php endif; ?>
            
            <div class="receipt-header">
                <h1><?php echo $store_name; ?></h1>
                <p><?php echo $store_address; ?></p>
                <p><?php echo $store_phone; ?></p>
            </div>
            
            <div class="receipt-details">
                <p><strong>Return ID:</strong> <span><?php echo $return['return_id']; ?></span></p>
                <p><strong>Date:</strong> <span><?php echo $return['return_date']; ?></span></p>
                <p><strong>Cashier:</strong> <span><?php echo $return['cashier_name']; ?></span></p>
                <p><strong>Customer:</strong> <span><?php echo $return['customer_name']; ?></span></p>
                <p><strong>Original Transaction:</strong> <span><?php echo $return['original_transaction_id']; ?></span></p>
                <p><strong>Type:</strong> <span><?php echo ucfirst($return['return_type']); ?></span></p>
                <?php if ($return['reason']): ?>
                <p><strong>Reason:</strong> <span><?php echo $return['reason']; ?></span></p>
                <?php endif; ?>
            </div>
            
            <!-- Returned Products Section -->
            <h3 style="text-align: center; margin: 10px 0;">Returned Products</h3>
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
                    <?php 
                    $total_return_amount = 0;
                    foreach ($return_items as $item): 
                        $itemTotal = $item['return_price'] * $item['quantity_returned'];
                        $total_return_amount += $itemTotal;
                    ?>
                    <tr>
                        <td>
                            <?php echo $item['product_name']; ?>
                            <?php if ($item['is_open_product'] && $item['weight_kg'] > 0): ?>
                                <br><small>(<?php echo number_format($item['weight_kg'], 3); ?> kg)</small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($item['is_open_product']): ?>
                                <?php echo number_format($item['quantity_returned'], 3); ?> kg
                            <?php else: ?>
                                <?php echo intval($item['quantity_returned']); ?>
                            <?php endif; ?>
                        </td>
                        <td>$<?php echo number_format($item['return_price'], 2); ?></td>
                        <td>$<?php echo number_format($itemTotal, 2); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3" style="text-align: right;">Total Return Amount:</td>
                        <td>$<?php echo number_format($total_return_amount, 2); ?></td>
                    </tr>
                </tfoot>
            </table>
            
            <!-- Exchange Products Section -->
            <?php if (!empty($exchange_items)): ?>
            <h3 style="text-align: center; margin: 10px 0;">Exchange Products</h3>
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
                    <?php 
                    $total_exchange_amount = 0;
                    foreach ($exchange_items as $item): 
                        if ($item['is_open_product']) {
                            $itemTotal = $item['price'] * $item['weight_kg'];
                        } else {
                            $itemTotal = $item['price'] * $item['quantity'];
                        }
                        $total_exchange_amount += $itemTotal;
                    ?>
                    <tr>
                        <td>
                            <?php echo $item['product_name']; ?>
                            <?php if ($item['is_open_product'] && $item['weight_kg'] > 0): ?>
                                <br><small>(<?php echo number_format($item['weight_kg'], 3); ?> kg)</small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($item['is_open_product']): ?>
                                <?php echo number_format($item['weight_kg'], 3); ?> kg
                            <?php else: ?>
                                <?php echo intval($item['quantity']); ?>
                            <?php endif; ?>
                        </td>
                        <td>$<?php echo number_format($item['price'], 2); ?></td>
                        <td>$<?php echo number_format($itemTotal, 2); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3" style="text-align: right;">Total Exchange Amount:</td>
                        <td>$<?php echo number_format($total_exchange_amount, 2); ?></td>
                    </tr>
                </tfoot>
            </table>
            <?php endif; ?>
            
            <!-- Amount Summary Section -->
            <div class="amount-section <?php echo $is_refundable ? 'refundable' : ($is_payable ? 'payable' : 'settled'); ?>">
                <h3 style="margin: 0 0 10px 0;">Amount Summary</h3>
                <p style="margin: 5px 0;"><strong>Total Return Amount:</strong> $<?php echo number_format($return['total_return_amount'], 2); ?></p>
                
                <?php if ($return['return_type'] == 'exchange'): ?>
                <p style="margin: 5px 0;"><strong>Total Exchange Amount:</strong> $<?php echo number_format($return['exchange_amount'], 2); ?></p>
                <?php endif; ?>
                
                <p style="margin: 5px 0; font-size: 16px; font-weight: bold;">
                    <strong><?php echo $amount_label; ?>:</strong> 
                    $<?php echo number_format(abs($net_amount), 2); ?>
                </p>
                
                <p style="margin: 5px 0;">
                    <strong>Payment Method:</strong> <?php echo $return['payment_method']; ?>
                </p>
                
                <?php if ($is_refundable): ?>
                <p style="margin: 5px 0; color: #4CAF50; font-weight: bold;">
                    <i class="fas fa-arrow-left"></i> Amount to be returned to customer
                </p>
                <?php elseif ($is_payable): ?>
                <p style="margin: 5px 0; color: #f44336; font-weight: bold;">
                    <i class="fas fa-arrow-right"></i> Amount payable by customer
                </p>
                <?php else: ?>
                <p style="margin: 5px 0; color: #ff9800; font-weight: bold;">
                    <i class="fas fa-check"></i> Amount settled (no balance)
                </p>
                <?php endif; ?>
            </div>
            
            <div class="receipt-footer">
                <p>Thank you for your business!</p>
                <p><small>Return processed on <?php echo date('M j, Y H:i', strtotime($return['return_date'])); ?></small></p>
            </div>
            
            <div class="no-print receipt-actions">
                <button onclick="window.print()" class="btn"><i class="fas fa-print"></i> Print Receipt</button>
                <a href="return_exchange.php" class="btn"><i class="fas fa-exchange-alt"></i> New Return</a>
                <a href="dashboard.php" class="btn"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            </div>
        </div>
    </div>
</body>
</html>