<?php
// Add to includes/sales.php - Update the processSale function

// Function to process a sale - UPDATED VERSION
function processSale($customer_name, $payment_method, $cart_items, $cashier_id, $discount_type = 'none', $discount_value = 0, $cash_given = 0, $tax_rate = 10) {
    global $pdo;
    
    try {
        $pdo->beginTransaction();
        
        // Generate transaction ID
        $transaction_id = "TXN" . time();
        
        // Calculate totals
        $subtotal = 0;
        foreach ($cart_items as $item) {
            // For open products, use the price directly (already calculated)
            // For regular products, use price * quantity
            if (isset($item['is_open_product']) && $item['is_open_product']) {
                $subtotal += $item['price'];
            } else {
                $subtotal += $item['price'] * $item['quantity'];
            }
        }
        
        // Apply discount
        $discount_amount = 0;
        if ($discount_type === 'amount') {
            $discount_amount = min($discount_value, $subtotal);
        } else if ($discount_type === 'percentage') {
            $discount_amount = $subtotal * ($discount_value / 100);
        }
        
        $taxable_amount = $subtotal - $discount_amount;
        $tax_amount = $taxable_amount * ($tax_rate / 100);
        $total_amount = $taxable_amount + $tax_amount;
        
        // Calculate change if payment is cash
        $change_amount = 0;
        if ($payment_method === 'Cash') {
            $change_amount = $cash_given - $total_amount;
            if ($change_amount < 0) {
                return [
                    'success' => false,
                    'message' => 'Cash given is less than total amount'
                ];
            }
        }
        
        // Insert transaction
        $sql = "INSERT INTO transactions (transaction_id, transaction_date, cashier_id, customer_name,
                payment_method, subtotal, tax_amount, discount_amount, total_amount, cash_given, change_amount, tax_rate)
                VALUES (:transaction_id, NOW(), :cashier_id, :customer_name, :payment_method,
                :subtotal, :tax_amount, :discount_amount, :total_amount, :cash_given, :change_amount, :tax_rate)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':transaction_id' => $transaction_id,
            ':cashier_id' => $cashier_id,
            ':customer_name' => $customer_name,
            ':payment_method' => $payment_method,
            ':subtotal' => $subtotal,
            ':tax_amount' => $tax_amount,
            ':discount_amount' => $discount_amount,
            ':total_amount' => $total_amount,
            ':cash_given' => $cash_given,
            ':change_amount' => $change_amount,
            ':tax_rate' => $tax_rate
        ]);
        
        // Insert transaction items and update inventory
        foreach ($cart_items as $item) {
            // Insert transaction item
            $sql = "INSERT INTO transaction_items (transaction_id, product_id, quantity, price, discount, weight_kg)
                    VALUES (:transaction_id, :product_id, :quantity, :price, 0, :weight_kg)";
            $stmt = $pdo->prepare($sql);
            
            // For open products, quantity is always 1, weight is the actual weight
            // For regular products, quantity is the number of items, weight is 0
            $quantity = (isset($item['is_open_product']) && $item['is_open_product']) ? 1 : $item['quantity'];
            $weight = isset($item['weight']) ? $item['weight'] : 0;
            
            $stmt->execute([
                ':transaction_id' => $transaction_id,
                ':product_id' => $item['id'],
                ':quantity' => $quantity,
                ':price' => $item['price'],
                ':weight_kg' => $weight
            ]);
            
            // Update product stock
            if (isset($item['is_open_product']) && $item['is_open_product']) {
                // For open products, subtract weight
                $sql = "UPDATE products SET quantity_in_stock = quantity_in_stock - :weight
                        WHERE product_id = :product_id AND quantity_in_stock >= :weight";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':weight' => $item['weight'],
                    ':product_id' => $item['id']
                ]);
                
                if ($stmt->rowCount() == 0) {
                    throw new Exception("Not enough stock available for product: " . $item['name']);
                }
            } else {
                // For regular products, subtract quantity
                $sql = "UPDATE products SET quantity_in_stock = quantity_in_stock - :quantity
                        WHERE product_id = :product_id";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':quantity' => $item['quantity'],
                    ':product_id' => $item['id']
                ]);
            }
        }
        
        $pdo->commit();
        
        return [
            'success' => true,
            'transaction_id' => $transaction_id
        ];
    } catch (Exception $e) {
        $pdo->rollBack();
        return [
            'success' => false,
            'message' => 'Error processing sale: ' . $e->getMessage()
        ];
    }
}

// Function to get products
function getProducts() {
    global $pdo;
    
    $sql = "SELECT * FROM products ORDER BY product_name";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Function to search products by barcode
function searchProductsByBarcode($barcode) {
    global $pdo;
    
    $sql = "SELECT * FROM products WHERE barcode = :barcode";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':barcode', $barcode, PDO::PARAM_STR);
    $stmt->execute();
    
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Function to search products by name or ID
function searchProducts($search_term) {
    global $pdo;
    
    $sql = "SELECT * FROM products WHERE product_name LIKE :search OR product_id LIKE :search OR barcode LIKE :search ORDER BY product_name";
    $stmt = $pdo->prepare($sql);
    $search_param = '%' . $search_term . '%';
    $stmt->bindParam(':search', $search_param, PDO::PARAM_STR);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>