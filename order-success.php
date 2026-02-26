<?php
session_start();
include "db_conn.php";
include "php/func-book.php";

if (!isset($_SESSION['customer_id'])) {
    header("Location: login.php");
    exit;
}

$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
if (!$order_id) {
    header("Location: index.php");
    exit;
}

$user_id = $_SESSION['customer_id'];

// Lấy thông tin đơn hàng
$stmt = $conn->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
$stmt->execute([$order_id, $user_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    header("Location: index.php?error=Không tìm thấy đơn hàng");
    exit;
}

// Lấy chi tiết đơn hàng
$stmt = $conn->prepare("SELECT oi.*, b.title, b.cover 
                        FROM order_items oi 
                        JOIN books b ON oi.book_id = b.id 
                        WHERE oi.order_id = ?");
$stmt->execute([$order_id]);
$order_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đặt hàng thành công - Nhà Sách Online</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <?php include "php/header.php"; ?>
    
    <?php 
    // Kiểm tra và hiển thị popup lên hạng
    $upgrade_level = $_SESSION['membership_upgrade'] ?? null;
    if ($upgrade_level) {
        unset($_SESSION['membership_upgrade']); // Xóa sau khi lấy
    }
    ?>

    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card border-success">
                    <div class="card-header bg-success text-white text-center">
                        <i class="fas fa-check-circle fa-3x mb-3"></i>
                        <h3 class="mb-0">Đặt hàng thành công!</h3>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <strong>Mã đơn hàng:</strong> #<?=$order['id']?><br>
                            <strong>Ngày đặt:</strong> <?=date('d/m/Y H:i', strtotime($order['created_at']))?><br>
                            <strong>Tổng tiền:</strong> <span class="text-danger"><?=format_price($order['total_amount'])?></span><br>
                            <strong>Phương thức thanh toán:</strong> 
                            <?php
                            $payment_method = $order['payment_method'] ?? 'balance';
                            $payment_channel = $order['payment_channel'] ?? $payment_method;
                            if ($payment_method === 'cod'): ?>
                                <span class="badge bg-primary"><i class="fas fa-truck"></i> Thanh toán khi nhận hàng (COD)</span>
                            <?php else: ?>
                                <?php if ($payment_channel === 'momo_demo'): ?>
                                    <span class="badge bg-success"><i class="fas fa-mobile-alt"></i> Ví MoMo (Demo)</span>
                                <?php elseif ($payment_channel === 'zalopay_demo'): ?>
                                    <span class="badge bg-info"><i class="fas fa-qrcode"></i> ZaloPay (Demo)</span>
                                <?php elseif ($payment_channel === 'card_demo'): ?>
                                    <span class="badge bg-secondary"><i class="fas fa-credit-card"></i> Thẻ VISA/MasterCard (Demo)</span>
                                <?php else: ?>
                                    <span class="badge bg-success"><i class="fas fa-wallet"></i> Số dư tài khoản</span>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                        
                        <?php if (($order['payment_method'] ?? 'balance') === 'cod'): ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Lưu ý:</strong> Bạn đã chọn thanh toán khi nhận hàng. Vui lòng chuẩn bị <strong><?=format_price($order['total_amount'])?></strong> khi nhận hàng.
                        </div>
                        <?php endif; ?>

                        <h5 class="mb-3">Chi tiết đơn hàng:</h5>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Sản phẩm</th>
                                        <th>Loại</th>
                                        <th>Số lượng</th>
                                        <th>Giá</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($order_items as $item): ?>
                                        <tr>
                                            <td>
                                                <img src="uploads/cover/<?=$item['cover']?>" width="50" class="me-2">
                                                <?=htmlspecialchars($item['title'])?>
                                            </td>
                                            <td>
                                                <?php if ($item['book_type'] == 'pdf'): ?>
                                                    <span class="badge bg-danger">PDF</span>
                                                <?php else: ?>
                                                    <span class="badge bg-primary">Bản cứng</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?=$item['quantity']?></td>
                                            <td><?=format_price($item['price'] * $item['quantity'])?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="d-flex gap-2 mt-4">
                            <a href="my-orders.php" class="btn btn-primary">
                                <i class="fas fa-list me-2"></i>Xem đơn hàng của tôi
                            </a>
                            <a href="index.php" class="btn btn-outline-secondary">
                                <i class="fas fa-home me-2"></i>Về trang chủ
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

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
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const modal = new bootstrap.Modal(document.getElementById('upgradeModal'));
            modal.show();
        });
    </script>
    <?php endif; ?>

    <?php include "php/footer.php"; ?>
</body>
</html>

