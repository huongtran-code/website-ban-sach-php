<?php  
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_email'])) {
    header("Location: login.php");
    exit;
}

require_once __DIR__ . "/../config/bootstrap.php";
include MODELS_PATH . "func-book.php";
include MODELS_PATH . "func-category.php";
include MODELS_PATH . "func-author.php";

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$book = get_book_by_id($conn, $id);

if (!$book) {
    header("Location: admin.php?error=Không tìm thấy sách");
    exit;
}

$categories = get_all_categories($conn);
$authors = get_all_author($conn);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sửa sách - Quản trị</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../public/css/style.css">
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

    <?php include VIEWS_PATH . "admin-nav.php"; ?>

    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header bg-warning">
                        <h5 class="mb-0"><i class="fas fa-edit me-2"></i>Sửa sách</h5>
                    </div>
                    <div class="card-body p-4">
                        <?php if (isset($_GET['error'])): ?>
                            <div class="alert alert-danger"><?=htmlspecialchars($_GET['error'])?></div>
                        <?php endif; ?>

                        <form action="../app/controllers/edit-book.php" method="post" enctype="multipart/form-data">
                            <input type="hidden" name="book_id" value="<?=$book['id']?>">
                            
                            <div class="mb-3">
                                <label class="form-label">Tên sách</label>
                                <input type="text" class="form-control" name="book_title" value="<?=htmlspecialchars($book['title'])?>" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Mô tả</label>
                                <textarea class="form-control" name="book_description" rows="3" required><?=htmlspecialchars($book['description'])?></textarea>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Tác giả</label>
                                    <select name="book_author" class="form-select" required>
                                        <?php if ($authors != 0): ?>
                                            <?php foreach ($authors as $author): ?>
                                                <option value="<?=$author['id']?>" <?=$author['id'] == $book['author_id'] ? 'selected' : ''?>>
                                                    <?=htmlspecialchars($author['name'])?>
                                                </option>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Danh mục</label>
                                    <select name="book_category" class="form-select" required>
                                        <?php if ($categories != 0): ?>
                                            <?php foreach ($categories as $cat): ?>
                                                <option value="<?=$cat['id']?>" <?=$cat['id'] == $book['category_id'] ? 'selected' : ''?>>
                                                    <?=htmlspecialchars($cat['name'])?>
                                                </option>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-3 mb-3">
                                    <label class="form-label"><i class="fas fa-money-bill-wave me-1"></i>Giá tiền (VNĐ)</label>
                                    <input type="number" class="form-control" name="book_price" 
                                           value="<?=isset($book['price']) ? $book['price'] : 0?>" 
                                           min="0" step="1000" required>
                                    <small class="text-muted">Nhập giá tiền bằng số (VD: 50000)</small>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label"><i class="fas fa-box me-1"></i>Số lượng (Kho)</label>
                                    <input type="number" class="form-control" name="book_stock" 
                                           value="<?=isset($book['stock']) ? $book['stock'] : 0?>" 
                                           min="0" required>
                                    <small class="text-muted">Số lượng sách còn trong kho</small>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label"><i class="fas fa-percent me-1"></i>% Giảm giá</label>
                                    <input type="number" class="form-control" name="discount_percent" 
                                           value="<?=isset($book['discount_percent']) ? $book['discount_percent'] : 0?>" 
                                           min="0" max="100" step="1">
                                    <small class="text-muted">Mặc định: 0 (để trống = 0)</small>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label"><i class="fas fa-undo-alt me-1"></i>Số ngày cho phép hoàn trả</label>
                                    <input type="number" class="form-control" name="return_days" 
                                           value="<?=isset($book['return_days']) ? $book['return_days'] : 7?>" 
                                           min="0" max="365" step="1">
                                    <small class="text-muted">Mặc định: 7 (để trống = 7)</small>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Ảnh bìa mới (để trống nếu không đổi)</label>
                                    <input type="file" class="form-control" name="book_cover" accept="image/*">
                                    <small class="text-muted">Bìa hiện tại: <?=$book['cover']?></small>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">File mới (để trống nếu không đổi)</label>
                                    <input type="file" class="form-control" name="file" accept=".pdf">
                                    <small class="text-muted">File hiện tại: <?=$book['file']?></small>
                                </div>
                            </div>

                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-warning">
                                    <i class="fas fa-save me-1"></i>Cập nhật
                                </button>
                                <a href="admin.php" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left me-1"></i>Quay lại
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
