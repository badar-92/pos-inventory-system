<?php
include 'includes/config.php';
include 'includes/auth.php';
include 'includes/sales.php';

// Check if user is logged in and is cashier or admin
checkAuthentication();

if ($_SESSION['role'] != 'cashier' && $_SESSION['role'] != 'admin') {
    header("Location: dashboard.php");
    exit;
}

// Process sale if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['process_sale'])) {
    $customer_name = $_POST['customer_name'];
    $payment_method = $_POST['payment_method'];
    $cart_items = json_decode($_POST['cart_items'], true);
    $discount_type = $_POST['discount_type'];
    $discount_value = $_POST['discount_value'];
    $cash_given = $_POST['cash_given'];
    $tax_rate = $_POST['tax_rate'];
    
    $result = processSale($customer_name, $payment_method, $cart_items, $_SESSION['user_id'], $discount_type, $discount_value, $cash_given, $tax_rate);
    
    if ($result['success']) {
        header("Location: receipt.php?transaction_id=" . $result['transaction_id']);
        exit;
    } else {
        $error = $result['message'];
    }
}

// Get products for selection
$products = getProducts();
?>

<?php include 'includes/sidebar.php'; ?>

<div class="container">
    <h1><i class="fas fa-cash-register"></i> Point of Sale</h1>
    
    <?php if (isset($error)) echo "<div class='error'>$error</div>"; ?>
    
    <div class="pos-container">
        <div class="product-selection">
            <div class="search-section">
                <h2><i class="fas fa-search"></i> Product Search</h2>
                <div class="search-box">
                    <input type="text" id="search-input" placeholder="Search by name, ID, or barcode...">
                    <button id="search-btn" class="btn"><i class="fas fa-search"></i> Search</button>
                </div>
                <div class="barcode-section">
                    <h3><i class="fas fa-barcode"></i> Barcode Scanner</h3>
                    <input type="text" id="barcode-input" placeholder="Scan barcode or enter manually">
                    <button id="scan-btn" class="btn"><i class="fas fa-camera"></i> Simulate Scan</button>
                </div>
            </div>
            
            <h2><i class="fas fa-boxes"></i> Products</h2>
            <div class="product-grid" id="product-grid">
                <?php foreach ($products as $product): ?>
                <div class="product-item" data-id="<?php echo $product['product_id']; ?>"
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
                    <button class="btn btn-sm add-to-cart"><i class="fas fa-cart-plus"></i> Add to Cart</button>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <div class="shopping-cart">
            <h2><i class="fas fa-shopping-cart"></i> Shopping Cart</h2>
            <form method="post" action="">
                <div class="customer-details">
                    <div class="form-group">
                        <label for="customer_name">Customer Name:</label>
                        <input type="text" id="customer_name" name="customer_name" required>
                    </div>
                    
                    <div class="tax-section">
                        <div class="form-group">
                            <label for="tax_rate">Tax Rate (%):</label>
                            <input type="number" step="0.01" id="tax_rate" name="tax_rate" value="10.00" required>
                        </div>
                    </div>
                    
                    <div class="discount-section">
                        <div class="form-group">
                            <label for="discount_type">Discount Type:</label>
                            <select id="discount_type" name="discount_type">
                                <option value="none">No Discount</option>
                                <option value="amount">Amount</option>
                                <option value="percentage">Percentage</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="discount_value">Discount Value:</label>
                            <input type="number" step="0.01" id="discount_value" name="discount_value" value="0" disabled>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="payment_method">Payment Method:</label>
                        <select id="payment_method" name="payment_method" required onchange="toggleCashPayment()">
                            <option value="Cash">Cash</option>
                            <option value="Card">Card</option>
                        </select>
                    </div>
                    
                    <div id="cash_payment_section" class="cash-payment">
                        <div class="form-group">
                            <label for="cash_given">Cash Given:</label>
                            <input type="number" step="0.01" id="cash_given" name="cash_given" value="0">
                        </div>
                        <div class="form-group">
                            <label>Change:</label>
                            <span id="change_amount">$0.00</span>
                        </div>
                    </div>
                </div>
                
                <div id="cart-items-container">
                    <!-- Cart items will be added here dynamically -->
                </div>
                
                <table>
                    <tfoot>
                        <tr>
                            <td colspan="3">Subtotal</td>
                            <td id="subtotal">$0.00</td>
                        </tr>
                        <tr id="discount_row" style="display: none;">
                            <td colspan="3">Discount</td>
                            <td id="discount_amount">$0.00</td>
                        </tr>
                        <tr>
                            <td colspan="3">Tax (<span id="tax_rate_display">10.00</span>%)</td>
                            <td id="tax">$0.00</td>
                        </tr>
                        <tr>
                            <td colspan="3">Total</td>
                            <td id="total">$0.00</td>
                        </tr>
                    </tfoot>
                </table>
                
                <input type="hidden" name="cart_items" id="cart_items">
                <button type="submit" name="process_sale" class="btn"><i class="fas fa-check-circle"></i> Complete Sale</button>
            </form>
        </div>
    </div>
