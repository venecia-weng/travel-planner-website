<?php
session_start();

if (isset($_POST['add_to_cart'])) {
    $item_id = $_POST['item_id'];
    $quantity = $_POST['quantity'];

    if (isset($_SESSION['cart'][$item_id])) {
        $_SESSION['cart'][$item_id] += $quantity;
    } else {
        $_SESSION['cart'][$item_id] = $quantity;
    }

    echo array_sum($_SESSION['cart']);
}