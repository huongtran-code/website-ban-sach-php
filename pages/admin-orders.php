<?php
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_email'])) {
    header("Location: login.php");
    exit;
}

require_once __DIR__ . "/../config/bootstrap.php";
include MODELS_PATH . "func-user.php";
include MODELS_PATH . "func-book.php";

// Lấy danh sách đơn hàng
$stmt = $conn->prepare("SELECT o.*, u.full_name, u.email, u.phone,
                        (SELECT COUNT(*) FROM order_items oi WHERE oi.order_id = o.id) as item_count
                        FROM orders o
                        JOIN users u ON o.user_id = u.id
                        ORDER BY o.created_at DESC");
$stmt->execute();
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Kiểm tra xem đơn hàng có bản cứng không
foreach ($orders as &$order) {
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM order_items WHERE order_id = ? AND book_type = 'hardcopy'");
    $stmt->execute([$order['id']]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $order['has_hardcopy'] = $result['count'] > 0;
}
unset($order);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý đơn hàng - Admin</title>
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
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle me-2"></i><?=htmlspecialchars($_GET['success'])?></div>
        <?php endif; ?>
        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i><?=htmlspecialchars($_GET['error'])?></div>
        <?php endif; ?>

        <h2 class="mb-4"><i class="fas fa-shopping-bag text-primary me-2"></i>Quản lý đơn hàng</h2>

        <div class="card">
            <div class="card-body p-0">
                <?php if (empty($orders)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-box-open fa-4x text-muted mb-3"></i>
                        <p class="text-muted">Chưa có đơn hàng nào</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-dark">
                                <tr>
                                    <th>Mã đơn</th>
                                    <th>Khách hàng</th>
                                    <th>Số lượng</th>
                                    <th>Tổng tiền</th>
                                    <th>Loại</th>
                                    <th>Trạng thái</th>
                                    <th>Ngày đặt</th>
                                    <th>Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orders as $order): ?>
                                    <tr>
                                        <td><strong>#<?=$order['id']?></strong></td>
                                        <td>
                                            <div><?=htmlspecialchars($order['full_name'])?></div>
                                            <small class="text-muted"><?=htmlspecialchars($order['email'])?></small>
                                        </td>
                                        <td><?=$order['item_count']?> sản phẩm</td>
                                        <td><strong><?=format_price($order['total_amount'])?></strong></td>
                                        <td>
                                            <?php if ($order['has_hardcopy']): ?>
                                                <span class="badge bg-primary">Bản cứng</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">PDF</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php 
                                            $status_class = [
                                                'pending' => 'bg-warning',
                                                'pending_cod' => 'bg-warning',
                                                'processing' => 'bg-info',
                                                'shipped' => 'bg-primary',
                                                'completed' => 'bg-success',
                                                'return_requested' => 'bg-warning',
                                                'returned' => 'bg-secondary',
                                                'cancelled' => 'bg-danger'
                                            ];
                                            $status_text = [
                                                'pending' => 'Đang xử lý',
                                                'pending_cod' => 'Chờ giao hàng (COD)',
                                                'processing' => 'Đang chuẩn bị',
                                                'shipped' => 'Đã giao hàng',
                                                'completed' => 'Hoàn thành',
                                                'return_requested' => 'Yêu cầu hoàn trả',
                                                'returned' => 'Đã hoàn trả',
                                                'cancelled' => 'Đã hủy'
                                            ];
                                            $s = $order['status'];
                                            $payment = $order['payment_method'] ?? 'balance';
                                            $channel = $order['payment_channel'] ?? $payment;
                                            ?>
                                            <span class="badge <?=$status_class[$s] ?? 'bg-secondary'?>">
                                                <?=$status_text[$s] ?? $s?>
                                            </span>
                                            <?php if ($payment === 'cod'): ?>
                                                <br><small class="badge bg-info mt-1"><i class="fas fa-truck"></i> COD</small>
                                            <?php elseif ($channel === 'momo_demo'): ?>
                                                <br><small class="badge bg-success mt-1"><i class="fas fa-mobile-alt"></i> MoMo (Demo)</small>
                                            <?php elseif ($channel === 'zalopay_demo'): ?>
                                                <br><small class="badge bg-info mt-1"><i class="fas fa-qrcode"></i> ZaloPay (Demo)</small>
                                            <?php elseif ($channel === 'card_demo'): ?>
                                                <br><small class="badge bg-secondary mt-1"><i class="fas fa-credit-card"></i> Thẻ (Demo)</small>
                                            <?php endif; ?>
                                        </td>
                                        <td><?=date('d/m/Y H:i', strtotime($order['created_at']))?></td>
                                        <td>
                                            <a href="admin-order-detail.php?id=<?=$order['id']?>" class="btn btn-info btn-sm">
                                                <i class="fas fa-eye"></i> Chi tiết
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

