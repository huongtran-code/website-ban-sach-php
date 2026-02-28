<?php session_start(); require_once __DIR__ . "/../config/bootstrap.php"; ?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giới Thiệu - Nhà Sách Online</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../public/css/style.css">
</head>
<body>
    <?php include VIEWS_PATH . "header.php"; ?>

    <div class="container py-5">
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0"><i class="fas fa-info-circle me-2"></i>Giới thiệu về Nhà Sách Online</h4>
                    </div>
                    <div class="card-body">
                        <img src="https://via.placeholder.com/800x300?text=Nha+Sach+Online" class="img-fluid rounded mb-4" alt="">
                        
                        <h5>Chào mừng bạn đến với Nhà Sách Online!</h5>
                        <p>Nhà Sách Online là hệ thống bán sách trực tuyến hàng đầu Việt Nam, được thành lập với sứ mệnh mang tri thức đến mọi người một cách dễ dàng và tiện lợi nhất.</p>
                        
                        <h5 class="mt-4">Tầm nhìn</h5>
                        <p>Trở thành nền tảng sách trực tuyến số 1 Việt Nam, nơi mọi người có thể tiếp cận kho tàng tri thức vô hạn.</p>
                        
                        <h5 class="mt-4">Sứ mệnh</h5>
                        <ul>
                            <li>Cung cấp sách chất lượng với giá cả hợp lý</li>
                            <li>Đa dạng thể loại từ văn học, kinh tế đến khoa học, công nghệ</li>
                            <li>Dịch vụ giao hàng nhanh chóng, tận nơi</li>
                            <li>Hỗ trợ khách hàng 24/7</li>
                        </ul>
                        
                        <h5 class="mt-4">Cam kết của chúng tôi</h5>
                        <div class="row mt-3">
                            <div class="col-md-4 text-center mb-3">
                                <i class="fas fa-check-circle text-success fa-3x mb-2"></i>
                                <h6>100% Chính hãng</h6>
                            </div>
                            <div class="col-md-4 text-center mb-3">
                                <i class="fas fa-shipping-fast text-primary fa-3x mb-2"></i>
                                <h6>Giao hàng nhanh</h6>
                            </div>
                            <div class="col-md-4 text-center mb-3">
                                <i class="fas fa-undo text-warning fa-3x mb-2"></i>
                                <h6>Đổi trả dễ dàng</h6>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include VIEWS_PATH . "footer.php"; ?>
</body>
</html>
