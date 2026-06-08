<?php
include('../db.php');

if (!isset($_GET['id'])) {
    die('Food ID not provided.');
}

$id = $_GET['id'];

try {
    $stmt = $conn->prepare("DELETE FROM products WHERE product_id = :id");
    $stmt->execute(['id' => $id]);

    header("Location: manager.php?deleted=1");
    exit();
} catch (PDOException $e) {
    echo "Delete failed: " . $e->getMessage();
}
