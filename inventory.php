<?php
include 'includes/config.php';
include 'includes/auth.php';
include 'includes/barcode.php';

// Check if user is logged in and has appropriate permissions
checkAuthentication();
if ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'manager') {
    header("Location: dashboard.php");
    exit;
}
function getStickerData($product_id) {
    // Default: 1 copy for every product
    return ['copies' => 1];
}

// Handle add product form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_product'])) {
    $product_id = uniqid("prod_");
    $product_name = trim($_POST['product_name']);
    $category = trim($_POST['category']);
    $subcategory = trim($_POST['subcategory']);
    $description = trim($_POST['description']);
    $purchase_price = floatval($_POST['purchase_price']);
    $selling_price = floatval($_POST['selling_price']);
    $reorder_point = intval($_POST['reorder_point']);
    $supplier_id = trim($_POST['supplier_id']);
    $location = trim($_POST['location']);
    $product_type = $_POST['product_type'];

    // Image upload
    $image = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $targetDir = "uploads/products/";
        if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
        $image = $targetDir . basename($_FILES["image"]["name"]);
        move_uploaded_file($_FILES["image"]["tmp_name"], $image);
    }

    // ✅ Generate scannable Code-128 barcode
    if (!is_dir("uploads/barcodes/")) {
        mkdir("uploads/barcodes/", 0777, true);
    }

    // Use the fixed utility class for barcode
    $barcode = BarcodeGeneratorUtil::generateBarcode($product_id);
    $barcodePath = "uploads/barcodes/{$barcode}.png";

    // Generate and save barcode image
    BarcodeGeneratorUtil::generateBarcodeImage($barcode, $product_id);

    // Handle packaged vs open products
    if ($product_type == 'packaged') {
        $quantity_in_stock = isset($_POST['quantity_in_stock']) ? floatval($_POST['quantity_in_stock']) : 0;
        $is_open_product = 0;
        $price_per_kg = 0;
    } else { // open product
        $weight_kg = isset($_POST['weight_kg']) ? floatval($_POST['weight_kg']) : 0;
        $weight_grams = isset($_POST['weight_grams']) ? floatval($_POST['weight_grams']) : 0;
        $quantity_in_stock = $weight_kg + ($weight_grams / 1000); // stored in kg with decimals
        $is_open_product = 1;
        $price_per_kg = isset($_POST['price_per_kg']) ? floatval($_POST['price_per_kg']) : 0;
    }

    // ✅ Insert product into DB
    $stmt = $pdo->prepare("INSERT INTO products 
        (product_id, product_name, category, subcategory, description, purchase_price, selling_price, quantity_in_stock, reorder_point, supplier_id, location, barcode, image, is_open_product, price_per_kg)
        VALUES (:product_id, :product_name, :category, :subcategory, :description, :purchase_price, :selling_price, :quantity_in_stock, :reorder_point, :supplier_id, :location, :barcode, :image, :is_open_product, :price_per_kg)");
    
    $stmt->execute([
        'product_id' => $product_id,
        'product_name' => $product_name,
        'category' => $category,
        'subcategory' => $subcategory,
        'description' => $description,
        'purchase_price' => $purchase_price,
        'selling_price' => $selling_price,
        'quantity_in_stock' => $quantity_in_stock,
        'reorder_point' => $reorder_point,
        'supplier_id' => $supplier_id,
        'location' => $location,
        'barcode' => $barcode,
        'image' => $image,
        'is_open_product' => $is_open_product,
        'price_per_kg' => $price_per_kg
    ]);

    // ✅ Save sticker data safely
   // saveStickerData($product_id, $barcode, 1);
    
    header("Location: inventory.php?success=1");
    exit;
}

