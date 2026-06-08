<?php
session_start();
include('../db.php');

// Check authorization
if ($_SESSION['role'] !== 'manager') {
    header('HTTP/1.1 403 Forbidden');
    exit();
}

header('Content-Type: application/json');

try {
    $stmt = $conn->query("
        SELECT p.product_id, p.product_name, p.price, p.category_id, c.category_name, 
               SUM(oi.quantity) AS total_sold
        FROM order_items1 oi
        JOIN products p ON oi.product_id = p.product_id
        LEFT JOIN categories c ON p.category_id = c.category_id
        GROUP BY oi.product_id
        ORDER BY total_sold DESC
    ");
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (PDOException $e) {
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(['error' => $e->getMessage()]);
}