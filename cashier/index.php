<?php
session_start();
include('../db.php');

// Role check
if ($_SESSION['role'] !== 'cashier') {
    header("Location: login.php");
    exit();
}

// CSRF protection
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Initialize cart if not set
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Fetch active promotions
try {
    $promoStmt = $conn->query("SELECT * FROM promotions WHERE is_active = 1");
    $activePromos = [
        'top_items' => ['is_active' => false, 'discount_percent' => 0],
        'menu' => ['is_active' => false, 'discount_percent' => 0]
    ];
    
    foreach ($promoStmt->fetchAll(PDO::FETCH_ASSOC) as $promo) {
        $activePromos[$promo['promo_type']] = [
            'is_active' => (bool)$promo['is_active'],
            'discount_percent' => (float)$promo['discount_percent']
        ];
    }
} catch (Exception $e) {
    error_log("Promotion loading error: " . $e->getMessage());
}

// Fetch all products and apply discounts
try {
    $products = $conn->query("SELECT * FROM products")->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($products as &$product) {
        $price = (float)$product['price'];
        $category = strtolower($product['category']);
        $name = strtolower($product['product_name']);
        $isTopSelling = (bool)$product['is_top_selling'];
        $excluded = ($category === 'drink' || strpos($name, 'salad') !== false);

        if (!$excluded) {
            if ($isTopSelling && $activePromos['top_items']['is_active']) {
                $discountPercent = $activePromos['top_items']['discount_percent'];
            } elseif ($activePromos['menu']['is_active']) {
                $discountPercent = $activePromos['menu']['discount_percent'];
            } else {
                $discountPercent = 0;
            }
        } else {
            $discountPercent = 0;
        }

        $product['final_price'] = round($price * (1 - $discountPercent / 100), 2);
        $product['has_discount'] = $discountPercent > 0;
        $product['discount_percent'] = $discountPercent;
        
        // Set default image if not exists
        if (empty($product['image_path'])) {
            $product['image_path'] = $category === 'drink' ? 'images/drink.jpg' : 'images/pizza.jpg';
        }
    }
    unset($product);
} catch (Exception $e) {
    $products = [];
    error_log("Product loading error: " . $e->getMessage());
}

