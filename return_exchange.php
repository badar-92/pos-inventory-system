<?php
include 'includes/config.php';
include 'includes/auth.php';

// Check if user is logged in and is cashier or admin
checkAuthentication();
if ($_SESSION['role'] != 'cashier' && $_SESSION['role'] != 'admin') {
    header("Location: dashboard.php");
    exit;
}

$error = '';
$success = '';
$transaction = null;
$items = [];
$return_items = [];
$exchange_products = [];

// Get products for exchange selection
$sql_products = "SELECT * FROM products WHERE quantity_in_stock > 0 ORDER BY product_name";
$stmt_products = $pdo->prepare($sql_products);
$stmt_products->execute();
$exchange_products = $stmt_products->fetchAll(PDO::FETCH_ASSOC);

// Handle transaction lookup
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['lookup_transaction'])) {
    $transaction_id = trim($_POST['transaction_id']);
    
    // Get transaction details
    $sql = "SELECT t.*, u.full_name as cashier_name
            FROM transactions t
            LEFT JOIN users u ON t.cashier_id = u.user_id
            WHERE t.transaction_id = :transaction_id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':transaction_id', $transaction_id, PDO::PARAM_STR);
    $stmt->execute();
    $transaction = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($transaction) {
        // Get transaction items
        $sql = "SELECT ti.product_id, ti.quantity, ti.price, ti.weight_kg, 
                       p.product_name, p.is_open_product, p.price_per_kg
                FROM transaction_items ti
                LEFT JOIN products p ON ti.product_id = p.product_id
                WHERE ti.transaction_id = :transaction_id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':transaction_id', $transaction_id, PDO::PARAM_STR);
        $stmt->execute();
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!$items) {
            $items = [];
            $error = "No items found in this transaction!";
        }
    } else {
        $error = "Transaction not found!";
        $items = [];
    }
}

