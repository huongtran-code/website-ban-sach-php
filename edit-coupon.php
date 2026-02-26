<?php
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_email'])) {
    header("Location: adminlogin.php");
    exit;
}

include "db_conn.php";
include "php/func-coupon.php";
include "php/func-book.php";
include "php/func-category.php";

$coupon_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$coupon = $coupon_id > 0 ? get_coupon_by_id($conn, $coupon_id) : null;

if (!$coupon) {
    header("Location: admin-coupons.php?error=Mã khuyến mãi không tồn tại");
    exit;
}

$categories = get_all_categories($conn);
$all_books = get_all_books($conn);

// Parse apply_to_ids
$apply_to_ids = [];
if (!empty($coupon['apply_to_ids'])) {
    $apply_to_ids = json_decode($coupon['apply_to_ids'], true);
    if (!is_array($apply_to_ids)) {
        $apply_to_ids = [];
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sửa mã khuyến mãi - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <header class="header admin-header">
        <div class="header-main">
            <div class="container">
                <a href="admin.php" class="logo">
                    <span class="logo-icon">⚙️</span>
                    <span>Quản trị viên</span>
                </a>
                <div class="header-actions">
                    <a href="index.php"><i class="fas fa-store"></i> Xem cửa hàng</a>
                    <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a>
                </div>
            </div>
        </div>
    </header>

    <?php include "php/admin-nav.php"; ?>

    <div class="container py-4">
        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i><?=htmlspecialchars($_GET['error'])?></div>
        <?php endif; ?>
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle me-2"></i><?=htmlspecialchars($_GET['success'])?></div>
        <?php endif; ?>

        <h2 class="mb-4"><i class="fas fa-edit text-primary me-2"></i>Sửa mã khuyến mãi</h2>

        <div class="row">
            <div class="col-lg-8 mx-auto">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-ticket-alt me-2"></i>Sửa mã: <?=htmlspecialchars($coupon['code'])?></h5>
                    </div>
                    <div class="card-body">
                        <form action="php/edit-coupon.php" method="post">
                            <input type="hidden" name="id" value="<?=$coupon['id']?>">
                            
                            <div class="mb-3">
                                <label class="form-label">Mã khuyến mãi</label>
                                <input type="text" name="code" class="form-control" value="<?=htmlspecialchars($coupon['code'])?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Mô tả</label>
                                <input type="text" name="description" class="form-control" value="<?=htmlspecialchars($coupon['description'] ?? '')?>" placeholder="VD: Giảm 10% cho sách khuyến mãi">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">% Giảm giá</label>
                                <input type="number" name="discount_percent" class="form-control" min="1" max="100" step="1" value="<?=$coupon['discount_percent']?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Áp dụng cho</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="apply_type" id="edit_apply_all" value="all" <?=$coupon['apply_type'] == 'all' ? 'checked' : ''?> onchange="toggleEditApplyOptions()">
                                    <label class="form-check-label" for="edit_apply_all">
                                        Tất cả sách
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="apply_type" id="edit_apply_category" value="category" <?=$coupon['apply_type'] == 'category' ? 'checked' : ''?> onchange="toggleEditApplyOptions()">
                                    <label class="form-check-label" for="edit_apply_category">
                                        Danh mục cụ thể
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="apply_type" id="edit_apply_book" value="book" <?=$coupon['apply_type'] == 'book' ? 'checked' : ''?> onchange="toggleEditApplyOptions()">
                                    <label class="form-check-label" for="edit_apply_book">
                                        Sách cụ thể
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="apply_type" id="edit_apply_promotion" value="promotion" <?=$coupon['apply_type'] == 'promotion' ? 'checked' : ''?> onchange="toggleEditApplyOptions()">
                                    <label class="form-check-label" for="edit_apply_promotion">
                                        Sách trong mục Khuyến mãi
                                    </label>
                                </div>
                            </div>
                            
                            <div class="mb-3" id="editCategorySelect" style="display: <?=$coupon['apply_type'] == 'category' ? 'block' : 'none'?>;">
                                <label class="form-label">Chọn danh mục</label>
                                <select name="apply_to_ids[]" class="form-select" multiple size="5">
                                    <?php if ($categories != 0): ?>
                                        <?php foreach ($categories as $cat): ?>
                                            <option value="<?=$cat['id']?>" <?=in_array($cat['id'], $apply_to_ids) ? 'selected' : ''?>><?=htmlspecialchars($cat['name'])?></option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                                <small class="text-muted">Giữ Ctrl (Windows) hoặc Cmd (Mac) để chọn nhiều danh mục</small>
                            </div>
                            
                            <div class="mb-3" id="editBookSelect" style="display: <?=$coupon['apply_type'] == 'book' ? 'block' : 'none'?>;">
                                <label class="form-label">Chọn sách</label>
                                <select name="apply_to_ids[]" class="form-select" multiple size="5">
                                    <?php if ($all_books != 0): ?>
                                        <?php foreach ($all_books as $book): ?>
                                            <option value="<?=$book['id']?>" <?=in_array($book['id'], $apply_to_ids) ? 'selected' : ''?>><?=htmlspecialchars($book['title'])?></option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                                <small class="text-muted">Giữ Ctrl (Windows) hoặc Cmd (Mac) để chọn nhiều sách</small>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Tổng số lượt sử dụng (toàn hệ thống)</label>
                                <input type="number" name="usage_limit" class="form-control" min="1" value="<?=$coupon['usage_limit'] ?? ''?>" placeholder="Để trống = vô tận">
                                <small class="text-muted">VD: 100 nghĩa là mã này chỉ được dùng 100 lần tổng cộng</small>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Số lượt mỗi user được dùng</label>
                                <input type="number" name="max_usage_per_user" class="form-control" min="1" value="<?=$coupon['max_usage_per_user'] ?? ''?>" placeholder="Để trống = vô tận">
                                <small class="text-muted">VD: 1 nghĩa là mỗi khách chỉ được dùng mã này 1 lần</small>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Ngày hết hạn (tuỳ chọn)</label>
                                <input type="datetime-local" name="expires_at" class="form-control" value="<?=$coupon['expires_at'] ? date('Y-m-d\TH:i', strtotime($coupon['expires_at'])) : ''?>">
                            </div>
                            
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i>Lưu thay đổi
                                </button>
                                <a href="admin-coupons.php" class="btn btn-secondary">
                                    <i class="fas fa-times me-1"></i>Hủy
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function toggleEditApplyOptions() {
            const applyType = document.querySelector('input[name="apply_type"]:checked').value;
            const categorySelect = document.getElementById('editCategorySelect');
            const bookSelect = document.getElementById('editBookSelect');
            
            if (applyType === 'category') {
                categorySelect.style.display = 'block';
                bookSelect.style.display = 'none';
            } else if (applyType === 'book') {
                categorySelect.style.display = 'none';
                bookSelect.style.display = 'block';
            } else {
                categorySelect.style.display = 'none';
                bookSelect.style.display = 'none';
            }
        }
    </script>
</body>
</html>




