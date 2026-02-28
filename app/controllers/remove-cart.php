<?php
session_start();
require_once __DIR__ . "/../../config/database.php";
include __DIR__ . "/../models/func-cart.php";

if (!isset($_SESSION['customer_id'])) {
    header("Location: ../../pages/login.php");
    exit;
}

if (isset($_GET['id'])) {
    $book_id = (int)$_GET['id'];
    remove_from_cart($conn, $_SESSION['customer_id'], $book_id);
}

header("Location: ../../pages/cart.php");
exit;