// Handle return/exchange submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['process_return'])) {
    $transaction_id = $_POST['transaction_id'];
    $return_type = $_POST['return_type'];
    $customer_name = $_POST['customer_name'];
    $payment_method = $_POST['payment_method'];
    $reason = $_POST['reason'];
    
    // Get selected items for return
    $return_items = [];
    $total_return_amount = 0;
    
    // Process packaged products (return_qty)
    if (isset($_POST['return_qty']) && is_array($_POST['return_qty'])) {
        foreach ($_POST['return_qty'] as $product_id => $qty) {
            $qty = floatval($qty);
            if ($qty > 0) {
                // Find the original item
                $sql = "SELECT ti.*, p.product_name, p.is_open_product
                        FROM transaction_items ti
                        LEFT JOIN products p ON ti.product_id = p.product_id
                        WHERE ti.transaction_id = :transaction_id AND ti.product_id = :product_id";
                $stmt = $pdo->prepare($sql);
                $stmt->bindParam(':transaction_id', $transaction_id, PDO::PARAM_STR);
                $stmt->bindParam(':product_id', $product_id, PDO::PARAM_STR);
                $stmt->execute();
                $original_item = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($original_item) {
                    $return_price = $original_item['price'];
                    $return_amount = $return_price * $qty;
                    $total_return_amount += $return_amount;
                    
                    $return_items[] = [
                        'product_id' => $product_id,
                        'quantity' => $qty,
                        'price' => $return_price,
                        'is_open_product' => $original_item['is_open_product'],
                        'weight_kg' => 0,
                        'amount' => $return_amount,
                        'product_name' => $original_item['product_name']
                    ];
                }
            }
        }
    }
    
    // Process open products (return_weight)
    if (isset($_POST['return_weight']) && is_array($_POST['return_weight'])) {
        foreach ($_POST['return_weight'] as $product_id => $weight) {
            $weight = floatval($weight);
            if ($weight > 0) {
                // Find the original item
                $sql = "SELECT ti.*, p.product_name, p.is_open_product, p.price_per_kg
                        FROM transaction_items ti
                        LEFT JOIN products p ON ti.product_id = p.product_id
                        WHERE ti.transaction_id = :transaction_id AND ti.product_id = :product_id";
                $stmt = $pdo->prepare($sql);
                $stmt->bindParam(':transaction_id', $transaction_id, PDO::PARAM_STR);
                $stmt->bindParam(':product_id', $product_id, PDO::PARAM_STR);
                $stmt->execute();
                $original_item = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($original_item) {
                    $price_per_kg = $original_item['is_open_product'] ? ($original_item['price'] / $original_item['weight_kg']) : 0;
                    $return_price = $price_per_kg * $weight;
                    $return_amount = $return_price;
                    $total_return_amount += $return_amount;
                    
                    $return_items[] = [
                        'product_id' => $product_id,
                        'quantity' => 1,
                        'price' => $return_price,
                        'is_open_product' => $original_item['is_open_product'],
                        'weight_kg' => $weight,
                        'amount' => $return_amount,
                        'product_name' => $original_item['product_name']
                    ];
                }
            }
        }
    }
    
    // Process exchange products
    $exchange_items = [];
    $total_exchange_amount = 0;
    
    if (isset($_POST['exchange_items']) && !empty($_POST['exchange_items'])) {
        $exchange_data = json_decode($_POST['exchange_items'], true);
        if (is_array($exchange_data)) {
            foreach ($exchange_data as $item) {
                $product_id = $item['id'];
                $quantity = floatval($item['quantity']);
                $price = floatval($item['price']);
                $weight = isset($item['weight']) ? floatval($item['weight']) : 0;
                $is_open = isset($item['is_open_product']) ? $item['is_open_product'] : false;
                
                if ($quantity > 0 || $weight > 0) {
                    $exchange_amount = $is_open ? $price : ($price * $quantity);
                    $total_exchange_amount += $exchange_amount;
                    
                    $exchange_items[] = [
                        'product_id' => $product_id,
                        'product_name' => $item['name'],
                        'quantity' => $quantity,
                        'price' => $price,
                        'weight_kg' => $weight,
                        'is_open_product' => $is_open,
                        'amount' => $exchange_amount
                    ];
                }
            }
        }
    }
    
    if (empty($return_items)) {
        $error = "Please select at least one item to return/exchange.";
    } else {
        // Calculate net amount
        $net_amount = $total_return_amount - $total_exchange_amount;
        
        // Process the return
        try {
            $pdo->beginTransaction();
            
            // Insert return record (let MySQL auto-generate the return_id)
            $sql = "INSERT INTO returns (original_transaction_id, return_date, cashier_id,
                    customer_name, total_return_amount, exchange_amount, net_amount, return_type, payment_method, reason)
                    VALUES (:original_transaction_id, NOW(), :cashier_id,
                    :customer_name, :total_return_amount, :exchange_amount, :net_amount, :return_type, :payment_method, :reason)";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':original_transaction_id' => $transaction_id,
                ':cashier_id' => $_SESSION['user_id'],
                ':customer_name' => $customer_name,
                ':total_return_amount' => $total_return_amount,
                ':exchange_amount' => $total_exchange_amount,
                ':net_amount' => $net_amount,
                ':return_type' => $return_type,
                ':payment_method' => $payment_method,
                ':reason' => $reason
            ]);
            
            // Get the auto-generated return_id
            $return_id = $pdo->lastInsertId();
            
            // Insert return items and update inventory
            foreach ($return_items as $item) {
                // Insert return item
                $sql = "INSERT INTO return_items (return_id, product_id, quantity_returned,
                        return_price, is_open_product, weight_kg)
                        VALUES (:return_id, :product_id, :quantity_returned, :return_price,
                        :is_open_product, :weight_kg)";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':return_id' => $return_id,
                    ':product_id' => $item['product_id'],
                    ':quantity_returned' => $item['quantity'],
                    ':return_price' => $item['price'],
                    ':is_open_product' => $item['is_open_product'],
                    ':weight_kg' => $item['weight_kg']
                ]);
                
                // Update inventory - add returned items back to stock
                if ($item['is_open_product']) {
                    // For open products, add weight back
                    $sql = "UPDATE products SET quantity_in_stock = quantity_in_stock + :weight
                            WHERE product_id = :product_id";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([
                        ':weight' => $item['weight_kg'],
                        ':product_id' => $item['product_id']
                    ]);
                } else {
                    // For packaged products, add quantity back
                    $sql = "UPDATE products SET quantity_in_stock = quantity_in_stock + :quantity
                            WHERE product_id = :product_id";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([
                        ':quantity' => $item['quantity'],
                        ':product_id' => $item['product_id']
                    ]);
                }
            }
            
            // Process exchange items (if any) - create a new sale transaction
            $exchange_transaction_id = null;
            if (!empty($exchange_items) && $return_type == 'exchange') {
                $exchange_transaction_id = "EXC" . time();
                
                // Create exchange transaction
                $sql = "INSERT INTO transactions (transaction_id, transaction_date, cashier_id, customer_name,
                        payment_method, subtotal, tax_amount, discount_amount, total_amount, transaction_type)
                        VALUES (:transaction_id, NOW(), :cashier_id, :customer_name,
                        :payment_method, :subtotal, 0, 0, :total_amount, 'exchange')";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':transaction_id' => $exchange_transaction_id,
                    ':cashier_id' => $_SESSION['user_id'],
                    ':customer_name' => $customer_name,
                    ':payment_method' => $payment_method,
                    ':subtotal' => $total_exchange_amount,
                    ':total_amount' => $total_exchange_amount
                ]);
                
                // Insert exchange transaction items and update inventory
                foreach ($exchange_items as $item) {
                    // Insert transaction item
                    $sql = "INSERT INTO transaction_items (transaction_id, product_id, quantity, price, weight_kg)
                            VALUES (:transaction_id, :product_id, :quantity, :price, :weight_kg)";
                    
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([
                        ':transaction_id' => $exchange_transaction_id,
                        ':product_id' => $item['product_id'],
                        ':quantity' => $item['quantity'],
                        ':price' => $item['price'],
                        ':weight_kg' => $item['weight_kg']
                    ]);
                    
                    // Update inventory - subtract exchanged items from stock
                    if ($item['is_open_product']) {
                        // For open products, subtract weight
                        $sql = "UPDATE products SET quantity_in_stock = quantity_in_stock - :weight
                                WHERE product_id = :product_id AND quantity_in_stock >= :weight";
                        $stmt = $pdo->prepare($sql);
                        $stmt->execute([
                            ':weight' => $item['weight_kg'],
                            ':product_id' => $item['product_id']
                        ]);
                    } else {
                        // For packaged products, subtract quantity
                        $sql = "UPDATE products SET quantity_in_stock = quantity_in_stock - :quantity
                                WHERE product_id = :product_id";
                        $stmt = $pdo->prepare($sql);
                        $stmt->execute([
                            ':quantity' => $item['quantity'],
                            ':product_id' => $item['product_id']
                        ]);
                    }
                }
                
                // Update return record with exchange transaction ID
                $sql = "UPDATE returns SET exchange_transaction_id = :exchange_transaction_id WHERE return_id = :return_id";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':exchange_transaction_id' => $exchange_transaction_id,
                    ':return_id' => $return_id
                ]);
            }
            
            $pdo->commit();
            
            // Redirect to return receipt
            header("Location: return_receipt.php?return_id=" . $return_id);
            exit;
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Error processing return: " . $e->getMessage();
        }
    }
}
?>

