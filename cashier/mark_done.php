<?php
include('../db.php');

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $stmt = $conn->prepare("UPDATE orders1 SET status = 'Done' WHERE order_id = ?");
    $stmt->execute([$id]);
}

header("Location: orders.php");

