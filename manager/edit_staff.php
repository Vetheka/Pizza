<?php
include '../db_conn.php';

$id = $_GET['id']; // This is users.id, which matches staff.user_id

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    $salary = $_POST['salary'];
    $role = $_POST['role'];

    // Update staff table
    $stmt = $conn->prepare("UPDATE staff SET name = ?, email = ?, phone = ?, address = ?, salary = ? WHERE user_id = ?");
    $stmt->execute([$name, $email, $phone, $address, $salary, $id]);

    // Update users table
    $stmt = $conn->prepare("UPDATE users SET role = ? WHERE id = ?");
    $stmt->execute([$role, $id]);

    header("Location: manage_staff.php");
    exit();
}

// Fetch staff and user info
$stmt = $conn->prepare("SELECT s.*, u.role FROM staff s JOIN users u ON s.user_id = u.id WHERE s.user_id = ?");
$stmt->execute([$id]);
$staff = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$staff) {
    echo "Staff not found.";
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Staff</title>
</head>
<body>
    <h2>Edit Staff</h2>
    <form method="POST">
        <label>Name:</label><br>
        <input type="text" name="name" value="<?= htmlspecialchars($staff['name']) ?>" required><br><br>

        <label>Email:</label><br>
        <input type="email" name="email" value="<?= htmlspecialchars($staff['email']) ?>" required><br><br>

        <label>Phone:</label><br>
        <input type="text" name="phone" value="<?= htmlspecialchars($staff['phone']) ?>" required><br><br>

        <label>Address:</label><br>
        <input type="text" name="address" value="<?= htmlspecialchars($staff['address']) ?>" required><br><br>

        <label>Salary:</label><br>
        <input type="number" step="0.01" name="salary" value="<?= htmlspecialchars($staff['salary']) ?>" required><br><br>

        <label>Role:</label><br>
        <select name="role" required>
            <option value="cashier" <?= $staff['role'] == 'cashier' ? 'selected' : '' ?>>Cashier</option>
            <option value="stock" <?= $staff['role'] == 'stock' ? 'selected' : '' ?>>Stock</option>
            <option value="manager" <?= $staff['role'] == 'manager' ? 'selected' : '' ?>>Manager</option>
        </select><br><br>

        <button type="submit">Update Staff</button>
    </form>
</body>
</html>
