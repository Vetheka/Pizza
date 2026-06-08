<?php
include('../db.php');

$id = $_POST['id'] ?? null;
$name = $_POST['name'];
$email = $_POST['email'];
$phone = $_POST['phone'];
$address = $_POST['address'];
$salary = $_POST['salary'];
$role = $_POST['role'];

if ($id) {
    // ✅ Update existing staff and role
    $stmt = $conn->prepare("UPDATE staff SET name = ?, email = ?, phone = ?, address = ?, salary = ? WHERE user_id = ?");
    $stmt->execute([$name, $email, $phone, $address, $salary, $id]);

    $stmt = $conn->prepare("UPDATE users SET role = ? WHERE id = ?");
    $stmt->execute([$role, $id]);
} else {
    // ✅ First insert into `users`
    $stmt = $conn->prepare("INSERT INTO users (role) VALUES (?)");
    $stmt->execute([$role]);
    $newId = $conn->lastInsertId();

    // ✅ Then insert into `staff`, using the generated user ID
    $stmt = $conn->prepare("INSERT INTO staff (user_id, name, email, phone, address, salary) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$newId, $name, $email, $phone, $address, $salary]);
}

header("Location: staff_management.php");
exit();