<?php include 'includes/sidebar.php'; ?>

<div class="container">
    <h1><i class="fas fa-exchange-alt"></i> Return & Exchange</h1>
    
    <?php if ($error): ?>
        <div class="error"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <!-- Transaction Lookup Form -->
    <div class="form-section">
        <h2><i class="fas fa-search"></i> Lookup Transaction</h2>
        <form method="post" action="">
            <div class="form-group">
                <label for="transaction_id">Transaction ID:</label>
                <input type="text" id="transaction_id" name="transaction_id"
                       value="<?php echo isset($_POST['transaction_id']) ? htmlspecialchars($_POST['transaction_id']) : ''; ?>"
                       required>
            </div>
            <button type="submit" name="lookup_transaction" class="btn">
                <i class="fas fa-search"></i> Lookup Transaction
            </button>
        </form>
    </div>
    
    <!-- Return/Exchange Form -->
    <?php if ($transaction && is_array($items) && !empty($items)): ?>
    <div class="form-section">
        <h2><i class="fas fa-receipt"></i> Original Transaction Details</h2>
        
        <div class="transaction-details">
            <p><strong>Transaction ID:</strong> <?php echo $transaction['transaction_id']; ?></p>
            <p><strong>Date:</strong> <?php echo $transaction['transaction_date']; ?></p>
            <p><strong>Original Customer:</strong> <?php echo $transaction['customer_name']; ?></p>
            <p><strong>Total Amount:</strong> $<?php echo number_format($transaction['total_amount'], 2); ?></p>
        </div>
        
        <form method="post" action="" id="returnForm">
            <input type="hidden" name="transaction_id" value="<?php echo $transaction['transaction_id']; ?>">
            <input type="hidden" name="customer_name" value="<?php echo $transaction['customer_name']; ?>">
            
            <h3><i class="fas fa-list"></i> Select Items for Return</h3>
            
            <table>
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Original Qty</th>
                        <th>Price</th>
                        <th>Return Qty</th>
                        <th>Return Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): 
                        $max_qty = $item['is_open_product'] ? $item['weight_kg'] : $item['quantity'];
                        $item_total = $item['price'] * ($item['is_open_product'] ? 1 : $item['quantity']);
                        $product_id = $item['product_id'];
                        $price_per_kg = $item['is_open_product'] ? ($item['price'] / $item['weight_kg']) : 0;
                    ?>
                    <tr>
                        <td>
                            <?php echo htmlspecialchars($item['product_name']); ?>
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
                        <td>
                            <?php if ($item['is_open_product']): ?>
                                $<?php echo number_format($price_per_kg, 2); ?>/kg
                            <?php else: ?>
                                $<?php echo number_format($item['price'], 2); ?>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($item['is_open_product']): ?>
                                <!-- Open Product Weight Input -->
                                <div class="weight-inputs">
                                    <div style="display: flex; gap: 5px; align-items: center; margin-bottom: 5px;">
                                        <input type="number" 
                                               name="weight_kg_<?php echo $product_id; ?>"
                                               id="weight_kg_<?php echo $product_id; ?>"
                                               class="weight-kg" 
                                               min="0" 
                                               max="<?php echo floor($item['weight_kg']); ?>" 
                                               value="0"
                                               data-product-id="<?php echo $product_id; ?>"
                                               style="width: 60px;">
                                        <span>kg</span>
                                        <input type="number" 
                                               name="weight_grams_<?php echo $product_id; ?>"
                                               id="weight_grams_<?php echo $product_id; ?>"
                                               class="weight-grams" 
                                               min="0" 
                                               max="999" 
                                               value="0"
                                               data-product-id="<?php echo $product_id; ?>"
                                               style="width: 60px;">
                                        <span>g</span>
                                    </div>
                                </div>
                                <input type="hidden" 
                                       name="return_weight[<?php echo $product_id; ?>]" 
                                       id="return_weight_<?php echo $product_id; ?>"
                                       class="return-weight" 
                                       value="0"
                                       data-product-id="<?php echo $product_id; ?>"
                                       data-price-per-kg="<?php echo $price_per_kg; ?>">
                                <small>Max: <?php echo number_format($item['weight_kg'], 3); ?> kg</small>
                            <?php else: ?>
                                <!-- Packaged Product Quantity Controls -->
                                <div class="quantity-controls" style="display: flex; align-items: center; gap: 10px;">
                                    <button type="button" class="quantity-btn decrease-return-qty" 
                                            data-product-id="<?php echo $product_id; ?>">-</button>
                                    <input type="number" 
                                           name="return_qty[<?php echo $product_id; ?>]"
                                           id="return_qty_<?php echo $product_id; ?>"
                                           class="return-qty" 
                                           min="0" 
                                           max="<?php echo intval($item['quantity']); ?>" 
                                           value="0"
                                           data-product-id="<?php echo $product_id; ?>"
                                           data-price="<?php echo $item['price']; ?>"
                                           data-is-open="0"
                                           style="width: 60px; text-align: center;">
                                    <button type="button" class="quantity-btn increase-return-qty" 
                                            data-product-id="<?php echo $product_id; ?>">+</button>
                                </div>
                                <small>(max: <?php echo intval($item['quantity']); ?>)</small>
                            <?php endif; ?>
                        </td>
                        <td class="return-amount" id="return_amount_<?php echo $product_id; ?>">$0.00</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <!-- Exchange Products Section -->
            <div id="exchangeSection" style="display: none;">
                <h3><i class="fas fa-sync-alt"></i> Exchange Products</h3>
                
                <div class="product-selection">
                    <div class="search-section">
                        <h4><i class="fas fa-search"></i> Product Search</h4>
                        <div class="search-box">
                            <input type="text" id="exchange-search-input" placeholder="Search by name, ID, or barcode...">
                            <button type="button" id="exchange-search-btn" class="btn"><i class="fas fa-search"></i> Search</button>
                        </div>
                    </div>

                    <h4><i class="fas fa-boxes"></i> Available Products</h4>
                    <div class="product-grid" id="exchange-product-grid">
                        <?php foreach ($exchange_products as $product): ?>
                        <div class="product-item" 
                             data-id="<?php echo $product['product_id']; ?>"
                             data-name="<?php echo htmlspecialchars($product['product_name']); ?>"
                             data-price="<?php echo $product['selling_price']; ?>"
                             data-stock="<?php echo $product['quantity_in_stock']; ?>"
                             data-barcode="<?php echo $product['barcode']; ?>"
                             data-open="<?php echo $product['is_open_product']; ?>"
                             data-price-per-kg="<?php echo $product['price_per_kg']; ?>">
                            <?php if (!empty($product['image'])): ?>
                            <img src="<?php echo $product['image']; ?>" alt="<?php echo $product['product_name']; ?>" class="product-image">
                            <?php else: ?>
                            <div style="height: 120px; background: #f0f0f0; display: flex; align-items: center; justify-content: center; border-radius: 8px; margin-bottom: 10px;">
                                <i class="fas fa-image" style="font-size: 40px; color: #ccc;"></i>
                            </div>
                            <?php endif; ?>
                            <h3><?php echo $product['product_name']; ?></h3>
                            <p class="product-price">$<?php echo number_format($product['selling_price'], 2); ?></p>
                            <p>Stock: <?php echo $product['quantity_in_stock']; ?></p>
                            <?php if ($product['is_open_product']): ?>
                            <p>Price per kg: $<?php echo number_format($product['price_per_kg'], 2); ?></p>
                            <?php elseif (!empty($product['barcode'])): ?>
                            <p>Barcode: <?php echo $product['barcode']; ?></p>
                            <?php endif; ?>
                            <button type="button" class="btn btn-sm add-to-exchange-cart"><i class="fas fa-cart-plus"></i> Add to Exchange</button>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="shopping-cart">
                    <h4><i class="fas fa-shopping-cart"></i> Exchange Cart</h4>
                    <div id="exchange-cart-items-container">
                        <!-- Exchange cart items will be added here dynamically -->
                    </div>
                    <table>
                        <tfoot>
                            <tr>
                                <td colspan="3">Exchange Total</td>
                                <td id="exchange-total">$0.00</td>
                            </tr>
                        </tfoot>
                    </table>
                    <input type="hidden" name="exchange_items" id="exchange_items">
                </div>
            </div>
            
            <div class="form-group">
                <label for="return_type">Return Type:</label>
                <select id="return_type" name="return_type" required onchange="toggleExchangeSection()">
                    <option value="return">Return for Refund</option>
                    <option value="exchange">Exchange</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="payment_method">Refund Method:</label>
                <select id="payment_method" name="payment_method" required>
                    <option value="Cash">Cash</option>
                    <option value="Card">Card</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="reason">Reason for Return:</label>
                <textarea id="reason" name="reason" placeholder="Optional reason for return..."></textarea>
            </div>
            
            <div class="return-summary">
                <h3>Return & Exchange Summary</h3>
                <p><strong>Total Return Amount:</strong> $<span id="totalReturnAmount">0.00</span></p>
                <p id="exchangeSummary" style="display: none;"><strong>Total Exchange Amount:</strong> $<span id="totalExchangeAmount">0.00</span></p>
                <p id="netSummary" style="display: none;">
                    <strong>Net Amount:</strong> 
                    <span id="netAmountLabel"></span> $<span id="netAmount">0.00</span>
                </p>
            </div>
            
            <button type="submit" name="process_return" class="btn btn-warning">
                <i class="fas fa-check-circle"></i> Process Return/Exchange
            </button>
        </form>
    </div>
    <?php elseif ($transaction && empty($items)): ?>
        <div class="error">No items found in this transaction.</div>
    <?php endif; ?>
