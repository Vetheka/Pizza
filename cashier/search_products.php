<?php
session_start();
include('../db.php');

if (!isset($_GET['term'])) {
    header('HTTP/1.1 400 Bad Request');
    exit();
}

$searchTerm = '%' . strtolower($_GET['term']) . '%';

try {
    $stmt = $conn->prepare("SELECT * FROM products WHERE LOWER(product_name) LIKE ?");
    $stmt->execute([$searchTerm]);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Apply discounts (same logic as your main page)
    foreach ($products as &$product) {
        $price = (float)$product['price'];
        $category = strtolower($product['category']);
        $name = strtolower($product['product_name']);
        $isTopSelling = (bool)$product['is_top_selling'];
        $excluded = ($category === 'drink' || strpos($name, 'salad') !== false);

        // Apply your discount logic here (same as main page)
        // ...

        $product['final_price'] = round($price * (1 - $discountPercent / 100), 2);
        $product['has_discount'] = $discountPercent > 0;
        $product['discount_percent'] = $discountPercent;
    }
    unset($product);
    
    header('Content-Type: application/json');
    echo json_encode($products);
} catch (Exception $e) {
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(['error' => $e->getMessage()]);
}