<?php
session_start();
include('../db.php');

// Check user role
$userRole = $_SESSION['role'] ?? null;
if ($userRole !== 'manager') {
    header("Location: login.php");
    exit();
}

// Stock Management Functions
function getInventoryItems($conn) {
    $query = $conn->query("SELECT * FROM inventory ORDER BY item_name");
    return $query->fetchAll(PDO::FETCH_ASSOC);
}

function getLowStockItems($conn) {
    $query = $conn->query("
        SELECT item_name, quantity, unit, low_stock_threshold 
        FROM inventory 
        WHERE quantity <= low_stock_threshold
    ");
    return $query->fetchAll(PDO::FETCH_ASSOC);
}

// Handle inventory form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_inventory_item'])) {
        $stmt = $conn->prepare("
            INSERT INTO inventory (item_name, description, quantity, unit, category, low_stock_threshold)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $_POST['item_name'],
            $_POST['description'],
            $_POST['quantity'],
            $_POST['unit'],
            $_POST['category'],
            $_POST['low_stock_threshold']
        ]);
        $_SESSION['success'] = "Inventory item added successfully!";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }

    if (isset($_POST['update_inventory'])) {
        $stmt = $conn->prepare("UPDATE inventory SET quantity = ? WHERE id = ?");
        $stmt->execute([$_POST['quantity'], $_POST['id']]);
        $_SESSION['success'] = "Inventory updated successfully!";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
}

// Get inventory data
$items = getInventoryItems($conn);
$low_stock_items = getLowStockItems($conn);

// Promotion Management Functions
function getPromoStatus($conn, $promoType) {
    $stmt = $conn->prepare("SELECT is_active, discount_percent FROM promotions WHERE promo_type = ?");
    $stmt->execute([$promoType]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: ['is_active' => 0, 'discount_percent' => 0];
}

// CSRF token generation
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Handle promotion form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("CSRF token validation failed");
    }
    
    try {
        // Update top items promotion
        if (isset($_POST['update_top'])) {
            $isActive = isset($_POST['is_active_top']) ? 1 : 0;
            $discountPercent = min(max(0, (float)$_POST['discount_percent_top']), 100);
            
            $stmt = $conn->prepare("UPDATE promotions SET is_active = ?, discount_percent = ? WHERE promo_type = 'top_items'");
            $stmt->execute([$isActive, $discountPercent]);
        }

        // Update menu items promotion
        if (isset($_POST['update_menu'])) {
            $isActive = isset($_POST['is_active_menu']) ? 1 : 0;
            $discountPercent = min(max(0, (float)$_POST['discount_percent_menu']), 100);
            
            $stmt = $conn->prepare("UPDATE promotions SET is_active = ?, discount_percent = ? WHERE promo_type = 'menu'");
            $stmt->execute([$isActive, $discountPercent]);
        }

        $_SESSION['success'] = "Promotions updated successfully!";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    } catch (PDOException $e) {
        die("Database error: " . $e->getMessage());
    }
}

// Get current promotion status
$topPromo = getPromoStatus($conn, 'top_items');
$menuPromo = getPromoStatus($conn, 'menu');

$promoActiveTop = $topPromo['is_active'];
$promoActiveMenu = $menuPromo['is_active'];
$discountPercentTop = $topPromo['discount_percent'];
$discountPercentMenu = $menuPromo['discount_percent'];

