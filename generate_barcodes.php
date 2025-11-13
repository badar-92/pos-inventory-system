<?php
include 'includes/config.php';
include 'includes/auth.php';
include 'includes/barcode.php';

checkAuthentication();

echo "<h2>Generating Barcodes for All Products</h2>";

// Generate missing barcodes for all products
$generated = BarcodeGenerator::generateAllBarcodes();

echo "<h3 style='color: green;'>Successfully generated $generated barcodes!</h3>";
echo "<p><a href='inventory.php'>Go to Inventory</a></p>";
echo "<p><a href='sticker.php?product_id=1'>Test Sticker for Product 1</a></p>";

// List all products with their barcodes
$sql = "SELECT product_id, product_name, barcode FROM products";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h3>Product Barcodes:</h3>";
echo "<table border='1' cellpadding='8'>";
echo "<tr><th>ID</th><th>Product Name</th><th>Barcode</th><th>Image</th></tr>";
foreach ($products as $product) {
    $barcode_path = 'uploads/barcodes/' . $product['barcode'] . '.png';
    $image_exists = file_exists($barcode_path) ? "✅ Exists" : "❌ Missing";
    echo "<tr>";
    echo "<td>{$product['product_id']}</td>";
    echo "<td>{$product['product_name']}</td>";
    echo "<td>{$product['barcode']}</td>";
    echo "<td>$image_exists</td>";
    echo "</tr>";
}
echo "</table>";
<?php
require_once 'includes/config.php';
require_once 'includes/barcode.php';

$count = BarcodeGeneratorUtil::generateAllBarcodes();
echo "✅ {$count} barcodes generated successfully.";
?>

?>