
<?php
include('../db.php');

$orders = $conn->query("
    SELECT o.order_id, o.table_number, o.status, o.order_date, o.discount,
           COALESCE(SUM(oi.price * oi.quantity), 0) AS subtotal,
           ROUND(COALESCE(SUM(oi.price * oi.quantity), 0) * (1 - o.discount / 100), 2) AS total
    FROM orders1 o
    LEFT JOIN order_items1 oi ON o.order_id = oi.order_id
    GROUP BY o.order_id, o.table_number, o.status, o.order_date, o.discount
    ORDER BY o.order_date DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Active Orders</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <link rel="stylesheet" href="order.css" />
</head>
<body>
    <div class="container">
        <header>
            <div class="header-title">
                <i class="fas fa-clipboard-list"></i>
                <h1>Active Orders</h1>
            </div>
            <nav class="header-nav">
                <a href="index.php" class="nav-btn">
                    <i class="fas fa-arrow-left"></i>
                    <span>Back</span>
                </a>
                <a href="../logout.php" class="nav-btn">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </nav>
        </header>

        <div class="orders-card">
            <div class="card-header">
                <h2 class="card-title">Current Orders</h2>
                <div class="card-actions">
                    <span><?= count($orders) ?> active orders</span>
                </div>
            </div>

            <?php if (count($orders) > 0): ?>
            <table class="orders-table">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Table</th>
                        <th>Status</th>
                        <th>Total</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                    <tr>
                        <td>#<?= $order['order_id'] ?></td>

<td><?= $order['table_number'] ?></td>
                        <td>
                            <?php 
                            $statusClass = 'status-'.strtolower($order['status']);
                            echo '<span class="status '.$statusClass.'">'.$order['status'].'</span>';
                            ?>
                        </td>
                        <td>$<?= number_format($order['total'], 2) ?></td>
                        <td>
                            <div class="action-btns">
                                <a href="invoice.php?order_id=<?= $order['order_id'] ?>" class="btn btn-primary">
                                    <i class="fas fa-print"></i>
                                    <span>Print</span>
                                </a>
                                <?php if ($order['status'] != 'Done'): ?>
                                <a href="mark_done.php?id=<?= $order['order_id'] ?>" class="btn btn-outline">
                                    <i class="fas fa-check"></i>
                                    <span>Done</span>
                                </a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-clipboard"></i>
                <h3>No Active Orders</h3>
                <p>There are currently no active orders to display.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>