// Handle update product form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_product'])) {
    $product_id = trim($_POST['product_id']);
    $product_name = trim($_POST['product_name']);
    $category = trim($_POST['category']);
    $subcategory = trim($_POST['subcategory']);
    $description = trim($_POST['description']);
    $purchase_price = floatval($_POST['purchase_price']);
    $selling_price = floatval($_POST['selling_price']);
    $reorder_point = intval($_POST['reorder_point']);
    $supplier_id = trim($_POST['supplier_id']);
    $location = trim($_POST['location']);
    $product_type = $_POST['product_type'];

    // Image upload
    $image = $_POST['current_image'];
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $targetDir = "uploads/products/";
        if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
        $image = $targetDir . basename($_FILES["image"]["name"]);
        move_uploaded_file($_FILES["image"]["tmp_name"], $image);
    }

    // Handle packaged vs open products
    if ($product_type == 'packaged') {
        $quantity_in_stock = isset($_POST['quantity_in_stock']) ? floatval($_POST['quantity_in_stock']) : 0;
        $is_open_product = 0;
        $price_per_kg = 0;
    } else {
        $weight_kg = isset($_POST['weight_kg']) ? floatval($_POST['weight_kg']) : 0;
        $weight_grams = isset($_POST['weight_grams']) ? floatval($_POST['weight_grams']) : 0;
        $quantity_in_stock = $weight_kg + ($weight_grams / 1000);
        $is_open_product = 1;
        $price_per_kg = isset($_POST['price_per_kg']) ? floatval($_POST['price_per_kg']) : 0;
    }

    // ✅ Update product in DB
    $stmt = $pdo->prepare("UPDATE products SET 
        product_name = :product_name, 
        category = :category, 
        subcategory = :subcategory, 
        description = :description, 
        purchase_price = :purchase_price, 
        selling_price = :selling_price, 
        quantity_in_stock = :quantity_in_stock, 
        reorder_point = :reorder_point, 
        supplier_id = :supplier_id, 
        location = :location, 
        image = :image, 
        is_open_product = :is_open_product, 
        price_per_kg = :price_per_kg 
        WHERE product_id = :product_id");
    
    $stmt->execute([
        'product_id' => $product_id,
        'product_name' => $product_name,
        'category' => $category,
        'subcategory' => $subcategory,
        'description' => $description,
        'purchase_price' => $purchase_price,
        'selling_price' => $selling_price,
        'quantity_in_stock' => $quantity_in_stock,
        'reorder_point' => $reorder_point,
        'supplier_id' => $supplier_id,
        'location' => $location,
        'image' => $image,
        'is_open_product' => $is_open_product,
        'price_per_kg' => $price_per_kg
    ]);
    
    header("Location: inventory.php?success=2");
    exit;
}

