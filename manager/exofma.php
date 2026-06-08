 <?php
session_start();
include('../db.php');

// Initialize session variables if not set
if (!isset($_SESSION['promo_active_top'])) {
    $_SESSION['promo_active_top'] = false;
}
if (!isset($_SESSION['promo_active_menu'])) {
    $_SESSION['promo_active_menu'] = false;
}
if (!isset($_SESSION['discount_percent_top'])) {
    $_SESSION['discount_percent_top'] = 10; // Default 10%
}
if (!isset($_SESSION['discount_percent_menu'])) {
    $_SESSION['discount_percent_menu'] = 10; // Default 10%
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['toggle_top'])) {
        $_SESSION['promo_active_top'] = !$_SESSION['promo_active_top'];
    }
    if (isset($_POST['toggle_menu'])) {
        $_SESSION['promo_active_menu'] = !$_SESSION['promo_active_menu'];
    }
    if (isset($_POST['discount_percent_top'])) {
        $dp_top = (float)$_POST['discount_percent_top'];
        $_SESSION['discount_percent_top'] = max(0, min($dp_top, 100));
    }
    if (isset($_POST['discount_percent_menu'])) {
        $dp_menu = (float)$_POST['discount_percent_menu'];
        $_SESSION['discount_percent_menu'] = max(0, min($dp_menu, 100));
    }
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

$promoActiveTop = $_SESSION['promo_active_top'];
$promoActiveMenu = $_SESSION['promo_active_menu'];
$discountPercentTop = $_SESSION['discount_percent_top'];
$discountPercentMenu = $_SESSION['discount_percent_menu'];
$promoActive = $_SESSION['promo_active'] ?? true;

$userRole = $_SESSION['role'] ?? null;
$userId = $_SESSION['user_id'] ?? null;

if ($userRole === 'manager') {
    $stmt = $conn->prepare("SELECT * FROM orders1");
    $stmt->execute();
} elseif ($userRole === 'cashier') {
    $stmt = $conn->prepare("SELECT * FROM orders1 WHERE user_id = :user_id");
    $stmt->execute(['user_id' => $userId]);
} else {
    header("Location: login.php");
    exit();
}

$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

