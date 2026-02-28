<?php
session_start();
require_once __DIR__ . "/../../config/database.php";
include __DIR__ . "/../models/func-promotion.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../pages/adminlogin.php");
    exit;
}

if (isset($_POST['id']) && isset($_POST['name'])) {
    $id = (int)$_POST['id'];
    $name = trim($_POST['name']);
    $description = trim($_POST['description'] ?? '');
    $discount_percent = (int)$_POST['discount_percent'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $banner_image = null;

    if (empty($name)) {
        header("Location: ../../pages/edit-promotion.php?id=$id&error=Vui lòng nhập tên chương trình");
        exit;
    }

    if ($discount_percent < 1 || $discount_percent > 100) {
        header("Location: ../../pages/edit-promotion.php?id=$id&error=% giảm giá phải từ 1-100");
        exit;
    }

    if (strtotime($end_date) <= strtotime($start_date)) {
        header("Location: ../../pages/edit-promotion.php?id=$id&error=Ngày kết thúc phải sau ngày bắt đầu");
        exit;
    }

    // Xử lý upload banner
    if (isset($_FILES['banner']) && $_FILES['banner']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = __DIR__ . '/../../storage/uploads/banners/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_ext = strtolower(pathinfo($_FILES['banner']['name'], PATHINFO_EXTENSION));
        $allowed_ext = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        if (in_array($file_ext, $allowed_ext)) {
            $banner_image = 'promo_' . time() . '.' . $file_ext;
            move_uploaded_file($_FILES['banner']['tmp_name'], $upload_dir . $banner_image);
        }
    }

    try {
        if (update_promotion($conn, $id, $name, $description, $discount_percent, $start_date, $end_date, $banner_image)) {
            header("Location: ../../pages/edit-promotion.php?id=$id&success=Cập nhật chương trình thành công");
        } else {
            header("Location: ../../pages/edit-promotion.php?id=$id&error=Không thể cập nhật chương trình");
        }
    } catch (PDOException $e) {
        header("Location: ../../pages/edit-promotion.php?id=$id&error=Lỗi: " . urlencode($e->getMessage()));
    }
    exit;
}

header("Location: ../../pages/admin-coupons.php#programs");
exit;
