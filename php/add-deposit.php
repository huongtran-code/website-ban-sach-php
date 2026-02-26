<?php
session_start();
include "../db_conn.php";
include "func-user.php";
include "func-transaction.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

if (isset($_POST['user_id']) && isset($_POST['amount'])) {
    $user_id = (int)$_POST['user_id'];
    $amount = (float)$_POST['amount'];
    $description = $_POST['description'] ?? 'Nạp tiền từ admin';

    if ($amount < 1000) {
        header("Location: ../admin-users.php?error=Số tiền tối thiểu là 1.000đ");
        exit;
    }

    update_user_balance($conn, $user_id, $amount);
    add_transaction($conn, $user_id, 'deposit', $amount, $description);

    header("Location: ../admin-users.php?success=Nạp tiền thành công");
    exit;
} else {
    header("Location: ../admin-users.php?error=Vui lòng điền đầy đủ thông tin");
    exit;
}
