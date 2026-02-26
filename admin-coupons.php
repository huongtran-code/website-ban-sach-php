<?php  
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_email'])) {
    header("Location: login.php");
    exit;
}

include "db_conn.php";
include "php/func-coupon.php";
include "php/func-book.php";
include "php/func-author.php";
include "php/func-category.php";

// Tự động import database update nếu chưa có cột usage_limit
try {
    $stmt = $conn->query("SELECT usage_limit FROM coupons LIMIT 1");
} catch (PDOException $e) {
    // Nếu chưa có cột, import SQL
    if (file_exists('database_coupon_usage_update.sql')) {
        $sql_content = file_get_contents('database_coupon_usage_update.sql');
        $statements = array_filter(array_map('trim', explode(';', $sql_content)));
        foreach ($statements as $stmt) {
            if (!empty($stmt) && stripos($stmt, 'USE ') === false) {
                try {
                    $conn->exec($stmt);
                } catch (PDOException $ex) {
                    // Bỏ qua lỗi duplicate
                }
            }
        }
    }
}

// Tự động thêm cột max_usage_per_user nếu chưa có
try {
    $stmt = $conn->query("SELECT max_usage_per_user FROM coupons LIMIT 1");
} catch (PDOException $e) {
    try {
        $conn->exec("ALTER TABLE coupons ADD COLUMN max_usage_per_user INT NULL COMMENT 'Số lần tối đa mỗi user có thể dùng, NULL = vô tận'");
    } catch (PDOException $ex) {
        // Bỏ qua nếu đã tồn tại
    }
}

// Tự động thêm cột discount_type nếu chưa có
try {
    $stmt = $conn->query("SELECT discount_type FROM coupons LIMIT 1");
} catch (PDOException $e) {
    try {
        $conn->exec("ALTER TABLE coupons ADD COLUMN discount_type ENUM('percent', 'freeship') DEFAULT 'percent' COMMENT 'Loại giảm giá: percent hoặc freeship'");
    } catch (PDOException $ex) {
        // Bỏ qua nếu đã tồn tại
    }
}

