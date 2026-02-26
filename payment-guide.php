<?php session_start(); include "db_conn.php"; ?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hướng Dẫn Thanh Toán - Nhà Sách Online</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <?php include "php/header.php"; ?>

    <div class="container py-5">
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h4 class="mb-0"><i class="fas fa-credit-card me-2"></i>Hướng dẫn thanh toán</h4>
                    </div>
                    <div class="card-body">
                        <h5>Các phương thức thanh toán</h5>
                        
                        <div class="card mb-3">
                            <div class="card-header">
                                <i class="fas fa-money-bill-wave text-success me-2"></i>
                                Thanh toán khi nhận hàng (COD)
                            </div>
                            <div class="card-body">
                                Bạn thanh toán trực tiếp cho nhân viên giao hàng khi nhận sách.
                            </div>
                        </div>
                        
                        <div class="card mb-3">
                            <div class="card-header">
                                <i class="fas fa-university text-primary me-2"></i>
                                Chuyển khoản ngân hàng
                            </div>
                            <div class="card-body">
                                <p><strong>Ngân hàng:</strong> Vietcombank</p>
                                <p><strong>Số TK:</strong> 1234567890</p>
                                <p><strong>Chủ TK:</strong> CONG TY TNHH NHA SACH ONLINE</p>
                                <p><strong>Nội dung:</strong> [Mã đơn hàng] + [SĐT]</p>
                            </div>
                        </div>
                        
                        <div class="card mb-3">
                            <div class="card-header">
                                <i class="fas fa-wallet text-danger me-2"></i>
                                Ví điện tử
                            </div>
                            <div class="card-body">
                                <span class="badge bg-danger me-2">MoMo</span>
                                <span class="badge bg-primary me-2">ZaloPay</span>
                                <span class="badge bg-info me-2">VNPay</span>
                                <span class="badge bg-warning text-dark">ShopeePay</span>
                                <p class="mt-2">Quét mã QR để thanh toán nhanh chóng.</p>
                            </div>
                        </div>
                        
                        <div class="card mb-3">
                            <div class="card-header">
                                <i class="fas fa-piggy-bank text-warning me-2"></i>
                                Số dư tài khoản
                            </div>
                            <div class="card-body">
                                Nạp tiền vào tài khoản và thanh toán nhanh chóng. <a href="deposit.php">Nạp tiền ngay</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include "php/footer.php"; ?>
</body>
</html>