</div>

<!-- Modal for Open Product Weight/Price - Updated Version -->
<div id="openProductModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h2>Enter Weight or Price</h2>
        
        <div class="form-group">
            <label for="price_per_kg_display">Price per Kg:</label>
            <span id="price_per_kg_display">$0.00</span>
        </div>
        
        <div class="weight-price-inputs">
            <div class="form-group">
                <label for="weight_kg">Kilograms:</label>
                <input type="number" id="weight_kg" min="0" value="0" step="1">
            </div>
            <div class="form-group">
                <label for="weight_grams">Grams:</label>
                <input type="number" id="weight_grams" min="0" max="999" value="0" step="1">
            </div>
            <div class="form-group">
                <label for="price_input">Amount ($):</label>
                <input type="number" id="price_input" step="0.01" value="0.00">
            </div>
        </div>
        
        <div class="form-group">
            <label>Total Weight: <span id="total_weight">0.000</span> kg</label>
        </div>
        <div class="form-group">
            <label>Total Price: $<span id="total_price">0.00</span></label>
        </div>
        
        <button id="add_open_product" class="btn">Add to Cart</button>
    </div>
</div>

<script src="js/pos.js"></script>
<script src="js/barcode.js"></script>

<script>
// Toggle cash payment section
function toggleCashPayment() {
    const paymentMethod = document.getElementById('payment_method').value;
    const cashSection = document.getElementById('cash_payment_section');
    cashSection.style.display = paymentMethod === 'Cash' ? 'block' : 'none';
    
    if (paymentMethod === 'Cash') {
        calculateChange();
    }
}

// Update tax rate display
document.getElementById('tax_rate').addEventListener('input', function() {
    document.getElementById('tax_rate_display').textContent = this.value;
    updateCart();
});

// Toggle discount field
document.getElementById('discount_type').addEventListener('change', function() {
    const discountValue = document.getElementById('discount_value');
    discountValue.disabled = this.value === 'none';
    
    if (this.value === 'none') {
        discountValue.value = '0';
    }
    
    updateCart();
});

// Calculate change for cash payments
function calculateChange() {
    const cashGiven = parseFloat(document.getElementById('cash_given').value) || 0;
    const totalAmount = parseFloat(document.getElementById('total').textContent.replace('$', '')) || 0;
    const change = cashGiven - totalAmount;
    
    document.getElementById('change_amount').textContent = '$' + change.toFixed(2);
}

// Global variables for open product handling
let currentOpenProduct = null;
let currentPricePerKg = 0;
let isProgrammaticUpdate = false;

