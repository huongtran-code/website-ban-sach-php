<?php
session_start();
include "../db_conn.php";
include "func-transaction.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

if (isset($_POST['type']) && isset($_POST['amount']) && isset($_POST['description'])) {
    $type = $_POST['type'];
    $amount = (float)$_POST['amount'];
    $description = $_POST['description'];

    if (!in_array($type, ['revenue', 'expense'])) {
        header("Location: ../admin-transactions.php?error=Loại giao dịch không hợp lệ");
        exit;
    }

    if ($amount < 1000) {
        header("Location: ../admin-transactions.php?error=Số tiền tối thiểu là 1.000đ");
        exit;
    }

    add_transaction($conn, null, $type, $amount, $description);

    header("Location: ../admin-transactions.php?success=Thêm giao dịch thành công");
    exit;
} else {
    header("Location: ../admin-transactions.php?error=Vui lòng điền đầy đủ thông tin");
    exit;
}
