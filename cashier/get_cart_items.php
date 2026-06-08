<?php
session_start();
$cartHTML = '';
$subTotal = 0;
$totalDiscountAmount = 0;

if (!empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $price = $item['price'] ?? 0;
        $originalPrice = $item['original_price'] ?? $price;
        $discountPercent = ($originalPrice > $price) ? round(100 - ($price / $originalPrice * 100)) : 0;
        $qty = $item['quantity'] ?? 0;
        $fullPrice = $originalPrice * $qty;
        $discountAmount = ($originalPrice - $price) * $qty;
        $subTotal += $fullPrice;
        $totalDiscountAmount += $discountAmount;

        $cartHTML .= '<div class="cart-item" style="display:flex; align-items:center; padding:8px; border-bottom:1px solid #ccc;">';
        $cartHTML .= '<div class="item-name" style="flex:2;">' . htmlspecialchars($item['name']) . '</div>';
         $cartHTML .= '<div class="item-fullprice" style="flex:1; text-align:right;">$' . number_format($fullPrice, 2) . '</div>';
      
        $cartHTML .= '<div class="item-discount" style="flex:1; text-align:center;">' . $discountPercent . '%</div>';
        $cartHTML .= '<div class="item-quantity" style="flex:1; text-align:center;">' . $qty . '</div>';
        $cartHTML .= '<div class="item-price" style="flex:1; text-align:right;">$' . number_format($price, 2) . '</div>';
       
        // Remove button with data-id attribute (make sure your cart items have an 'id')
        $cartHTML .= '<div class="item-remove" style="flex:0.5; text-align:center;">';
      $cartHTML .= '<a href="remove_from_cart.php?id=' . urlencode($item['id']) . '" style="color:red; text-decoration:none; margin-left:10px;">x</a>';
        $cartHTML .= '</div>';

        $cartHTML .= '</div>';

        
    }
} else {
  //  $cartHTML .= '<p>Your cart is empty.</p>';
}
echo $cartHTML;
?>
