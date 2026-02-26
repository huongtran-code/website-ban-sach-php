<?php 
session_start();
include "db_conn.php";
include "php/func-book.php";
include "php/func-author.php";
include "php/func-category.php";

$books = get_bestseller_books($conn, 50);
$authors = get_all_author($conn);
$categories = get_all_categories($conn);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sách Bán Chạy - Nhà Sách Online</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <?php include "php/header.php"; ?>

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
                    <h2><i class="fas fa-fire"></i> Sách Bán Chạy</h2>
                </div>

                <?php if ($books == 0): ?>
                    <div class="empty-state">
                        <i class="fas fa-box-open" style="font-size: 80px; color: #ddd;"></i>
                        <h3>Chưa có dữ liệu</h3>
                    </div>
                <?php else: ?>
                    <div class="books-grid">
                        <?php $rank = 0; foreach ($books as $book): $rank++;
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
                                <span class="badge bg-warning text-dark">TOP <?=$rank?></span>
                                <img src="uploads/cover/<?=$book['cover']?>" alt="" class="cover" onerror="this.src='https://via.placeholder.com/200x250'">
                                <div class="info">
                                    <h3 class="title"><?=htmlspecialchars($book['title'])?></h3>
                                    <div class="author"><i class="fas fa-user-edit"></i> <?=htmlspecialchars($author_name)?></div>
                                    <small class="text-muted"><i class="fas fa-eye"></i> <?=$book['view_count'] ?? 0?> lượt xem</small>
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

    <?php include "php/footer.php"; ?>
</body>
</html>
