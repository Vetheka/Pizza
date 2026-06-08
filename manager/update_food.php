<?php
include('../db.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die('Invalid request method');
}

$id = $_POST['id'] ?? null;
$product_name = $_POST['product_name'] ?? null;
$price = $_POST['price'] ?? null;
$category_id = $_POST['category_id'] ?? null;

if (!$id || !$product_name || !$price || !$category_id) {
    die('All fields are required.');
}

$stmt = $conn->prepare("UPDATE products SET product_name = :name, price = :price, category_id = :category_id WHERE product_id = :id");
$success = $stmt->execute([
    'name' => $product_name,
    'price' => $price,
    'category_id' => $category_id,
    'id' => $id
]);

if ($success) {
    header('Location: manager.php');
    exit;
} else {
    die('Update failed.');
}