// Handle sticker update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_sticker'])) {
    $product_id = trim($_POST['product_id']);
    $copies = intval($_POST['copies']);
    
    $stmt = $pdo->prepare("SELECT barcode FROM products WHERE product_id = :product_id");
    $stmt->execute(['product_id' => $product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($product) {
        saveStickerData($product_id, $product['barcode'], $copies);
        header("Location: inventory.php?success=3");
        exit;
    }
}

// Handle product deletion
if (isset($_GET['delete'])) {
    $product_id = $_GET['delete'];
    
    $stmt = $pdo->prepare("SELECT * FROM products WHERE product_id = :product_id");
    $stmt->execute(['product_id' => $product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($product) {
        $stmt = $pdo->prepare("DELETE FROM products WHERE product_id = :product_id");
        $stmt->execute(['product_id' => $product_id]);
        
        if (!empty($product['image']) && file_exists($product['image'])) unlink($product['image']);
        $barcode_path = "uploads/barcodes/{$product['barcode']}.png";
        if (!empty($product['barcode']) && file_exists($barcode_path)) unlink($barcode_path);
        
        header("Location: inventory.php?success=4");
        exit;
    }
}

// Fetch all products
$stmt = $pdo->query("SELECT * FROM products ORDER BY date_added DESC");
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Edit mode
$edit_product = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE product_id = :product_id");
    $stmt->execute(['product_id' => $_GET['edit']]);
    $edit_product = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Sticker data
$sticker_data = null;
if (isset($_GET['sticker'])) {
    $sticker_data = getStickerData($_GET['sticker']);
}

include 'includes/sidebar.php';
?>

<!-- (HTML and JS remain unchanged below this point) -->


<div class="container">
    <h1><i class="fas fa-box"></i> Inventory Management</h1>
    
    <?php if (isset($_GET['success'])): ?>
        <div class="success">
            <?php 
            if ($_GET['success'] == 1) echo "Product added successfully!";
            elseif ($_GET['success'] == 2) echo "Product updated successfully!";
            elseif ($_GET['success'] == 3) echo "Sticker copies updated successfully!";
            elseif ($_GET['success'] == 4) echo "Product deleted successfully!";
            ?>
        </div>
    <?php endif; ?>
    
    <div class="form-section">
        <h2><i class="fas fa-plus-circle"></i> <?php echo $edit_product ? 'Edit Product' : 'Add New Product'; ?></h2>
        <form method="POST" action="" enctype="multipart/form-data">
            <?php if ($edit_product): ?>
                <input type="hidden" name="product_id" value="<?php echo $edit_product['product_id']; ?>">
                <input type="hidden" name="current_image" value="<?php echo $edit_product['image']; ?>">
                <input type="hidden" name="update_product" value="1">
            <?php else: ?>
                <input type="hidden" name="add_product" value="1">
            <?php endif; ?>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="product_name">Product Name</label>
                        <input type="text" id="product_name" name="product_name" value="<?php echo $edit_product ? $edit_product['product_name'] : ''; ?>" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="category">Category</label>
                        <input type="text" id="category" name="category" value="<?php echo $edit_product ? $edit_product['category'] : ''; ?>">
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="subcategory">Subcategory</label>
                        <input type="text" id="subcategory" name="subcategory" value="<?php echo $edit_product ? $edit_product['subcategory'] : ''; ?>">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description"><?php echo $edit_product ? $edit_product['description'] : ''; ?></textarea>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="purchase_price">Purchase Price</label>
                        <input type="number" step="0.01" id="purchase_price" name="purchase_price" value="<?php echo $edit_product ? $edit_product['purchase_price'] : ''; ?>" required>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="selling_price">Selling Price</label>
                        <input type="number" step="0.01" id="selling_price" name="selling_price" value="<?php echo $edit_product ? $edit_product['selling_price'] : ''; ?>" required>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="reorder_point">Reorder Point</label>
                        <input type="number" id="reorder_point" name="reorder_point" value="<?php echo $edit_product ? $edit_product['reorder_point'] : '5'; ?>">
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="supplier_id">Supplier ID</label>
                        <input type="text" id="supplier_id" name="supplier_id" value="<?php echo $edit_product ? $edit_product['supplier_id'] : ''; ?>">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="location">Location</label>
                        <input type="text" id="location" name="location" value="<?php echo $edit_product ? $edit_product['location'] : ''; ?>">
                    </div>
                </div>
            </div>
            
            <div class="form-group">
                <label for="image">Product Image</label>
                <div class="image-upload-container" onclick="document.getElementById('image').click()">
                    <i class="fas fa-cloud-upload-alt"></i>
                    <p>Click to upload product image</p>
                    <?php if ($edit_product && $edit_product['image']): ?>
                        <img src="<?php echo $edit_product['image']; ?>" class="image-preview" id="image_preview">
                    <?php else: ?>
                        <img src="" class="image-preview" id="image_preview" style="display: none;">
                    <?php endif; ?>
                </div>
                <input type="file" id="image" name="image" style="display: none;" onchange="previewImage(this, 'image_preview')">
            </div>
            
            <div class="form-group">
                <label for="product_type">Product Type</label>
                <select id="product_type" name="product_type" required onchange="toggleProductFields()">
                    <option value="packaged" <?php echo ($edit_product && !$edit_product['is_open_product']) ? 'selected' : ''; ?>>Packaged</option>
                    <option value="open" <?php echo ($edit_product && $edit_product['is_open_product']) ? 'selected' : ''; ?>>Open (Weight Based)</option>
                </select>
            </div>
            
            <div class="packaged-fields" id="packaged_fields" style="<?php echo ($edit_product && $edit_product['is_open_product']) ? 'display: none;' : ''; ?>">
                <div class="form-group">
                    <label for="quantity_in_stock">Quantity in Stock (Units)</label>
                    <input type="number" step="0.001" id="quantity_in_stock" name="quantity_in_stock" value="<?php echo ($edit_product && !$edit_product['is_open_product']) ? $edit_product['quantity_in_stock'] : ''; ?>">
                </div>
            </div>
            
            <div class="open-fields" id="open_fields" style="<?php echo ($edit_product && $edit_product['is_open_product']) ? '' : 'display: none;'; ?>">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="weight_kg">Weight (Kg)</label>
                            <input type="number" step="0.001" id="weight_kg" name="weight_kg" value="<?php echo ($edit_product && $edit_product['is_open_product']) ? floor($edit_product['quantity_in_stock']) : ''; ?>">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="weight_grams">Weight (Grams)</label>
                            <input type="number" id="weight_grams" name="weight_grams" value="<?php echo ($edit_product && $edit_product['is_open_product']) ? round(($edit_product['quantity_in_stock'] - floor($edit_product['quantity_in_stock'])) * 1000) : ''; ?>">
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label for="price_per_kg">Price per Kg</label>
                    <input type="number" step="0.01" id="price_per_kg" name="price_per_kg" value="<?php echo $edit_product ? $edit_product['price_per_kg'] : ''; ?>">
                </div>
            </div>
            
            <button type="submit" class="btn"><?php echo $edit_product ? 'Update Product' : 'Add Product'; ?></button>
            
            <?php if ($edit_product): ?>
                <a href="inventory.php" class="btn btn-outline">Cancel</a>
            <?php endif; ?>
        </form>
    </div>
    
    <div class="table-section">
        <h2><i class="fas fa-list"></i> Products List</h2>
        
        <table>
            <thead>
                <tr>
                    <th>Image</th>
                    <th>Name</th>
                    <th>Category</th>
                    <th>Stock</th>
                    <th>Price</th>
                    <th>Type</th>
                    <th>Barcode</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $p): ?>
                    <tr>
                        <td>
                            <?php if ($p['image']): ?>
                                <img src="<?php echo $p['image']; ?>" width="50" height="50" style="object-fit: cover; border-radius: 5px;">
                            <?php else: ?>
                                <div style="width: 50px; height: 50px; background: #f0f0f0; display: flex; align-items: center; justify-content: center; border-radius: 5px;">
                                    <i class="fas fa-image" style="color: #ccc;"></i>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($p['product_name']); ?></td>
                        <td><?php echo htmlspecialchars($p['category']); ?></td>
                        <td>
                            <?php if ($p['is_open_product']): ?>
                                <?php echo number_format($p['quantity_in_stock'], 3); ?> Kg
                            <?php else: ?>
                                <?php echo intval($p['quantity_in_stock']); ?> Units
                            <?php endif; ?>
                        </td>
                        <td>$<?php echo number_format($p['selling_price'], 2); ?></td>
                        <td><?php echo $p['is_open_product'] ? 'Open (Weight)' : 'Packaged'; ?></td>
                      <td>
    <?php if ($p['barcode']): ?>
        <img src="uploads/barcodes/<?php echo $p['barcode']; ?>.png?t=<?php echo time(); ?>" width="120" alt="Barcode">
    <?php else: ?>
        No barcode
    <?php endif; ?>
</td>
                        <td>
                            <div class="action-buttons">
                                <a href="inventory.php?edit=<?php echo $p['product_id']; ?>" class="btn btn-sm"><i class="fas fa-edit"></i> Edit</a>
                                <a href="inventory.php?sticker=<?php echo $p['product_id']; ?>" class="btn btn-sm btn-success"><i class="fas fa-barcode"></i> Sticker</a>
                                <a href="#" onclick="confirmDelete('<?php echo $p['product_id']; ?>')" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i> Delete</a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if (isset($_GET['sticker']) && $sticker_data): ?>
<div id="stickerModal" class="modal" style="display: block;">
    <div class="modal-content">
        <span class="close" onclick="closeModal('stickerModal')">&times;</span>
        <h2><i class="fas fa-barcode"></i> Manage Sticker</h2>
        
        <?php 
        $stmt = $pdo->prepare("SELECT * FROM products WHERE product_id = :product_id");
        $stmt->execute(['product_id' => $_GET['sticker']]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        ?>
        
        <div class="sticker-preview">
            <div class="sticker sticker-2inch">
                <div class="sticker-header">
                    <h3>Badar Mart</h3>
                </div>
                <div class="sticker-body">
                    <div class="product-name"><?php echo $product['product_name']; ?></div>
                    <div class="product-price">$<?php echo number_format($product['selling_price'], 2); ?></div>
                    <?php if ($product['barcode']): 
                        $barcode_path = "uploads/barcodes/{$product['barcode']}.png";
                    ?>
                    <div class="barcode">
                        <?php if (file_exists($barcode_path)): ?>
                            <img src="<?php echo $barcode_path; ?>?t=<?php echo time(); ?>" alt="Barcode">
                        <?php else: ?>
                            <div style="color: red; font-size: 8px;">Barcode image not found</div>
                        <?php endif; ?>
                        <div class="barcode-number"><?php echo $product['barcode']; ?></div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <form method="POST" action="">
            <input type="hidden" name="product_id" value="<?php echo $_GET['sticker']; ?>">
            <input type="hidden" name="update_sticker" value="1">
            
            <div class="form-group">
                <label for="copies">Number of Copies</label>
                <input type="number" id="copies" name="copies" value="<?php echo $sticker_data['copies']; ?>" min="1" max="100" required>
            </div>
            
            <button type="submit" class="btn"><i class="fas fa-save"></i> Update Copies</button>
            <button type="button" class="btn btn-success" onclick="printSticker('<?php echo $_GET['sticker']; ?>')"><i class="fas fa-print"></i> Print Sticker</button>
        </form>
    </div>
</div>
<?php endif; ?>

<script>
function toggleProductFields() {
    const productType = document.getElementById('product_type').value;
    const packagedFields = document.getElementById('packaged_fields');
    const openFields = document.getElementById('open_fields');
    
    if (productType === 'packaged') {
        packagedFields.style.display = 'block';
        openFields.style.display = 'none';
    } else {
        packagedFields.style.display = 'none';
        openFields.style.display = 'block';
    }
}

function previewImage(input, previewId) {
    const preview = document.getElementById(previewId);
    const file = input.files[0];
    
    if (file) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.style.display = 'block';
        }
        
        reader.readAsDataURL(file);
    } else {
        preview.style.display = 'none';
    }
}

function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
    window.history.replaceState({}, document.title, window.location.pathname);
}

function confirmDelete(productId) {
    if (confirm('Are you sure you want to delete this product? This action cannot be undone.')) {
        window.location.href = 'inventory.php?delete=' + productId;
    }
}

function printSticker(productId) {
    const printWindow = window.open('sticker.php?product_id=' + productId, '_blank');
    printWindow.onload = function() {
        printWindow.print();
    };
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('stickerModal');
    if (event.target == modal) {
        closeModal('stickerModal');
    }
}
</script>

<?php include 'includes/footer.php'; ?>
