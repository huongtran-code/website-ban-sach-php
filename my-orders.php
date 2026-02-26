<?php 
session_start();
include "db_conn.php";
include "php/func-book.php";

if (!isset($_SESSION['customer_id'])) {
    header("Location: login.php?error=Vui lòng đăng nhập");
    exit;
}

$stmt = $conn->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$_SESSION['customer_id']]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đơn hàng của tôi - Nhà Sách Online</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <?php include "php/header.php"; ?>

    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-shopping-bag text-warning"></i> Đơn hàng của tôi</h2>
            <a href="account.php" class="btn btn-outline-primary"><i class="fas fa-arrow-left me-2"></i>Quay lại</a>
        </div>

        <?php if (empty($orders)): ?>
            <div class="empty-state text-center py-5">
                <i class="fas fa-box-open" style="font-size: 100px; color: #ddd;"></i>
                <h3 class="mt-4">Chưa có đơn hàng nào</h3>
                <p class="text-muted">Hãy mua sách để có đơn hàng đầu tiên!</p>
                <a href="index.php" class="btn btn-primary"><i class="fas fa-shopping-cart me-2"></i>Mua sắm ngay</a>
            </div>
        <?php else: ?>
            <div class="card">
                <div class="card-body p-0">
                    <table class="table table-hover mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>Mã đơn hàng</th>
                                <th>Tổng tiền</th>
                                <th>Trạng thái</th>
                                <th>Ngày đặt</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td><strong>#<?=$order['id']?></strong></td>
                                    <td class="text-danger"><?=format_price($order['total_amount'])?></td>
                                    <td>
                                        <?php 
                                        $status_class = [
                                            'pending' => 'bg-warning',
                                            'processing' => 'bg-info',
                                            'shipped' => 'bg-primary',
                                            'completed' => 'bg-success',
                                            'return_requested' => 'bg-warning',
                                            'returned' => 'bg-secondary',
                                            'cancelled' => 'bg-danger'
                                        ];
                                        $status_text = [
                                            'pending' => 'Đang xử lý',
                                            'processing' => 'Đang chuẩn bị',
                                            'shipped' => 'Đã giao hàng',
                                            'completed' => 'Hoàn thành',
                                            'return_requested' => 'Yêu cầu hoàn trả',
                                            'returned' => 'Đã hoàn trả',
                                            'cancelled' => 'Đã hủy'
                                        ];
                                        $s = $order['status'];
                                        ?>
                                        <span class="badge <?=$status_class[$s] ?? 'bg-secondary'?>"><?=$status_text[$s] ?? $s?></span>
                                    </td>
                                    <td><?=date('d/m/Y H:i', strtotime($order['created_at']))?></td>
                                    <td>
                                        <a href="order-detail.php?id=<?=$order['id']?>" class="btn btn-info btn-sm">
                                            <i class="fas fa-eye"></i> Chi tiết
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <?php include "php/footer.php"; ?>
</body>
</html>
