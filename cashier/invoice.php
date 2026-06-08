<?php
session_start();
include('../db.php');

if (!isset($_GET['order_id'])) {
    header("Location: orders.php");
    exit();
}

$order_id = $_GET['order_id'];

// Fetch order details
$stmt = $conn->prepare("SELECT * FROM orders1 WHERE order_id = :order_id");
$stmt->bindParam(':order_id', $order_id);
$stmt->execute();
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    echo "Order not found.";
    exit();
}

// Fetch order items
$stmt = $conn->prepare("
    SELECT oi.quantity, oi.price, p.product_name, p.category
    FROM order_items1 oi
    JOIN products p ON oi.product_id = p.product_id
    WHERE oi.order_id = :order_id
");
$stmt->bindParam(':order_id', $order_id);
$stmt->execute();
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate subtotal (assuming 10% tax rate)
$subtotal = $order['total'] / 1.1;
$tax = $order['total'] - $subtotal;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title> Pizza Company- Invoice #<?= $order_id ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #2e7d32;
            --primary-light: #60ad5e;
            --primary-dark: #005005;
            --text-primary: #212121;
            --text-secondary: #757575;
            --divider: #e0e0e6;
            --danger: #e74c3c;
            --gray-light: #f8f9fa;
            --gray-medium: #6c757d;
            --gray-dark: #495057;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }
        
        body {
            color: var(--text-primary);
            line-height: 1.5;
            background-color: white;
        }
        
        .invoice-container {
            width: 100%;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        
        /* Header Styles */
        .invoice-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--primary);
        }
        
        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .logo-icon {
            font-size: 1.8rem;
            color: var(--primary);
        }
        
        .logo-text {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--primary);
        }
        
        .invoice-title {
            text-align: right;
        }
        
        .invoice-title h1 {
            font-size: 1.5rem;
            color: var(--primary);
            margin-bottom: 5px;
        }
        
        .invoice-title p {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }
        
        /* Items Table - Critical Fixes */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            table-layout: fixed;
        }
        
        .items-table thead th {
            background-color: var(--gray-light);
            color: var(--gray-dark);
            text-align: left;
            padding: 10px 8px;
            border-bottom: 2px solid var(--divider);
            font-weight: 600;
            font-size: 0.9rem;
        }
        
        .items-table tbody td {
            padding: 10px 8px;
            border-bottom: 1px solid var(--divider);
            vertical-align: middle;
            font-size: 0.9rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .items-table tbody tr:last-child td {
            border-bottom: none;
        }
        
        .item-name {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .item-name i {
            font-size: 1rem;
            color: var(--primary);
            flex-shrink: 0;
        }
        
        .item-name span {
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .text-right {
            text-align: right;
        }
        
        .text-center {
            text-align: center;
        }
        
        /* Summary Section */
        .invoice-summary {
            margin-top: 25px;
            width: 100%;
            max-width: 300px;
            margin-left: auto;
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 6px;
            padding: 6px 0;
        }
        
        .summary-row.total {
            font-size: 1rem;
            font-weight: 600;
            color: var(--primary-dark);
            margin-top: 8px;
            padding-top: 8px;
            border-top: 2px dashed var(--divider);
        }
        
        /* Footer */
        .thank-you {
            text-align: center;
            margin-top: 30px;
            padding: 15px;
            border-top: 2px solid var(--divider);
            font-style: italic;
            color: var(--text-secondary);
            font-size: 0.9rem;
        }
        
        .footer {
            margin-top: 20px;
            font-size: 0.8rem;
            color: var(--text-secondary);
            text-align: center;
            padding-top: 10px;
            border-top: 1px solid var(--divider);
        }
        
        /* Print Actions */
        .print-actions {
            text-align: center;
            margin-top: 20px;
            padding: 15px;
            background-color: var(--gray-light);
            border-radius: 8px;
        }
        
        .btn {
            padding: 8px 16px;
            border-radius: 6px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            margin: 0 5px;
            font-size: 0.9rem;
        }
        
        .btn-primary {
            background-color: var(--primary);
            color: white;
        }
        
        .btn-outline {
            background-color: transparent;
            color: var(--primary);
            border: 1px solid var(--primary);
        }
        
        /* Print Styles */
        @media print {
            @page {
                size: A5 portrait;
                margin: 5mm;
            }
            
            body {
                font-size: 11px;
                padding: 0;
                margin: 0;
            }
            
            .invoice-container {
                padding: 0;
                width: 100%;
                max-width: 100%;
            }
            
            .no-print {
                display: none !important;
            }
            
            .items-table {
                font-size: 10px;
            }
            
            .items-table td, 
            .items-table th {
                padding: 6px 4px;
            }
            
            .item-name i {
                font-size: 0.9rem;
            }
        }
        
        /* Thermal Printer Styles */
        @media print and (max-width: 80mm) {
            @page {
                size: 80mm auto;
                margin: 2mm;
            }
            
            body {
                font-family: 'Courier New', monospace;
                font-size: 9px;
                padding: 1mm;
            }
            
            .items-table {
                font-size: 9px;
            }
            
            .item-name i {
                display: none;
            }
            
            .logo-icon {
                display: none;
            }
        }
        
        /* Mobile Responsive */
        @media (max-width: 768px) {
            .invoice-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            
            .invoice-title {
                text-align: left;
                width: 100%;
            }
            
            .items-table {
                font-size: 0.8rem;
            }
        }
    </style>
</head>
<body>
    <div class="invoice-container">
        <div class="invoice-header">
            <div class="logo">
                <i class="fas fa-pizza-slice logo-icon"></i>
                <span class="logo-text">Pizza Company</span>
            </div>
            <div class="invoice-title">
                <h1>INVOICE</h1>
                <p>#<?= $order_id ?></p>
            </div>
        </div>
        
        <div class="invoice-info">
            <div class="info-group">
                <h3>Order Date</h3>
                <p><?= date('F j, Y, g:i a', strtotime($order['order_date'])) ?></p>
            </div>
            <div class="info-group">
                <h3>Table Number</h3>
                <p><?= htmlspecialchars($order['table_number']) ?></p>
            </div>
        </div>
        
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 50%">Item</th>
                    <th style="width: 15%" class="text-right">Price</th>
                    <th style="width: 10%" class="text-center">Qty</th>
                    <th style="width: 25%" class="text-right">Amount</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                <tr>
                    <td>
                        <div class="item-name">
                            <i class="fas fa-<?= 
                                $item['category'] === 'drink' ? 'glass-whiskey' : 
                                ($item['category'] === 'salad' ? 'leaf' : 'pizza-slice') 
                            ?>"></i>
                            <span><?= htmlspecialchars($item['product_name']) ?></span>
                        </div>
                    </td>
                    <td class="text-right">$<?= number_format($item['price'], 2) ?></td>
                    <td class="text-center"><?= $item['quantity'] ?></td>
                    <td class="text-right">$<?= number_format($item['price'] * $item['quantity'], 2) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <div class="invoice-summary">
            <div class="summary-row">
                <span>Subtotal:</span>
                <span>$<?= number_format($subtotal, 2) ?></span>
            </div>
            <div class="summary-row">
                <span>Tax (10%):</span>
                <span>$<?= number_format($tax, 2) ?></span>
            </div>
            <div class="summary-row total">
                <span>Total:</span>
                <span>$<?= number_format($order['total'], 2) ?></span>
            </div>
        </div>
        
        <div class="thank-you">
            <p>Thank you for dining with us!</p>
            <p>Hope to see you again soon</p>
        </div>
        
        <div class="footer">
            <p>1st Main Street • (+855) 9688-17699</p>
        </div>
        
        <div class="print-actions no-print">
            <a href="orders.php" class="btn btn-outline">
                <i class="fas fa-arrow-left"></i> Back to Orders
            </a>
            <button onclick="window.print()" class="btn btn-primary">
                <i class="fas fa-print"></i> Print Invoice
            </button>
        </div>
    </div>
</body>
</html>