try {
    $today = date('Y-m-d');

    // Today's sales
    $salesTodayStmt = $conn->prepare("SELECT SUM(total) AS total FROM orders1 WHERE DATE(order_date) = :today");
    $salesTodayStmt->execute(['today' => $today]);
    $todayTotal = $salesTodayStmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

    // Total sales
    $salesTotalStmt = $conn->query("SELECT SUM(total) AS total FROM orders1");
    $total = $salesTotalStmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;


    // Top-selling items - fixed 10% discount when promo active
    $topItemsStmt = $conn->query("
        SELECT p.product_id, p.product_name, p.price, SUM(oi.quantity) AS total_sold
        FROM order_items1 oi
        JOIN products p ON oi.product_id = p.product_id
        GROUP BY oi.product_id
        ORDER BY total_sold DESC
        LIMIT 5
    ");
    $topItems = $topItemsStmt->fetchAll(PDO::FETCH_ASSOC);

    // Staff list (joining staff for name)
    $staffStmt = $conn->query("
        SELECT u.id, s.name, u.role 
        FROM users u 
        JOIN staff s ON u.id = s.user_id 
        WHERE u.role != 'owner'
    ");

    // Menu with discount logic:
    // - Drinks and Salad: 0% discount always
    // - Others: max 20% discount allowed, discount value stored in DB
    // We'll fetch the discount column, but enforce these rules on the fly.
    $menuStmt = $conn->query("SELECT * FROM products");

    // Sales last 7 days
    $salesDates = [];
    $salesAmounts = [];
    for ($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $salesDates[] = $date;

        $stmtDay = $conn->prepare("SELECT SUM(total) AS total FROM orders1 WHERE DATE(order_date) = :date");
        $stmtDay->execute(['date' => $date]);
        $totalDay = $stmtDay->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
        $salesAmounts[] = (float)$totalDay;
    }

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

/// Helper function to calculate discounted price
function calculateDiscountedPrice($price, $discountPercent) {
    return $price * (1 - $discountPercent / 100);
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Manager Dashboard</title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background: #f6fff7;
            margin: 0;
            padding: 0;
        }
        .header {
            background-color: #218838;
            color: white;
            padding: 20px;
            text-align: center;
            position: relative;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .logout-btn {
            position: absolute;
            right: 20px;
            top: 20px;
            background: #c82333;
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            text-decoration: none;
            font-weight: bold;
        }
        .logout-btn:hover {
            background: #a71d2a;
        }
        .promo-toggle-btn {
            position: absolute;
            left: 20px;
            top: 20px;
            background: #ffc107;
            color: #212529;
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            font-weight: bold;
            cursor: pointer;
            text-decoration: none;
        }
        .promo-toggle-btn:hover {
            background: #e0a800;
        }
        .container {
            padding: 30px;
            max-width: 1200px;
            margin: auto;
        }
        h2 {
            color: #218838;
            margin-bottom: 15px;
        }
        .card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 3px 8px rgba(0,0,0,0.05);
            padding: 25px;
            margin-bottom: 30px;
        }
        .summary p {
            font-size: 18px;
            margin: 10px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        th, td {
            padding: 12px 14px;
            border-bottom: 1px solid #e0e0e0;
        }
        th {
            background: #eaffea;
            color: #2e7d32;
            text-align: left;
        }
        td {
            background: #ffffff;
        }
        form input, form button {
            padding: 10px 12px;
            margin-right: 10px;
            margin-bottom: 10px;
            border: 1px solid #ccc;
            border-radius: 6px;
        }
        form button {
            background-color: #28a745;
            color: white;
            border: none;
            font-weight: bold;
            cursor: pointer;
        }
        form button:hover {
            background-color: #218838;
        }
        a {
            color: #28a745;
            text-decoration: none;
        }
        a:hover {
            text-decoration: underline;
        }
        .actions a {
            margin-right: 8px;
        }
        /* Discount style */
        .discount-label {
            background-color: #ffcc00;
            color: #333;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 0.9em;
            font-weight: bold;
        }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="header">
        <h1>Manager Dashboard</h1>
        <a href="../logout.php" class="logout-btn">Logout</a>
        <a href="?toggle_promo=1" class="promo-toggle-btn">
            <?= $promoActive ? "Disable Promotion" : "Enable Promotion" ?>
        </a>
    </div>

    <div class="container">
        <div class="card summary">
            <h2>Sales Summary</h2>
            <p><strong>Today's Sales:</strong> $<?= number_format($todayTotal, 2) ?></p>
            <p><strong>Total Sales:</strong> $<?= number_format($total, 2) ?></p>
        </div>

        <div class="card">
            <h2>Sales in Last 7 Days</h2>
            <canvas id="salesChart" width="400" height="150"></canvas>
        </div>
<div class="card">
            <h2>Top-Selling Items</h2>
            <form method="post">
                <button type="submit" name="toggle_top">
                    <?= $promoActiveTop ? "Disable Top Items Discount" : "Enable Top Items Discount" ?>
                </button>
                <label>Discount Percent:
                    <input type="number" name="discount_percent_top" value="<?= $discountPercentTop ?>" min="0" max="100">
                </label>
                <button type="submit">Apply</button>
            </form>
            <table>
                <thead>
                    <tr>
                        <th>Item Name</th>
                        <th>Quantity Sold</th>
                        <th>Original Price</th>
                        <th>Price After Discount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($topItems as $item): 
                        $price = (float)$item['price'];
                        $discountedPrice = $promoActiveTop ? calculateDiscountedPrice($price, $discountPercentTop) : $price;
                    ?>
                        <tr>
                            <td><?= htmlspecialchars($item['product_name']) ?></td>
                            <td><?= (int)$item['total_sold'] ?></td>
                            <td>$<?= number_format($price, 2) ?></td>
                            <td>
                                <?php if ($promoActiveTop): ?>
                                    <span class="discount-label">$<?= number_format($discountedPrice, 2) ?></span>
                                <?php else: ?>
                                    $<?= number_format($discountedPrice, 2) ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="card">
            <h2>Staff Accounts </h2>
            <a href="staff_management.php" class="btn">Manage Staff</a>
            <table>
                <thead>
                    <tr>
                        <th>Staff ID</th>
                        <th>Name</th>
                        <th>Role</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $staffStmt = $conn->query("
                        SELECT u.id, s.name, u.role 
                        FROM users u 
                        JOIN staff s ON u.id = s.user_id 
                        WHERE u.role != 'owner'
                        ORDER BY 
                            CASE u.role 
                                WHEN 'manager' THEN 1 
                                WHEN 'cashier' THEN 2 
                                ELSE 3 
                            END
                        LIMIT 5
                    ");
                    while ($staff = $staffStmt->fetch(PDO::FETCH_ASSOC)):
                        $role = $staff['role'];
                        $color = $role === 'manager' ? 'blue' : ($role === 'cashier' ? 'green' : 'gray');
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($staff['id']) ?></td>
                        <td><?= htmlspecialchars($staff['name']) ?></td>
                        <td style="color: <?= $color ?>; font-weight: bold;"><?= ucfirst($role) ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <div class="card">
            <h2>Menu Items</h2>
            <form method="post">
                <button type="submit" name="toggle_menu">
                    <?= $promoActiveMenu ? "Disable Menu Items Discount" : "Enable Menu Items Discount" ?>
                </button>
                <label>Discount Percent:
                    <input type="number" name="discount_percent_menu" value="<?= $discountPercentMenu ?>" min="0" max="100">
                </label>
                <button type="submit">Apply</button>
            </form>
            <table>
                <thead>
                    <tr>
                        <th>Product Name</th>
                        <th>Category</th>
                        <th>Original Price</th>
                        <th>Price After Discount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($menuItems as $product): 
                        $category = strtolower($product['category_name'] ?? '');
                        $price = (float)$product['price'];
                        if (!$promoActiveMenu || $category === 'drink' || $category === 'salad') {
                            $discountedPrice = $price;
                        } else {
                            $discountedPrice = calculateDiscountedPrice($price, $discountPercentMenu);
                        }
                    ?>
                        <tr>
                            <td><?= htmlspecialchars($product['product_name']) ?></td>
                            <td><?= ucfirst($category) ?></td>
                            <td>$<?= number_format($price, 2) ?></td>
                            <td>
                                <?php if ($discountedPrice < $price): ?>
                                    <span class="discount-label">$<?= number_format($discountedPrice, 2) ?></span>
                                <?php else: ?>
                                    $<?= number_format($discountedPrice, 2) ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        const ctxSales = document.getElementById('salesChart').getContext('2d');
        const salesChart = new Chart(ctxSales, {
            type: 'line',
            data: {
                labels: <?= json_encode($salesDates) ?>,
                datasets: [{
                    label: 'Sales ($)',
                    data: <?= json_encode($salesAmounts) ?>,
                    borderColor: '#28a745',
                    backgroundColor: 'rgba(40, 167, 69, 0.2)',
                    fill: true,
                    tension: 0.3
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    </script>
</body>
</html>
