<?php session_start(); include "db_conn.php"; ?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chính Sách Đổi Trả - Nhà Sách Online</title>
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
                    <div class="card-header bg-warning">
                        <h4 class="mb-0"><i class="fas fa-undo me-2"></i>Chính sách đổi trả hàng</h4>
                    </div>
                    <div class="card-body">
                        <h5>1. Điều kiện đổi trả</h5>
                        <ul>
                            <li>Sản phẩm còn nguyên vẹn, chưa qua sử dụng</li>
                            <li>Còn đầy đủ bao bì, tem nhãn</li>
                            <li>Trong vòng 7 ngày kể từ ngày nhận hàng</li>
                            <li>Có hóa đơn mua hàng</li>
                        </ul>
                        
                        <h5 class="mt-4">2. Trường hợp được đổi trả</h5>
                        <ul>
                            <li>Sách bị lỗi in ấn (thiếu trang, mờ chữ, rách...)</li>
                            <li>Sách giao không đúng với đơn đặt hàng</li>
                            <li>Sách bị hư hỏng trong quá trình vận chuyển</li>
                        </ul>
                        
                        <h5 class="mt-4">3. Quy trình đổi trả</h5>
                        <ol>
                            <li>Liên hệ hotline 1900 6656 hoặc email hotro@nhasach.com</li>
                            <li>Cung cấp mã đơn hàng và lý do đổi trả</li>
                            <li>Gửi sản phẩm về địa chỉ của chúng tôi</li>
                            <li>Nhận sản phẩm mới hoặc hoàn tiền trong 3-5 ngày làm việc</li>
                        </ol>
                        
                        <div class="alert alert-info mt-4">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Lưu ý:</strong> Chi phí vận chuyển đổi trả sẽ do Nhà Sách Online chịu nếu lỗi thuộc về chúng tôi.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include "php/footer.php"; ?>
</body>
</html>
