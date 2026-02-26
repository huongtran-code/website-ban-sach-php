<?php
session_start();
include "../db_conn.php";
include "func-cart.php";

if (!isset($_SESSION['customer_id'])) {
    header("Location: ../login.php");
    exit;
}

if (isset($_GET['id'])) {
    $book_id = (int)$_GET['id'];
    remove_from_cart($conn, $_SESSION['customer_id'], $book_id);
}

header("Location: ../cart.php");
exit;