</div>

<!-- Exchange Product Weight Modal -->
<div id="exchangeProductModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h2>Enter Weight or Price</h2>
        
        <div class="form-group">
            <label for="exchange_price_per_kg_display">Price per Kg:</label>
            <span id="exchange_price_per_kg_display">$0.00</span>
        </div>
        
        <div class="weight-price-inputs">
            <div class="form-group">
                <label for="exchange_weight_kg">Kilograms:</label>
                <input type="number" id="exchange_weight_kg" min="0" value="0" step="1">
            </div>
            <div class="form-group">
                <label for="exchange_weight_grams">Grams:</label>
                <input type="number" id="exchange_weight_grams" min="0" max="999" value="0" step="1">
            </div>
            <div class="form-group">
                <label for="exchange_price_input">Amount ($):</label>
                <input type="number" id="exchange_price_input" step="0.01" value="0.00">
            </div>
        </div>
        
        <div class="form-group">
            <label>Total Weight: <span id="exchange_total_weight">0.000</span> kg</label>
        </div>
        <div class="form-group">
            <label>Total Price: $<span id="exchange_total_price">0.00</span></label>
        </div>
        
        <button type="button" id="add_exchange_product" class="btn">Add to Exchange Cart</button>
    </div>
</div>

<script>
// Exchange cart functionality
let exchangeCart = [];
let currentExchangeProduct = null;
let currentExchangePricePerKg = 0;
let isProgrammaticUpdate = false;

