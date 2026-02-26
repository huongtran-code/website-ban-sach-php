<?php
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_email'])) {
    header("Location: login.php");
    exit;
}

include "db_conn.php";
include "php/func-user.php";
include "php/func-book.php";

$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Lấy thông tin đơn hàng
$stmt = $conn->prepare("SELECT o.*, u.full_name, u.email, u.phone, u.address
                        FROM orders o
                        JOIN users u ON o.user_id = u.id
                        WHERE o.id = ?");
$stmt->execute([$order_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    header("Location: admin-orders.php?error=Không tìm thấy đơn hàng");
    exit;
}

// Lấy chi tiết đơn hàng
$stmt = $conn->prepare("SELECT oi.*, b.title, b.cover 
                        FROM order_items oi 
                        JOIN books b ON oi.book_id = b.id 
                        WHERE oi.order_id = ?");
$stmt->execute([$order_id]);
$order_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Kiểm tra xem có bản cứng không
$has_hardcopy = false;
foreach ($order_items as $item) {
    if ($item['book_type'] == 'hardcopy') {
        $has_hardcopy = true;
        break;
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chi tiết đơn hàng #<?=$order_id?> - Admin</title>
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
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-receipt me-2"></i>Chi tiết đơn hàng #<?=$order_id?></h2>
            <a href="admin-orders.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>Quay lại
            </a>
        </div>

        <div class="row">
            <div class="col-lg-8">
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-box me-2"></i>Sản phẩm</h5>
                    </div>
                    <div class="card-body">
                        <?php foreach ($order_items as $item): ?>
                            <div class="card mb-3">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col-md-2">
                                            <img src="uploads/cover/<?=$item['cover']?>" 
                                                 class="img-fluid rounded" 
                                                 onerror="this.src='https://via.placeholder.com/100x130'">
                                        </div>
                                        <div class="col-md-6">
                                            <h5><?=htmlspecialchars($item['title'])?></h5>
                                            <p class="mb-1">
                                                <span class="badge bg-<?=$item['book_type'] == 'pdf' ? 'danger' : 'primary'?>">
                                                    <?=$item['book_type'] == 'pdf' ? 'PDF' : 'Bản cứng'?>
                                                </span>
                                            </p>
                                            <p class="text-muted mb-0">
                                                Số lượng: <?=$item['quantity']?><br>
                                                Giá: <?=format_price($item['price'])?>
                                            </p>
                                            <?php if ($item['book_type'] == 'hardcopy' && $item['shipping_address']): ?>
                                                <p class="mt-2 mb-0">
                                                    <small><strong>Địa chỉ giao hàng:</strong><br>
                                                    <?=htmlspecialchars($item['shipping_address'])?></small>
                                                </p>
                                            <?php endif; ?>
                                        </div>
                                        <div class="col-md-4 text-end">
                                            <strong><?=format_price($item['price'] * $item['quantity'])?></strong>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card mb-4">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Thông tin đơn hàng</h5>
                    </div>
                    <div class="card-body">
                        <p><strong>Mã đơn:</strong> #<?=$order['id']?></p>
                        <p><strong>Ngày đặt:</strong> <?=date('d/m/Y H:i', strtotime($order['created_at']))?></p>
                        <p><strong>Trạng thái:</strong> 
                            <span class="badge bg-<?=$order['status'] == 'completed' ? 'success' : ($order['status'] == 'shipped' ? 'primary' : 'warning')?>">
                                <?php
                                $status_text = [
                                    'pending' => 'Đang xử lý',
                                    'processing' => 'Đang chuẩn bị',
                                    'shipped' => 'Đã giao hàng',
                                    'completed' => 'Hoàn thành',
                                    'return_requested' => 'Yêu cầu hoàn trả',
                                    'returned' => 'Đã hoàn trả',
                                    'cancelled' => 'Đã hủy'
                                ];
                                echo $status_text[$order['status']] ?? $order['status'];
                                ?>
                            </span>
                        </p>
                        <hr>
                        <h4 class="text-end">
                            Tổng tiền: <span class="text-danger"><?=format_price($order['total_amount'])?></span>
                        </h4>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="fas fa-user me-2"></i>Thông tin khách hàng</h5>
                    </div>
                    <div class="card-body">
                        <p><strong>Họ tên:</strong> <?=htmlspecialchars($order['full_name'])?></p>
                        <p><strong>Email:</strong> <?=htmlspecialchars($order['email'])?></p>
                        <p><strong>Điện thoại:</strong> <?=htmlspecialchars($order['phone'] ?? 'N/A')?></p>
                        <?php if ($order['address']): ?>
                            <p><strong>Địa chỉ:</strong> <?=htmlspecialchars($order['address'])?></p>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if ($has_hardcopy && $order['status'] != 'cancelled' && $order['status'] != 'returned'): ?>
                    <div class="card">
                        <div class="card-header bg-warning">
                            <h5 class="mb-0"><i class="fas fa-truck me-2"></i>Cập nhật trạng thái</h5>
                        </div>
                        <div class="card-body">
                            <form action="php/update-order-status.php" method="post" onsubmit="return confirmStatusChange(this)">
                                <input type="hidden" name="order_id" value="<?=$order_id?>">
                                
                                <div class="mb-3">
                                    <label class="form-label">Trạng thái mới</label>
                                    <select name="status" class="form-select" required id="statusSelect">
                                        <option value="pending" <?=$order['status'] == 'pending' ? 'selected' : ''?>>Đang xử lý</option>
                                        <option value="processing" <?=$order['status'] == 'processing' ? 'selected' : ''?>>Đang chuẩn bị</option>
                                        <option value="shipped" <?=$order['status'] == 'shipped' ? 'selected' : ''?>>Đã giao hàng</option>
                                        <option value="completed" <?=$order['status'] == 'completed' ? 'selected' : ''?>>Hoàn thành</option>
                                        <option value="return_requested" <?=$order['status'] == 'return_requested' ? 'selected' : ''?>>Yêu cầu hoàn trả</option>
                                        <option value="returned" <?=$order['status'] == 'returned' ? 'selected' : ''?>>Đã hoàn trả</option>
                                        <option value="cancelled" <?=$order['status'] == 'cancelled' ? 'selected' : ''?>>Đã hủy</option>
                                    </select>
                                </div>

                                <?php if ($order['status'] == 'return_requested'): ?>
                                    <div class="alert alert-info mb-3">
                                        <i class="fas fa-info-circle me-2"></i>
                                        <strong>Lưu ý:</strong> Khi xác nhận hoàn trả, hệ thống sẽ:
                                        <ul class="mb-0 mt-2">
                                            <li>Refund <strong>85%</strong> giá trị đơn hàng cho khách hàng</li>
                                            <li>Khách hàng mất <strong>15%</strong> phí hoàn trả</li>
                                            <li>Cộng lại số lượng sách vào kho</li>
                                        </ul>
                                        <div class="mt-2">
                                            <strong>Tổng tiền đơn hàng:</strong> <?=format_price($order['total_amount'])?><br>
                                            <strong>Số tiền refund:</strong> <span class="text-success"><?=format_price($order['total_amount'] * 0.85)?></span><br>
                                            <strong>Phí hoàn trả (15%):</strong> <span class="text-danger"><?=format_price($order['total_amount'] * 0.15)?></span>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <button type="submit" class="btn btn-warning w-100">
                                    <i class="fas fa-save me-2"></i>Cập nhật trạng thái
                                </button>
                            </form>
                        </div>
                    </div>
                <?php elseif ($order['status'] == 'returned'): ?>
                    <div class="card">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0"><i class="fas fa-check-circle me-2"></i>Đã hoàn trả</h5>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-success">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>Đơn hàng đã được hoàn trả thành công!</strong><br>
                                <div class="mt-2">
                                    <strong>Tổng tiền đơn hàng:</strong> <?=format_price($order['total_amount'])?><br>
                                    <strong>Số tiền đã refund:</strong> <span class="text-success"><?=format_price($order['total_amount'] * 0.85)?></span><br>
                                    <strong>Phí hoàn trả (15%):</strong> <span class="text-danger"><?=format_price($order['total_amount'] * 0.15)?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function confirmStatusChange(form) {
            const statusSelect = document.getElementById('statusSelect');
            const selectedStatus = statusSelect.value;
            
            if (selectedStatus === 'returned') {
                return confirm('Bạn có chắc muốn xác nhận hoàn trả? Hệ thống sẽ refund 85% giá trị đơn hàng cho khách hàng (mất 15% phí).');
            }
            return true;
        }
    </script>
</body>
</html>

