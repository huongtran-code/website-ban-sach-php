<?php
session_start();
require_once __DIR__ . "/../config/bootstrap.php";
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Demo thanh toán - Nhà Sách Online</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: #f5f7fb;
        }
        .payment-demo-container {
            max-width: 800px;
            margin: 40px auto;
        }
        .payment-card {
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(15, 35, 52, 0.12);
            overflow: hidden;
        }
        .payment-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
            padding: 20px 24px;
        }
        .payment-header h3 {
            margin: 0;
        }
        .payment-method-btn {
            border-radius: 12px;
            padding: 14px 18px;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 12px;
            border: 1px solid #e2e6f0;
            background: #fff;
            transition: all 0.2s ease;
            cursor: pointer;
        }
        .payment-method-btn:hover {
            box-shadow: 0 8px 20px rgba(15, 35, 52, 0.12);
            transform: translateY(-1px);
        }
        .payment-method-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .payment-method-icon {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 18px;
        }
        .payment-method-icon.visa {
            background: linear-gradient(135deg, #1a1f71, #3b82f6);
        }
        .payment-method-icon.momo {
            background: linear-gradient(135deg, #ae2070, #ff4b8b);
        }
        .payment-method-icon.zalopay {
            background: linear-gradient(135deg, #0ea5e9, #22c55e);
        }
        .badge-demo {
            background: #fee2e2;
            color: #b91c1c;
            font-size: 11px;
            text-transform: uppercase;
            border-radius: 999px;
            padding: 4px 10px;
            font-weight: 700;
        }
    </style>
</head>
<body>
<?php include VIEWS_PATH . "header.php"; ?>

<div class="payment-demo-container">
    <div class="card payment-card">
        <div class="payment-header d-flex justify-content-between align-items-center">
            <div>
                <h3 class="mb-1"><i class="fas fa-credit-card me-2"></i>Demo thanh toán</h3>
                <div class="small text-white-50">
                    Mô phỏng thanh toán nhanh bằng VISA / MasterCard / MoMo / ZaloPay (không trừ tiền thật).
                </div>
            </div>
            <span class="badge-demo">Demo</span>
        </div>
        <div class="card-body">
            <p class="mb-3">
                Đây chỉ là <strong>trang demo</strong> để bạn trải nghiệm giao diện thanh toán. 
                Hệ thống <strong>không kết nối cổng thanh toán thật</strong> và <strong>không yêu cầu nhập thông tin thẻ</strong>.
            </p>

            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                Chọn một phương thức bên dưới, hệ thống sẽ giả lập kết quả <strong>Thanh toán thành công</strong>.
            </div>

            <div class="mt-4">
                <button type="button" class="payment-method-btn w-100" onclick="fakePay('VISA / MasterCard')">
                    <div class="payment-method-info">
                        <div class="payment-method-icon visa">
                            <i class="fas fa-credit-card"></i>
                        </div>
                        <div>
                            <div>VISA / MasterCard</div>
                            <small class="text-muted">Thanh toán bằng thẻ quốc tế</small>
                        </div>
                    </div>
                    <i class="fas fa-chevron-right text-muted"></i>
                </button>

                <button type="button" class="payment-method-btn w-100" onclick="fakePay('MoMo')">
                    <div class="payment-method-info">
                        <div class="payment-method-icon momo">
                            <span class="fw-bold">Mo</span>
                        </div>
                        <div>
                            <div>MoMo</div>
                            <small class="text-muted">Thanh toán qua ví MoMo</small>
                        </div>
                    </div>
                    <i class="fas fa-chevron-right text-muted"></i>
                </button>

                <button type="button" class="payment-method-btn w-100" onclick="fakePay('ZaloPay')">
                    <div class="payment-method-info">
                        <div class="payment-method-icon zalopay">
                            <i class="fas fa-wallet"></i>
                        </div>
                        <div>
                            <div>ZaloPay</div>
                            <small class="text-muted">Thanh toán qua ví ZaloPay</small>
                        </div>
                    </div>
                    <i class="fas fa-chevron-right text-muted"></i>
                </button>
            </div>

            <hr class="my-4">

            <p class="small text-muted mb-2">
                Sau khi thanh toán <strong>thật</strong> bằng bất kỳ phương thức nào (VISA, MasterCard, MoMo, ZaloPay, chuyển khoản ngân hàng),
                vui lòng sử dụng khung chat góc phải dưới màn hình để liên hệ admin và xác minh nạp tiền.
            </p>
            <a href="account.php" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-arrow-left me-1"></i> Quay lại tài khoản
            </a>
        </div>
    </div>
</div>

<?php include VIEWS_PATH . "footer.php"; ?>

<script>
function fakePay(method) {
    const msg = `Đây là giao dịch DEMO.\n\nHệ thống giả lập: Thanh toán bằng ${method} thành công.\n\nVui lòng chat với admin để được xác minh khi bạn thanh toán thật.`;
    alert(msg);
}
</script>
</body>
</html>




