<?php
session_start();
include('../db.php');

if ($_SESSION['role'] !== 'manager') {
    header("Location: ../login.php");
    exit();
}

$id = $_POST['id'] ?? '';

if ($id) {
    $conn->beginTransaction();

    // Delete staff info linked to user id first
    $stmtStaff = $conn->prepare("DELETE FROM staff WHERE user_id = :id");
    $stmtStaff->execute([':id' => $id]);

    // Then delete user account
    $stmtUser = $conn->prepare("DELETE FROM users WHERE id = :id");
    $stmtUser->execute([':id' => $id]);

    $conn->commit();
}

header('Location: staff_management.php');
exit();
