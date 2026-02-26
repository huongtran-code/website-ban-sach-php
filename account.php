<?php 
session_start();
include "db_conn.php";
include "php/func-user.php";
include "php/func-book.php";
include "php/func-transaction.php";
include "php/func-settings.php";
include "php/func-rental.php";

// Kiểm tra và hiển thị popup lên hạng
$upgrade_level = $_SESSION['membership_upgrade'] ?? null;
if ($upgrade_level) {
    unset($_SESSION['membership_upgrade']); // Xóa sau khi lấy
}

if (!isset($_SESSION['customer_id'])) {
    header("Location: login.php?error=Vui lòng đăng nhập");
    exit;
}

$user = get_user_by_id($conn, $_SESSION['customer_id']);
if (!$user) {
    session_destroy();
    header("Location: login.php");
    exit;
}

// Nếu tài khoản đã bị khóa thì đăng xuất và báo lỗi
if (!empty($user['is_banned'])) {
    $reason = $user['ban_reason'] ?? 'Tài khoản của bạn đã bị khóa. Vui lòng liên hệ quản trị viên.';
    session_destroy();
    header("Location: login.php?error=" . urlencode($reason));
    exit;
}

// Get user's orders
$stmt = $conn->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC LIMIT 10");
$stmt->execute([$_SESSION['customer_id']]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get user's transactions
$stmt = $conn->prepare("SELECT * FROM transactions WHERE user_id = ? ORDER BY created_at DESC LIMIT 10");
$stmt->execute([$_SESSION['customer_id']]);
$transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get user's active rentals
$active_rentals = get_user_rentals($conn, $_SESSION['customer_id'], true);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tài khoản - Nhà Sách Online</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <?php include "php/header.php"; ?>

    <div class="container py-4">
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle me-2"></i><?=htmlspecialchars($_GET['success'])?></div>
        <?php endif; ?>
        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i><?=htmlspecialchars($_GET['error'])?></div>
        <?php endif; ?>

        <div class="row">
            <!-- User Info -->
            <div class="col-lg-4 mb-4">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-user me-2"></i>Thông tin tài khoản</h5>
                    </div>
                    <div class="card-body text-center">
                        <div class="mb-3">
                            <i class="fas fa-user-circle fa-5x text-primary"></i>
                        </div>
                        <h4><?=htmlspecialchars($user['full_name'])?></h4>
                        <p class="text-muted mb-2"><i class="fas fa-envelope me-2"></i><?=htmlspecialchars($user['email'])?></p>
                        <?php if ($user['phone']): ?>
                            <p class="text-muted mb-3"><i class="fas fa-phone me-2"></i><?=htmlspecialchars($user['phone'])?></p>
                        <?php endif; ?>
                        
                        <div class="bg-light rounded p-3 mb-3">
                            <small class="text-muted">Số dư tài khoản</small>
                            <h3 class="text-success mb-0"><?=format_price($user['balance'])?></h3>
                        </div>
                        
                        <?php 
                        $membership_level = $user['membership_level'] ?? 'normal';
                        $membership_name = get_membership_name($membership_level);
                        $membership_discount = get_membership_discount($membership_level);
                        $total_spent = isset($user['total_spent']) ? (float)$user['total_spent'] : 0;
                        
                        // Tính hạng tiếp theo
                        $next_level = '';
                        $next_threshold = 0;
                        if ($membership_level == 'normal') {
                            $next_level = 'Bạc';
                            $next_threshold = 5000000;
                        } elseif ($membership_level == 'silver') {
                            $next_level = 'Vàng';
                            $next_threshold = 10000000;
                        } elseif ($membership_level == 'gold') {
                            $next_level = 'Kim cương';
                            $next_threshold = 20000000;
                        }
                        ?>
                        
                        <div class="bg-light rounded p-3 mb-3">
                            <small class="text-muted">Hạng thành viên</small>
                            <h4 class="mb-1">
                                <?php if ($membership_level == 'diamond'): ?>
                                    <i class="fas fa-gem text-primary"></i> <?=$membership_name?>
                                <?php elseif ($membership_level == 'gold'): ?>
                                    <i class="fas fa-medal text-warning"></i> <?=$membership_name?>
                                <?php elseif ($membership_level == 'silver'): ?>
                                    <i class="fas fa-medal text-secondary"></i> <?=$membership_name?>
                                <?php else: ?>
                                    <i class="fas fa-user"></i> <?=$membership_name?>
                                <?php endif; ?>
                            </h4>
                            <?php if ($membership_discount > 0): ?>
                                <small class="text-success">Giảm <?=$membership_discount?>% cho mọi đơn hàng</small>
                            <?php endif; ?>
                            <?php if ($next_threshold > 0): ?>
                                <div class="mt-2">
                                    <small class="text-muted">Còn <?=format_price($next_threshold - $total_spent)?> để lên hạng <?=$next_level?></small>
                                    <div class="progress mt-1" style="height: 8px;">
                                        <?php 
                                        $progress = min(100, ($total_spent / $next_threshold) * 100);
                                        ?>
                                        <div class="progress-bar" role="progressbar" style="width: <?=$progress?>%"></div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="bg-light rounded p-3 mb-3">
                            <small class="text-muted">Tổng tiền đã mua</small>
                            <h5 class="mb-0"><?=format_price($total_spent)?></h5>
                        </div>
                        
                        <button class="btn btn-success w-100" data-bs-toggle="modal" data-bs-target="#depositModal">
                            <i class="fas fa-plus-circle me-2"></i>Nạp tiền
                        </button>
                    </div>
                </div>
                
                <!-- Quick Actions -->
                <div class="card mt-3">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="fas fa-bolt me-2"></i>Thao tác nhanh</h6>
                    </div>
                    <div class="list-group list-group-flush">
                        <a href="my-rentals.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-book-reader me-2 text-warning"></i>Sách đang thuê
                        </a>
                        <a href="my-orders.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-shopping-bag me-2 text-primary"></i>Đơn hàng của tôi
                        </a>
                        <a href="logout.php" class="list-group-item list-group-item-action text-danger">
                            <i class="fas fa-sign-out-alt me-2"></i>Đăng xuất
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Orders & Transactions -->
            <div class="col-lg-8">
                <!-- Recent Orders -->
                <div class="card mb-4">
                    <div class="card-header bg-warning d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-shopping-bag me-2"></i>Đơn hàng gần đây</h5>
                        <a href="my-orders.php" class="btn btn-light btn-sm">Xem tất cả</a>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($orders)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-box-open fa-3x text-muted mb-2"></i>
                                <p class="text-muted mb-0">Chưa có đơn hàng nào</p>
                            </div>
                        <?php else: ?>
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Mã đơn</th>
                                        <th>Tổng tiền</th>
                                        <th>Trạng thái</th>
                                        <th>Ngày đặt</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($orders as $order): ?>
                                        <tr>
                                            <td><a href="order-detail.php?id=<?=$order['id']?>">#<?=$order['id']?></a></td>
                                            <td><?=format_price($order['total_amount'])?></td>
                                            <td>
                                                <?php 
                                                $status_class = [
                                                    'pending' => 'bg-warning',
                                                    'confirmed' => 'bg-info',
                                                    'shipped' => 'bg-primary',
                                                    'delivered' => 'bg-success',
                                                    'cancelled' => 'bg-danger'
                                                ];
                                                $status_text = [
                                                    'pending' => 'Chờ xử lý',
                                                    'confirmed' => 'Đã xác nhận',
                                                    'shipped' => 'Đang giao',
                                                    'delivered' => 'Đã giao',
                                                    'cancelled' => 'Đã hủy'
                                                ];
                                                $s = $order['status'];
                                                ?>
                                                <span class="badge <?=$status_class[$s] ?? 'bg-secondary'?>"><?=$status_text[$s] ?? $s?></span>
                                            </td>
                                            <td><?=date('d/m/Y', strtotime($order['created_at']))?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Active Rentals -->
                <div class="card mb-4">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0"><i class="fas fa-book-reader me-2"></i>Sách đang thuê</h5>
                    </div>
                    <div class="card-body p-0">
                        <?php if ($active_rentals == 0): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-book-open fa-3x text-muted mb-2"></i>
                                <p class="text-muted mb-0">Hiện bạn chưa thuê sách nào.</p>
                            </div>
                        <?php else: ?>
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Sách</th>
                                        <th>Giá thuê</th>
                                        <th>Bắt đầu</th>
                                        <th>Kết thúc</th>
                                        <th>Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($active_rentals as $r): ?>
                                        <tr>
                                            <td>
                                                <strong><?=htmlspecialchars($r['title'])?></strong>
                                            </td>
                                            <td class="text-danger"><?=format_price($r['price'])?></td>
                                            <td><?=date('d/m/Y H:i', strtotime($r['start_date']))?></td>
                                            <td><?=date('d/m/Y H:i', strtotime($r['end_date']))?></td>
                                            <td>
                                                <a href="preview-book.php?id=<?=$r['book_id']?>" class="btn btn-sm btn-primary">
                                                    <i class="fas fa-book-open me-1"></i>Đọc ngay
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Transaction History -->
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="fas fa-history me-2"></i>Lịch sử giao dịch</h5>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($transactions)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-receipt fa-3x text-muted mb-2"></i>
                                <p class="text-muted mb-0">Chưa có giao dịch nào</p>
                            </div>
                        <?php else: ?>
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Loại</th>
                                        <th>Mô tả</th>
                                        <th>Số tiền</th>
                                        <th>Thời gian</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($transactions as $t): 
                                        $ti = get_transaction_type_info($t['type']);
                                    ?>
                                        <tr>
                                            <td>
                                                <span class="badge bg-<?=$ti['color']?>">
                                                    <i class="fas <?=$ti['icon']?> me-1"></i><?=$ti['label']?>
                                                </span>
                                            </td>
                                            <td><?=htmlspecialchars($t['description'] ?? '')?></td>
                                            <td>
                                                <?php
                                                $is_out = in_array($t['type'], ['purchase','rental','rental_extend','rental_penalty']);
                                                ?>
                                                <strong class="<?=$is_out ? 'text-danger' : 'text-success'?>">
                                                    <?=$is_out ? '-' : '+'?><?=format_price($t['amount'])?>
                                                </strong>
                                            </td>
                                            <td><?=date('d/m/Y H:i', strtotime($t['created_at']))?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Deposit Modal -->
    <div class="modal fade" id="depositModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title"><i class="fas fa-plus-circle me-2"></i>Nạp tiền vào tài khoản</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <?php
                    $momo_qr_url = get_setting($conn, 'momo_qr_url', '');
                    $zalopay_qr_url = get_setting($conn, 'zalopay_qr_url', '');
                    ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        Bạn có thể nạp tiền bằng các phương thức sau. Đây là <strong>mô phỏng (demo)</strong>, hệ thống không trừ tiền thật.
                    </div>
                    
                    <ul class="nav nav-tabs mb-3" id="depositTab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="bank-tab" data-bs-toggle="tab" data-bs-target="#bankTab" type="button" role="tab">
                                <i class="fas fa-university me-1"></i> Chuyển khoản ngân hàng
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="card-tab" data-bs-toggle="tab" data-bs-target="#cardTab" type="button" role="tab">
                                <i class="fas fa-credit-card me-1"></i> Thẻ VISA / MasterCard
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="wallet-tab" data-bs-toggle="tab" data-bs-target="#walletTab" type="button" role="tab">
                                <i class="fas fa-qrcode me-1"></i> Ví MoMo / ZaloPay
                            </button>
                        </li>
                    </ul>
                    
                    <div class="tab-content">
                        <div class="tab-pane fade show active" id="bankTab" role="tabpanel" aria-labelledby="bank-tab">
                            <div class="card bg-light mb-3">
                                <div class="card-body">
                                    <h6 class="mb-2"><strong>Chuyển khoản ngân hàng</strong></h6>
                                    <p class="mb-1"><strong>Ngân hàng:</strong> Vietcombank</p>
                                    <p class="mb-1"><strong>Số TK:</strong> 1234567890</p>
                                    <p class="mb-1"><strong>Chủ TK:</strong> CONG TY TNHH NHA SACH ONLINE</p>
                                    <p class="mb-0"><strong>Nội dung:</strong> NAP <?=$user['id']?> <?=$user['email']?></p>
                                </div>
                            </div>
                            <p class="text-muted small mb-0">
                                Sau khi chuyển khoản <strong>thật</strong>, vui lòng chat với admin để được xác minh và cộng tiền.
                            </p>
                        </div>
                        
                        <div class="tab-pane fade" id="cardTab" role="tabpanel" aria-labelledby="card-tab">
                            <div class="card bg-light mb-3">
                                <div class="card-body">
                                    <h6 class="mb-3"><strong>Hệ thống giả lập: Thanh toán bằng VISA / MasterCard</strong></h6>
                                    <div class="row g-3">
                                        <div class="col-12">
                                            <label class="form-label"><strong>Số tiền nạp (VNĐ)</strong></label>
                                            <input type="number" class="form-control" id="demoCardAmount" placeholder="100000" min="10000" step="10000" value="100000">
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label">Số thẻ</label>
                                            <input type="text" class="form-control" id="demoCardNumber" placeholder="1234 5678 9012 3456" maxlength="19">
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label">Tên chủ thẻ</label>
                                            <input type="text" class="form-control" id="demoCardName" placeholder="NGUYEN VAN A" style="text-transform: uppercase;">
                                        </div>
                                        <div class="col-4">
                                            <label class="form-label">Tháng</label>
                                            <input type="text" class="form-control" id="demoCardExpMonth" placeholder="MM" maxlength="2">
                                        </div>
                                        <div class="col-4">
                                            <label class="form-label">Năm</label>
                                            <input type="text" class="form-control" id="demoCardExpYear" placeholder="YY" maxlength="2">
                                        </div>
                                        <div class="col-4">
                                            <label class="form-label">CVC</label>
                                            <input type="text" class="form-control" id="demoCardCvc" placeholder="123" maxlength="4">
                                        </div>
                                    </div>
                                    <button type="button" class="btn btn-primary w-100 mt-3" id="btnCardPayment">
                                        <i class="fas fa-credit-card me-1"></i> Thanh toán
                                    </button>
                                    <p class="text-muted small mt-2 mb-0">
                                        <i class="fas fa-info-circle me-1"></i>Đây là demo, hệ thống không trừ tiền thật.
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="tab-pane fade" id="walletTab" role="tabpanel" aria-labelledby="wallet-tab">
                            <div class="mb-3">
                                <label class="form-label"><strong>Số tiền nạp (VNĐ)</strong></label>
                                <input type="number" class="form-control" id="demoWalletAmount" placeholder="100000" min="10000" step="10000" value="100000">
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <h6><strong><i class="fas fa-mobile-alt text-danger me-1"></i>MoMo</strong></h6>
                                    <?php if ($momo_qr_url): ?>
                                        <img src="<?=htmlspecialchars($momo_qr_url)?>" alt="MoMo QR" class="img-fluid rounded border mb-2" style="max-height: 200px;">
                                    <?php else: ?>
                                        <div class="border rounded p-3 text-muted small mb-2 text-center" style="min-height: 150px; display: flex; align-items: center; justify-content: center;">
                                            <span>Chưa cấu hình QR MoMo</span>
                                        </div>
                                    <?php endif; ?>
                                    <button type="button" class="btn btn-danger w-100" id="btnMomoPayment">
                                        <i class="fas fa-check-circle me-1"></i> Xác nhận thanh toán MoMo
                                    </button>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <h6><strong><i class="fas fa-mobile-alt text-primary me-1"></i>ZaloPay</strong></h6>
                                    <?php if ($zalopay_qr_url): ?>
                                        <img src="<?=htmlspecialchars($zalopay_qr_url)?>" alt="ZaloPay QR" class="img-fluid rounded border mb-2" style="max-height: 200px;">
                                    <?php else: ?>
                                        <div class="border rounded p-3 text-muted small mb-2 text-center" style="min-height: 150px; display: flex; align-items: center; justify-content: center;">
                                            <span>Chưa cấu hình QR ZaloPay</span>
                                        </div>
                                    <?php endif; ?>
                                    <button type="button" class="btn btn-primary w-100" id="btnZaloPayment">
                                        <i class="fas fa-check-circle me-1"></i> Xác nhận thanh toán ZaloPay
                                    </button>
                                </div>
                            </div>
                            <p class="text-muted small mb-0">
                                <i class="fas fa-info-circle me-1"></i>Đây là demo, hệ thống không kiểm tra giao dịch thật.
                            </p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" onclick="openChatWithAdmin()">
                        <i class="fas fa-comments me-2"></i>Chat với admin
                    </button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                </div>
            </div>
        </div>
    </div>

    <?php include "php/footer.php"; ?>
    
    <script>
        function openChatWithAdmin() {
            const chatWidget = document.querySelector('#chatWidgetToggle, .chat-widget-toggle');
            if (chatWidget) chatWidget.click();
            const modal = bootstrap.Modal.getInstance(document.getElementById('depositModal'));
            if (modal) modal.hide();
        }
        
        // Hàm nạp tiền qua API
        function processDeposit(amount, method) {
            if (!amount || amount < 10000) {
                alert('Vui lòng nhập số tiền nạp tối thiểu 10.000đ');
                return;
            }
            
            fetch('php/demo-deposit.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'amount=' + amount + '&method=' + encodeURIComponent(method)
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    alert('✅ Nạp tiền thành công!\n\nSố tiền: ' + data.amount_formatted + '\nPhương thức: ' + method + '\n\n(Đây là demo, không trừ tiền thật)');
                    location.reload();
                } else {
                    alert('❌ Lỗi: ' + data.message);
                }
            })
            .catch(err => {
                alert('❌ Có lỗi xảy ra: ' + err.message);
            });
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            // Thanh toán thẻ VISA/MasterCard
            var btnCard = document.getElementById('btnCardPayment');
            if (btnCard) {
                btnCard.addEventListener('click', function() {
                    const amount = parseInt(document.getElementById('demoCardAmount').value) || 0;
                    const number = document.getElementById('demoCardNumber').value.trim();
                    const name = document.getElementById('demoCardName').value.trim();
                    const month = document.getElementById('demoCardExpMonth').value.trim();
                    const year = document.getElementById('demoCardExpYear').value.trim();
                    const cvc = document.getElementById('demoCardCvc').value.trim();
                    
                    if (!number || !name || !month || !year || !cvc) {
                        alert('Vui lòng điền đầy đủ thông tin thẻ');
                        return;
                    }
                    
                    processDeposit(amount, 'VISA/MasterCard');
                });
            }
            
            // Thanh toán MoMo
            var btnMomo = document.getElementById('btnMomoPayment');
            if (btnMomo) {
                btnMomo.addEventListener('click', function() {
                    const amount = parseInt(document.getElementById('demoWalletAmount').value) || 0;
                    processDeposit(amount, 'MoMo');
                });
            }
            
            // Thanh toán ZaloPay
            var btnZalo = document.getElementById('btnZaloPayment');
            if (btnZalo) {
                btnZalo.addEventListener('click', function() {
                    const amount = parseInt(document.getElementById('demoWalletAmount').value) || 0;
                    processDeposit(amount, 'ZaloPay');
                });
            }
            
            // Membership Upgrade Modal
            <?php if ($upgrade_level): ?>
            var upgradeModalEl = document.getElementById('upgradeModal');
            if (upgradeModalEl) {
                const upgradeModal = new bootstrap.Modal(upgradeModalEl);
                upgradeModal.show();
            }
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
