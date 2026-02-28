<?php
// Admin Navigation Menu Component
$current_page = basename($_SERVER['PHP_SELF']);
?>
<nav class="admin-nav">
    <div class="container">
        <ul>
            <li><a href="admin.php" class="<?=$current_page == 'admin.php' ? 'active' : ''?>"><i class="fas fa-tachometer-alt"></i><span>Dashboard</span></a></li>
            <li><a href="admin-books.php" class="<?=in_array($current_page, ['admin-books.php', 'edit-book.php']) ? 'active' : ''?>"><i class="fas fa-book"></i><span>Quản lý sách</span></a></li>
            <li><a href="admin-categories.php" class="<?=in_array($current_page, ['admin-categories.php', 'add-category.php', 'edit-category.php']) ? 'active' : ''?>"><i class="fas fa-folder"></i><span>Danh mục</span></a></li>
            <li><a href="admin-authors.php" class="<?=in_array($current_page, ['admin-authors.php', 'edit-author.php']) ? 'active' : ''?>"><i class="fas fa-user-edit"></i><span>Tác giả</span></a></li>
            <li><a href="admin-users.php" class="<?=in_array($current_page, ['admin-users.php', 'add-user.php', 'edit-user.php', 'admin-deposit.php']) ? 'active' : ''?>"><i class="fas fa-users"></i><span>Người dùng</span></a></li>
            <li><a href="admin-rentals.php" class="<?=$current_page == 'admin-rentals.php' ? 'active' : ''?>"><i class="fas fa-book-reader"></i><span>Sách thuê</span></a></li>
            <li><a href="admin-orders.php" class="<?=in_array($current_page, ['admin-orders.php', 'admin-order-detail.php']) ? 'active' : ''?>"><i class="fas fa-shopping-bag"></i><span>Đơn hàng</span></a></li>
            <li><a href="admin-transactions.php" class="<?=$current_page == 'admin-transactions.php' ? 'active' : ''?>"><i class="fas fa-chart-bar"></i><span>Thống kê</span></a></li>
            <li><a href="admin-coupons.php" class="<?=$current_page == 'admin-coupons.php' ? 'active' : ''?>"><i class="fas fa-tags"></i><span>Khuyến mãi</span></a></li>
            <li><a href="admin-chat.php" class="<?=$current_page == 'admin-chat.php' ? 'active' : ''?>"><i class="fas fa-comments"></i><span>Chat</span></a></li>
            <li><a href="admin-settings.php" class="<?=$current_page == 'admin-settings.php' ? 'active' : ''?>"><i class="fas fa-cog"></i><span>Cài đặt</span></a></li>
        </ul>
    </div>
</nav>

