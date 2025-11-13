<?php
require_once 'includes/config.php';
require_once 'includes/barcode.php';

if (!isset($_GET['product_id'])) {
    die("Product ID not provided.");
}

$product_id = intval($_GET['product_id']);
$stmt = $pdo->prepare("SELECT * FROM products WHERE product_id = ?");
$stmt->execute([$product_id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    die("Invalid Product ID.");
}

// ✅ Ensure barcode exists
$barcode = BarcodeGeneratorUtil::ensureBarcode($product_id);
$product['barcode'] = $barcode;
$barcodePath = "uploads/barcodes/{$barcode}.png";
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Product Sticker</title>
<style>
body {
    font-family: Arial, sans-serif;
    text-align: center;
}
.sticker {
    border: 1px solid #000;
    width: 250px;
    margin: 20px auto;
    padding: 10px;
}
.product-name {
    font-weight: bold;
    font-size: 14px;
    margin-bottom: 8px;
}
.barcode img {
    width: 200px;
    height: auto;
}
.barcode-text {
    font-size: 12px;
    margin-top: 5px;
}
@media print {
    body { margin: 0; }
    .sticker { page-break-inside: avoid; }
    button { display: none; }
}
</style>
</head>
<body>
<div class="sticker">
    <div class="product-name"><?= htmlspecialchars($product['product_name']) ?></div>
    <div class="barcode">
        <?php if (file_exists($barcodePath)): ?>
            <img src="<?= $barcodePath ?>" alt="Barcode">
        <?php else: ?>
            <div style="color:red;">Barcode image not found</div>
        <?php endif; ?>
    </div>
    <div class="barcode-text"><?= htmlspecialchars($product['barcode']) ?></div>
</div>
<button onclick="window.print()">Print Sticker</button>
</body>
</html>