// Tự động tạo bảng promotions nếu chưa có
try {
    $stmt = $conn->query("SELECT 1 FROM promotions LIMIT 1");
} catch (PDOException $e) {
    try {
        $conn->exec("CREATE TABLE IF NOT EXISTS promotions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            description TEXT,
            discount_percent INT NOT NULL DEFAULT 0,
            start_date DATETIME NOT NULL,
            end_date DATETIME NOT NULL,
            is_active TINYINT(1) DEFAULT 1,
            banner_image VARCHAR(255) DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    } catch (PDOException $ex) {
        // Bỏ qua nếu đã tồn tại
    }
}

// Tự động tạo bảng promotion_books nếu chưa có
try {
    $stmt = $conn->query("SELECT 1 FROM promotion_books LIMIT 1");
} catch (PDOException $e) {
    try {
        $conn->exec("CREATE TABLE IF NOT EXISTS promotion_books (
            id INT AUTO_INCREMENT PRIMARY KEY,
            promotion_id INT NOT NULL,
            book_id INT NOT NULL,
            custom_discount_percent INT DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_promotion_id (promotion_id),
            INDEX idx_book_id (book_id),
            UNIQUE KEY unique_promo_book (promotion_id, book_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    } catch (PDOException $ex) {
        // Bỏ qua nếu đã tồn tại
    }
}

include "php/func-promotion.php";
$promotions = get_all_promotions($conn);

$coupons = get_all_coupons($conn);
$promotion_books = get_promotion_books($conn, 1000); // Lấy tất cả sách khuyến mãi
$authors = get_all_author($conn);
$categories = get_all_categories($conn);
$all_books = get_all_books($conn); // Lấy tất cả sách để chọn
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý khuyến mãi - Admin</title>
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
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle me-2"></i><?=htmlspecialchars($_GET['success'])?></div>
        <?php endif; ?>
        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i><?=htmlspecialchars($_GET['error'])?></div>
        <?php endif; ?>

        <h2 class="mb-4"><i class="fas fa-tags text-primary"></i> Quản lý khuyến mãi</h2>

        <!-- Tabs -->
        <ul class="nav nav-tabs mb-4" id="promotionTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="coupons-tab" data-bs-toggle="tab" data-bs-target="#coupons" type="button" role="tab">
                    <i class="fas fa-ticket-alt me-1"></i>Mã khuyến mãi
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="programs-tab" data-bs-toggle="tab" data-bs-target="#programs" type="button" role="tab">
                    <i class="fas fa-gift me-1"></i>Chương trình KM
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="books-tab" data-bs-toggle="tab" data-bs-target="#books" type="button" role="tab">
                    <i class="fas fa-book me-1"></i>Sách khuyến mãi
                </button>
            </li>
        </ul>

        <div class="tab-content" id="promotionTabsContent">
            <!-- Tab Mã khuyến mãi -->
            <div class="tab-pane fade show active" id="coupons" role="tabpanel">
                <div class="row">
                    <div class="col-lg-5">
                        <div class="card mb-4">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0"><i class="fas fa-plus-circle me-2"></i>Thêm mã khuyến mãi</h5>
                            </div>
                            <div class="card-body">
                                <form action="php/add-coupon.php" method="post">
                                    <div class="mb-3">
                                        <label class="form-label">Mã khuyến mãi</label>
                                        <input type="text" name="code" class="form-control" placeholder="VD: SALE10" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Mô tả</label>
                                        <input type="text" name="description" class="form-control" placeholder="VD: Giảm 10% cho sách khuyến mãi">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Loại mã</label>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="discount_type" id="type_percent" value="percent" checked onchange="toggleDiscountType()">
                                            <label class="form-check-label" for="type_percent">
                                                <i class="fas fa-percent text-danger"></i> Giảm giá %
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="discount_type" id="type_freeship" value="freeship" onchange="toggleDiscountType()">
                                            <label class="form-check-label" for="type_freeship">
                                                <i class="fas fa-truck text-success"></i> Miễn phí vận chuyển
                                            </label>
                                        </div>
                                    </div>
                                    <div class="mb-3" id="discountPercentGroup">
                                        <label class="form-label">% Giảm giá</label>
                                        <input type="number" name="discount_percent" class="form-control" min="1" max="100" step="1" value="10">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Áp dụng cho</label>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="apply_type" id="apply_all" value="all" checked onchange="toggleApplyOptions()">
                                            <label class="form-check-label" for="apply_all">
                                                Tất cả sách
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="apply_type" id="apply_category" value="category" onchange="toggleApplyOptions()">
                                            <label class="form-check-label" for="apply_category">
                                                Danh mục cụ thể
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="apply_type" id="apply_book" value="book" onchange="toggleApplyOptions()">
                                            <label class="form-check-label" for="apply_book">
                                                Sách cụ thể
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="apply_type" id="apply_promotion" value="promotion" onchange="toggleApplyOptions()">
                                            <label class="form-check-label" for="apply_promotion">
                                                Sách trong mục Khuyến mãi
                                            </label>
                                        </div>
                                    </div>
                                    <div class="mb-3" id="categorySelect" style="display: none;">
                                        <label class="form-label">Chọn danh mục</label>
                                        <select name="apply_to_ids[]" class="form-select" multiple size="5">
                                            <?php if ($categories != 0): ?>
                                                <?php foreach ($categories as $cat): ?>
                                                    <option value="<?=$cat['id']?>"><?=htmlspecialchars($cat['name'])?></option>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </select>
                                        <small class="text-muted">Giữ Ctrl (Windows) hoặc Cmd (Mac) để chọn nhiều danh mục</small>
                                    </div>
                                    <div class="mb-3" id="bookSelect" style="display: none;">
                                        <label class="form-label">Chọn sách</label>
                                        <select name="apply_to_ids[]" class="form-select" multiple size="5">
                                            <?php if ($all_books != 0): ?>
                                                <?php foreach ($all_books as $book): ?>
                                                    <option value="<?=$book['id']?>"><?=htmlspecialchars($book['title'])?></option>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </select>
                                        <small class="text-muted">Giữ Ctrl (Windows) hoặc Cmd (Mac) để chọn nhiều sách</small>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Tổng số lượt sử dụng (toàn hệ thống)</label>
                                        <input type="number" name="usage_limit" class="form-control" min="1" placeholder="Để trống = vô tận">
                                        <small class="text-muted">VD: 100 nghĩa là mã này chỉ được dùng 100 lần tổng cộng</small>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Số lượt mỗi user được dùng</label>
                                        <input type="number" name="max_usage_per_user" class="form-control" min="1" placeholder="Để trống = vô tận">
                                        <small class="text-muted">VD: 1 nghĩa là mỗi khách chỉ được dùng mã này 1 lần</small>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Ngày hết hạn (tuỳ chọn)</label>
                                        <input type="datetime-local" name="expires_at" class="form-control">
                                    </div>
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="fas fa-save me-1"></i>Lưu mã
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-7">
                        <div class="card">
                            <div class="card-header bg-secondary text-white">
                                <h5 class="mb-0"><i class="fas fa-tags me-2"></i>Danh sách mã khuyến mãi</h5>
                            </div>
                            <div class="card-body p-0">
                                <?php if ($coupons == 0): ?>
                                    <div class="text-center py-4">
                                        <p class="text-muted mb-0">Chưa có mã khuyến mãi nào</p>
                                    </div>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover mb-0">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Mã</th>
                                                    <th>Loại</th>
                                                    <th>Giảm</th>
                                                    <th>Áp dụng</th>
                                                    <th>Lượt dùng</th>
                                                    <th>Trạng thái</th>
                                                    <th>Hết hạn</th>
                                                    <th class="text-end">Thao tác</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($coupons as $c): ?>
                                                    <tr>
                                                        <td>
                                                            <strong class="text-primary"><?=htmlspecialchars($c['code'])?></strong>
                                                            <?php if (!empty($c['description'])): ?>
                                                                <br><small class="text-muted"><?=htmlspecialchars($c['description'])?></small>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <?php $discount_type = $c['discount_type'] ?? 'percent'; ?>
                                                            <?php if ($discount_type === 'freeship'): ?>
                                                                <span class="badge bg-success"><i class="fas fa-truck"></i> Freeship</span>
                                                            <?php else: ?>
                                                                <span class="badge bg-danger"><i class="fas fa-percent"></i> Giảm giá</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <?php if ($discount_type === 'freeship'): ?>
                                                                <span class="text-success">100% ship</span>
                                                            <?php else: ?>
                                                                <span class="badge bg-danger"><?=$c['discount_percent']?>%</span>
                                                            <?php endif; ?>
                                                        </td>
                                                    <td>
                                                        <?php 
                                                        $apply_type = $c['apply_type'] ?? ($c['apply_to_promotion_only'] ? 'promotion' : 'all');
                                                        $apply_type_labels = [
                                                            'all' => ['text' => 'Tất cả sách', 'class' => 'bg-info text-dark'],
                                                            'category' => ['text' => 'Danh mục', 'class' => 'bg-primary text-white'],
                                                            'book' => ['text' => 'Sách cụ thể', 'class' => 'bg-success text-white'],
                                                            'promotion' => ['text' => 'Sách khuyến mãi', 'class' => 'bg-warning text-dark']
                                                        ];
                                                        $label = $apply_type_labels[$apply_type] ?? $apply_type_labels['all'];
                                                        ?>
                                                        <span class="badge <?=$label['class']?>"><?=$label['text']?></span>
                                                        <?php if (($apply_type === 'category' || $apply_type === 'book') && !empty($c['apply_to_ids'])): 
                                                            $apply_ids = json_decode($c['apply_to_ids'], true);
                                                            if (is_array($apply_ids) && count($apply_ids) > 0):
                                                                if ($apply_type === 'category'):
                                                                    $selected_cats = [];
                                                                    foreach ($categories as $cat) {
                                                                        if (in_array($cat['id'], $apply_ids)) {
                                                                            $selected_cats[] = $cat['name'];
                                                                        }
                                                                    }
                                                                    if (count($selected_cats) > 0):
                                                                        echo '<br><small class="text-muted">' . htmlspecialchars(implode(', ', array_slice($selected_cats, 0, 3)));
                                                                        if (count($selected_cats) > 3) echo '...';
                                                                        echo '</small>';
                                                                    endif;
                                                                elseif ($apply_type === 'book'):
                                                                    $selected_books = [];
                                                                    foreach ($all_books as $book) {
                                                                        if (in_array($book['id'], $apply_ids)) {
                                                                            $selected_books[] = $book['title'];
                                                                        }
                                                                    }
                                                                    if (count($selected_books) > 0):
                                                                        echo '<br><small class="text-muted">' . htmlspecialchars(implode(', ', array_slice($selected_books, 0, 2)));
                                                                        if (count($selected_books) > 2) echo '...';
                                                                        echo '</small>';
                                                                    endif;
                                                                endif;
                                                            endif;
                                                        endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php 
                                                        $usage_count = $c['usage_count'] ?? 0;
                                                        $usage_limit = $c['usage_limit'] ?? null;
                                                        $max_per_user = $c['max_usage_per_user'] ?? null;
                                                        
                                                        // Hiển thị tổng lượt dùng
                                                        if ($usage_limit === null):
                                                            echo '<span class="badge bg-info" title="Tổng lượt">∞</span>';
                                                        else:
                                                            $remaining = max(0, $usage_limit - $usage_count);
                                                            echo '<span class="badge ' . ($remaining > 0 ? 'bg-success' : 'bg-danger') . '" title="Tổng lượt">' . $usage_count . '/' . $usage_limit . '</span>';
                                                        endif;
                                                        
                                                        // Hiển thị giới hạn per-user
                                                        if ($max_per_user !== null):
                                                            echo ' <span class="badge bg-warning text-dark" title="Mỗi user">' . $max_per_user . '/user</span>';
                                                        endif;
                                                        ?>
                                                    </td>
                                                        <td>
                                                            <?php if ($c['is_active']): ?>
                                                                <span class="badge bg-success">Đang dùng</span>
                                                            <?php else: ?>
                                                                <span class="badge bg-secondary">Tạm tắt</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <?php if ($c['expires_at']): ?>
                                                                <small><?=date('d/m/Y H:i', strtotime($c['expires_at']))?></small>
                                                            <?php else: ?>
                                                                <span class="text-muted">Không giới hạn</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td class="text-end">
                                                            <a href="edit-coupon.php?id=<?=$c['id']?>" class="btn btn-sm btn-outline-primary" title="Sửa">
                                                                <i class="fas fa-edit"></i>
                                                            </a>
                                                            <form action="php/toggle-coupon.php" method="post" class="d-inline">
                                                                <input type="hidden" name="id" value="<?=$c['id']?>">
                                                                <input type="hidden" name="is_active" value="<?=$c['is_active'] ? 0 : 1?>">
                                                                <button type="submit" class="btn btn-sm <?=$c['is_active'] ? 'btn-outline-secondary' : 'btn-outline-success'?>" title="<?=$c['is_active'] ? 'Tạm tắt' : 'Kích hoạt'?>">
                                                                    <i class="fas <?=$c['is_active'] ? 'fa-pause' : 'fa-play'?>"></i>
                                                                </button>
                                                            </form>
                                                            <form action="php/delete-coupon.php" method="post" class="d-inline" onsubmit="return confirm('Bạn có chắc muốn xóa mã khuyến mãi này?');">
                                                                <input type="hidden" name="id" value="<?=$c['id']?>">
                                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Xóa">
                                                                    <i class="fas fa-trash"></i>
                                                                </button>
                                                            </form>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab Chương trình khuyến mãi -->
            <div class="tab-pane fade" id="programs" role="tabpanel">
                <div class="row">
                    <div class="col-lg-5">
                        <div class="card mb-4">
                            <div class="card-header bg-success text-white">
                                <h5 class="mb-0"><i class="fas fa-plus-circle me-2"></i>Tạo chương trình mới</h5>
                            </div>
                            <div class="card-body">
                                <form action="php/add-promotion.php" method="post" enctype="multipart/form-data">
                                    <div class="mb-3">
                                        <label class="form-label">Tên chương trình</label>
                                        <input type="text" name="name" class="form-control" placeholder="VD: Flash Sale Tháng 1" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Mô tả</label>
                                        <textarea name="description" class="form-control" rows="2" placeholder="VD: Giảm giá lớn cho tất cả sách văn học"></textarea>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">% Giảm giá mặc định</label>
                                        <input type="number" name="discount_percent" class="form-control" min="1" max="100" value="20" required>
                                        <small class="text-muted">Áp dụng cho tất cả sách trong chương trình (có thể tùy chỉnh riêng từng sách)</small>
                                    </div>
                                    <div class="row">
                                        <div class="col-6">
                                            <div class="mb-3">
                                                <label class="form-label">Ngày bắt đầu</label>
                                                <input type="datetime-local" name="start_date" class="form-control" required>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="mb-3">
                                                <label class="form-label">Ngày kết thúc</label>
                                                <input type="datetime-local" name="end_date" class="form-control" required>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Ảnh banner (tuỳ chọn)</label>
                                        <input type="file" name="banner" class="form-control" accept="image/*">
                                    </div>
                                    <button type="submit" class="btn btn-success w-100">
                                        <i class="fas fa-save me-1"></i>Tạo chương trình
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-7">
                        <div class="card">
                            <div class="card-header bg-secondary text-white">
                                <h5 class="mb-0"><i class="fas fa-gift me-2"></i>Danh sách chương trình</h5>
                            </div>
                            <div class="card-body p-0">
                                <?php if ($promotions == 0): ?>
                                    <div class="text-center py-4">
                                        <p class="text-muted mb-0">Chưa có chương trình khuyến mãi nào</p>
                                    </div>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover mb-0">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Tên chương trình</th>
                                                    <th>Giảm</th>
                                                    <th>Thời gian</th>
                                                    <th>Trạng thái</th>
                                                    <th class="text-end">Thao tác</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($promotions as $promo): 
                                                    $now = new DateTime();
                                                    $start = new DateTime($promo['start_date']);
                                                    $end = new DateTime($promo['end_date']);
                                                    $is_running = $promo['is_active'] && $now >= $start && $now <= $end;
                                                    $is_upcoming = $promo['is_active'] && $now < $start;
                                                    $is_expired = $now > $end;
                                                ?>
                                                    <tr>
                                                        <td>
                                                            <strong><?=htmlspecialchars($promo['name'])?></strong>
                                                            <?php if (!empty($promo['description'])): ?>
                                                                <br><small class="text-muted"><?=htmlspecialchars($promo['description'])?></small>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td><span class="badge bg-danger"><?=$promo['discount_percent']?>%</span></td>
                                                        <td>
                                                            <small>
                                                                <?=date('d/m/Y H:i', strtotime($promo['start_date']))?><br>
                                                                → <?=date('d/m/Y H:i', strtotime($promo['end_date']))?>
                                                            </small>
                                                        </td>
                                                        <td>
                                                            <?php if (!$promo['is_active']): ?>
                                                                <span class="badge bg-secondary">Tạm tắt</span>
                                                            <?php elseif ($is_expired): ?>
                                                                <span class="badge bg-dark">Đã kết thúc</span>
                                                            <?php elseif ($is_running): ?>
                                                                <span class="badge bg-success">Đang chạy</span>
                                                            <?php elseif ($is_upcoming): ?>
                                                                <span class="badge bg-info">Sắp diễn ra</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td class="text-end">
                                                            <a href="edit-promotion.php?id=<?=$promo['id']?>" class="btn btn-sm btn-outline-primary" title="Sửa & Thêm sách">
                                                                <i class="fas fa-edit"></i>
                                                            </a>
                                                            <form action="php/toggle-promotion.php" method="post" class="d-inline">
                                                                <input type="hidden" name="id" value="<?=$promo['id']?>">
                                                                <input type="hidden" name="is_active" value="<?=$promo['is_active'] ? 0 : 1?>">
                                                                <button type="submit" class="btn btn-sm <?=$promo['is_active'] ? 'btn-outline-secondary' : 'btn-outline-success'?>">
                                                                    <i class="fas <?=$promo['is_active'] ? 'fa-pause' : 'fa-play'?>"></i>
                                                                </button>
                                                            </form>
                                                            <form action="php/delete-promotion.php" method="post" class="d-inline" onsubmit="return confirm('Bạn có chắc muốn xóa chương trình này?');">
                                                                <input type="hidden" name="id" value="<?=$promo['id']?>">
                                                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                                                    <i class="fas fa-trash"></i>
                                                                </button>
                                                            </form>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab Sách khuyến mãi -->
            <div class="tab-pane fade" id="books" role="tabpanel">
                <div class="card">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0"><i class="fas fa-book me-2"></i>Danh sách sách đang khuyến mãi</h5>
                    </div>
                    <div class="card-body p-0">
                        <?php if ($promotion_books == 0): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-book" style="font-size: 60px; color: #ddd;"></i>
                                <p class="text-muted mt-3 mb-0">Chưa có sách nào trong chương trình khuyến mãi</p>
                                <a href="admin-books.php?tab=add" class="btn btn-primary mt-3">
                                    <i class="fas fa-plus me-1"></i>Thêm sách khuyến mãi
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 80px;">Ảnh</th>
                                            <th>Tên sách</th>
                                            <th>Tác giả</th>
                                            <th>Giá gốc</th>
                                            <th>% Giảm</th>
                                            <th>Giá sau giảm</th>
                                            <th>Tồn kho</th>
                                            <th class="text-end">Thao tác</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($promotion_books as $book): 
                                            $author_name = "Không rõ";
                                            if ($authors != 0) {
                                                foreach ($authors as $a) {
                                                    if ($a['id'] == $book['author_id']) { 
                                                        $author_name = $a['name']; 
                                                        break; 
                                                    }
                                                }
                                            }
                                            $price = isset($book['price']) ? (float)$book['price'] : 0;
                                            $discount = isset($book['discount_percent']) ? (int)$book['discount_percent'] : 0;
                                            $final_price = $price * (100 - $discount) / 100;
                                        ?>
                                            <tr>
                                                <td>
                                                    <img src="uploads/cover/<?=$book['cover']?>" alt="<?=htmlspecialchars($book['title'])?>" style="width: 60px; height: 80px; object-fit: cover; border-radius: 4px;" onerror="this.src='https://via.placeholder.com/60x80'">
                                                </td>
                                                <td>
                                                    <strong><?=htmlspecialchars($book['title'])?></strong>
                                                </td>
                                                <td><small><?=htmlspecialchars($author_name)?></small></td>
                                                <td><small class="text-muted text-decoration-line-through"><?=format_price($price)?></small></td>
                                                <td>
                                                    <span class="badge bg-danger">-<?=$discount?>%</span>
                                                </td>
                                                <td>
                                                    <strong class="text-success"><?=format_price($final_price)?></strong>
                                                </td>
                                                <td>
                                                    <?php if ($book['stock'] > 0): ?>
                                                        <span class="badge bg-success"><?=$book['stock']?> cuốn</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-danger">Hết hàng</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-end">
                                                    <a href="edit-book.php?id=<?=$book['id']?>" class="btn btn-sm btn-outline-primary" title="Sửa sách">
                                                        <i class="fas fa-edit"></i> Sửa
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleApplyOptions() {
            const applyType = document.querySelector('input[name="apply_type"]:checked').value;
            const categorySelect = document.getElementById('categorySelect');
            const bookSelect = document.getElementById('bookSelect');
            
            categorySelect.style.display = (applyType === 'category') ? 'block' : 'none';
            bookSelect.style.display = (applyType === 'book') ? 'block' : 'none';
            
            // Clear selections when switching
            if (applyType !== 'category') {
                document.querySelector('#categorySelect select').selectedIndex = -1;
            }
            if (applyType !== 'book') {
                document.querySelector('#bookSelect select').selectedIndex = -1;
            }
        }
        
        function toggleDiscountType() {
            const discountType = document.querySelector('input[name="discount_type"]:checked').value;
            const discountPercentGroup = document.getElementById('discountPercentGroup');
            
            if (discountType === 'freeship') {
                discountPercentGroup.style.display = 'none';
            } else {
                discountPercentGroup.style.display = 'block';
            }
        }
    </script>
</body>
</html>
