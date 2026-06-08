<?php
session_start();
include('../db.php');

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['table_number'])) {
    $table_number = $_POST['table_number'];
    $user_id = 1; // Change this as needed
    $status = 'Pending';
    $order_date = date('Y-m-d H:i:s');
    $total = 0;

    // Calculate original total
    foreach ($_SESSION['cart'] as $item) {
        $total += $item['price'] * $item['quantity'];
    }

    // Get discount from session
    $discount = isset($_SESSION['discount']) ? $_SESSION['discount'] : 0;

    // Calculate final total after discount
    $final_total = $total - $discount;

    // Insert order
    $stmt = $conn->prepare("INSERT INTO orders1 (user_id, total, discount, order_date, status, table_number) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$user_id, $final_total, $discount, $order_date, $status, $table_number]);
    $order_id = $conn->lastInsertId();

    // Insert order items
    $item_stmt = $conn->prepare("INSERT INTO order_items1 (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");

    foreach ($_SESSION['cart'] as $item) {
        $item_stmt->execute([
            $order_id,
            $item['id'],       // Use 'id' here for product_id
            $item['quantity'], // Quantity key is 'quantity'
            $item['price']     // Price key is 'price'
        ]);
    }

    // Clear cart and discount
    unset($_SESSION['cart']);
    unset($_SESSION['discount']);

    header("Location: orders.php");
    exit;
}
?>
