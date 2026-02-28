<?php 
session_start();
require_once __DIR__ . "/../config/bootstrap.php";
include MODELS_PATH . "func-book.php";
include MODELS_PATH . "func-author.php";
include MODELS_PATH . "func-category.php";
include MODELS_PATH . "func-promotion.php";
include MODELS_PATH . "func-user.php";
include MODELS_PATH . "func-notification.php";

// Lấy thông báo đơn hàng gần đây
$recent_orders = get_recent_orders($conn, 10);

// Kiểm tra và hiển thị popup lên hạng
$upgrade_level = $_SESSION['membership_upgrade'] ?? null;
if ($upgrade_level) {
    unset($_SESSION['membership_upgrade']); // Xóa sau khi lấy
}

$sort = isset($_GET['sort']) ? $_GET['sort'] : 'default';

// Sách hay mỗi ngày - random 20 cuốn, refresh mỗi 30 phút
if ($sort === 'default') {
    $books = get_random_books_cached($conn, 20);
    $next_refresh = get_next_refresh_time();
} else {
    $books = get_all_books($conn, $sort);
    $next_refresh = 0;
}

$authors = get_all_author($conn);
$categories = get_all_categories($conn);
$active_promotions = get_active_promotions($conn);
$promo_books = get_books_with_active_promotions($conn, 10);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nhà Sách Online - Kho Sách Hay</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick-theme.css">
    <link rel="stylesheet" href="../public/css/style.css">
    <style>
        /* Promotion Slider */
        .promotion-slider { margin: 0 -8px; }
        .promotion-slider .slick-slide { padding: 0 8px; }
        .promotion-slider .slick-prev, .promotion-slider .slick-next { z-index: 10; width: 36px; height: 36px; background: rgba(0,0,0,0.3); border-radius: 50%; }
        .promotion-slider .slick-prev { left: 12px; }
        .promotion-slider .slick-next { right: 12px; }
        .promotion-slider .slick-prev:before, .promotion-slider .slick-next:before { font-size: 20px; }
        .promotion-slider .slick-dots { bottom: 10px; }
        .promotion-slider .slick-dots li button:before { font-size: 10px; color: rgba(255,255,255,0.5); }
        .promotion-slider .slick-dots li.slick-active button:before { color: #fff; }
        .promo-slide { border-radius: 12px; padding: 28px 32px; color: white; position: relative; overflow: hidden; min-height: 200px; display: flex !important; align-items: center; }
        
        /* Promo Books Slider */
        .promo-books-slider { margin: 0 -8px; }
        .promo-books-slider .slick-slide { padding: 0 8px; }
        .promo-books-slider .slick-prev, .promo-books-slider .slick-next { z-index: 10; width: 36px; height: 36px; background: rgba(0,0,0,0.3); border-radius: 50%; }
        .promo-books-slider .slick-prev { left: 12px; }
        .promo-books-slider .slick-next { right: 12px; }
        .promo-books-slider .slick-prev:before, .promo-books-slider .slick-next:before { font-size: 20px; color: white; }
        .promo-books-slider .slick-dots { bottom: 10px; }
        .promo-books-slider .slick-dots li button:before { font-size: 10px; color: rgba(255,255,255,0.5); }
        .promo-books-slider .slick-dots li.slick-active button:before { color: #F7941E; }
        .promo-books-slide { border-radius: 12px; padding: 20px; color: white; position: relative; overflow: visible; min-height: 200px; background: linear-gradient(135deg, #C92127 0%, #a91b20 100%); }
        .promo-books-slide .decoration-circle { position: absolute; border-radius: 50%; background: rgba(255,255,255,0.08); pointer-events: none; z-index: 0; }
        .promo-book-item { position: relative; z-index: 1; text-align: center; }
        .promo-book-item * { position: relative; z-index: 1; }
        .promo-book-item img { width: 120px; height: 160px; object-fit: cover; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.25); margin: 0 auto 10px; display: block; }
        .promo-book-item h6 { color: white; font-size: 0.85rem; margin-bottom: 8px; height: 38px; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; font-weight: 500; }
        .promo-book-item .price-info { margin-bottom: 10px; }
        .promo-book-item .price-info .old-price { color: rgba(255,255,255,0.6); text-decoration: line-through; font-size: 0.8rem; }
        .promo-book-item .price-info .new-price { color: #F7941E; font-weight: 700; font-size: 1.05rem; }
        .promo-book-item .btn { background: white; color: #C92127; border: none; font-weight: 600; position: relative; z-index: 10; pointer-events: auto !important; font-size: 12px; border-radius: 6px; }
        .promo-book-item .btn:hover { background: #F7941E; color: #fff; }
        .promo-book-item .d-flex { position: relative; z-index: 10; }
        .promo-slide .decoration-circle { position: absolute; border-radius: 50%; background: rgba(255,255,255,0.08); pointer-events: none; z-index: 0; }
        .promo-slide .row { position: relative; z-index: 1; width: 100%; margin: 0; }
        .promo-slide .col-md-8, .promo-slide .col-12 { position: relative; z-index: 2; padding-right: 15px; }
        .promo-slide .col-md-4 { position: relative; z-index: 2; padding-left: 15px; }
        .promo-slide a.btn { position: relative; z-index: 3; pointer-events: auto; white-space: nowrap; flex-shrink: 0; margin-left: auto; }
        
        /* Purchase Notification */
        .purchase-notification {
            position: fixed;
            bottom: 20px;
            left: 20px;
            max-width: 320px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.12);
            padding: 14px 16px;
            z-index: 1050;
            animation: slideInLeft 0.4s ease-out;
            border-left: 4px solid #16a34a;
        }
        .purchase-notification.hide { animation: slideOutLeft 0.4s ease-in forwards; }
        .purchase-notification .close-btn { position: absolute; top: 4px; right: 8px; background: none; border: none; font-size: 16px; color: #aaa; cursor: pointer; }
        .purchase-notification .notification-icon { width: 36px; height: 36px; background: linear-gradient(135deg, #16a34a 0%, #22c55e 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 16px; flex-shrink: 0; }
        .purchase-notification .notification-content { flex: 1; margin-left: 12px; }
        .purchase-notification .notification-text { font-size: 12px; color: #555; margin: 0; line-height: 1.5; }
        @keyframes slideInLeft { from { transform: translateX(-120%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
        @keyframes slideOutLeft { from { transform: translateX(0); opacity: 1; } to { transform: translateX(-120%); opacity: 0; } }
        
        /* Toast for add to cart */
        .toast-container { position: fixed; top: 80px; right: 20px; z-index: 1060; }
    </style>
</head>
<body>
    <?php include VIEWS_PATH . "header.php"; ?>

    <!-- Purchase Notification Popup -->
    <?php if (!empty($recent_orders)): ?>
    <div id="purchaseNotification" class="purchase-notification" style="display: none;">
        <button class="close-btn" onclick="closePurchaseNotification()">&times;</button>
        <div class="d-flex align-items-center">
            <div class="notification-icon">
                <i class="fas fa-shopping-bag"></i>
            </div>
            <div class="notification-content">
                <p class="notification-text" id="notificationText"></p>
            </div>
        </div>
    </div>
    <script>
        var purchaseNotifications = <?=json_encode(array_map(function($order) {
            return format_order_notification($order);
        }, $recent_orders))?>;
        var currentNotificationIndex = 0;
        var notificationInterval;
        
        function showPurchaseNotification() {
            if (purchaseNotifications.length === 0) return;
            
            var notification = document.getElementById('purchaseNotification');
            var text = document.getElementById('notificationText');
            
            text.textContent = purchaseNotifications[currentNotificationIndex];
            notification.style.display = 'block';
            notification.classList.remove('hide');
            
            // Ẩn sau 5 giây
            setTimeout(function() {
                notification.classList.add('hide');
                setTimeout(function() {
                    notification.style.display = 'none';
                    notification.classList.remove('hide');
                    
                    // Chuyển sang thông báo tiếp theo
                    currentNotificationIndex = (currentNotificationIndex + 1) % purchaseNotifications.length;
                }, 500);
            }, 5000);
        }
        
        function closePurchaseNotification() {
            var notification = document.getElementById('purchaseNotification');
            notification.classList.add('hide');
            setTimeout(function() {
                notification.style.display = 'none';
                notification.classList.remove('hide');
            }, 500);
        }
        
        // Hiển thị thông báo đầu tiên sau 3 giây, sau đó mỗi 15 giây
        setTimeout(function() {
            showPurchaseNotification();
            notificationInterval = setInterval(showPurchaseNotification, 15000);
        }, 3000);
    </script>
    <?php endif; ?>

    <!-- Promotion Slider -->
    <?php if ($active_promotions != 0): ?>
        <?php 
        // Các màu gradient cho các slide
        $gradients = [
            'linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%)',
            'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
            'linear-gradient(135deg, #11998e 0%, #38ef7d 100%)',
            'linear-gradient(135deg, #fc4a1a 0%, #f7b733 100%)',
            'linear-gradient(135deg, #8E2DE2 0%, #4A00E0 100%)',
            'linear-gradient(135deg, #00b4db 0%, #0083b0 100%)',
        ];
        ?>
        <div class="container mt-3">
            <div class="promotion-slider">
                <?php $i = 0; foreach ($active_promotions as $promo): $gradient = $gradients[$i % count($gradients)]; $i++; ?>
                    <div>
                        <div class="promo-slide" style="background: <?=$gradient?>;">
                            <div class="decoration-circle" style="top: -50px; right: -50px; width: 200px; height: 200px;"></div>
                            <div class="decoration-circle" style="bottom: -30px; left: 30%; width: 100px; height: 100px;"></div>
                            <div class="row align-items-center w-100 g-3 m-0">
                                <div class="col-md-7 col-12 pe-md-3">
                                    <h2 class="mb-2">
                                        <i class="fas fa-fire me-2"></i><?=htmlspecialchars($promo['name'])?>
                                    </h2>
                                    <?php if (!empty($promo['description'])): ?>
                                        <p class="mb-2 opacity-75"><?=htmlspecialchars($promo['description'])?></p>
                                    <?php endif; ?>
                                    <div class="d-flex align-items-center gap-3 flex-wrap">
                                        <span class="badge bg-warning text-dark fs-5 px-3 py-2">Giảm <?=$promo['discount_percent']?>%</span>
                                        <span class="bg-white bg-opacity-25 rounded px-3 py-1">
                                            <i class="fas fa-clock me-1"></i>
                                            Đến <?=date('d/m/Y H:i', strtotime($promo['end_date']))?>
                                        </span>
                                    </div>
                                </div>
                                <div class="col-md-5 col-12 text-md-end text-center mt-md-0 mt-3 ps-md-3">
                                    <a href="promotions.php?id=<?=$promo['id']?>" class="btn btn-light btn-lg shadow">
                                        <i class="fas fa-shopping-bag me-2"></i>Xem ngay
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Promotion Books Section -->
    <?php if ($promo_books != 0): ?>
        <div class="container mt-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0"><i class="fas fa-bolt text-danger me-2"></i>Sách đang khuyến mãi</h4>
                <a href="promotions.php" class="btn btn-sm btn-outline-danger">Xem tất cả <i class="fas fa-arrow-right ms-1"></i></a>
            </div>
            <div class="promo-books-slider">
                <?php foreach ($promo_books as $book): 
                    $final_discount = $book['final_discount'];
                    $final_price = $book['price'] * (100 - $final_discount) / 100;
                    $promotion_id = isset($book['promotion_id']) ? $book['promotion_id'] : 0;
                ?>
                    <div>
                        <div class="promo-books-slide">
                            <div class="decoration-circle" style="top: -30px; right: -30px; width: 120px; height: 120px;"></div>
                            <div class="decoration-circle" style="bottom: -20px; left: -20px; width: 80px; height: 80px;"></div>
                            <div class="promo-book-item">
                                <span class="badge bg-warning text-dark position-absolute" style="top: 10px; right: 10px; z-index: 2;">-<?=$final_discount?>%</span>
                                <img src="../storage/uploads/cover/<?=$book['cover']?>" alt="<?=htmlspecialchars($book['title'])?>" onerror="this.src='https://via.placeholder.com/120x160'">
                                <h6><?=htmlspecialchars($book['title'])?></h6>
                                <div class="price-info">
                                    <div class="old-price"><?=format_price($book['price'])?></div>
                                    <div class="new-price"><?=format_price($final_price)?></div>
                                </div>
                                <div class="d-flex gap-2" style="position: relative; z-index: 10;">
                                    <a href="book-detail.php?id=<?=$book['id']?>" class="btn btn-sm flex-fill" style="position: relative; z-index: 10; pointer-events: auto;">Xem chi tiết</a>
                                    <?php 
                                    $stock = isset($book['stock']) ? (int)$book['stock'] : 0;
                                    if ($stock > 0): 
                                    ?>
                                        <a href="add-to-cart.php?id=<?=$book['id']?>&redirect=index.php" class="btn btn-sm flex-fill" style="background: #ffd700; color: #000; position: relative; z-index: 10; pointer-events: auto; font-weight: 600;">
                                            <i class="fas fa-cart-plus"></i> Mua
                                        </a>
                                    <?php else: ?>
                                        <span class="btn btn-sm flex-fill" style="background: #6c757d; color: #fff; cursor: not-allowed; opacity: 0.6;">
                                            <i class="fas fa-times"></i> Hết hàng
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Main Content -->
    <div class="container">
        <div class="main-content">
            <!-- Sidebar -->
            <aside class="sidebar">
                <div class="sidebar-widget">
                    <h3><i class="fas fa-list"></i> Danh mục sách</h3>
                    <ul>
                        <?php if ($categories != 0): ?>
                            <?php foreach ($categories as $category): ?>
                                <li>
                                    <a href="category.php?id=<?=$category['id']?>">
                                        <i class="fas fa-chevron-right"></i>
                                        <?=htmlspecialchars($category['name'])?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <li><a href="#">Chưa có danh mục</a></li>
                        <?php endif; ?>
                    </ul>
                </div>

                <div class="sidebar-widget">
                    <h3><i class="fas fa-user-edit"></i> Tác giả</h3>
                    <ul>
                        <?php if ($authors != 0): ?>
                            <?php foreach ($authors as $author): ?>
                                <li>
                                    <a href="author.php?id=<?=$author['id']?>">
                                        <i class="fas fa-chevron-right"></i>
                                        <?=htmlspecialchars($author['name'])?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <li><a href="#">Chưa có tác giả</a></li>
                        <?php endif; ?>
                    </ul>
                </div>
            </aside>

            <!-- Books Section -->
            <section class="books-section">
                <div class="section-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <h2><i class="fas fa-book-open"></i> Sách hay mỗi ngày</h2>
                        <div class="d-flex align-items-center gap-2">
                            <a href="new-books.php" class="small">Xem tất cả <i class="fas fa-arrow-right"></i></a>
                        </div>
                    </div>
                    <form method="get" class="d-flex align-items-center gap-2">
                        <label for="sortSelect" class="me-2 small text-muted">Sắp xếp:</label>
                        <select id="sortSelect" name="sort" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="default" <?=$sort == 'default' ? 'selected' : ''?>>🎲 Ngẫu nhiên</option>
                            <option value="price_asc" <?=$sort == 'price_asc' ? 'selected' : ''?>>Giá tăng dần</option>
                            <option value="price_desc" <?=$sort == 'price_desc' ? 'selected' : ''?>>Giá giảm dần</option>
                            <option value="views_desc" <?=$sort == 'views_desc' ? 'selected' : ''?>>Lượt xem nhiều nhất</option>
                            <option value="rating_desc" <?=$sort == 'rating_desc' ? 'selected' : ''?>>Đánh giá cao nhất</option>
                            <option value="sales_desc" <?=$sort == 'sales_desc' ? 'selected' : ''?>>Bán chạy nhất</option>
                        </select>
                    </form>
                </div>

                <?php if ($books == 0): ?>
                    <div class="empty-state">
                        <i class="fas fa-box-open" style="font-size: 80px; color: #ddd;"></i>
                        <h3>Chưa có sách trong cửa hàng</h3>
                        <p>Vui lòng quay lại sau!</p>
                    </div>
                <?php else: ?>
                    <div class="books-grid">
                        <?php foreach ($books as $book): ?>
                            <?php
                            $author_name = "Không rõ";
                            $category_name = "";
                            if ($authors != 0) {
                                foreach ($authors as $author) {
                                    if ($author['id'] == $book['author_id']) {
                                        $author_name = $author['name'];
                                        break;
                                    }
                                }
                            }
                            if ($categories != 0) {
                                foreach ($categories as $category) {
                                    if ($category['id'] == $book['category_id']) {
                                        $category_name = $category['name'];
                                        break;
                                    }
                                }
                            }
                            $stock = isset($book['stock']) ? (int)$book['stock'] : 0;
                            $price = isset($book['price']) ? (float)$book['price'] : 0;
                            $discount = isset($book['discount_percent']) ? (int)$book['discount_percent'] : 0;
                            ?>
                            <div class="book-card">
                                <?php if ($discount > 0): ?>
                                    <span class="badge bg-danger">-<?=$discount?>%</span>
                                <?php elseif (isset($book['is_new']) && $book['is_new']): ?>
                                    <span class="badge">Mới</span>
                                <?php endif; ?>
                                
                                <img src="../storage/uploads/cover/<?=$book['cover']?>" 
                                     alt="<?=htmlspecialchars($book['title'])?>" 
                                     class="cover"
                                     onerror="this.src='https://via.placeholder.com/200x250?text=No+Cover'">
                                <div class="info">
                                    <h3 class="title"><?=htmlspecialchars($book['title'])?></h3>
                                    <div class="author">
                                        <i class="fas fa-user-edit"></i> <?=htmlspecialchars($author_name)?>
                                    </div>
                                    <?php if ($category_name): ?>
                                        <span class="category"><?=htmlspecialchars($category_name)?></span>
                                    <?php endif; ?>
                                    
                                    <?php if ($price > 0): ?>
                                        <div class="price-box mt-2">
                                            <?php if ($discount > 0): ?>
                                                <span class="old-price"><?=format_price($price)?></span>
                                                <span class="new-price"><?=format_price($price * (100 - $discount) / 100)?></span>
                                            <?php else: ?>
                                                <span class="new-price"><?=format_price($price)?></span>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="stock-info mt-2">
                                        <?php if ($stock > 0): ?>
                                            <small class="text-success"><i class="fas fa-check-circle"></i> Còn <?=$stock?> cuốn</small>
                                        <?php else: ?>
                                            <small class="text-danger"><i class="fas fa-times-circle"></i> Hết hàng</small>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="actions">
                                        <a href="book-detail.php?id=<?=$book['id']?>" class="btn-view">
                                            <i class="fas fa-eye"></i> Xem chi tiết
                                        </a>
                                        <?php if ($stock > 0): ?>
                                            <a href="add-to-cart.php?id=<?=$book['id']?>" class="btn-download">
                                                <i class="fas fa-cart-plus"></i> Mua
                                            </a>
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
    
    <!-- Slick Slider JS -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.min.js"></script>
    <script>
        $(document).ready(function(){
            // Promotion Slider
            $('.promotion-slider').slick({
                dots: true,
                infinite: true,
                speed: 500,
                slidesToShow: 1,
                slidesToScroll: 1,
                autoplay: true,
                autoplaySpeed: 4000,
                arrows: true,
                pauseOnHover: true,
                fade: false,
                cssEase: 'ease-in-out'
            });
            
            // Promotion Books Slider
            $('.promo-books-slider').slick({
                dots: true,
                infinite: true,
                speed: 500,
                slidesToShow: 5,
                slidesToScroll: 2,
                autoplay: true,
                autoplaySpeed: 3500,
                arrows: true,
                pauseOnHover: true,
                cssEase: 'ease-in-out',
                responsive: [
                    {
                        breakpoint: 1200,
                        settings: {
                            slidesToShow: 4,
                            slidesToScroll: 2
                        }
                    },
                    {
                        breakpoint: 992,
                        settings: {
                            slidesToShow: 3,
                            slidesToScroll: 2
                        }
                    },
                    {
                        breakpoint: 768,
                        settings: {
                            slidesToShow: 2,
                            slidesToScroll: 1
                        }
                    },
                    {
                        breakpoint: 576,
                        settings: {
                            slidesToShow: 1,
                            slidesToScroll: 1
                        }
                    }
                ]
            });
            
            // Membership Upgrade Modal
            <?php if ($upgrade_level): ?>
            const upgradeModal = new bootstrap.Modal(document.getElementById('upgradeModal'));
            upgradeModal.show();
            <?php endif; ?>
        });
    </script>
    
    <!-- Membership Upgrade Modal -->
    <?php if ($upgrade_level): ?>
    <div class="modal fade" id="upgradeModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-body text-center p-5">
                    <div class="mb-4">
                        <?php if ($upgrade_level == 'Kim cương'): ?>
                            <i class="fas fa-gem fa-5x text-primary"></i>
                        <?php elseif ($upgrade_level == 'Vàng'): ?>
                            <i class="fas fa-medal fa-5x text-warning"></i>
                        <?php elseif ($upgrade_level == 'Bạc'): ?>
                            <i class="fas fa-medal fa-5x text-secondary"></i>
                        <?php endif; ?>
                    </div>
                    <h2 class="mb-3">🎉 Chúc mừng! 🎉</h2>
                    <h4 class="text-primary mb-3">Bạn đã lên hạng <strong><?=$upgrade_level?></strong>!</h4>
                    <p class="text-muted mb-4">
                        Bạn sẽ được giảm giá tự động cho mọi đơn hàng tiếp theo.
                    </p>
                    <button type="button" class="btn btn-primary btn-lg" data-bs-dismiss="modal">
                        <i class="fas fa-check me-2"></i>Tuyệt vời!
                    </button>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</body>
</html>