// Open weight modal for open products
function openWeightModal(productItem) {
    currentOpenProduct = productItem;
    currentPricePerKg = parseFloat(productItem.getAttribute('data-price-per-kg'));
    
    // Reset inputs
    document.getElementById('weight_kg').value = '0';
    document.getElementById('weight_grams').value = '0';
    document.getElementById('price_input').value = '0.00';
    
    // Set price per kg display
    document.getElementById('price_per_kg_display').textContent = '$' + currentPricePerKg.toFixed(2);
    
    // Update calculation
    updateOpenProductCalculation('weight');
    
    // Show modal
    document.getElementById('openProductModal').style.display = 'block';
}

// Update open product calculation - FIXED VERSION
function updateOpenProductCalculation(source) {
    if (isProgrammaticUpdate) {
        isProgrammaticUpdate = false;
        return;
    }
    
    const kg = parseFloat(document.getElementById('weight_kg').value) || 0;
    const grams = parseFloat(document.getElementById('weight_grams').value) || 0;
    const priceInput = parseFloat(document.getElementById('price_input').value) || 0;
    
    const totalWeight = kg + (grams / 1000);
    
    if (source === 'weight') {
        // Calculate price based on weight
        const calculatedPrice = totalWeight * currentPricePerKg;
        isProgrammaticUpdate = true;
        document.getElementById('price_input').value = calculatedPrice.toFixed(2);
    } else if (source === 'price') {
        // Calculate weight based on price
        const calculatedWeight = priceInput / currentPricePerKg;
        const calculatedKg = Math.floor(calculatedWeight);
        const calculatedGrams = Math.round((calculatedWeight - calculatedKg) * 1000);
        isProgrammaticUpdate = true;
        document.getElementById('weight_kg').value = calculatedKg;
        document.getElementById('weight_grams').value = calculatedGrams;
    }
    
    // Update display regardless of source
    const finalWeight = parseFloat(document.getElementById('weight_kg').value || 0) + 
                       (parseFloat(document.getElementById('weight_grams').value || 0) / 1000);
    const finalPrice = finalWeight * currentPricePerKg;
    
    document.getElementById('total_weight').textContent = finalWeight.toFixed(3);
    document.getElementById('total_price').textContent = finalPrice.toFixed(2);
}

// Event listeners for weight and price inputs - UPDATED
document.getElementById('weight_kg').addEventListener('input', function() {
    updateOpenProductCalculation('weight');
});
document.getElementById('weight_grams').addEventListener('input', function() {
    updateOpenProductCalculation('weight');
});
document.getElementById('price_input').addEventListener('input', function() {
    updateOpenProductCalculation('price');
});

// Add product with weight to cart - FIXED VERSION
document.getElementById('add_open_product').addEventListener('click', function() {
    const kg = parseFloat(document.getElementById('weight_kg').value) || 0;
    const grams = parseFloat(document.getElementById('weight_grams').value) || 0;
    const totalWeight = kg + (grams / 1000);
    
    if (totalWeight <= 0) {
        alert('Please enter a valid weight');
        return;
    }
    
    const productId = currentOpenProduct.getAttribute('data-id');
    const productName = currentOpenProduct.getAttribute('data-name');
    const calculatedPrice = totalWeight * currentPricePerKg;
    const productImage = currentOpenProduct.querySelector('img') ? 
                         currentOpenProduct.querySelector('img').src : '';
    
    // Add to cart with weight
    cart.push({
        id: productId,
        name: productName,
        price: calculatedPrice,
        quantity: 1,
        weight: totalWeight,
        price_per_kg: currentPricePerKg,
        image: productImage,
        is_open_product: true
    });
    
    updateCart();
    
    // Close modal
    document.getElementById('openProductModal').style.display = 'none';
});
    
 

// Close modal when clicking on X
document.querySelectorAll('.close').forEach(button => {
    button.addEventListener('click', function() {
        document.getElementById('openProductModal').style.display = 'none';
    });
});

// Close modal when clicking outside
window.addEventListener('click', function(event) {
    if (event.target == document.getElementById('openProductModal')) {
        document.getElementById('openProductModal').style.display = 'none';
    }
});

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    toggleCashPayment();
});
</script>

<?php include 'includes/footer.php'; ?>