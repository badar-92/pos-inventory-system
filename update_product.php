<?php
include 'includes/config.php';
include 'includes/auth.php';

// Check if user is logged in and is admin
checkAuthentication();
if ($_SESSION['role'] != 'admin') {
    header("Location: dashboard.php");
    exit;
}

// Update product
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_product'])) {
    $product_id = trim($_POST['product_id']);
    $product_name = trim($_POST['product_name']);
    $category = trim($_POST['category']);
    $subcategory = trim($_POST['subcategory']);
    $description = trim($_POST['description']);
    $purchase_price = trim($_POST['purchase_price']);
    $selling_price = trim($_POST['selling_price']);
    $quantity_in_stock = trim($_POST['quantity_in_stock']);
    $reorder_point = trim($_POST['reorder_point']);
    $supplier_id = trim($_POST['supplier_id']);
    $location = trim($_POST['location']);
    $barcode = trim($_POST['barcode']);
    
    try {
        // Update product
        $sql = "UPDATE products SET 
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
                barcode = :barcode, 
                last_updated = NOW() 
                WHERE product_id = :product_id";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':product_id', $product_id, PDO::PARAM_STR);
        $stmt->bindParam(':product_name', $product_name, PDO::PARAM_STR);
        $stmt->bindParam(':category', $category, PDO::PARAM_STR);
        $stmt->bindParam(':subcategory', $subcategory, PDO::PARAM_STR);
        $stmt->bindParam(':description', $description, PDO::PARAM_STR);
        $stmt->bindParam(':purchase_price', $purchase_price);
        $stmt->bindParam(':selling_price', $selling_price);
        $stmt->bindParam(':quantity_in_stock', $quantity_in_stock, PDO::PARAM_INT);
        $stmt->bindParam(':reorder_point', $reorder_point, PDO::PARAM_INT);
        $stmt->bindParam(':supplier_id', $supplier_id, PDO::PARAM_STR);
        $stmt->bindParam(':location', $location, PDO::PARAM_STR);
        $stmt->bindParam(':barcode', $barcode, PDO::PARAM_STR);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Product updated successfully!";
        } else {
            $_SESSION['error'] = "Error updating product!";
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error: " . $e->getMessage();
    }
    
    header("Location: inventory.php");
    exit;
} else {
    header("Location: inventory.php");
    exit;
}
?>