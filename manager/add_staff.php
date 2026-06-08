<?php
// Connect to the database
include('../db.php');

// Fetch all users who are not 'owner' (to assign staff info)
$stmt = $conn->query("SELECT id, role FROM users WHERE role != 'owner'");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Staff Info</title>
</head>
<body>
    <h2>Add Staff Information</h2>
    <form method="post" action="save_staff.php">
        <label>User:</label>
        <select name="user_id" required>
            <?php foreach ($users as $user): ?>
                <option value="<?= $user['id'] ?>">ID <?= $user['id'] ?> - <?= ucfirst($user['role']) ?></option>
            <?php endforeach; ?>
        </select><br><br>

        <label>Full Name:</label>
        <input type="text" name="full_name" required><br><br>

        <label>Phone:</label>
        <input type="text" name="phone" required><br><br>

        <label>Address:</label>
        <textarea name="address" required></textarea><br><br>

        <label>Position:</label>
        <input type="text" name="position" required><br><br>

        <button type="submit">Save Staff Info</button>
    </form>
</body>
</html>
