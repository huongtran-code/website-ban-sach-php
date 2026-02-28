<?php
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_email'])) {
    header("Location: adminlogin.php");
    exit;
}

require_once __DIR__ . "/../config/bootstrap.php";
include MODELS_PATH . "func-settings.php";

$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cod_fee_percent = trim($_POST['cod_fee_percent'] ?? '2');
    $momo_qr_url = trim($_POST['momo_qr_url'] ?? '');
    $zalopay_qr_url = trim($_POST['zalopay_qr_url'] ?? '');
    
    // Xử lý upload ảnh QR
    $upload_dir = __DIR__ . '/uploads/qr/';
    $upload_base_url = 'uploads/qr/';
    if (!is_dir($upload_dir)) {
        @mkdir($upload_dir, 0777, true);
    }
    $allowed_ext = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    // MoMo QR
    if (isset($_FILES['momo_qr_file']) && $_FILES['momo_qr_file']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['momo_qr_file']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, $allowed_ext)) {
            $file_name = 'momo_qr_' . time() . '_' . uniqid() . '.' . $ext;
            $target_path = $upload_dir . $file_name;
            if (move_uploaded_file($_FILES['momo_qr_file']['tmp_name'], $target_path)) {
                $momo_qr_url = $upload_base_url . $file_name;
            }
        }
    }

    // ZaloPay QR
    if (isset($_FILES['zalopay_qr_file']) && $_FILES['zalopay_qr_file']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['zalopay_qr_file']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, $allowed_ext)) {
            $file_name = 'zalopay_qr_' . time() . '_' . uniqid() . '.' . $ext;
            $target_path = $upload_dir . $file_name;
            if (move_uploaded_file($_FILES['zalopay_qr_file']['tmp_name'], $target_path)) {
                $zalopay_qr_url = $upload_base_url . $file_name;
            }
        }
    }
    
    // Rental settings
    $rental_auto_extend = isset($_POST['rental_auto_extend']) ? '1' : '0';
    $rental_max_late = trim($_POST['rental_max_late'] ?? '3');
    $rental_late_fee_percent = trim($_POST['rental_late_fee_percent'] ?? '10');
    
    try {
        update_setting($conn, 'cod_fee_percent', $cod_fee_percent, 'Phí COD (% giá trị đơn hàng sau giảm giá)');
        update_setting($conn, 'momo_qr_url', $momo_qr_url, 'URL ảnh QR thanh toán MoMo (demo)');
        update_setting($conn, 'zalopay_qr_url', $zalopay_qr_url, 'URL ảnh QR thanh toán ZaloPay (demo)');
        update_setting($conn, 'rental_auto_extend', $rental_auto_extend, 'Tự động gia hạn thuê sách');
        update_setting($conn, 'rental_max_late', $rental_max_late, 'Số lần trễ hạn tối đa');
        update_setting($conn, 'rental_late_fee_percent', $rental_late_fee_percent, 'Phí phạt trễ hạn (%)');
        
        header("Location: admin-settings.php?success=Cập nhật cài đặt thành công");
        exit;
    } catch (Exception $e) {
        $error = "Có lỗi xảy ra: " . $e->getMessage();
    }
}

$cod_fee_percent = (float)get_setting($conn, 'cod_fee_percent', 2);
$momo_qr_url = get_setting($conn, 'momo_qr_url', '');
$zalopay_qr_url = get_setting($conn, 'zalopay_qr_url', '');
$rental_auto_extend = get_setting($conn, 'rental_auto_extend', '1');
$rental_max_late = get_setting($conn, 'rental_max_late', '3');
$rental_late_fee_percent = get_setting($conn, 'rental_late_fee_percent', '10');
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cài đặt hệ thống - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../public/css/style.css">
</head>
<body>
<?php include VIEWS_PATH . "admin-nav.php"; ?>

