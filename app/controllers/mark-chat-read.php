<?php
session_start();
require_once __DIR__ . "/../../config/database.php";
include __DIR__ . "/../models/func-chat.php";

if (!isset($_SESSION['customer_id']) && !isset($_SESSION['locked_user_id'])) {
    exit;
}

$user_id = $_SESSION['customer_id'] ?? $_SESSION['locked_user_id'];
mark_messages_as_read($conn, $user_id);




