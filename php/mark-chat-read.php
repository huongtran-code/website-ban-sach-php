<?php
session_start();
include "../db_conn.php";
include "func-chat.php";

if (!isset($_SESSION['customer_id']) && !isset($_SESSION['locked_user_id'])) {
    exit;
}

$user_id = $_SESSION['customer_id'] ?? $_SESSION['locked_user_id'];
mark_messages_as_read($conn, $user_id);




