<?php 
session_start();
include "db_conn.php";
include "php/func-book.php";
include "php/func-author.php";
include "php/func-category.php";
include "php/func-promotion.php";

$promotion_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$promotion = null;
$promo_books_list = [];

if ($promotion_id > 0) {
    // Xem chi tiết một chương trình cụ thể
    $promotion = get_promotion_by_id($conn, $promotion_id);
    $promo_books_list = get_books_in_promotion($conn, $promotion_id);
} else {
    // Xem tất cả sách khuyến mãi
    $promo_books_list = get_books_with_active_promotions($conn, 100);
}

// Lấy cả sách có discount_percent > 0 (khuyến mãi cũ)
$legacy_promo_books = get_promotion_books($conn, 50);

$authors = get_all_author($conn);
$categories = get_all_categories($conn);
$active_promotions = get_active_promotions($conn);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?=$promotion ? htmlspecialchars($promotion['name']) : 'Khuyến Mãi'?> - Nhà Sách Online</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <?php include "php/header.php"; ?>

    <!-- Promotion Banner nếu xem chi tiết -->
    <?php if ($promotion): ?>
        <div class="container mt-3">
            <div class="promotion-banner" style="background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%); border-radius: 12px; padding: 30px; color: white; position: relative; overflow: hidden;">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h1 class="mb-2">
                            <i class="fas fa-fire me-2"></i><?=htmlspecialchars($promotion['name'])?>
                        </h1>
                        <?php if (!empty($promotion['description'])): ?>
                            <p class="mb-3 fs-5"><?=htmlspecialchars($promotion['description'])?></p>
                        <?php endif; ?>
                        <div class="d-flex align-items-center gap-3 flex-wrap">
                            <span class="badge bg-warning text-dark fs-4 p-2">Giảm đến <?=$promotion['discount_percent']?>%</span>
                            <span class="bg-white bg-opacity-25 rounded px-3 py-2">
                                <i class="fas fa-clock me-1"></i>
                                Từ <?=date('d/m/Y', strtotime($promotion['start_date']))?> đến <?=date('d/m/Y', strtotime($promotion['end_date']))?>
                            </span>
                        </div>
                    </div>
                    <div class="col-md-4 text-end">
                        <?php if (!empty($promotion['banner_image'])): ?>
                            <img src="uploads/banners/<?=$promotion['banner_image']?>" class="img-fluid rounded" style="max-height: 150px;">
                        <?php endif; ?>
                    </div>
                </div>
                <div style="position: absolute; top: -50px; right: -50px; width: 200px; height: 200px; background: rgba(255,255,255,0.1); border-radius: 50%;"></div>
            </div>
        </div>
    <?php endif; ?>

    <div class="container py-4">
        <div class="row">
            <!-- Sidebar - Danh sách chương trình -->
            <div class="col-lg-3">
                <div class="card mb-4">
                    <div class="card-header bg-danger text-white">
                        <h5 class="mb-0"><i class="fas fa-fire me-2"></i>Chương trình KM</h5>
                    </div>
                    <div class="list-group list-group-flush">
                        <a href="promotions.php" class="list-group-item list-group-item-action <?=!$promotion_id ? 'active' : ''?>">
                            <i class="fas fa-tags me-2"></i>Tất cả khuyến mãi
                        </a>
                        <?php if ($active_promotions != 0): ?>
                            <?php foreach ($active_promotions as $promo): ?>
                                <a href="promotions.php?id=<?=$promo['id']?>" class="list-group-item list-group-item-action <?=$promotion_id == $promo['id'] ? 'active' : ''?>">
                                    <i class="fas fa-gift me-2"></i><?=htmlspecialchars($promo['name'])?>
                                    <span class="badge bg-danger float-end">-<?=$promo['discount_percent']?>%</span>
                                </a>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="mb-0"><i class="fas fa-list me-2"></i>Danh mục</h5>
                    </div>
                    <div class="list-group list-group-flush">
                        <?php if ($categories != 0): ?>
                            <?php foreach ($categories as $cat): ?>
                                <a href="category.php?id=<?=$cat['id']?>" class="list-group-item list-group-item-action">
                                    <?=htmlspecialchars($cat['name'])?>
                                </a>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-lg-9">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>
                        <i class="fas fa-tags text-danger me-2"></i>
                        <?=$promotion ? htmlspecialchars($promotion['name']) : 'Tất cả sách khuyến mãi'?>
                    </h2>
                </div>

                <?php 
                // Gộp sách từ chương trình KM mới và sách có discount cũ
                $all_promo_books = [];
                
                if ($promo_books_list != 0 && is_array($promo_books_list)) {
                    foreach ($promo_books_list as $book) {
                        $book_id = isset($book['id']) ? $book['id'] : (isset($book['book_id']) ? $book['book_id'] : null);
                        if ($book_id) {
                            // Tính final_discount cho từng sách
                            if (isset($book['custom_discount_percent'])) {
                                $book['final_discount'] = $book['custom_discount_percent'];
                            } elseif (isset($promotion['discount_percent'])) {
                                $book['final_discount'] = $promotion['discount_percent'];
                            } elseif (isset($book['promo_discount'])) {
                                $book['final_discount'] = $book['promo_discount'];
                            } else {
                                $book['final_discount'] = isset($book['book_discount']) ? $book['book_discount'] : 0;
                            }
                            $all_promo_books[$book_id] = $book;
                        }
                    }
                }
                
                // Nếu xem tất cả, gộp thêm sách có discount_percent > 0
                if (!$promotion_id && $legacy_promo_books != 0 && is_array($legacy_promo_books)) {
                    foreach ($legacy_promo_books as $book) {
                        if (isset($book['id']) && !isset($all_promo_books[$book['id']])) {
                            $all_promo_books[$book['id']] = $book;
                        }
                    }
                }
                ?>

                <?php if (empty($all_promo_books)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-tags" style="font-size: 80px; color: #ddd;"></i>
                        <h3 class="mt-3 text-muted">Chưa có sách trong chương trình này</h3>
                        <p class="text-muted">Vui lòng quay lại sau!</p>
                        <a href="index.php" class="btn btn-primary mt-2">
                            <i class="fas fa-home me-2"></i>Về trang chủ
                        </a>
                    </div>
                <?php else: ?>
                    <div class="row">
                        <?php foreach ($all_promo_books as $book):
                            $author_name = "Không rõ";
                            if ($authors != 0) {
                                foreach ($authors as $a) {
                                    if ($a['id'] == ($book['author_id'] ?? 0)) { 
                                        $author_name = $a['name']; 
                                        break; 
                                    }
                                }
                            }
                            $stock = isset($book['stock']) ? (int)$book['stock'] : 0;
                            $price = isset($book['price']) ? (float)$book['price'] : 0;
                            // Ưu tiên final_discount từ chương trình, fallback về discount_percent
                            $discount = isset($book['final_discount']) ? (int)$book['final_discount'] : (isset($book['discount_percent']) ? (int)$book['discount_percent'] : 0);
                            $final_price = $price * (100 - $discount) / 100;
                            $book_id = $book['id'] ?? $book['book_id'];
                        ?>
                            <div class="col-6 col-md-4 col-lg-3 mb-4">
                                <div class="card h-100 shadow-sm border-0">
                                    <div class="position-relative">
                                        <span class="badge bg-danger position-absolute" style="top: 10px; left: 10px; z-index: 1; font-size: 0.9em;">-<?=$discount?>%</span>
                                        <img src="uploads/cover/<?=$book['cover']?>" class="card-img-top" style="height: 200px; object-fit: cover;" onerror="this.src='https://via.placeholder.com/200x250'">
                                    </div>
                                    <div class="card-body">
                                        <h6 class="card-title text-truncate" title="<?=htmlspecialchars($book['title'])?>"><?=htmlspecialchars($book['title'])?></h6>
                                        <p class="card-text small text-muted mb-2">
                                            <i class="fas fa-user-edit"></i> <?=htmlspecialchars($author_name)?>
                                        </p>
                                        <div class="mb-2">
                                            <span class="text-muted text-decoration-line-through small"><?=format_price($price)?></span>
                                            <span class="text-danger fw-bold ms-1"><?=format_price($final_price)?></span>
                                        </div>
                                        <?php if ($stock > 0): ?>
                                            <small class="text-success"><i class="fas fa-check-circle"></i> Còn hàng</small>
                                        <?php else: ?>
                                            <small class="text-danger"><i class="fas fa-times-circle"></i> Hết hàng</small>
                                        <?php endif; ?>
                                    </div>
                                    <div class="card-footer bg-white border-0 pt-0">
                                        <div class="d-grid gap-2">
                                            <a href="book-detail.php?id=<?=$book_id?>" class="btn btn-outline-danger btn-sm">
                                                <i class="fas fa-eye"></i> Xem chi tiết
                                            </a>
                                            <?php if ($stock > 0): ?>
                                                <a href="add-to-cart.php?id=<?=$book_id?>&redirect=promotions.php<?=$promotion_id ? '?id='.$promotion_id : ''?>" class="btn btn-danger btn-sm">
                                                    <i class="fas fa-cart-plus"></i> Mua ngay
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php include "php/footer.php"; ?>
</body>
</html>