<div class="container py-4">
    <h2 class="mb-4"><i class="fas fa-cog text-primary me-2"></i>Cài đặt hệ thống</h2>
    
    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle me-2"></i><?=htmlspecialchars($success)?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-triangle me-2"></i><?=htmlspecialchars($error)?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <form method="post" class="card shadow-sm" enctype="multipart/form-data">
        <div class="card-body">
            <h5 class="mb-3"><i class="fas fa-truck me-2"></i>Phí COD</h5>
            <div class="row mb-4">
                <div class="col-md-4">
                    <label class="form-label">Phí COD (%)</label>
                    <div class="input-group">
                        <input type="number" name="cod_fee_percent" class="form-control" min="0" max="50" step="0.1" value="<?=htmlspecialchars($cod_fee_percent)?>">
                        <span class="input-group-text">%</span>
                    </div>
                    <small class="text-muted">Tính trên giá trị đơn hàng sau giảm giá.</small>
                </div>
            </div>

            <hr>

            <h5 class="mb-3"><i class="fas fa-qrcode me-2"></i>QR thanh toán MoMo / ZaloPay (Demo)</h5>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">URL ảnh QR MoMo</label>
                    <input type="text" name="momo_qr_url" class="form-control mb-2" placeholder="https://..." value="<?=htmlspecialchars($momo_qr_url)?>">
                    <label class="form-label">Hoặc upload ảnh QR MoMo</label>
                    <input type="file" name="momo_qr_file" class="form-control" accept="image/*">
                    <small class="text-muted">Có thể dán URL trực tiếp hoặc upload ảnh (jpg, png, gif, webp).</small>
                    <?php if ($momo_qr_url): ?>
                        <div class="mt-2">
                            <img src="<?=htmlspecialchars($momo_qr_url)?>" alt="MoMo QR" class="img-fluid rounded border">
                        </div>
                    <?php endif; ?>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">URL ảnh QR ZaloPay</label>
                    <input type="text" name="zalopay_qr_url" class="form-control mb-2" placeholder="https://..." value="<?=htmlspecialchars($zalopay_qr_url)?>">
                    <label class="form-label">Hoặc upload ảnh QR ZaloPay</label>
                    <input type="file" name="zalopay_qr_file" class="form-control" accept="image/*">
                    <small class="text-muted">Có thể dán URL trực tiếp hoặc upload ảnh (jpg, png, gif, webp).</small>
                    <?php if ($zalopay_qr_url): ?>
                        <div class="mt-2">
                            <img src="<?=htmlspecialchars($zalopay_qr_url)?>" alt="ZaloPay QR" class="img-fluid rounded border">
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <hr>
            
            <h5 class="mb-3"><i class="fas fa-book-reader me-2"></i>Cài đặt thuê sách</h5>
            <div class="row">
                <div class="col-md-4 mb-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="rental_auto_extend" name="rental_auto_extend" value="1" <?=$rental_auto_extend == '1' ? 'checked' : ''?>>
                        <label class="form-check-label" for="rental_auto_extend">
                            <strong>Tự động gia hạn</strong>
                        </label>
                    </div>
                    <small class="text-muted">Khi bật, hệ thống sẽ tự động gia hạn cho sách quá hạn nếu user đã chọn tùy chọn này.</small>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Số lần trễ hạn tối đa</label>
                    <input type="number" name="rental_max_late" class="form-control" min="1" max="10" value="<?=htmlspecialchars($rental_max_late)?>">
                    <small class="text-muted">Vượt quá số này sẽ hiển thị cảnh báo.</small>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Phí phạt trễ hạn (%)</label>
                    <div class="input-group">
                        <input type="number" name="rental_late_fee_percent" class="form-control" min="0" max="100" value="<?=htmlspecialchars($rental_late_fee_percent)?>">
                        <span class="input-group-text">%</span>
                    </div>
                    <small class="text-muted">Tính trên giá thuê/ngày.</small>
                </div>
            </div>
        </div>
        <div class="card-footer text-end">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save me-1"></i>Lưu cài đặt
            </button>
        </div>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>