// Fetch other data
try {
    $today = date('Y-m-d');

    // Today's sales
    $salesTodayStmt = $conn->prepare("SELECT SUM(total) AS total FROM orders1 WHERE DATE(order_date) = ?");
    $salesTodayStmt->execute([$today]);
    $todayTotal = $salesTodayStmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

    // Total sales
    $salesTotalStmt = $conn->query("SELECT SUM(total) AS total FROM orders1");
    $total = $salesTotalStmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

    // Top-selling items
    $topItemsStmt = $conn->query("
        SELECT p.product_id, p.product_name, p.price, p.category_id, c.category_name, 
               SUM(oi.quantity) AS total_sold
        FROM order_items1 oi
        JOIN products p ON oi.product_id = p.product_id
        LEFT JOIN categories c ON p.category_id = c.category_id
        GROUP BY oi.product_id
        ORDER BY total_sold DESC
        LIMIT 5
    ");
    $topItems = $topItemsStmt->fetchAll(PDO::FETCH_ASSOC);

    // Staff list
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
    $staffList = $staffStmt->fetchAll(PDO::FETCH_ASSOC);

    // Menu items with category info
    $menuStmt = $conn->query("
        SELECT p.*, c.category_name 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.category_id
        ORDER BY p.product_name
    ");
    $menuItems = $menuStmt->fetchAll(PDO::FETCH_ASSOC);

    // Sales last 7 days
    $salesDates = [];
    $salesAmounts = [];
    for ($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $salesDates[] = date('D', strtotime($date));
        $stmtDay = $conn->prepare("SELECT SUM(total) AS total FROM orders1 WHERE DATE(order_date) = ?");
        $stmtDay->execute([$date]);
        $totalDay = $stmtDay->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
        $salesAmounts[] = (float)$totalDay;
    }

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

function calculateDiscountedPrice($price, $discountPercent, $category) {
    $excludedCategories = ['drinks', 'salad', 'beverages'];
    $categoryLower = strtolower(trim($category));
    return in_array($categoryLower, $excludedCategories) 
        ? $price 
        : $price * (1 - $discountPercent / 100);
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manager Dashboard</title>
    <style>
        :root {
            --primary-green: #2E8B57;
            --dark-green: #1F5E3A;
            --light-green: #E8F5E9;
            --accent-green: #4CAF50;
            --text-dark: #333333;
            --text-light: #FFFFFF;
            --border-radius: 8px;
            --box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            --transition: all 0.3s ease;
            --warning-color: #FFC107;
            --danger-color: #DC3545;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #F5F5F5;
            color: var(--text-dark);
            line-height: 1.6;
        }
        
        .header {
            background-color: var(--primary-green);
            color: var(--text-light);
            padding: 1.5rem 2rem;
            box-shadow: var(--box-shadow);
            position: relative;
            z-index: 10;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header h1 {
            font-weight: 600;
            font-size: 1.8rem;
        }
        
        .header-controls {
            display: flex;
            gap: 1rem;
        }
        
        .btn {
            padding: 0.6rem 1.2rem;
            border-radius: var(--border-radius);
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
            font-size: 0.9rem;
        }
        
        .btn-primary {
            background-color: var(--dark-green);
            color: var(--text-light);
        }
        
        .btn-primary:hover {
            background-color: #16492D;
            transform: translateY(-2px);
        }
        
        .btn-secondary {
            background-color: var(--accent-green);
            color: var(--text-light);
        }
        
        .btn-secondary:hover {
            background-color: #3E8E41;
            transform: translateY(-2px);
        }
        
        .btn-warning {
            background-color: var(--warning-color);
            color: var(--text-dark);
        }
        
        .btn-warning:hover {
            background-color: #E0A800;
            transform: translateY(-2px);
        }
        
        .btn-danger {
            background-color: var(--danger-color);
            color: var(--text-light);
        }
        
        .btn-danger:hover {
            background-color: #BD2130;
            transform: translateY(-2px);
        }
        
        .container {
            padding: 2rem;
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }
        
        .card {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 1.5rem;
            transition: var(--transition);
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.12);
        }
        
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
        }
        
        .card-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: var(--primary-green);
        }
        
        .card-body {
            margin-top: 1rem;
        }
        
        .summary-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.8rem;
            padding: 0.8rem 0;
            border-bottom: 1px dashed rgba(0, 0, 0, 0.1);
        }
        
        .summary-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }
        
        .summary-label {
            font-weight: 500;
            color: #555;
        }
        
        .summary-value {
            font-weight: 600;
            color: var(--dark-green);
            font-size: 1.1rem;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }
        
        th {
            background-color: var(--light-green);
            color: var(--dark-green);
            text-align: left;
            padding: 0.8rem 1rem;
            font-weight: 600;
        }
        
        td {
            padding: 0.8rem 1rem;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }
        
        tr:hover td {
            background-color: rgba(46, 139, 87, 0.05);
        }
        
        .badge {
            display: inline-block;
            padding: 0.25rem 0.6rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .badge-success {
            background-color: rgba(46, 139, 87, 0.2);
            color: var(--dark-green);
        }
        
        .badge-warning {
            background-color: rgba(255, 193, 7, 0.2);
            color: #856404;
        }
        
        .badge-danger {
            background-color: rgba(220, 53, 69, 0.2);
            color: #721C24;
        }
        
        .badge-primary {
            background-color: rgba(0, 123, 255, 0.2);
            color: #004085;
        }
        
        .form-group {
            margin-bottom: 1rem;
        }
        
        .form-control {
            width: 100%;
            padding: 0.6rem 1rem;
            border: 1px solid #ddd;
            border-radius: var(--border-radius);
            font-size: 1rem;
            transition: var(--transition);
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary-green);
            box-shadow: 0 0 0 3px rgba(46, 139, 87, 0.2);
        }
        
        .form-inline {
            display: flex;
            gap: 1rem;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        
        .form-inline .form-control {
            width: auto;
        }
        
        .chart-container {
            position: relative;
            height: 300px;
            width: 100%;
        }
        
        .discount-tag {
            background-color: var(--accent-green);
            color: white;
            padding: 0.2rem 0.5rem;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: bold;
            margin-left: 0.5rem;
        }
        
        .action-link {
            color: var(--primary-green);
            margin-right: 1rem;
            text-decoration: none;
            font-weight: 500;
            transition: var(--transition);
        }
        
        .action-link:hover {
            color: var(--dark-green);
            text-decoration: underline;
        }
        
        .action-link.delete {
            color: var(--danger-color);
        }
        
        .action-link.delete:hover {
            color: #B71C1C;
        }
        
        .status-active {
            color: var(--primary-green);
            font-weight: 600;
        }
        
        .status-inactive {
            color: #6C757D;
        }
        
        .stock-warning {
            color: var(--warning-color);
            font-weight: 600;
        }
        
        .stock-danger {
            color: var(--danger-color);
            font-weight: 600;
        }
        
        .modal {
            display: none;
            position: fixed;
            z-index: 100;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.4);
        }
        
        .modal-content {
            background-color: #fefefe;
            margin: 10% auto;
            padding: 20px;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            width: 80%;
            max-width: 600px;
        }
        
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        
        .close:hover {
            color: black;
        }
        
        .tab-container {
            margin-top: 20px;
        }
        
        .tab-buttons {
            display: flex;
            border-bottom: 1px solid #ddd;
            margin-bottom: 20px;
        }
        
        .tab-btn {
            padding: 10px 20px;
            background: none;
            border: none;
            cursor: pointer;
            font-weight: 600;
            color: #666;
            transition: var(--transition);
        }
        
        .tab-btn.active {
            color: var(--primary-green);
            border-bottom: 2px solid var(--primary-green);
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .stock-level {
            display: inline-block;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            margin-right: 5px;
        }
        
        .stock-ok {
            background-color: var(--accent-green);
        }
        
        .stock-low {
            background-color: var(--warning-color);
        }
        
        .stock-critical {
            background-color: var(--danger-color);
        }
        
        .search-container {
            display: flex;
            gap: 10px;
            margin-bottom: 15px;
        }
        
        .search-container input {
            flex: 1;
            max-width: 300px;
        }
        
        .show-more {
            text-align: center;
            margin-top: 15px;
        }
        
        .show-more-btn {
            background: none;
            border: none;
            color: var(--primary-green);
            cursor: pointer;
            font-weight: 600;
            text-decoration: underline;
        }
        
        .chart-card {
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        
        .discount-panel {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .switch {
            position: relative;
            display: inline-block;
            width: 60px;
            height: 34px;
        }
        
        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        
        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 34px;
        }
        
        .slider:before {
            position: absolute;
            content: "";
            height: 26px;
            width: 26px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }
        
        input:checked + .slider {
            background-color: #28a745;
        }
        
        input:checked + .slider:before {
            transform: translateX(26px);
        }
        
        @media (max-width: 768px) {
            .grid {
                grid-template-columns: 1fr;
            }
            
            .header {
                flex-direction: column;
                gap: 1rem;
                padding: 1rem;
            }
            
            .header-controls {
                width: 100%;
                justify-content: space-between;
            }
            
            .container {
                padding: 1rem;
            }
            
            .form-inline {
                flex-wrap: wrap;
            }
        }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="header">
        <h1>Manager Dashboard</h1>
        <div class="header-controls">
            <a href="../logout.php" class="btn btn-danger">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                    <path fill-rule="evenodd" d="M10 12.5a.5.5 0 0 1-.5.5h-8a.5.5 0 0 1-.5-.5v-9a.5.5 0 0 1 .5-.5h8a.5.5 0 0 1 .5.5v2a.5.5 0 0 0 1 0v-2A1.5 1.5 0 0 0 9.5 2h-8A1.5 1.5 0 0 0 0 3.5v9A1.5 1.5 0 0 0 1.5 14h8a1.5 1.5 0 0 0 1.5-1.5v-2a.5.5 0 0 0-1 0v2z"/>
                    <path fill-rule="evenodd" d="M15.854 8.354a.5.5 0 0 0 0-.708l-3-3a.5.5 0 0 0-.708.708L14.293 7.5H5.5a.5.5 0 0 0 0 1h8.793l-2.147 2.146a.5.5 0 0 0 .708.708l3-3z"/>
                </svg>
                Logout
            </a>
        </div>
    </div>

    <div class="container">
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?= $_SESSION['success'] ?></div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <div class="grid">
            <!-- Sales Summary Card -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Sales Summary</h2>
                </div>
                <div class="card-body">
                    <div class="summary-item">
                        <span class="summary-label">Today's Sales</span>
                        <span class="summary-value">$<?= number_format($todayTotal, 2) ?></span>
                    </div>
                    <div class="summary-item">
                        <span class="summary-label">Total Sales</span>
                        <span class="summary-value">$<?= number_format($total, 2) ?></span>
                    </div>
                    <div class="summary-item">
                        <span class="summary-label">Active Promotions</span>
                        <span class="summary-value"><?= ($promoActiveTop ? 1 : 0) + ($promoActiveMenu ? 1 : 0) ?></span>
                    </div>
                    <div class="summary-item">
                        <span class="summary-label">Low Stock Items</span>
                        <span class="summary-value"><?= count($low_stock_items) ?></span>
                    </div>
                </div>
            </div>

            <!-- Sales Chart Card -->
            <div class="card chart-card">
                <div class="card-header">
                    <h2 class="card-title">Weekly Sales</h2>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="salesChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Promotion Management Section -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Promotion Management</h2>
            </div>
            <div class="card-body">
                <!-- Top Items Discount Panel -->
                <div class="discount-panel">
                    <h3>Top-Selling Items Discount</h3>
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <div class="form-group">
                            <label class="switch">
                                <input type="checkbox" name="is_active_top" <?= $topPromo['is_active'] ? 'checked' : '' ?>>
                                <span class="slider"></span>
                            </label>
                            <span>Enable Discount</span>
                        </div>
                        <div class="form-group">
                            <label>Discount Percentage:</label>
                            <input type="number" name="discount_percent_top" value="<?= $topPromo['discount_percent'] ?>" min="0" max="100" step="1">
                            %
                        </div>
                        <button type="submit" name="update_top" class="btn btn-primary">Save</button>
                    </form>
                </div>

                <!-- Menu Items Discount Panel -->
                <div class="discount-panel">
                    <h3>Menu-Wide Discount</h3>
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <div class="form-group">
                            <label class="switch">
                                <input type="checkbox" name="is_active_menu" <?= $menuPromo['is_active'] ? 'checked' : '' ?>>
                                <span class="slider"></span>
                            </label>
                            <span>Enable Discount</span>
                        </div>
                        <div class="form-group">
                            <label>Discount Percentage:</label>
                            <input type="number" name="discount_percent_menu" value="<?= $menuPromo['discount_percent'] ?>" min="0" max="100" step="1">
                            %
                        </div>
                        <p><small>Note: Excludes drinks and salads</small></p>
                        <button type="submit" name="update_menu" class="btn btn-primary">Save</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Stock Management Section -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Inventory Management</h2>
                <button onclick="document.getElementById('addInventoryModal').style.display='block'" class="btn btn-primary">
                    Add Inventory Item
                </button>
            </div>
            <div class="card-body">
                <div class="tab-container">
                    <div class="tab-buttons">
                        <button class="tab-btn active" onclick="openTab(event, 'inventoryTab')">All Items</button>
                        <button class="tab-btn" onclick="openTab(event, 'lowStockTab')">Low Stock</button>
                    </div>
                    
                    <!-- All Inventory Items Tab -->
                    <div id="inventoryTab" class="tab-content active">
                        <div class="search-container">
                            <input type="text" id="inventorySearch" class="form-control" placeholder="Search inventory items...">
                            <button class="btn btn-secondary" onclick="searchInventory()">Search</button>
                        </div>
                        <table id="inventoryTable">
                            <thead>
                                <tr>
                                    <th>Item Name</th>
                                    <th>Description</th>
                                    <th>Quantity</th>
                                    <th>Unit</th>
                                    <th>Category</th>
                                    <th>Threshold</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($items as $item): 
                                    $statusClass = '';
                                    $statusText = '';
                                    if ($item['quantity'] <= 0) {
                                        $statusClass = 'stock-critical';
                                        $statusText = 'Out of Stock';
                                    } elseif ($item['quantity'] <= $item['low_stock_threshold']) {
                                        $statusClass = 'stock-warning';
                                        $statusText = 'Low';
                                    } else {
                                        $statusClass = 'stock-ok';
                                        $statusText = 'OK';
                                    }
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars($item['item_name']) ?></td>
                                    <td><?= htmlspecialchars($item['description']) ?></td>
                                    <td class="<?= $statusClass ?>"><?= $item['quantity'] ?></td>
                                    <td><?= htmlspecialchars($item['unit']) ?></td>
                                    <td><?= htmlspecialchars($item['category']) ?></td>
                                    <td><?= $item['low_stock_threshold'] ?></td>
                                    <td>
                                        <span class="stock-level <?= $statusClass ?>"></span>
                                        <?= $statusText ?>
                                    </td>
                                    <td>
                                        <button onclick="openUpdateModal(<?= $item['id'] ?>, '<?= htmlspecialchars($item['item_name']) ?>', <?= $item['quantity'] ?>)" 
                                                class="btn btn-warning" style="padding: 0.3rem 0.6rem; font-size: 0.8rem;">
                                            Update
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Low Stock Tab -->
                    <div id="lowStockTab" class="tab-content">
                        <?php if (count($low_stock_items) > 0): ?>
                            <table id="lowStockTable">
                                <thead>
                                    <tr>
                                        <th>Item Name</th>
                                        <th>Quantity</th>
                                        <th>Unit</th>
                                        <th>Threshold</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($low_stock_items as $item): 
                                        $statusClass = $item['quantity'] <= 0 ? 'stock-critical' : 'stock-warning';
                                        $statusText = $item['quantity'] <= 0 ? 'Out of Stock' : 'Low';
                                    ?>
                                    <tr>
                                        <td><?= htmlspecialchars($item['item_name']) ?></td>
                                        <td class="<?= $statusClass ?>"><?= $item['quantity'] ?></td>
                                        <td><?= htmlspecialchars($item['unit']) ?></td>
                                        <td><?= $item['low_stock_threshold'] ?></td>
                                        <td>
                                            <span class="stock-level <?= $statusClass ?>"></span>
                                            <?= $statusText ?>
                                        </td>
                                        <td>
                                           <button onclick='openUpdateModal(<?= $item["id"] ?>, <?= json_encode($item["item_name"]) ?>, <?= $item["quantity"] ?>)'
        class="btn btn-warning" style="padding: 0.3rem 0.6rem; font-size: 0.8rem;">
    Update
</button>

                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <p>✅ All inventory levels are okay.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top Selling Items -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Top Selling Items</h2>
                <form method="post" class="form-inline">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <input type="hidden" name="toggle_top" value="1">
                    <button type="submit" name="apply_top" class="btn <?= $promoActiveTop ? 'btn-danger' : 'btn-secondary' ?>">
                        <?= $promoActiveTop ? 'Disable Discount' : 'Enable Discount' ?>
                    </button>
                    <input type="number" name="discount_percent_top" value="<?= $discountPercentTop ?>" min="0" max="100" class="form-control" style="width: 80px;">
                    <button type="submit" name="apply_top" class="btn btn-primary">Apply</button>
                </form>
            </div>
            <div class="card-body">
                <table id="topItemsTable">
                    <thead>
                        <tr>
                            <th>Product Name</th>
                            <th>Quantity Sold</th>
                            <th>Original Price</th>
                            <th>Discounted Price</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_slice($topItems, 0, 5) as $item): ?>
                        <tr>
                            <td><?= htmlspecialchars($item['product_name']) ?></td>
                            <td><?= htmlspecialchars($item['total_sold']) ?></td>
                            <td>$<?= number_format($item['price'], 2) ?></td>
                            <td>
                                $<?= number_format(calculateDiscountedPrice($item['price'], $discountPercentTop, $item['category_name']), 2) ?>
                                <?php if ($promoActiveTop && !in_array(strtolower($item['category_name']), ['drink', 'salad'])): ?>
                                    <span class="discount-tag"><?= $discountPercentTop ?>% off</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php if (count($topItems) > 5): ?>
                <div class="show-more">
                    <button class="show-more-btn" onclick="showAll('topItemsTable', <?= count($topItems) ?>)">
                        Show All (<?= count($topItems) ?>)
                    </button>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Staff Management -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Staff Accounts</h2>
                <a href="staff_management.php" class="btn btn-primary">Manage Staff</a>
            </div>
            <div class="card-body">
                <table id="staffTable">
                    <thead>
                        <tr>
                            <th>Staff ID</th>
                            <th>Name</th>
                            <th>Role</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_slice($staffList, 0, 5) as $staff): ?>
                        <tr>
                            <td><?= htmlspecialchars($staff['id']) ?></td>
                            <td><?= htmlspecialchars($staff['name']) ?></td>
                            <td style="color: <?= $staff['role'] === 'manager' ? 'blue' : ($staff['role'] === 'cashier' ? 'green' : 'gray') ?>; font-weight: bold;">
                                <?= ucfirst($staff['role']) ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php if (count($staffList) > 5): ?>
                <div class="show-more">
                    <button class="show-more-btn" onclick="showAll('staffTable', <?= count($staffList) ?>)">
                        Show All (<?= count($staffList) ?>)
                    </button>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Menu Management -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Menu Items</h2>
                <form method="post" class="form-inline">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <input type="hidden" name="toggle_menu" value="1">
                    <button type="submit" name="apply_menu" class="btn <?= $promoActiveMenu ? 'btn-danger' : 'btn-secondary' ?>">
                        <?= $promoActiveMenu ? 'Disable Discount' : 'Enable Discount' ?>
                    </button>
                    <input type="number" name="discount_percent_menu" value="<?= $discountPercentMenu ?>" min="0" max="100" class="form-control" style="width: 80px;">
                    <button type="submit" name="apply_menu" class="btn btn-primary">Apply</button>
                    <a href="add_food.php" class="btn btn-primary">Add Product</a>
                </form>
            </div>
            <div class="card-body">
                <div class="search-container">
                    <input type="text" id="menuSearch" class="form-control" placeholder="Search menu items...">
                    <button class="btn btn-secondary" onclick="searchMenu()">Search</button>
                </div>
                <table id="menuTable">
                    <thead>
                        <tr>
                            <th>Product Name</th>
                            <th>Category</th>
                            <th>Original Price</th>
                            <th>Discounted Price</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_slice($menuItems, 0, 5) as $item): ?>
                        <tr>
                            <td><?= htmlspecialchars($item['product_name']) ?></td>
                            <td><?= htmlspecialchars($item['category_name'] ?? 'Uncategorized') ?></td>
                            <td>$<?= number_format($item['price'], 2) ?></td>
                            <td>
                                $<?= number_format(calculateDiscountedPrice($item['price'], $discountPercentMenu, $item['category_name']), 2) ?>
                                <?php if ($promoActiveMenu && !in_array(strtolower($item['category_name']), ['drink', 'salad'])): ?>
                                    <span class="discount-tag"><?= $discountPercentMenu ?>% off</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="edit_food.php?id=<?= $item['product_id'] ?>" class="action-link">Edit</a>
                                <a href="delete_food.php?id=<?= $item['product_id'] ?>" class="action-link delete">Delete</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php if (count($menuItems) > 5): ?>
                <div class="show-more">
                    <button class="show-more-btn" onclick="showAll('menuTable', <?= count($menuItems) ?>)">
                        Show All (<?= count($menuItems) ?>)
                    </button>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Add Inventory Item Modal -->
        <div id="addInventoryModal" class="modal">
            <div class="modal-content">
                <span class="close" onclick="document.getElementById('addInventoryModal').style.display='none'">&times;</span>
                <h2>Add Inventory Item</h2>
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <div class="form-group">
                        <label>Item Name:</label>
                        <input type="text" name="item_name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Description:</label>
                        <input type="text" name="description" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Quantity:</label>
                        <input type="number" name="quantity" class="form-control" step="0.001" required>
                    </div>
                    <div class="form-group">
                        <label>Unit:</label>
                        <select name="unit" class="form-control" required>
                            <option value="kg">kg</option>
                            <option value="g">g</option>
                            <option value="l">l</option>
                            <option value="ml">ml</option>
                            <option value="pcs">pcs</option>
                            <option value="box">box</option>
                            <option value="pack">pack</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Category:</label>
                        <input type="text" name="category" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Low Stock Threshold:</label>
                        <input type="number" name="low_stock_threshold" class="form-control" step="0.001" required>
                    </div>
                    <button type="submit" name="add_inventory_item" class="btn btn-primary">Add Item</button>
                </form>
            </div>
        </div>

        <!-- Update Inventory Modal -->
        <div id="updateInventoryModal" class="modal">
            <div class="modal-content">
                <span class="close" onclick="document.getElementById('updateInventoryModal').style.display='none'">&times;</span>
                <h2>Update Inventory</h2>
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <input type="hidden" name="id" id="updateItemId">
                    <div class="form-group">
                        <label id="updateItemName"></label>
                    </div>
                    <div class="form-group">
                        <label>Quantity:</label>
                        <input type="number" name="quantity" id="updateItemQuantity" class="form-control" required>
                    </div>
                    <button type="submit" name="update_inventory" class="btn btn-primary">Update</button>
                </form>
            </div>
        </div>

        <script>
            // Sales Chart
            const salesCtx = document.getElementById('salesChart').getContext('2d');
            const salesChart = new Chart(salesCtx, {
                type: 'line',
                data: {
                    labels: <?= json_encode($salesDates) ?>,
                    datasets: [{
                        label: 'Daily Sales ($)',
                        data: <?= json_encode($salesAmounts) ?>,
                        backgroundColor: 'rgba(46, 139, 87, 0.1)',
                        borderColor: 'rgba(46, 139, 87, 1)',
                        borderWidth: 2,
                        tension: 0.3,
                        fill: true,
                        pointBackgroundColor: '#2E8B57',
                        pointBorderColor: '#fff',
                        pointHoverRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false,
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)'
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });

            // Tab functionality
            function openTab(evt, tabName) {
                const tabContents = document.getElementsByClassName("tab-content");
                for (let i = 0; i < tabContents.length; i++) {
                    tabContents[i].classList.remove("active");
                }
                
                const tabButtons = document.getElementsByClassName("tab-btn");
                for (let i = 0; i < tabButtons.length; i++) {
                    tabButtons[i].classList.remove("active");
                }
                
                document.getElementById(tabName).classList.add("active");
                evt.currentTarget.classList.add("active");
            }

            // Open update modal
            function openUpdateModal(id, name, quantity) {
                document.getElementById('updateItemId').value = id;
                document.getElementById('updateItemName').textContent = name;
                document.getElementById('updateItemQuantity').value = quantity;
                document.getElementById('updateInventoryModal').style.display = 'block';
            }

            // Search functions
            function searchMenu() {
                const query = document.getElementById("menuSearch").value.toLowerCase();
                const rows = document.querySelectorAll("#menuTable tbody tr");
                let hasMatches = false;

                rows.forEach(row => {
                    const name = row.cells[0].textContent.toLowerCase();
                    const category = row.cells[1].textContent.toLowerCase();
                    const isMatch = name.includes(query) || category.includes(query);
                    row.style.display = isMatch ? "" : "none";
                    if (isMatch) hasMatches = true;
                });

                if (!hasMatches) {
                    alert("No matching items found.");
                }
            }

            function searchInventory() {
                const query = document.getElementById("inventorySearch").value.toLowerCase();
                const rows = document.querySelectorAll("#inventoryTable tbody tr");
                let hasMatches = false;

                rows.forEach(row => {
                    const name = row.cells[0].textContent.toLowerCase();
                    const desc = row.cells[1].textContent.toLowerCase();
                    const category = row.cells[4].textContent.toLowerCase();
                    const isMatch = name.includes(query) || desc.includes(query) || category.includes(query);
                    row.style.display = isMatch ? "" : "none";
                    if (isMatch) hasMatches = true;
                });

                if (!hasMatches) {
                    alert("No matching inventory items found.");
                }
            }

            function showAll(tableId, totalRows) {
                const rows = document.querySelectorAll(`#${tableId} tbody tr`);
                rows.forEach(row => {
                    row.style.display = "";
                });

                const btn = document.querySelector(`#${tableId} + .show-more .show-more-btn`);
                if (btn) btn.style.display = 'none';
            }

            // Close modals when clicking outside
            window.onclick = function(event) {
                if (event.target.className === 'modal') {
                    event.target.style.display = 'none';
                }
            }
        </script>
    </div>
</body>
</html>