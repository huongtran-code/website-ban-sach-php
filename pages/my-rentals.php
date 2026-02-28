<?php
session_start();
require_once __DIR__ . "/../config/bootstrap.php";
include MODELS_PATH . "func-book.php";
include MODELS_PATH . "func-rental.php";
include MODELS_PATH . "func-settings.php";

if (!isset($_SESSION['customer_id'])) {
    header("Location: login.php?error=Vui lòng đăng nhập");
    exit;
}

$user_id = $_SESSION['customer_id'];
$filter = $_GET['filter'] ?? 'all';

if ($filter == 'all') {
    $rentals = get_user_rentals($conn, $user_id);
} else {
    $rentals = get_user_rentals($conn, $user_id, $filter);
}

$max_late = (int)get_setting($conn, 'rental_max_late', 3);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sách đang thuê - Nhà Sách Online</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../public/css/style.css">
</head>
<body>
    <?php include VIEWS_PATH . "header.php"; ?>

    <div class="container py-4">
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle me-2"></i><?=htmlspecialchars($_GET['success'])?></div>
        <?php endif; ?>
        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i><?=htmlspecialchars($_GET['error'])?></div>
        <?php endif; ?>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-book-reader text-primary me-2"></i>Sách đang thuê</h2>
            <a href="account.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i>Quay lại
            </a>
        </div>
        
        <?php 
        $has_overdue = false;
        foreach ($rentals as $rental) {
            if ($rental['status'] == 'active' && strtotime($rental['end_date']) < time()) {
                $has_overdue = true;
                break;
            }
        }
        
        if ($has_overdue): 
        ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle me-2"></i>
                Bạn có sách đã quá hạn. Vui lòng gia hạn hoặc trả sách sớm để tránh phát sinh phí phạt.
            </div>
        <?php endif; ?>

        <!-- Filter -->
        <div class="mb-4">
            <div class="btn-group">
                <a href="?filter=all" class="btn btn-<?=$filter=='all'?'primary':'outline-primary'?>">Tất cả</a>
                <a href="?filter=active" class="btn btn-<?=$filter=='active'?'success':'outline-success'?>">Đang thuê</a>
                <a href="?filter=returned" class="btn btn-<?=$filter=='returned'?'secondary':'outline-secondary'?>">Đã trả</a>
                <a href="?filter=expired" class="btn btn-<?=$filter=='expired'?'danger':'outline-danger'?>">Quá hạn</a>
            </div>
        </div>

        <?php if (empty($rentals)): ?>
            <div class="text-center py-5">
                <i class="fas fa-book-open fa-4x text-muted mb-3"></i>
                <p class="text-muted">Bạn chưa thuê sách nào</p>
                <a href="index.php" class="btn btn-primary">Khám phá sách</a>
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($rentals as $rental): 
                    $is_overdue = $rental['status'] == 'active' && strtotime($rental['end_date']) < time();
                    $days_left = ceil((strtotime($rental['end_date']) - time()) / 86400);
                    
                    $ext_fee = 0;
                    $daily_rate = 0;
                    $overdue_penalty = 0;
                    $overdue_days = 0;
                    if ($rental['status'] == 'active') {
                        $ext_info = calculate_extension_fee($conn, $rental['id'], 7);
                        if ($ext_info) {
                            $ext_fee = $ext_info['fee'];
                            $daily_rate = $ext_info['daily_rate'];
                        }
                        if ($is_overdue) {
                            $od_info = calculate_overdue_penalty($conn, $rental['id']);
                            if ($od_info && $od_info['is_overdue']) {
                                $overdue_penalty = $od_info['penalty'];
                                $overdue_days = $od_info['days_late'];
                            }
                        }
                    }
                ?>
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card h-100 <?=$is_overdue ? 'border-danger' : ''?>">
                            <?php if ($is_overdue): ?>
                                <div class="position-absolute top-0 end-0 m-2">
                                    <span class="badge bg-danger"><i class="fas fa-exclamation-triangle me-1"></i>Quá hạn</span>
                                </div>
                            <?php endif; ?>
                            <?php if ($rental['late_count'] >= $max_late): ?>
                                <div class="position-absolute top-0 start-0 m-2">
                                    <span class="badge bg-warning text-dark"><i class="fas fa-bell me-1"></i>Cảnh báo</span>
                                </div>
                            <?php endif; ?>
                            <div class="row g-0">
                                <div class="col-4">
                                    <img src="../storage/uploads/cover/<?=$rental['cover']?>" class="img-fluid rounded-start h-100" style="object-fit: cover;" onerror="this.src='https://via.placeholder.com/150x200'">
                                </div>
                                <div class="col-8">
                                    <div class="card-body">
                                        <h6 class="card-title"><?=htmlspecialchars($rental['title'])?></h6>
                                        <p class="card-text small mb-1">
                                            <strong>Trạng thái:</strong>
                                            <?php if ($rental['status'] == 'active'): ?>
                                                <?php if ($is_overdue): ?>
                                                    <span class="badge bg-danger">Quá hạn</span>
                                                <?php else: ?>
                                                    <span class="badge bg-success">Đang thuê</span>
                                                <?php endif; ?>
                                            <?php elseif ($rental['status'] == 'returned'): ?>
                                                <span class="badge bg-secondary">Đã trả</span>
                                            <?php elseif ($rental['status'] == 'expired'): ?>
                                                <span class="badge bg-danger">Hết hạn</span>
                                            <?php endif; ?>
                                        </p>
                                        <p class="card-text small mb-1">
                                            <i class="fas fa-calendar-alt text-muted me-1"></i>
                                            <?=date('d/m/Y', strtotime($rental['start_date']))?> - <?=date('d/m/Y', strtotime($rental['end_date']))?>
                                        </p>
                                        <?php if ($rental['status'] == 'active'): ?>
                                            <p class="card-text small mb-1">
                                                <i class="fas fa-clock text-<?=$days_left <= 0 ? 'danger' : ($days_left <= 3 ? 'warning' : 'success')?> me-1"></i>
                                                <?php if ($days_left <= 0): ?>
                                                    Đã quá hạn <?=abs($days_left)?> ngày
                                                <?php else: ?>
                                                    Còn <?=$days_left?> ngày
                                                <?php endif; ?>
                                            </p>
                                            <?php if ($is_overdue && $overdue_penalty > 0): ?>
                                                <p class="card-text small mb-1">
                                                    <i class="fas fa-exclamation-circle text-danger me-1"></i>
                                                    <span class="text-danger fw-bold">Phí phạt: <?=number_format($overdue_penalty)?>đ</span>
                                                    <small class="text-muted">(<?=$overdue_days?> ngày trễ)</small>
                                                </p>
                                            <?php endif; ?>
                                            <div class="d-flex gap-2 mt-2">
                                                <button type="button" class="btn btn-sm btn-outline-primary"
                                                    onclick="openExtendModal(<?=$rental['id']?>, '<?=addslashes(htmlspecialchars($rental['title']))?>', <?=$daily_rate?>, <?=$overdue_penalty?>)">
                                                    <i class="fas fa-redo me-1"></i>Gia hạn
                                                </button>
                                                <?php
                                                    $return_confirm = "Xác nhận trả sách?";
                                                    if ($overdue_penalty > 0) {
                                                        $return_confirm .= "\n\nPhí phạt quá hạn " . $overdue_days . " ngày: " . number_format($overdue_penalty) . "đ sẽ được trừ từ số dư.";
                                                    }
                                                ?>
                                                <a href="../app/controllers/return-rental.php?id=<?=$rental['id']?>" class="btn btn-sm btn-outline-success" onclick="return confirm('<?=addslashes($return_confirm)?>')">
                                                    <i class="fas fa-check me-1"></i>Trả sách
                                                </a>
                                            </div>
                                        <?php endif; ?>
                                        <?php if ($rental['extend_count'] > 0): ?>
                                            <small class="text-muted d-block mt-2">Đã gia hạn <?=$rental['extend_count']?> lần</small>
                                        <?php endif; ?>
                                        <?php if ($rental['late_count'] > 0): ?>
                                            <small class="text-danger d-block">Trễ hạn <?=$rental['late_count']?> lần</small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Modal Gia hạn -->
    <div class="modal fade" id="extendModal" tabindex="-1" aria-labelledby="extendModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="extendModalLabel">
                        <i class="fas fa-redo me-2"></i>Gia hạn thuê sách
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="fw-bold mb-3" id="extendBookTitle"></p>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Chọn nhanh:</label>
                        <div class="d-flex gap-2 flex-wrap">
                            <button type="button" class="btn btn-outline-primary btn-sm quick-day" onclick="setExtendDays(3)">3 ngày</button>
                            <button type="button" class="btn btn-primary btn-sm quick-day active" onclick="setExtendDays(7)">7 ngày</button>
                            <button type="button" class="btn btn-outline-primary btn-sm quick-day" onclick="setExtendDays(14)">14 ngày</button>
                            <button type="button" class="btn btn-outline-primary btn-sm quick-day" onclick="setExtendDays(30)">30 ngày</button>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label mb-0 fw-bold">Tùy chỉnh số ngày:</label>
                            <span class="badge bg-primary fs-6" id="extendDaysDisplay">7 ngày</span>
                        </div>
                        <input type="range" class="form-range" id="extendDaysSlider" min="1" max="30" value="7" oninput="updateExtendFee()">
                        <div class="d-flex justify-content-between">
                            <small class="text-muted">1 ngày</small>
                            <small class="text-muted">30 ngày</small>
                        </div>
                    </div>
                    
                    <div class="card bg-light border-0">
                        <div class="card-body py-2">
                            <div class="d-flex justify-content-between small mb-1">
                                <span><i class="fas fa-tag text-muted me-1"></i>Giá thuê/ngày:</span>
                                <span id="extendDailyRate" class="fw-bold"></span>
                            </div>
                            <div class="d-flex justify-content-between small mb-1">
                                <span><i class="fas fa-coins text-primary me-1"></i>Phí gia hạn:</span>
                                <span id="extendFeeDisplay" class="fw-bold text-primary"></span>
                            </div>
                            <div id="extendPenaltyRow" class="d-flex justify-content-between small mb-1 text-danger" style="display: none !important;">
                                <span><i class="fas fa-exclamation-circle me-1"></i>Phí phạt quá hạn:</span>
                                <span id="extendPenaltyDisplay" class="fw-bold"></span>
                            </div>
                            <hr class="my-1">
                            <div class="d-flex justify-content-between">
                                <span class="fw-bold"><i class="fas fa-wallet me-1"></i>Tổng thanh toán:</span>
                                <span id="extendTotalDisplay" class="fw-bold text-success fs-5"></span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Hủy
                    </button>
                    <button type="button" class="btn btn-primary" onclick="confirmExtend()">
                        <i class="fas fa-check me-1"></i>Xác nhận gia hạn
                    </button>
                </div>
            </div>
        </div>
    </div>

    <?php include VIEWS_PATH . "footer.php"; ?>
    
    <script>
    let currentExtendRentalId = 0;
    let currentDailyRate = 0;
    let currentOverduePenalty = 0;
    
    function formatNumber(num) {
        return Math.round(num).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    }
    
    function roundTo1000(num) {
        return Math.max(1000, Math.round(num / 1000) * 1000);
    }
    
    function openExtendModal(rentalId, title, dailyRate, overduePenalty) {
        currentExtendRentalId = rentalId;
        currentDailyRate = dailyRate;
        currentOverduePenalty = overduePenalty;
        
        document.getElementById('extendBookTitle').textContent = title;
        document.getElementById('extendDailyRate').textContent = formatNumber(dailyRate) + 'đ';
        document.getElementById('extendDaysSlider').value = 7;
        
        var penaltyRow = document.getElementById('extendPenaltyRow');
        if (overduePenalty > 0) {
            penaltyRow.style.cssText = 'display: flex !important;';
            document.getElementById('extendPenaltyDisplay').textContent = formatNumber(overduePenalty) + 'đ';
        } else {
            penaltyRow.style.cssText = 'display: none !important;';
        }
        
        setExtendDays(7);
        
        var modal = new bootstrap.Modal(document.getElementById('extendModal'));
        modal.show();
    }
    
    function setExtendDays(days) {
        document.getElementById('extendDaysSlider').value = days;
        document.querySelectorAll('.quick-day').forEach(function(btn) {
            btn.classList.remove('btn-primary', 'active');
            btn.classList.add('btn-outline-primary');
        });
        document.querySelectorAll('.quick-day').forEach(function(btn) {
            if (btn.textContent.trim() === days + ' ngày') {
                btn.classList.remove('btn-outline-primary');
                btn.classList.add('btn-primary', 'active');
            }
        });
        updateExtendFee();
    }
    
    function updateExtendFee() {
        var days = parseInt(document.getElementById('extendDaysSlider').value);
        var extFee = roundTo1000(currentDailyRate * days);
        var total = extFee + currentOverduePenalty;
        
        document.getElementById('extendDaysDisplay').textContent = days + ' ngày';
        document.getElementById('extendFeeDisplay').textContent = formatNumber(extFee) + 'đ';
        document.getElementById('extendTotalDisplay').textContent = formatNumber(total) + 'đ';
        
        // Highlight quick button if matching
        document.querySelectorAll('.quick-day').forEach(function(btn) {
            var btnDays = parseInt(btn.textContent);
            if (btnDays === days) {
                btn.classList.remove('btn-outline-primary');
                btn.classList.add('btn-primary', 'active');
            } else {
                btn.classList.remove('btn-primary', 'active');
                btn.classList.add('btn-outline-primary');
            }
        });
    }
    
    function confirmExtend() {
        var days = parseInt(document.getElementById('extendDaysSlider').value);
        var extFee = roundTo1000(currentDailyRate * days);
        var total = extFee + currentOverduePenalty;
        
        var msg = 'Xác nhận gia hạn ' + days + ' ngày?\n\n';
        msg += 'Phí gia hạn: ' + formatNumber(extFee) + 'đ\n';
        if (currentOverduePenalty > 0) {
            msg += 'Phí phạt quá hạn: ' + formatNumber(currentOverduePenalty) + 'đ\n';
        }
        msg += '\nTổng trừ từ số dư: ' + formatNumber(total) + 'đ';
        
        if (confirm(msg)) {
            window.location.href = '../app/controllers/extend-rental.php?id=' + currentExtendRentalId + '&days=' + days;
        }
    }
    </script>
</body>
</html>