document.addEventListener('DOMContentLoaded', function() {
    // Quantity controls for packaged products (return)
    document.querySelectorAll('.increase-return-qty').forEach(button => {
        button.addEventListener('click', function() {
            const productId = this.getAttribute('data-product-id');
            const input = document.querySelector(`.return-qty[data-product-id="${productId}"]`);
            const max = parseInt(input.getAttribute('max'));
            const current = parseInt(input.value) || 0;
            
            if (current < max) {
                input.value = current + 1;
                updateReturnAmount(input);
            }
        });
    });

    document.querySelectorAll('.decrease-return-qty').forEach(button => {
        button.addEventListener('click', function() {
            const productId = this.getAttribute('data-product-id');
            const input = document.querySelector(`.return-qty[data-product-id="${productId}"]`);
            const current = parseInt(input.value) || 0;
            
            if (current > 0) {
                input.value = current - 1;
                updateReturnAmount(input);
            }
        });
    });

    // Weight calculation for open products (return)
    document.querySelectorAll('.weight-kg, .weight-grams').forEach(input => {
        input.addEventListener('input', function() {
            const productId = this.getAttribute('data-product-id');
            updateOpenProductWeight(productId);
        });
    });

    // Direct input change handlers (return)
    document.querySelectorAll('.return-qty').forEach(input => {
        input.addEventListener('input', function() {
            updateReturnAmount(this);
        });
    });

    // Exchange product search
    document.getElementById('exchange-search-btn').addEventListener('click', function(e) {
        e.preventDefault();
        searchExchangeProducts();
    });

    document.getElementById('exchange-search-input').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            searchExchangeProducts();
        }
    });

    // Add to exchange cart buttons - FIXED: Proper event delegation
    document.getElementById('exchange-product-grid').addEventListener('click', function(e) {
        if (e.target.classList.contains('add-to-exchange-cart')) {
            e.preventDefault();
            const productItem = e.target.closest('.product-item');
            addProductToExchangeCart(productItem);
        }
    });

    // Exchange weight modal functionality
    document.getElementById('exchange_weight_kg').addEventListener('input', function() {
        updateExchangeProductCalculation('weight');
    });

    document.getElementById('exchange_weight_grams').addEventListener('input', function() {
        updateExchangeProductCalculation('weight');
    });

    document.getElementById('exchange_price_input').addEventListener('input', function() {
        updateExchangeProductCalculation('price');
    });

    document.getElementById('add_exchange_product').addEventListener('click', function(e) {
        e.preventDefault();
        addExchangeProductWithWeight();
    });

    // Close modal when clicking on X
    document.querySelectorAll('#exchangeProductModal .close').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            document.getElementById('exchangeProductModal').style.display = 'none';
        });
    });

    // Close modal when clicking outside
    window.addEventListener('click', function(event) {
        if (event.target == document.getElementById('exchangeProductModal')) {
            document.getElementById('exchangeProductModal').style.display = 'none';
        }
    });

    // Initial calculation
    updateTotalReturnAmount();
});