// Fetch occupied tables
try {
    $stmt = $conn->prepare("SELECT table_number FROM orders1 WHERE status IN ('Pending', 'Processing')");
    $stmt->execute();
    $occupied_tables = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'table_number');
} catch (Exception $e) {
    $occupied_tables = [];
    error_log("Table status error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pizza Company POS</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #27ae60;
            --secondary-color: #2c3e50;
            --accent-color: #f39c12;
            --light-color: #ecf0f1;
            --dark-color: #34495e;
            --success-color: #2ecc71;
            --warning-color: #e67e22;
            --danger-color: #e74c3c;
            --leaf-green: #16a085;
            --fresh-green: #1abc9c;
        }
        
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Poppins', sans-serif;
        }
        
        body {
            background-color: #f5f5f5;
            color: #333;
            line-height: 1.6;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }
        
        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            margin-bottom: 20px;
            border-bottom: 2px solid var(--leaf-green);
            background-color: white;
            border-radius: 8px;
            padding: 15px 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        
        .logo {
            font-size: 24px;
            font-weight: 700;
            color: var(--primary-color);
            display: flex;
            align-items: center;
        }
        
        .logo:before {
            content: "🍕";
            margin-right: 10px;
            font-size: 28px;
        }
        
        .logout-btn {
            background-color: var(--secondary-color);
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.3s;
            font-weight: 500;
        }
        
        .logout-btn:hover {
            background-color: var(--dark-color);
            transform: translateY(-2px);
        }
        
        .search-box {
            margin-bottom: 20px;
            position: relative;
        }
        
        #searchInput {
            width: 100%;
            padding: 12px 15px 12px 40px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
            background-color: white;
            background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="%2327ae60" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>');
            background-repeat: no-repeat;
            background-position: 12px center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        
        #searchInput:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(39, 174, 96, 0.2);
        }
        
        .main-content {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
        }
        
        .menu-container, .cart-section {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 20px;
            border-top: 4px solid var(--primary-color);
        }
        
        .section-title {
            font-size: 20px;
            margin-bottom: 15px;
            color: var(--secondary-color);
            padding-bottom: 10px;
            border-bottom: 2px solid var(--light-color);
            display: flex;
            align-items: center;
        }
        
        .section-title:before {
            content: "";
            display: inline-block;
            width: 8px;
            height: 20px;
            background-color: var(--primary-color);
            margin-right: 10px;
            border-radius: 4px;
        }
        
        .menu-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .menu-card {
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            overflow: hidden;
            transition: all 0.3s;
            background-color: white;
        }
        
        .menu-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            border-color: var(--primary-color);
        }
        
        .menu-img {
            height: 150px;
            background-size: cover;
            background-position: center;
            transition: all 0.3s;
            background-color: #f5f5f5;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-secondary);
        }
        
        .menu-card:hover .menu-img {
            transform: scale(1.03);
        }
        
        .menu-content {
            padding: 15px;
        }
        
        .menu-name {
            font-size: 16px;
            margin-bottom: 10px;
            color: var(--dark-color);
            font-weight: 600;
        }
        
        .menu-price {
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .current-price {
            font-weight: 600;
            color: var(--primary-color);
            font-size: 18px;
        }
        
        .original-price {
            text-decoration: line-through;
            color: #999;
            font-size: 14px;
            margin-left: 5px;
        }
        
        .discount-badge {
            background-color: #ffeeee;
            color: var(--danger-color);
            font-size: 12px;
            padding: 2px 6px;
            border-radius: 4px;
            margin-left: 5px;
            font-weight: 600;
            border: 1px solid #ffcccc;
        }
        
        .quantity-control {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            overflow: hidden;
            width: fit-content;
        }
        
        .qty-btn {
            width: 30px;
            height: 30px;
            background-color: var(--light-color);
            border: none;
            font-size: 16px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background-color 0.3s;
        }
        
        .qty-btn:hover {
            background-color: #d5dbdb;
        }
        
        .quantity-input {
            width: 40px;
            height: 30px;
            text-align: center;
            border: none;
            border-left: 1px solid #ddd;
            border-right: 1px solid #ddd;
            margin: 0;
            padding: 0;
            font-weight: 500;
        }
        
        .add-btn {
            width: 100%;
            padding: 10px;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.3s;
            font-weight: 500;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .add-btn:hover {
            background-color: var(--leaf-green);
            transform: translateY(-2px);
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .add-btn:active {
            transform: translateY(0);
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            gap: 5px;
            margin-top: 20px;
        }
        
        .pagination-btn {
            padding: 5px 12px;
            border: 1px solid #ddd;
            background-color: white;
            cursor: pointer;
            border-radius: 4px;
            transition: all 0.3s;
        }
        
        .pagination-btn:hover {
            border-color: var(--primary-color);
            color: var(--primary-color);
        }
        
        .pagination-btn.active {
            background-color: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }
        
        .cart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .orders-btn {
            background-color: var(--secondary-color);
            color: white;
            padding: 8px 12px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .orders-btn:hover {
            background-color: var(--dark-color);
            transform: translateY(-2px);
        }
        
        .cart-items {
            max-height: 500px;
            overflow-y: auto;
            padding-right: 5px;
        }
        
        .cart-items::-webkit-scrollbar {
            width: 6px;
        }
        
        .cart-items::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 3px;
        }
        
        .cart-items::-webkit-scrollbar-thumb {
            background: var(--primary-color);
            border-radius: 3px;
        }
        
        .cart-item {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #eee;
            align-items: center;
            transition: background-color 0.2s;
        }
        
        .cart-item:hover {
            background-color: #f9f9f9;
        }
        
        .item-name {
            flex: 2;
            font-weight: 500;
        }
        
        .item-fullprice, .item-price {
            flex: 1;
            text-align: right;
        }
        
        .item-discount, .item-quantity {
            flex: 1;
            text-align: center;
        }
        
        .remove-item {
            color: var(--danger-color);
            text-decoration: none;
            margin-left: 10px;
            font-weight: bold;
            font-size: 18px;
            transition: transform 0.2s;
        }
        
        .remove-item:hover {
            transform: scale(1.2);
        }
        
        .summary {
            margin-top: 20px;
            padding-top: 15px;
            border-top: 2px solid var(--primary-color);
        }
        
        .summary p {
            margin-bottom: 8px;
            display: flex;
            justify-content: space-between;
        }
        
        .summary p span:first-child {
            color: var(--dark-color);
        }
        
        .summary p span:last-child {
            font-weight: 500;
        }
        
        .summary p:last-child span {
            font-weight: 700;
            color: var(--primary-color);
        }
        
        .table-select {
            margin: 20px 0;
        }
        
        .table-select label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: var(--dark-color);
        }
        
        #table_number {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            background-color: white;
            font-size: 15px;
            transition: all 0.3s;
        }
        
        #table_number:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(39, 174, 96, 0.2);
        }
        
        .place-order-btn {
            width: 100%;
            padding: 12px;
            background-color: var(--success-color);
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            transition: all 0.3s;
            font-weight: 600;
            margin-top: 10px;
        }
        
        .place-order-btn:hover {
            background-color: #27ae60;
            transform: translateY(-2px);
            box-shadow: 0 2px 10px rgba(46, 204, 113, 0.3);
        }
        
        .place-order-btn:disabled {
            background-color: #95a5a6;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }
        
        .promo-banner {
            background-color: #e8f5e9;
            padding: 15px;
            border-left: 5px solid var(--success-color);
            margin-bottom: 20px;
            border-radius: 4px;
            color: var(--dark-color);
            display: flex;
            align-items: center;
        }
        
        .promo-banner strong {
            margin-right: 10px;
            color: var(--primary-color);
        }
        
        .toast {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background-color: var(--success-color);
            color: white;
            padding: 15px 20px;
            border-radius: 4px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            z-index: 1000;
            transform: translateY(100px);
            opacity: 0;
            transition: transform 0.3s, opacity 0.3s;
            display: flex;
            align-items: center;
            font-weight: 500;
        }
        
        .toast:before {
            content: "✓";
            margin-right: 10px;
            font-size: 18px;
        }
        
        .toast.show {
            transform: translateY(0);
            opacity: 1;
        }
        
        .toast.error {
            background-color: var(--danger-color);
        }
        
        .toast.error:before {
            content: "⚠";
        }
        
        @media (max-width: 768px) {
            .main-content {
                grid-template-columns: 1fr;
            }
            
            .menu-grid {
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            }
            
            header {
                flex-direction: column;
                gap: 10px;
                align-items: flex-start;
            }
            
            .cart-item {
                font-size: 14px;
            }
        }
        .menu-img {
            height: 150px;
            background-size: cover;
            background-position: center;
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }
        
        .menu-img img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s;
        }
        
        .menu-card:hover .menu-img img {
            transform: scale(1.05);
        }
        
        .menu-img .category-icon {
            position: absolute;
            bottom: 10px;
            right: 10px;
            background: rgba(0,0,0,0.7);
            color: white;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <header>
            <div class="logo">Pizza Company POS</div>
            <form action="../logout.php" method="post">
                <button type="submit" class="logout-btn">Logout</button>
            </form>
        </header>

        <!-- Promo Banner -->
        <?php if ($activePromos['top_items']['is_active'] || $activePromos['menu']['is_active']): ?>
            <div class="promo-banner">
                <strong>ACTIVE PROMOTIONS:</strong>
                <div>
                    <?php if ($activePromos['top_items']['is_active']): ?>
                        <span>🏆 Top Items: <span style="color: var(--danger-color); font-weight: 600;"><?= number_format($activePromos['top_items']['discount_percent'], 2) ?>% OFF</span></span>
                        <?php if ($activePromos['menu']['is_active']): ?> • <?php endif; ?>
                    <?php endif; ?>
                    <?php if ($activePromos['menu']['is_active']): ?>
                        <span>🍕 Entire Menu: <span style="color: var(--danger-color); font-weight: 600;"><?= number_format($activePromos['menu']['discount_percent'], 2) ?>% OFF</span></span>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Search Box -->
        <div class="search-box">
            <input type="text" id="searchInput" placeholder="Search menu items..." autocomplete="off">
        </div>

        <div class="main-content">
            <!-- Menu Section -->
            <div class="menu-container">
                <h3 class="section-title">Our Menu</h3>

                <div class="menu-grid" id="menuItems">
                    <?php foreach ($products as $product): ?>
                        <div class="menu-card" data-name="<?= strtolower($product['product_name']) ?>" data-category="<?= strtolower($product['category']) ?>">
                            <div class="menu-img">
                                <img src="<?= htmlspecialchars($product['image_path']) ?>" alt="<?= htmlspecialchars($product['product_name']) ?>">
                                <div class="category-icon">
                                    <?= $product['category'] === 'drink' ? '🍹' : '🍕' ?>
                                </div>
                            </div>
                            <div class="menu-content">
                                <h4 class="menu-name"><?= htmlspecialchars($product['product_name']) ?></h4>
                                <p style="font-size: 13px; color: #666; margin-bottom: 10px;"><?= htmlspecialchars($product['description'] ?? '') ?></p>
                                <div class="menu-price">
                                    <?php if ($product['has_discount']): ?>
                                        <span class="current-price">$<?= number_format($product['final_price'], 2) ?></span>
                                        <span class="original-price">$<?= number_format($product['price'], 2) ?></span>
                                        <span class="discount-badge">
                                            SAVE <?= number_format($product['discount_percent']) ?>%
                                        </span>
                                    <?php else: ?>
                                        <span class="current-price">$<?= number_format($product['price'], 2) ?></span>
                                    <?php endif; ?>
                                </div>
                                <form class="add-to-cart-form" method="post" action="add_to_cart.php">
                                    <input type="hidden" name="product_id" value="<?= $product['product_id'] ?>">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                                    <div class="quantity-control">
                                        <button type="button" class="qty-btn minus">-</button>
                                        <input type="number" name="qty" value="1" min="1" class="quantity-input">
                                        <button type="button" class="qty-btn plus">+</button>
                                    </div>
                                    <button type="submit" class="add-btn">Add to Order</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Cart Section -->
            <div class="cart-section">
                <div class="cart-header">
                    <h3 class="section-title">Order Summary</h3>
                    <a href="orders.php" class="orders-btn">View Orders</a>
                </div>

                <div class="cart-items">
                    <?php if (!empty($_SESSION['cart'])): ?>
                        <?php
                        $subTotal = 0;
                        $totalDiscountAmount = 0;
                        
                        foreach ($_SESSION['cart'] as $item):
                            $price = $item['price'] ?? 0;
                            $originalPrice = $item['original_price'] ?? $price;
                            $qty = $item['quantity'] ?? 0;
                            $discountPercent = $item['discount_percent'] ?? 0;
                            $name = $item['name'] ?? 'Unnamed Item';
                            $id = $item['id'] ?? 0;

                            $fullPrice = $originalPrice * $qty;
                            $discountAmount = ($originalPrice - $price) * $qty;

                            $subTotal += $fullPrice;
                            $totalDiscountAmount += $discountAmount;
                        ?>
                            <div class="cart-item">
                                <div class="item-name"><?= htmlspecialchars($name) ?></div>
                                <div class="item-fullprice">$<?= number_format($fullPrice, 2) ?></div>
                                <div class="item-discount" style="color: var(--danger-color);"><?= (int)$discountPercent ?>%</div>
                                <div class="item-quantity"><?= (int)$qty ?></div>
                                <div class="item-price">$<?= number_format($price, 2) ?></div>
                                <a href="remove_from_cart.php?id=<?= urlencode($id) ?>" class="remove-item" title="Remove item">×</a>
                            </div>
                        <?php endforeach; ?>

                        <div class="summary">
                            <p><span>Subtotal:</span> <span>$<?= number_format($subTotal, 2) ?></span></p>
                            <p><span>Discounts:</span> <span style="color: var(--danger-color);">-$<?= number_format($totalDiscountAmount, 2) ?></span></p>
                            <p><span>Total:</span> <span>$<?= number_format($subTotal - $totalDiscountAmount, 2) ?></span></p>
                        </div>

                        <form action="checkout.php" method="post">
                            <div class="table-select">
                                <label for="table_number">Table Number</label>
                                <select name="table_number" id="table_number" required>
                                    <option value="" disabled selected>Select a table</option>
                                    <?php for ($i = 1; $i <= 20; $i++): ?>
                                        <option value="<?= $i ?>" <?= in_array($i, $occupied_tables) ? 'disabled' : '' ?>>
                                            Table <?= $i ?> <?= in_array($i, $occupied_tables) ? '(Occupied)' : '' ?>
                                        </option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <button type="submit" class="place-order-btn" <?= empty($_SESSION['cart']) ? 'disabled' : '' ?>>Complete Order</button>
                        </form>
                    <?php else: ?>
                        <div style="text-align: center; padding: 30px 0;">
                            <div style="font-size: 50px; color: #ddd; margin-bottom: 10px;">🛒</div>
                            <p>Your cart is empty</p>
                            <p style="color: #999; font-size: 14px;">Add items to get started</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast Notification -->
    <div class="toast" id="toast"></div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Enhanced search functionality
            const searchInput = document.getElementById('searchInput');
            if (searchInput) {
                searchInput.addEventListener('input', function() {
                    const searchTerm = this.value.toLowerCase().trim();
                    const menuCards = document.querySelectorAll('.menu-card');
                    
                    menuCards.forEach(card => {
                        const productName = card.getAttribute('data-name');
                        const productCategory = card.getAttribute('data-category');
                        
                        // Search in both name and category
                        if (productName.includes(searchTerm) || 
                            productCategory.includes(searchTerm) || 
                            searchTerm.length === 0) {
                            card.style.display = 'block';
                        } else {
                            card.style.display = 'none';
                        }
                    });
                });
            }

            // Quantity controls
            document.querySelectorAll('.quantity-control').forEach(control => {
                const input = control.querySelector('.quantity-input');
                control.querySelector('.minus').addEventListener('click', () => {
                    if (input.value > 1) input.value--;
                });
                control.querySelector('.plus').addEventListener('click', () => {
                    input.value++;
                });
            });

            // Add to cart forms with error handling
            document.querySelectorAll('.add-to-cart-form').forEach(form => {
                form.addEventListener('submit', async function(e) {
                    e.preventDefault();
                    
                    const button = form.querySelector('[type="submit"]');
                    const originalText = button.innerHTML;
                    
                    // Show loading state
                    button.disabled = true;
                    button.innerHTML = '<span style="display:inline-block; animation: spin 0.7s linear infinite;">↻</span> Adding...';
                    
                    try {
                        const response = await fetch('add_to_cart.php', {
                            method: 'POST',
                            body: new FormData(form)
                        });
                        
                        const result = await response.json();
                        
                        if (!response.ok || !result.success) {
                            throw new Error(result.error || 'Failed to add to cart');
                        }
                        
                        // Show success message
                        showToast(`${result.item.name} added to order!`);
                        
                        // Reload the page to update cart
                        setTimeout(() => location.reload(), 1000);
                        
                    } catch (error) {
                        showToast(error.message, 'error');
                        console.error('Cart error:', error);
                    } finally {
                        button.disabled = false;
                        button.innerHTML = originalText;
                    }
                });
            });

            // Toast notification function
            function showToast(message, type = 'success') {
                const toast = document.getElementById('toast');
                toast.textContent = message;
                toast.className = 'toast';
                toast.classList.add(type);
                toast.classList.add('show');
                
                setTimeout(() => {
                    toast.classList.remove('show');
                }, 3000);
            }

            // Add animation for spinner
            const style = document.createElement('style');
            style.textContent = `
                @keyframes spin {
                    0% { transform: rotate(0deg); }
                    100% { transform: rotate(360deg); }
                }
            `;
            document.head.appendChild(style);
        });
    </script>
</body>
</html>