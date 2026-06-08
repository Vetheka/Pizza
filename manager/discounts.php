<?php
$host = "localhost";
$user = "root";
$pass = "";
$db = "your_database_name"; // Replace with your DB name

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

function getDiscount($type, $conn) {
    $stmt = $conn->prepare("SELECT percent FROM discounts WHERE type = ?");
    $stmt->bind_param("s", $type);
    $stmt->execute();
    $stmt->bind_result($percent);
    if ($stmt->fetch()) {
        $stmt->close();
        return $percent;
    }
    $stmt->close();
    return null;
}

function saveDiscount($type, $percent, $conn) {
    $stmt = $conn->prepare("INSERT INTO discounts (type, percent) VALUES (?, ?)");
    $stmt->bind_param("si", $type, $percent);
    return $stmt->execute();
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $type = $_POST['type'];
    $percent = intval($_POST['percent']);
    if (!getDiscount($type, $conn)) {
        if (saveDiscount($type, $percent, $conn)) {
            echo "<script>alert('Discount applied successfully!'); window.location.href='discounts.php';</script>";
            exit;
        } else {
            echo "<script>alert('Failed to apply discount.');</script>";
        }
    }
}

$topSellingDiscount = getDiscount("top_selling", $conn);
$menuItemDiscount = getDiscount("menu_item", $conn);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Discount Management</title>
    <style>
        body {
            font-family: Arial;
            background: #f7f7f7;
            padding: 40px;
        }
        h2 {
            color: green;
        }
        .box {
            background: #fff;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 0 12px #ccc;
            margin-bottom: 20px;
            max-width: 500px;
        }
        input[type=number], button {
            padding: 10px;
            margin-top: 10px;
            font-size: 16px;
            width: 100%;
            box-sizing: border-box;
        }
        button:disabled {
            background-color: #ccc;
        }
    </style>
</head>
<body>

    <h2>Top Selling Discount</h2>
    <div class="box">
        <?php if ($topSellingDiscount): ?>
            <p><strong>Discount Applied:</strong> <?= $topSellingDiscount ?>%</p>
            <button disabled>Discount Already Set</button>
        <?php else: ?>
            <form method="post">
                <input type="hidden" name="type" value="top_selling">
                <label>Enter Discount Percentage:</label>
                <input type="number" name="percent" min="1" max="100" required>
                <button type="submit">Apply Top Selling Discount</button>
            </form>
        <?php endif; ?>
    </div>

    <h2>Menu Items Discount</h2>
    <div class="box">
        <?php if ($menuItemDiscount): ?>
            <p><strong>Discount Applied:</strong> <?= $menuItemDiscount ?>%</p>
            <button disabled>Discount Already Set</button>
        <?php else: ?>
            <form method="post">
                <input type="hidden" name="type" value="menu_item">
                <label>Enter Discount Percentage:</label>
                <input type="number" name="percent" min="1" max="100" required>
                <button type="submit">Apply Menu Discount</button>
            </form>
        <?php endif; ?>
    </div>

</body>
</html>
