<?php
include 'includes/config.php';
include 'includes/auth.php';
include 'includes/sales.php';

// Check if user is logged in
checkAuthentication();

// Search products
$search_results = [];
$search_term = '';

if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['search'])) {
    $search_term = trim($_GET['search']);
    if (!empty($search_term)) {
        $search_results = searchProducts($search_term);
    }
}
?>

<?php include 'includes/sidebar.php'; ?>

<div class="container">
    <h1><i class="fas fa-search"></i> Product Search</h1>
    
    <div class="form-section">
        <h2><i class="fas fa-search"></i> Search Products</h2>
        <form method="get" action="">
            <div class="form-group">
                <label for="search">Search by Name, ID, or Barcode:</label>
                <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($search_term); ?>" placeholder="Enter product name, ID, or barcode...">
            </div>
            <button type="submit" class="btn"><i class="fas fa-search"></i> Search</button>
        </form>
    </div>
    
    <?php if (!empty($search_term)): ?>
    <div class="table-section">
        <h2><i class="fas fa-list"></i> Search Results for "<?php echo htmlspecialchars($search_term); ?>"</h2>
        
        <?php if (empty($search_results)): ?>
        <p>No products found matching your search.</p>
        <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Image</th>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Category</th>
                    <th>Price</th>
                    <th>Stock</th>
                    <th>Location</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($search_results as $product): ?>
                <tr>
                    <td>
                        <?php if (!empty($product['image'])): ?>
                        <img src="<?php echo $product['image']; ?>" alt="<?php echo $product['product_name']; ?>" style="width: 50px; height: 50px; object-fit: cover; border-radius: 5px;">
                        <?php else: ?>
                        <div style="width: 50px; height: 50px; background: #f0f0f0; display: flex; align-items: center; justify-content: center; border-radius: 5px;">
                            <i class="fas fa-image" style="color: #ccc;"></i>
                        </div>
                        <?php endif; ?>
                    </td>
                    <td><?php echo $product['product_id']; ?></td>
                    <td><?php echo $product['product_name']; ?></td>
                    <td><?php echo $product['category']; ?></td>
                    <td>$<?php echo number_format($product['selling_price'], 2); ?></td>
                    <td><?php echo $product['quantity_in_stock']; ?></td>
                    <td><?php echo $product['location']; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>