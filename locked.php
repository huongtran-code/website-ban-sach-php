<?php
session_start();
if (!isset($_SESSION['locked_user_id'])) {
    header("Location: login.php");
    exit;
}

$reason = $_SESSION['locked_reason'] ?? 'Tài khoản của bạn đã bị khóa.';
$amount = $_SESSION['locked_amount'] ?? 0;
$penalty = $_SESSION['locked_penalty'] ?? 0;
$balance = $_SESSION['locked_balance'] ?? 0;

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tài khoản bị khóa - Nhà Sách Online</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <style>
        body {
            background: linear-gradient(135deg, #e31837 0%, #c41430 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .locked-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.3);
            max-width: 500px;
            width: 100%;
            padding: 40px;
            text-align: center;
        }
        .locked-icon {
            font-size: 60px;
            color: #dc3545;
            margin-bottom: 20px;
        }
        .amount-box {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
            border: 2px dashed #dee2e6;
        }
        .highlight-amount {
            color: #dc3545;
            font-size: 24px;
            font-weight: 700;
        }
    </style>
</head>
<body>
    <div class="locked-card">
        <i class="fas fa-user-lock locked-icon"></i>
        <h2 class="text-danger fw-bold mb-3">Tài khoản bị khóa tạm thời</h2>
        
        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle me-2"></i><?=htmlspecialchars($_GET['error'])?>
            </div>
        <?php endif; ?>
        
        <div class="alert alert-danger border-0 bg-danger bg-opacity-10 text-danger text-start">
            <i class="fas fa-info-circle me-2"></i><?=htmlspecialchars($reason)?>
        </div>
        
        <div class="amount-box">
            <p class="mb-2 text-muted">Số tiền cần nạp để mở khóa:</p>
            <div class="highlight-amount"><?=number_format($amount, 0, ',', '.')?>đ</div>
            <div class="mt-2 small text-muted">
                (Phí phạt: <?=number_format($penalty)?>đ - Số dư: <?=number_format($balance)?>đ)
            </div>
        </div>
        
        <h5 class="mb-3"><i class="fas fa-university me-2"></i>Hướng dẫn thanh toán</h5>
        <div class="text-start mb-4 bg-light p-3 rounded">
            <p class="mb-1"><strong>Ngân hàng:</strong> Vietcombank</p>
            <p class="mb-1"><strong>Số tài khoản:</strong> 1234567890</p>
            <p class="mb-1"><strong>Chủ tài khoản:</strong> NHA SACH ONLINE</p>
            <p class="mb-0"><strong>Nội dung:</strong> UNLOCK <?=htmlspecialchars($_SESSION['locked_user_id'])?></p>
        </div>
        
        <div class="d-grid gap-2">
            <a href="php/check-unlock.php" class="btn btn-success btn-lg">
                <i class="fas fa-sync-alt me-2"></i>Đã nạp tiền, mở khóa ngay
            </a>
            <a href="javascript:void(0)" onclick="toggleChatWidget()" class="btn btn-primary btn-lg">
                <i class="fas fa-comment-alt me-2"></i>Chat với Admin hỗ trợ
            </a>
            <a href="index.php" class="btn btn-outline-secondary">
                <i class="fas fa-home me-2"></i>Trang chủ
            </a>
        </div>
    </div>
    
    <?php include "php/chat-widget.php"; ?>
</body>
</html>
