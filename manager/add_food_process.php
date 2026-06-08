<?php
include '../db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_name = $_POST['product_name'];
    $price = $_POST['price'];
    $discount = min($_POST['discount'] ?? 0, 20);
    $category_id = $_POST['category_id'];

    $stmt = $conn->prepare("INSERT INTO products (product_name, price, discount, category_id) VALUES (?, ?, ?, ?)");
    $stmt->execute([$product_name, $price, $discount, $category_id]);

    header("Location: manager.php");
    exit();
}
?>