function toggleExchangeSection() {
    const returnType = document.getElementById('return_type').value;
    const exchangeSection = document.getElementById('exchangeSection');
    const exchangeSummary = document.getElementById('exchangeSummary');
    const netSummary = document.getElementById('netSummary');
    
    if (returnType === 'exchange') {
        exchangeSection.style.display = 'block';
        exchangeSummary.style.display = 'block';
        netSummary.style.display = 'block';
    } else {
        exchangeSection.style.display = 'none';
        exchangeSummary.style.display = 'none';
        netSummary.style.display = 'none';
    }
    updateNetAmount();
}

function searchExchangeProducts() {
    const searchTerm = document.getElementById('exchange-search-input').value.toLowerCase().trim();
    const productItems = document.querySelectorAll('#exchange-product-grid .product-item');
    
    productItems.forEach(item => {
        const productName = item.getAttribute('data-name').toLowerCase();
        const productId = item.getAttribute('data-id').toLowerCase();
        const barcode = item.getAttribute('data-barcode').toLowerCase();
        
        if (productName.includes(searchTerm) || productId.includes(searchTerm) || barcode.includes(searchTerm)) {
            item.style.display = 'block';
        } else {
            item.style.display = 'none';
        }
    });
}

function addProductToExchangeCart(productItem) {
    const productId = productItem.getAttribute('data-id');
    const productName = productItem.getAttribute('data-name');
    const productPrice = parseFloat(productItem.getAttribute('data-price'));
    const stock = parseFloat(productItem.getAttribute('data-stock'));
    const isOpenProduct = productItem.getAttribute('data-open') === '1';
    const pricePerKg = parseFloat(productItem.getAttribute('data-price-per-kg'));
    const productImage = productItem.querySelector('img') ? productItem.querySelector('img').src : '';

    // For open products, show weight modal
    if (isOpenProduct) {
        currentExchangeProduct = {
            id: productId,
            name: productName,
            price: productPrice,
            stock: stock,
            pricePerKg: pricePerKg,
            image: productImage,
            isOpenProduct: true
        };
        currentExchangePricePerKg = pricePerKg;
        
        // Reset inputs
        document.getElementById('exchange_weight_kg').value = '0';
        document.getElementById('exchange_weight_grams').value = '0';
        document.getElementById('exchange_price_input').value = '0.00';
        
        // Set price per kg display
        document.getElementById('exchange_price_per_kg_display').textContent = '$' + currentExchangePricePerKg.toFixed(2);
        
        // Update calculation
        updateExchangeProductCalculation('weight');
        
        // Show modal
        document.getElementById('exchangeProductModal').style.display = 'block';
        return;
    }

    // For packaged products, add directly to cart
    const existingItem = exchangeCart.find(item => item.id === productId && !item.is_open_product);

    if (existingItem) {
        if (existingItem.quantity < stock) {
            existingItem.quantity++;
            updateExchangeCart();
        } else {
            alert('Not enough stock available');
        }
    } else {
        if (stock > 0) {
            exchangeCart.push({
                id: productId,
                name: productName,
                price: productPrice,
                quantity: 1,
                image: productImage,
                is_open_product: false
            });
            updateExchangeCart();
        } else {
            alert('Product is out of stock');
        }
    }
}

