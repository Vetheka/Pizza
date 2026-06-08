<?php
// Start the session
session_start();

// DB connection
$conn = new mysqli("localhost", "root", "", "pizza");

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get inputs
$role = $_POST['role'];
$password = $_POST['password'];

// Sanitize input
$role = $conn->real_escape_string($role);

// Fetch user from DB
$sql = "SELECT * FROM users WHERE role = '$role' LIMIT 1";
$result = $conn->query($sql);

if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();

    // Compare SHA2-hashed password
    if (hash('sha256', $password) === $user['password']) {
        $_SESSION['role'] = $user['role'];

        // Redirect by role
        if ($role === 'cashier') {
            header("Location: cashier/index.php");
        } elseif ($role === 'manager') {
            header("Location: manager/manager.php");
        } elseif ($role === 'owner') {
            header("Location: owner_dashboard.php");
        }
        exit();
    } else {
        header("Location: login.php?error=Incorrect password.");
        exit();
    }
} else {
    header("Location: login.php?error=User not found.");
    exit();
}

$conn->close();
?>
