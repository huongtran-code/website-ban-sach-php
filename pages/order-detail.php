<?php
session_start();
require_once __DIR__ . "/../config/bootstrap.php";
include MODELS_PATH . "func-book.php";

if (!isset($_SESSION['customer_id'])) {
    header("Location: login.php");
    exit;
}

$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$user_id = $_SESSION['customer_id'];

// Lấy thông tin đơn hàng
$stmt = $conn->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
$stmt->execute([$order_id, $user_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    header("Location: my-orders.php?error=Không tìm thấy đơn hàng");
    exit;
}

// Lấy chi tiết đơn hàng
$stmt = $conn->prepare("SELECT oi.*, b.title, b.cover, b.file 
                        FROM order_items oi 
                        JOIN books b ON oi.book_id = b.id 
                        WHERE oi.order_id = ?");
$stmt->execute([$order_id]);
$order_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Lấy download history cho các sách PDF
$download_history = [];
foreach ($order_items as $item) {
    if ($item['book_type'] == 'pdf') {
        $stmt = $conn->prepare("SELECT * FROM download_history 
                                WHERE user_id = ? AND book_id = ? AND order_id = ?");
        $stmt->execute([$user_id, $item['book_id'], $order_id]);
        $dl = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($dl) {
            $download_history[$item['book_id']] = $dl;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chi tiết đơn hàng #<?=$order_id?> - Nhà Sách Online</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../public/css/style.css">
</head>
<body>
    <?php include VIEWS_PATH . "header.php"; ?>

    <div class="container py-4">
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i><?=htmlspecialchars($_GET['success'])?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i><?=htmlspecialchars($_GET['error'])?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-receipt me-2"></i>Chi tiết đơn hàng #<?=$order_id?></h2>
            <a href="my-orders.php" class="btn btn-outline-secondary">
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
                                            <img src="../storage/uploads/cover/<?=$item['cover']?>" 
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
                                            <?php if ($item['book_type'] == 'pdf'): ?>
                                                <?php 
                                                $dl_info = $download_history[$item['book_id']] ?? null;
                                                $remaining = $dl_info ? ($dl_info['max_downloads'] - $dl_info['download_count']) : 3;
                                                $can_download = $remaining > 0;
                                                ?>
                                                <div class="mb-2">
                                                    <small class="text-muted">
                                                        Đã download: <?=$dl_info ? $dl_info['download_count'] : 0?>/3<br>
                                                        Còn lại: 
                                                        <?php if ($can_download): ?>
                                                            <span class="badge bg-success"><?=$remaining?> lượt</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-danger">Hết lượt</span>
                                                        <?php endif; ?>
                                                    </small>
                                                </div>
                                                <?php if ($can_download): ?>
                                                    <a href="download-book.php?id=<?=$item['book_id']?>&order_id=<?=$order_id?>" 
                                                       class="btn btn-sm btn-primary">
                                                        <i class="fas fa-download"></i> Download
                                                    </a>
                                                <?php else: ?>
                                                    <button class="btn btn-sm btn-secondary" disabled>
                                                        <i class="fas fa-ban"></i> Hết lượt
                                                    </button>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <?php 
                                                // Hiển thị trạng thái giao hàng dựa trên trạng thái đơn hàng
                                                $shipping_status = [
                                                    'pending' => ['text' => 'Đang xử lý giao hàng', 'class' => 'bg-info'],
                                                    'processing' => ['text' => 'Đang chuẩn bị giao hàng', 'class' => 'bg-info'],
                                                    'shipped' => ['text' => 'Đã giao hàng', 'class' => 'bg-primary'],
                                                    'completed' => ['text' => 'Đã hoàn thành', 'class' => 'bg-success'],
                                                    'return_requested' => ['text' => 'Yêu cầu hoàn trả', 'class' => 'bg-warning'],
                                                    'returned' => ['text' => 'Đã hoàn trả', 'class' => 'bg-secondary'],
                                                    'cancelled' => ['text' => 'Đã hủy', 'class' => 'bg-danger']
                                                ];
                                                $status_info = $shipping_status[$order['status']] ?? ['text' => 'Đang xử lý', 'class' => 'bg-secondary'];
                                                ?>
                                                <span class="badge <?=$status_info['class']?>"><?=$status_info['text']?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Thông tin đơn hàng</h5>
                    </div>
                    <div class="card-body">
                        <p><strong>Mã đơn:</strong> #<?=$order['id']?></p>
                        <p><strong>Ngày đặt:</strong> <?=date('d/m/Y H:i', strtotime($order['created_at']))?></p>
                        <p><strong>Trạng thái:</strong> 
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
                            <span class="badge <?=$status_class[$s] ?? 'bg-secondary'?>">
                                <?=$status_text[$s] ?? $s?>
                            </span>
                        </p>
                        <hr>
                        <h4 class="text-end">
                            Tổng tiền: <span class="text-danger"><?=format_price($order['total_amount'])?></span>
                        </h4>
                        
                        <?php if ($order['status'] == 'shipped'): ?>
                            <hr>
                            <div class="d-grid gap-2">
                                <form action="../app/controllers/confirm-received.php" method="post" style="display: inline;">
                                    <input type="hidden" name="order_id" value="<?=$order_id?>">
                                    <button type="submit" class="btn btn-success w-100" onclick="return confirm('Bạn đã nhận được hàng?')">
                                        <i class="fas fa-check-circle me-2"></i>Đã nhận được hàng
                                    </button>
                                </form>
                                <form action="../app/controllers/request-return.php" method="post" style="display: inline;">
                                    <input type="hidden" name="order_id" value="<?=$order_id?>">
                                    <button type="submit" class="btn btn-warning w-100" onclick="return confirm('Bạn có chắc muốn hoàn trả hàng?')">
                                        <i class="fas fa-undo me-2"></i>Hoàn trả hàng
                                    </button>
                                </form>
                            </div>
                        <?php elseif ($order['status'] == 'completed'): ?>
                            <hr>
                            <div class="d-grid gap-2">
                                <form action="../app/controllers/request-return.php" method="post" style="display: inline;">
                                    <input type="hidden" name="order_id" value="<?=$order_id?>">
                                    <button type="submit" class="btn btn-warning w-100" onclick="return confirm('Bạn có chắc muốn hoàn trả hàng?')">
                                        <i class="fas fa-undo me-2"></i>Hoàn trả hàng
                                    </button>
                                </form>
                            </div>
                        <?php elseif ($order['status'] == 'return_requested'): ?>
                            <hr>
                            <div class="alert alert-warning">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>Yêu cầu hoàn trả đang được xử lý</strong><br>
                                Chúng tôi sẽ liên hệ với bạn sớm nhất.<br>
                                <small class="mt-2 d-block">
                                    <strong>Lưu ý:</strong> Khi hoàn trả thành công, bạn sẽ nhận lại <strong>85%</strong> giá trị đơn hàng (mất 15% phí hoàn trả).
                                </small>
                            </div>
                        <?php elseif ($order['status'] == 'returned'): ?>
                            <hr>
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle me-2"></i>
                                <strong>Đơn hàng đã được hoàn trả thành công!</strong><br>
                                <div class="mt-2">
                                    <strong>Tổng tiền đơn hàng:</strong> <?=format_price($order['total_amount'])?><br>
                                    <strong>Số tiền đã refund:</strong> <span class="text-success"><?=format_price($order['total_amount'] * 0.85)?></span><br>
                                    <strong>Phí hoàn trả (15%):</strong> <span class="text-danger"><?=format_price($order['total_amount'] * 0.15)?></span>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include VIEWS_PATH . "footer.php"; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>



