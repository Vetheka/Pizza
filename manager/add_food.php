<?php
include '../db.php';

// Fetch categories for the dropdown
$catStmt = $conn->query("SELECT * FROM categories ORDER BY category_name");
$categories = $catStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Add New Food</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f0fff0;
            padding: 30px;
        }
        .container {
            background: white;
            border-radius: 10px;
            max-width: 500px;
            margin: auto;
            padding: 30px;
            box-shadow: 0px 0px 10px rgba(0, 128, 0, 0.2);
        }
        h2 {
            color: #28a745;
            margin-bottom: 20px;
            text-align: center;
        }
        input, select, button {
            width: 100%;
            padding: 10px;
            margin-top: 15px;
            border-radius: 5px;
            border: 1px solid #ccc;
        }
        button {
            background: #28a745;
            color: white;
            border: none;
        }
        button:hover {
            background: #218838;
        }
        a {
            display: block;
            text-align: center;
            margin-top: 15px;
            color: #28a745;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Add New Food</h2>
        <form method="post" action="add_food_process.php">
            <label>Product Name:</label>
            <input type="text" name="product_name" required>

            <label>Price:</label>
            <input type="number" name="price" step="0.01" required>

            <label>Category:</label>
            <select name="category_id" required>
                <option value="">-- Select Category --</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat['category_id'] ?>"><?= htmlspecialchars($cat['category_name']) ?></option>
                <?php endforeach; ?>
            </select>

            <button type="submit">Add Food</button>
        </form>
        <a href="manager.php">← Back to Dashboard</a>
    </div>
</body>
</html>
