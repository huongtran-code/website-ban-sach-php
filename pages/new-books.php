<?php 
session_start();
require_once __DIR__ . "/../config/bootstrap.php";
include MODELS_PATH . "func-book.php";
include MODELS_PATH . "func-author.php";
include MODELS_PATH . "func-category.php";

$books = get_new_books($conn, 50);
$authors = get_all_author($conn);
$categories = get_all_categories($conn);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sách Mới - Nhà Sách Online</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../public/css/style.css">
</head>
<body>
    <?php include VIEWS_PATH . "header.php"; ?>

    <div class="container">
        <div class="main-content">
            <aside class="sidebar">
                <div class="sidebar-widget">
                    <h3><i class="fas fa-list"></i> Danh mục</h3>
                    <ul>
                        <?php if ($categories != 0): foreach ($categories as $cat): ?>
                            <li><a href="category.php?id=<?=$cat['id']?>"><i class="fas fa-chevron-right"></i><?=htmlspecialchars($cat['name'])?></a></li>
                        <?php endforeach; endif; ?>
                    </ul>
                </div>
            </aside>

            <section class="books-section">
                <div class="section-header">
                    <h2><i class="fas fa-book"></i> Sách Mới Nhất</h2>
                </div>

                <?php if ($books == 0): ?>
                    <div class="empty-state">
                        <i class="fas fa-box-open" style="font-size: 80px; color: #ddd;"></i>
                        <h3>Chưa có sách mới</h3>
                    </div>
                <?php else: ?>
                    <div class="books-grid">
                        <?php foreach ($books as $book): 
                            $author_name = "Không rõ";
                            if ($authors != 0) {
                                foreach ($authors as $a) {
                                    if ($a['id'] == $book['author_id']) { $author_name = $a['name']; break; }
                                }
                            }
                            $stock = isset($book['stock']) ? (int)$book['stock'] : 0;
                            $price = isset($book['price']) ? (float)$book['price'] : 0;
                        ?>
                            <div class="book-card">
                                <span class="badge">Mới</span>
                                <img src="../storage/uploads/cover/<?=$book['cover']?>" alt="" class="cover" onerror="this.src='https://via.placeholder.com/200x250'">
                                <div class="info">
                                    <h3 class="title"><?=htmlspecialchars($book['title'])?></h3>
                                    <div class="author"><i class="fas fa-user-edit"></i> <?=htmlspecialchars($author_name)?></div>
                                    <?php if ($price > 0): ?>
                                        <div class="price-box mt-2"><span class="new-price"><?=format_price($price)?></span></div>
                                    <?php endif; ?>
                                    <div class="stock-info mt-2">
                                        <?php if ($stock > 0): ?>
                                            <small class="text-success"><i class="fas fa-check-circle"></i> Còn <?=$stock?> cuốn</small>
                                        <?php else: ?>
                                            <small class="text-danger"><i class="fas fa-times-circle"></i> Hết hàng</small>
                                        <?php endif; ?>
                                    </div>
                                    <div class="actions">
                                        <a href="book-detail.php?id=<?=$book['id']?>" class="btn-view"><i class="fas fa-eye"></i> Xem chi tiết</a>
                                        <?php if ($stock > 0): ?>
                                            <a href="add-to-cart.php?id=<?=$book['id']?>" class="btn-download"><i class="fas fa-cart-plus"></i> Mua</a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        </div>
    </div>

    <?php include VIEWS_PATH . "footer.php"; ?>
</body>
</html>
