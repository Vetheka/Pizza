<?php
session_start();
$subTotal = 0;
$totalDiscountAmount = 0;

if (!empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $price = $item['price'] ?? 0;
        $originalPrice = $item['original_price'] ?? $price; 
        $qty = $item['quantity'] ?? 0;
        $fullPrice = $originalPrice * $qty;
        $discountAmount = ($originalPrice - $price) * $qty;
        $subTotal += $fullPrice;
        $totalDiscountAmount += $discountAmount;
    }
}

$discountPercentOverall = ($subTotal > 0) ? round(($totalDiscountAmount / $subTotal) * 100) : 0;
$total = $subTotal - $totalDiscountAmount;

echo json_encode([
    'subtotal' => number_format($subTotal, 2),
    'discount_percent' => $discountPercentOverall,
    'total' => number_format($total, 2)
]);
