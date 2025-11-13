<?php
// includes/barcode.php
require_once __DIR__ . '/../vendor/autoload.php';
use Picqer\Barcode\BarcodeGeneratorPNG;

class BarcodeGeneratorUtil
{
    public static function generateBarcode($product_id)
    {
        // Create standardized barcode code like BR0000000012
        $barcode = 'BR' . str_pad($product_id, 10, '0', STR_PAD_LEFT);
        return $barcode;
    }

    public static function generateBarcodeImage($barcode, $product_id)
    {
        $dir = __DIR__ . '/../uploads/barcodes/';
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $file = $dir . $barcode . '.png';
        if (!file_exists($file)) {
            $generator = new BarcodeGeneratorPNG();
            $barcodeData = $generator->getBarcode($barcode, $generator::TYPE_CODE_128, 2, 60);
            file_put_contents($file, $barcodeData);
        }

        return $file;
    }

    public static function ensureBarcode($product_id)
    {
        $barcode = self::generateBarcode($product_id);
        self::generateBarcodeImage($barcode, $product_id);
        return $barcode;
    }
}
