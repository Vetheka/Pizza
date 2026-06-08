<?php
session_start();

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    if (!empty($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $key => $item) {
            if ($item['id'] == $id) {
                unset($_SESSION['cart'][$key]);
                $_SESSION['cart'] = array_values($_SESSION['cart']);
                break;
            }
        }
    }
}

header('Location: index.php');
exit();
?>