function updateExchangeProductCalculation(source) {
    if (isProgrammaticUpdate) {
        isProgrammaticUpdate = false;
        return;
    }
    
    const kg = parseFloat(document.getElementById('exchange_weight_kg').value) || 0;
    const grams = parseFloat(document.getElementById('exchange_weight_grams').value) || 0;
    const priceInput = parseFloat(document.getElementById('exchange_price_input').value) || 0;
    
    const totalWeight = kg + (grams / 1000);
    
    if (source === 'weight') {
        // Calculate price based on weight
        const calculatedPrice = totalWeight * currentExchangePricePerKg;
        isProgrammaticUpdate = true;
        document.getElementById('exchange_price_input').value = calculatedPrice.toFixed(2);
    } else if (source === 'price') {
        // Calculate weight based on price
        const calculatedWeight = priceInput / currentExchangePricePerKg;
        const calculatedKg = Math.floor(calculatedWeight);
        const calculatedGrams = Math.round((calculatedWeight - calculatedKg) * 1000);
        isProgrammaticUpdate = true;
        document.getElementById('exchange_weight_kg').value = calculatedKg;
        document.getElementById('exchange_weight_grams').value = calculatedGrams;
    }
    
    // Update display regardless of source
    const finalWeight = parseFloat(document.getElementById('exchange_weight_kg').value || 0) +
                      (parseFloat(document.getElementById('exchange_weight_grams').value || 0) / 1000);
    const finalPrice = finalWeight * currentExchangePricePerKg;
    
    document.getElementById('exchange_total_weight').textContent = finalWeight.toFixed(3);
    document.getElementById('exchange_total_price').textContent = finalPrice.toFixed(2);
}

function addExchangeProductWithWeight() {
    const kg = parseFloat(document.getElementById('exchange_weight_kg').value) || 0;
    const grams = parseFloat(document.getElementById('exchange_weight_grams').value) || 0;
    const totalWeight = kg + (grams / 1000);
    
    if (totalWeight <= 0) {
        alert('Please enter a valid weight');
        return;
    }
    
    const calculatedPrice = totalWeight * currentExchangePricePerKg;
    
    // Add to exchange cart with weight
    exchangeCart.push({
        id: currentExchangeProduct.id,
        name: currentExchangeProduct.name,
        price: calculatedPrice,
        quantity: 1,
        weight: totalWeight,
        price_per_kg: currentExchangePricePerKg,
        image: currentExchangeProduct.image,
        is_open_product: true
    });
    
    updateExchangeCart();
    
    // Close modal
    document.getElementById('exchangeProductModal').style.display = 'none';
}

function updateExchangeCart() {
    const exchangeCartContainer = document.getElementById('exchange-cart-items-container');
    exchangeCartContainer.innerHTML = '';

    let exchangeTotal = 0;

    exchangeCart.forEach((item, index) => {
        const cartItem = document.createElement('div');
        cartItem.className = 'cart-item';
        
        let itemDetails = `
            ${item.image ? `<img src="${item.image}" alt="${item.name}" class="cart-item-image">` : 
            `<div style="width: 50px; height: 50px; background: #f0f0f0; display: flex; align-items: center; justify-content: center; border-radius: 5px; margin-right: 15px;">
                <i class="fas fa-image" style="color: #ccc;"></i>
            </div>`}
            <div class="cart-item-details">
                <div class="cart-item-name">${item.name}</div>
        `;
        
        if (item.is_open_product) {
            const kg = Math.floor(item.weight);
            const grams = Math.round((item.weight - kg) * 1000);
            itemDetails += `
                <div class="cart-item-price">$${item.price.toFixed(2)} (${kg} kg ${grams} g @ $${item.price_per_kg.toFixed(2)}/kg)</div>
            `;
        } else {
            itemDetails += `
                <div class="cart-item-price">$${item.price.toFixed(2)} each</div>
            `;
        }
        
        itemDetails += `</div>`;
        
        if (item.is_open_product) {
            itemDetails += `
                <div class="cart-item-controls">
                    <button type="button" class="btn btn-danger btn-sm" onclick="removeFromExchangeCart(${index})"><i class="fas fa-trash"></i></button>
                </div>
            `;
        } else {
            itemDetails += `
                <div class="cart-item-controls">
                    <button type="button" class="quantity-btn" onclick="decreaseExchangeQuantity(${index})">-</button>
                    <span>${item.quantity}</span>
                    <button type="button" class="quantity-btn" onclick="increaseExchangeQuantity(${index})">+</button>
                    <button type="button" class="btn btn-danger btn-sm" onclick="removeFromExchangeCart(${index})"><i class="fas fa-trash"></i></button>
                </div>
            `;
        }
        
        cartItem.innerHTML = itemDetails;
        exchangeCartContainer.appendChild(cartItem);

        exchangeTotal += item.is_open_product ? item.price : (item.price * item.quantity);
    });

    document.getElementById('exchange-total').textContent = '$' + exchangeTotal.toFixed(2);
    document.getElementById('totalExchangeAmount').textContent = exchangeTotal.toFixed(2);
    document.getElementById('exchange_items').value = JSON.stringify(exchangeCart);

    updateNetAmount();
}

