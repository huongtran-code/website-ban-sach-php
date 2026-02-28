<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$cart_count = 0;
$wishlist_count = 0;
if (isset($_SESSION['customer_id'])) {
    include_once __DIR__ . "/../models/func-cart.php";
    include_once __DIR__ . "/../models/func-wishlist.php";
    $cart_count = get_cart_count($conn, $_SESSION['customer_id']);
    $wishlist_count = get_wishlist_count($conn, $_SESSION['customer_id']);
}
?>
<!-- Header Top Bar -->
<div class="header-top">
    <div class="container">
        <div><i class="fas fa-phone-alt me-1"></i>Hotline: <strong>1900 6656</strong></div>
        <div>
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="admin.php"><i class="fas fa-user-cog me-1"></i>Quản trị</a>
                <span class="mx-2">|</span>
                <a href="logout.php"><i class="fas fa-sign-out-alt me-1"></i>Đăng xuất</a>
            <?php elseif (isset($_SESSION['customer_id'])): ?>
                <a href="account.php"><i class="fas fa-user me-1"></i><?=htmlspecialchars($_SESSION['customer_name'])?></a>
                <span class="mx-2">|</span>
                <a href="logout.php"><i class="fas fa-sign-out-alt me-1"></i>Đăng xuất</a>
            <?php else: ?>
                <a href="login.php"><i class="fas fa-sign-in-alt me-1"></i>Đăng nhập</a>
                <span class="mx-2">|</span>
                <a href="register.php"><i class="fas fa-user-plus me-1"></i>Đăng ký</a>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Header Main -->
<header class="header">
    <div class="header-main">
        <div class="container">
            <a href="index.php" class="logo">
                <span class="logo-icon">📚</span>
                <span>Nhà Sách Online</span>
            </a>

            <div class="search-box">
                <form action="search.php" method="get">
                    <label for="searchInput" class="visually-hidden">Tìm kiếm sách, tác giả</label>
                    <input type="text" id="searchInput" name="key" placeholder="Tìm kiếm sách, tác giả...">
                    <button type="submit" aria-label="Tìm kiếm"><i class="fas fa-search"></i></button>
                </form>
            </div>

            <div class="header-actions">
                <!-- Account link for mobile (visible only on mobile where header-top is hidden) -->
                <div class="mobile-account-link">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <a href="admin.php" title="Quản trị">
                            <i class="fas fa-user-cog"></i>
                            <span>Quản trị</span>
                        </a>
                    <?php elseif (isset($_SESSION['customer_id'])): ?>
                        <a href="account.php" title="Tài khoản">
                            <i class="fas fa-user"></i>
                            <span><?=htmlspecialchars($_SESSION['customer_name'])?></span>
                        </a>
                    <?php else: ?>
                        <a href="login.php" title="Đăng nhập">
                            <i class="fas fa-user"></i>
                            <span>Đăng nhập</span>
                        </a>
                    <?php endif; ?>
                </div>
                <a href="cart.php" title="Giỏ hàng">
                    <i class="fas fa-shopping-cart"></i>
                    <span>Giỏ Hàng</span>
                    <?php if ($cart_count > 0): ?>
                        <span class="badge bg-danger"><?=$cart_count?></span>
                    <?php endif; ?>
                </a>
            </div>
        </div>
    </div>
</header>

<!-- Navigation -->
<nav class="main-nav">
    <div class="container">
        <ul>
            <li><a href="index.php" class="<?=basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''?>"><i class="fas fa-home me-1"></i>Trang chủ</a></li>
            <li><a href="new-books.php" class="<?=basename($_SERVER['PHP_SELF']) == 'new-books.php' ? 'active' : ''?>"><i class="fas fa-book me-1"></i>Sách mới</a></li>
            <li><a href="bestsellers.php" class="<?=basename($_SERVER['PHP_SELF']) == 'bestsellers.php' ? 'active' : ''?>"><i class="fas fa-fire me-1"></i>Bán chạy</a></li>
            <li><a href="promotions.php" class="<?=basename($_SERVER['PHP_SELF']) == 'promotions.php' ? 'active' : ''?>"><i class="fas fa-tags me-1"></i>Khuyến mãi</a></li>
        </ul>
    </div>
</nav>

<?php if (isset($_SESSION['login_warning'])): ?>
    <div class="container mt-3">
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <strong>Cảnh báo:</strong> <?=htmlspecialchars($_SESSION['login_warning'])?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    </div>
    <?php unset($_SESSION['login_warning']); ?>
<?php endif; ?>
