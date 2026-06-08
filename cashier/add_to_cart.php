<?php
session_start();
require_once('../db.php');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
}

if (empty($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
    http_response_code(403);
    echo json_encode(['error' => 'Invalid CSRF Token']);
    exit;
}

if (!isset($_POST['product_id'], $_POST['qty'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing product ID or quantity']);
    exit;
}

$product_id = filter_var($_POST['product_id'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$quantity = filter_var($_POST['qty'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 100]]);

if ($product_id === false || $quantity === false) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid product ID or quantity']);
    exit;
}

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

try {
    $stmt = $conn->prepare("SELECT product_id, product_name, price FROM products WHERE product_id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) throw new Exception('Product not found');

    $discount_percent = 0;

    $promoStmt = $conn->prepare("SELECT discount_percent FROM promotions WHERE promo_type = 'menu' AND is_active = 1 LIMIT 1");
    $promoStmt->execute();
    $promo = $promoStmt->fetch(PDO::FETCH_ASSOC);

    if ($promo) {
        $discount_percent = (float)$promo['discount_percent'];
    }

    $original_price = (float)$product['price'];
    $final_price = round($original_price * (1 - $discount_percent / 100), 2);
    $safe_name = htmlspecialchars($product['product_name'], ENT_QUOTES, 'UTF-8');

    if (isset($_SESSION['cart'][$product_id])) {
        $_SESSION['cart'][$product_id]['quantity'] += $quantity;
    } else {
        $_SESSION['cart'][$product_id] = [
            'id' => $product_id,
            'name' => $safe_name,
            'price' => $final_price,
            'original_price' => $original_price,
            'quantity' => $quantity,
            'discount_percent' => $discount_percent
        ];
    }

    $cart_count = array_sum(array_column($_SESSION['cart'], 'quantity'));

    echo json_encode([
        'success' => true,
        'cart_count' => $cart_count,
        'item' => [
            'id' => $product_id,
            'name' => $safe_name,
            'price' => $final_price,
            'original_price' => $original_price,
            'quantity' => $_SESSION['cart'][$product_id]['quantity'],
            'discount_percent' => $discount_percent
        ]
    ]);
    exit;

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
    exit;
}