function increaseExchangeQuantity(index) {
    const item = exchangeCart[index];
    if (!item.is_open_product) {
        const stock = parseFloat(document.querySelector(`.product-item[data-id="${item.id}"]`).getAttribute('data-stock'));
        if (item.quantity < stock) {
            item.quantity++;
            updateExchangeCart();
        } else {
            alert('Not enough stock available');
        }
    }
}

function decreaseExchangeQuantity(index) {
    const item = exchangeCart[index];
    if (!item.is_open_product && item.quantity > 1) {
        item.quantity--;
        updateExchangeCart();
    }
}

function removeFromExchangeCart(index) {
    exchangeCart.splice(index, 1);
    updateExchangeCart();
}

function updateOpenProductWeight(productId) {
    const kgInput = document.querySelector(`.weight-kg[data-product-id="${productId}"]`);
    const gramsInput = document.querySelector(`.weight-grams[data-product-id="${productId}"]`);
    const hiddenInput = document.querySelector(`.return-weight[data-product-id="${productId}"]`);
    
    const kg = parseFloat(kgInput.value) || 0;
    const grams = parseFloat(gramsInput.value) || 0;
    const totalWeight = kg + (grams / 1000);
    
    // Update hidden input with total weight
    hiddenInput.value = totalWeight;
    
    // Calculate return amount
    const pricePerKg = parseFloat(hiddenInput.getAttribute('data-price-per-kg')) || 0;
    const returnAmount = totalWeight * pricePerKg;
    
    const amountCell = document.querySelector(`#return_amount_${productId}`);
    amountCell.textContent = '$' + returnAmount.toFixed(2);
    
    updateTotalReturnAmount();
}

function updateReturnAmount(input) {
    const productId = input.getAttribute('data-product-id');
    const qty = parseFloat(input.value) || 0;
    const price = parseFloat(input.getAttribute('data-price')) || 0;
    const returnAmount = qty * price;
    
    const amountCell = document.querySelector(`#return_amount_${productId}`);
    amountCell.textContent = '$' + returnAmount.toFixed(2);
    
    updateTotalReturnAmount();
}

function updateTotalReturnAmount() {
    let total = 0;
    
    // Sum packaged products
    document.querySelectorAll('.return-qty').forEach(input => {
        const qty = parseFloat(input.value) || 0;
        const price = parseFloat(input.getAttribute('data-price')) || 0;
        total += qty * price;
    });
    
    // Sum open products
    document.querySelectorAll('.return-weight').forEach(input => {
        const weight = parseFloat(input.value) || 0;
        const pricePerKg = parseFloat(input.getAttribute('data-price-per-kg')) || 0;
        total += weight * pricePerKg;
    });
    
    document.getElementById('totalReturnAmount').textContent = total.toFixed(2);
    updateNetAmount();
}

function updateNetAmount() {
    const returnAmount = parseFloat(document.getElementById('totalReturnAmount').textContent) || 0;
    const exchangeAmount = parseFloat(document.getElementById('totalExchangeAmount').textContent) || 0;
    const netAmount = returnAmount - exchangeAmount;
    
    document.getElementById('netAmount').textContent = Math.abs(netAmount).toFixed(2);
    
    const netAmountLabel = document.getElementById('netAmountLabel');
    if (netAmount > 0) {
        netAmountLabel.textContent = 'Refund Amount:';
        netAmountLabel.style.color = 'green';
    } else if (netAmount < 0) {
        netAmountLabel.textContent = 'Amount Payable:';
        netAmountLabel.style.color = 'red';
    } else {
        netAmountLabel.textContent = 'Net Amount:';
        netAmountLabel.style.color = 'black';
    }
}

// Form validation
document.getElementById('returnForm').addEventListener('submit', function(e) {
    let hasReturnItems = false;
    
    // Check packaged products
    document.querySelectorAll('.return-qty').forEach(input => {
        if (parseFloat(input.value) > 0) {
            hasReturnItems = true;
        }
    });
    
    // Check open products
    document.querySelectorAll('.return-weight').forEach(input => {
        if (parseFloat(input.value) > 0) {
            hasReturnItems = true;
        }
    });
    
    if (!hasReturnItems) {
        e.preventDefault();
        alert('Please select at least one item to return/exchange.');
    }
});
</script>

<?php include 'includes/footer.php'; ?>