<?php session_start(); require_once __DIR__ . "/../config/bootstrap.php"; ?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liên Hệ - Nhà Sách Online</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../public/css/style.css">
</head>
<body>
    <?php include VIEWS_PATH . "header.php"; ?>

    <div class="container py-5">
        <div class="row">
            <div class="col-lg-6 mb-4">
                <div class="card h-100">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0"><i class="fas fa-envelope me-2"></i>Liên hệ với chúng tôi</h4>
                    </div>
                    <div class="card-body">
                        <form>
                            <div class="mb-3">
                                <label class="form-label">Họ và tên</label>
                                <input type="text" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Số điện thoại</label>
                                <input type="tel" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Nội dung</label>
                                <textarea class="form-control" rows="5" required></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-paper-plane me-2"></i>Gửi tin nhắn
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 mb-4">
                <div class="card h-100">
                    <div class="card-header bg-success text-white">
                        <h4 class="mb-0"><i class="fas fa-map-marker-alt me-2"></i>Thông tin liên hệ</h4>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled">
                            <li class="mb-4">
                                <h5><i class="fas fa-building text-primary me-2"></i>Trụ sở chính</h5>
                                <p class="mb-0">123 Đường Sách, Quận 1, TP. Hồ Chí Minh</p>
                            </li>
                            <li class="mb-4">
                                <h5><i class="fas fa-phone text-success me-2"></i>Hotline</h5>
                                <p class="mb-0">1900 6656 (8:00 - 22:00)</p>
                            </li>
                            <li class="mb-4">
                                <h5><i class="fas fa-envelope text-danger me-2"></i>Email</h5>
                                <p class="mb-0">info@nhasachonline.com</p>
                                <p class="mb-0">hotro@nhasachonline.com</p>
                            </li>
                            <li class="mb-4">
                                <h5><i class="fas fa-clock text-warning me-2"></i>Giờ làm việc</h5>
                                <p class="mb-0">Thứ 2 - Thứ 6: 8:00 - 18:00</p>
                                <p class="mb-0">Thứ 7 - Chủ nhật: 9:00 - 17:00</p>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include VIEWS_PATH . "footer.php"; ?>
</body>
</html>
