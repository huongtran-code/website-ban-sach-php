<?php
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_email'])) {
    header("Location: adminlogin.php");
    exit;
}

include "db_conn.php";
include "php/func-promotion.php";
include "php/func-book.php";

$promotion_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$promotion = $promotion_id > 0 ? get_promotion_by_id($conn, $promotion_id) : null;

if (!$promotion) {
    header("Location: admin-coupons.php?error=Chương trình không tồn tại#programs");
    exit;
}

$promo_books = get_books_in_promotion($conn, $promotion_id);
$all_books = get_all_books($conn);

// Lấy danh sách book_id đã có trong chương trình
$existing_book_ids = [];
if ($promo_books != 0) {
    foreach ($promo_books as $pb) {
        $existing_book_ids[] = $pb['book_id'];
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sửa chương trình khuyến mãi - Admin</title>
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

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-gift text-success me-2"></i><?=htmlspecialchars($promotion['name'])?></h2>
            <a href="admin-coupons.php#programs" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-1"></i>Quay lại
            </a>
        </div>

        <div class="row">
            <div class="col-lg-5">
                <!-- Form sửa thông tin chương trình -->
                <div class="card mb-4">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="fas fa-edit me-2"></i>Thông tin chương trình</h5>
                    </div>
                    <div class="card-body">
                        <form action="php/edit-promotion.php" method="post" enctype="multipart/form-data">
                            <input type="hidden" name="id" value="<?=$promotion['id']?>">
                            
                            <div class="mb-3">
                                <label class="form-label">Tên chương trình</label>
                                <input type="text" name="name" class="form-control" value="<?=htmlspecialchars($promotion['name'])?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Mô tả</label>
                                <textarea name="description" class="form-control" rows="2"><?=htmlspecialchars($promotion['description'] ?? '')?></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">% Giảm giá mặc định</label>
                                <input type="number" name="discount_percent" class="form-control" min="1" max="100" value="<?=$promotion['discount_percent']?>" required>
                            </div>
                            <div class="row">
                                <div class="col-6">
                                    <div class="mb-3">
                                        <label class="form-label">Ngày bắt đầu</label>
                                        <input type="datetime-local" name="start_date" class="form-control" value="<?=date('Y-m-d\TH:i', strtotime($promotion['start_date']))?>" required>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="mb-3">
                                        <label class="form-label">Ngày kết thúc</label>
                                        <input type="datetime-local" name="end_date" class="form-control" value="<?=date('Y-m-d\TH:i', strtotime($promotion['end_date']))?>" required>
                                    </div>
                                </div>
                            </div>
                            <?php if (!empty($promotion['banner_image'])): ?>
                                <div class="mb-3">
                                    <label class="form-label">Banner hiện tại</label><br>
                                    <img src="uploads/banners/<?=$promotion['banner_image']?>" class="img-thumbnail" style="max-height: 100px;">
                                </div>
                            <?php endif; ?>
                            <div class="mb-3">
                                <label class="form-label">Đổi ảnh banner</label>
                                <input type="file" name="banner" class="form-control" accept="image/*">
                            </div>
                            <button type="submit" class="btn btn-success w-100">
                                <i class="fas fa-save me-1"></i>Lưu thay đổi
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-7">
                <!-- Danh sách sách trong chương trình -->
                <div class="card">
                    <div class="card-header bg-warning text-dark d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-book me-2"></i>Sách trong chương trình (<?=$promo_books == 0 ? 0 : count($promo_books)?>)</h5>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-sm btn-success" id="saveAllBtn" onclick="saveAllDiscounts()" style="display: none;">
                                <i class="fas fa-save me-1"></i>Lưu tất cả
                            </button>
                            <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addBookModal">
                                <i class="fas fa-plus me-1"></i>Thêm sách
                            </button>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <?php if ($promo_books == 0): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-book-open" style="font-size: 50px; color: #ddd;"></i>
                                <p class="text-muted mt-3 mb-0">Chưa có sách nào trong chương trình này</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Ảnh</th>
                                            <th>Tên sách</th>
                                            <th>Giá gốc</th>
                                            <th>% Giảm</th>
                                            <th>Giá KM</th>
                                            <th class="text-end">Thao tác</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($promo_books as $pb): 
                                            $discount = $pb['custom_discount_percent'] ?? $promotion['discount_percent'];
                                            $final_price = $pb['price'] * (100 - $discount) / 100;
                                        ?>
                                            <tr>
                                                <td>
                                                    <img src="uploads/cover/<?=$pb['cover']?>" style="width: 50px; height: 65px; object-fit: cover; border-radius: 4px;" onerror="this.src='https://via.placeholder.com/50x65'">
                                                </td>
                                                <td><?=htmlspecialchars($pb['title'])?></td>
                                                <td><small class="text-muted text-decoration-line-through"><?=number_format($pb['price'], 0, ',', '.')?>đ</small></td>
                                                <td>
                                                    <div class="d-flex align-items-center gap-1">
                                                        <div class="input-group input-group-sm" style="width: 100px;">
                                                            <button type="button" class="btn btn-outline-secondary" onclick="decreaseDiscount(<?=$pb['book_id']?>)" title="Giảm">
                                                                <i class="fas fa-minus"></i>
                                                            </button>
                                                            <input type="number" 
                                                                   id="discount-<?=$pb['book_id']?>" 
                                                                   class="form-control text-center discount-input" 
                                                                   min="1" 
                                                                   max="100" 
                                                                   value="<?=$discount?>" 
                                                                   placeholder="<?=$promotion['discount_percent']?>"
                                                                   data-book-id="<?=$pb['book_id']?>"
                                                                   data-original="<?=$discount?>"
                                                                   style="width: 50px;">
                                                            <button type="button" class="btn btn-outline-secondary" onclick="increaseDiscount(<?=$pb['book_id']?>)" title="Tăng">
                                                                <i class="fas fa-plus"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                    <?php if ($pb['custom_discount_percent']): ?>
                                                        <small class="text-muted d-block mt-1">(riêng)</small>
                                                    <?php else: ?>
                                                        <small class="text-muted d-block mt-1">(mặc định)</small>
                                                    <?php endif; ?>
                                                </td>
                                                <td><strong class="text-success"><?=number_format($final_price, 0, ',', '.')?>đ</strong></td>
                                                <td class="text-end">
                                                    <div class="btn-group" role="group">
                                                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="editBookDiscount(<?=$pb['book_id']?>, <?=$discount?>, <?=$promotion['discount_percent']?>)" title="Sửa">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <form action="php/remove-book-from-promotion.php" method="post" class="d-inline" onsubmit="return confirm('Xóa sách này khỏi chương trình?');">
                                                            <input type="hidden" name="promotion_id" value="<?=$promotion['id']?>">
                                                            <input type="hidden" name="book_id" value="<?=$pb['book_id']?>">
                                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Xóa">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </form>
                                                    </div>
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

    <!-- Modal Thêm sách -->
    <div class="modal fade" id="addBookModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-plus-circle me-2"></i>Thêm sách vào chương trình</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form action="php/add-book-to-promotion.php" method="post">
                    <div class="modal-body">
                        <input type="hidden" name="promotion_id" value="<?=$promotion['id']?>">
                        
                        <div class="mb-3">
                            <label class="form-label">Chọn sách <span class="text-danger">*</span></label>
                            <select name="book_id" class="form-select" required>
                                <option value="">-- Chọn sách --</option>
                                <?php 
                                $available_books = [];
                                if ($all_books != 0) {
                                    foreach ($all_books as $book) {
                                        if (!in_array($book['id'], $existing_book_ids)) {
                                            $available_books[] = $book;
                                        }
                                    }
                                }
                                ?>
                                <?php if (empty($available_books)): ?>
                                    <option value="" disabled>Tất cả sách đã được thêm vào chương trình này</option>
                                <?php else: ?>
                                    <?php foreach ($available_books as $book): ?>
                                        <option value="<?=$book['id']?>"><?=htmlspecialchars($book['title'])?></option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                            <?php if (!empty($available_books)): ?>
                                <small class="text-muted">Còn <?=count($available_books)?> sách có thể thêm</small>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">% Giảm giá riêng (tuỳ chọn)</label>
                            <div class="input-group">
                                <button type="button" class="btn btn-outline-secondary" onclick="decreaseAddDiscount()">
                                    <i class="fas fa-minus"></i>
                                </button>
                                <input type="number" name="custom_discount" id="add_discount" class="form-control text-center" min="1" max="100" placeholder="<?=$promotion['discount_percent']?>">
                                <button type="button" class="btn btn-outline-secondary" onclick="increaseAddDiscount()">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                            <small class="text-muted">Để trống sẽ dùng % giảm giá mặc định của chương trình (<?=$promotion['discount_percent']?>%)</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                        <button type="submit" class="btn btn-primary" <?=empty($available_books) ? 'disabled' : ''?>>
                            <i class="fas fa-plus me-1"></i>Thêm sách
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Sửa % Giảm giá -->
    <div class="modal fade" id="editDiscountModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Sửa % Giảm giá</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="editDiscountForm" action="php/update-book-promotion-discount.php" method="post">
                    <div class="modal-body">
                        <input type="hidden" name="promotion_id" value="<?=$promotion['id']?>">
                        <input type="hidden" name="book_id" id="modal_book_id">
                        <div class="mb-3">
                            <label class="form-label">% Giảm giá</label>
                            <div class="input-group">
                                <button type="button" class="btn btn-outline-secondary" onclick="decreaseModalDiscount()">
                                    <i class="fas fa-minus"></i>
                                </button>
                                <input type="number" name="custom_discount" id="modal_discount" class="form-control text-center" min="1" max="100" required>
                                <button type="button" class="btn btn-outline-secondary" onclick="increaseModalDiscount()">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                            <small class="text-muted">% mặc định của chương trình: <strong><?=$promotion['discount_percent']?>%</strong></small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save me-1"></i>Lưu thay đổi
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Kiểm tra thay đổi và hiển thị nút "Lưu tất cả"
        function checkChanges() {
            const inputs = document.querySelectorAll('.discount-input');
            let hasChanges = false;
            
            inputs.forEach(input => {
                const original = parseInt(input.getAttribute('data-original')) || 0;
                const current = parseInt(input.value) || 0;
                if (original !== current) {
                    hasChanges = true;
                }
            });
            
            const saveBtn = document.getElementById('saveAllBtn');
            if (hasChanges) {
                saveBtn.style.display = 'inline-block';
            } else {
                saveBtn.style.display = 'none';
            }
        }

        // Tăng/giảm discount trong bảng
        function increaseDiscount(bookId) {
            const input = document.getElementById('discount-' + bookId);
            let value = parseInt(input.value) || 0;
            if (value < 100) {
                input.value = value + 1;
                checkChanges();
            }
        }

        function decreaseDiscount(bookId) {
            const input = document.getElementById('discount-' + bookId);
            let value = parseInt(input.value) || 0;
            if (value > 1) {
                input.value = value - 1;
                checkChanges();
            }
        }

        // Lưu tất cả thay đổi
        function saveAllDiscounts() {
            const inputs = document.querySelectorAll('.discount-input');
            const updates = [];
            
            inputs.forEach(input => {
                const bookId = input.getAttribute('data-book-id');
                const original = parseInt(input.getAttribute('data-original')) || 0;
                const current = parseInt(input.value) || 0;
                
                if (original !== current) {
                    updates.push({
                        book_id: bookId,
                        discount: current
                    });
                }
            });
            
            if (updates.length === 0) {
                alert('Không có thay đổi nào để lưu!');
                return;
            }
            
            // Tạo form và submit
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'php/update-all-book-discounts.php';
            
            const promotionIdInput = document.createElement('input');
            promotionIdInput.type = 'hidden';
            promotionIdInput.name = 'promotion_id';
            promotionIdInput.value = '<?=$promotion['id']?>';
            form.appendChild(promotionIdInput);
            
            const updatesInput = document.createElement('input');
            updatesInput.type = 'hidden';
            updatesInput.name = 'updates';
            updatesInput.value = JSON.stringify(updates);
            form.appendChild(updatesInput);
            
            document.body.appendChild(form);
            form.submit();
        }

        // Lắng nghe thay đổi trên input
        document.addEventListener('DOMContentLoaded', function() {
            const inputs = document.querySelectorAll('.discount-input');
            inputs.forEach(input => {
                input.addEventListener('input', checkChanges);
                input.addEventListener('change', checkChanges);
            });
        });

        // Mở modal sửa discount
        function editBookDiscount(bookId, currentDiscount, defaultDiscount) {
            document.getElementById('modal_book_id').value = bookId;
            document.getElementById('modal_discount').value = currentDiscount;
            const modal = new bootstrap.Modal(document.getElementById('editDiscountModal'));
            modal.show();
        }

        // Tăng/giảm discount trong modal
        function increaseModalDiscount() {
            const input = document.getElementById('modal_discount');
            let value = parseInt(input.value) || 0;
            if (value < 100) {
                input.value = value + 1;
            }
        }

        function decreaseModalDiscount() {
            const input = document.getElementById('modal_discount');
            let value = parseInt(input.value) || 0;
            if (value > 1) {
                input.value = value - 1;
            }
        }

        // Tăng/giảm discount trong modal thêm sách
        function increaseAddDiscount() {
            const input = document.getElementById('add_discount');
            let value = parseInt(input.value) || 0;
            if (value < 100) {
                input.value = value + 1;
            }
        }

        function decreaseAddDiscount() {
            const input = document.getElementById('add_discount');
            let value = parseInt(input.value) || 0;
            if (value > 1) {
                input.value = value - 1;
            }
        }
    </script>
</body>
</html>
