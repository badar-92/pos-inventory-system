<?php
include 'includes/config.php';
include 'includes/auth.php';

// Check if user is logged in and is admin or manager
checkAuthentication();
if ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'manager') {
    header("Location: dashboard.php");
    exit;
}
$pageNames = [
    'dashboard' => 'Dashboard',
    'pos' => 'Point of Sale',
    'return_exchange' => 'Return & Exchange',
    'inventory' => 'Inventory Management',
    'users' => 'User Management',
    'reports' => 'Sales Reports',
    'search' => 'Product Search'
];

// Set default date range (current month)
$start_date = date('Y-m-01');
$end_date = date('Y-m-t');

// Process date filter
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['filter'])) {
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
}

// Get sales data for the selected period
$sql = "SELECT t.*, u.full_name as cashier_name 
        FROM transactions t 
        LEFT JOIN users u ON t.cashier_id = u.user_id 
        WHERE t.transaction_date BETWEEN :start_date AND :end_date 
        ORDER BY t.transaction_date DESC";
$stmt = $pdo->prepare($sql);
$stmt->bindParam(':start_date', $start_date, PDO::PARAM_STR);
$stmt->bindParam(':end_date', $end_date, PDO::PARAM_STR);
$stmt->execute();
$transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate totals
$total_sales = 0;
$total_transactions = count($transactions);
$sales_by_cashier = [];

foreach ($transactions as $transaction) {
    $total_sales += $transaction['total_amount'];
    
    $cashier_id = $transaction['cashier_id'];
    if (!isset($sales_by_cashier[$cashier_id])) {
        $sales_by_cashier[$cashier_id] = [
            'name' => $transaction['cashier_name'],
            'count' => 0,
            'amount' => 0
        ];
    }
    
    $sales_by_cashier[$cashier_id]['count']++;
    $sales_by_cashier[$cashier_id]['amount'] += $transaction['total_amount'];
}
?>

<?php include 'includes/sidebar.php'; ?>

<div class="container">
    <h1><i class="fas fa-chart-bar"></i> Sales Reports</h1>
    
    <div class="form-section">
        <h2><i class="fas fa-filter"></i> Filter Reports</h2>
        <form method="post" action="">
            <div class="form-group">
                <label for="start_date">Start Date:</label>
                <input type="date" id="start_date" name="start_date" value="<?php echo $start_date; ?>" required>
            </div>
            <div class="form-group">
                <label for="end_date">End Date:</label>
                <input type="date" id="end_date" name="end_date" value="<?php echo $end_date; ?>" required>
            </div>
            <button type="submit" name="filter" class="btn"><i class="fas fa-filter"></i> Apply Filter</button>
        </form>
    </div>
    
    <div class="report-summary">
        <h2>Sales Summary (<?php echo date('M j, Y', strtotime($start_date)); ?> to <?php echo date('M j, Y', strtotime($end_date)); ?>)</h2>
        <div class="summary-cards">
            <div class="summary-card">
                <h3>Total Sales</h3>
                <p>$<?php echo number_format($total_sales, 2); ?></p>
            </div>
            <div class="summary-card">
                <h3>Total Transactions</h3>
                <p><?php echo $total_transactions; ?></p>
            </div>
            <div class="summary-card">
                <h3>Average Sale</h3>
                <p>$<?php echo $total_transactions > 0 ? number_format($total_sales / $total_transactions, 2) : '0.00'; ?></p>
            </div>
        </div>
    </div>
    
    <div class="table-section">
        <h2><i class="fas fa-user"></i> Sales by Cashier</h2>
        <table>
            <thead>
                <tr>
                    <th>Cashier</th>
                    <th>Transactions</th>
                    <th>Total Sales</th>
                    <th>Average Sale</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($sales_by_cashier as $cashier): ?>
                <tr>
                    <td><?php echo $cashier['name']; ?></td>
                    <td><?php echo $cashier['count']; ?></td>
                    <td>$<?php echo number_format($cashier['amount'], 2); ?></td>
                    <td>$<?php echo $cashier['count'] > 0 ? number_format($cashier['amount'] / $cashier['count'], 2) : '0.00'; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <div class="table-section">
        <h2><i class="fas fa-receipt"></i> Transaction Details</h2>
        <table>
            <thead>
                <tr>
                    <th>Transaction ID</th>
                    <th>Date</th>
                    <th>Cashier</th>
                    <th>Customer</th>
                    <th>Payment Method</th>
                    <th>Total Amount</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($transactions as $transaction): ?>
                <tr>
                    <td><?php echo $transaction['transaction_id']; ?></td>
                    <td><?php echo date('M j, Y H:i', strtotime($transaction['transaction_date'])); ?></td>
                    <td><?php echo $transaction['cashier_name']; ?></td>
                    <td><?php echo $transaction['customer_name']; ?></td>
                    <td><?php echo $transaction['payment_method']; ?></td>
                    <td>$<?php echo number_format($transaction['total_amount'], 2); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>