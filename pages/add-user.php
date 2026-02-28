<?php  
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_email'])) {
    header("Location: login.php");
    exit;
}

require_once __DIR__ . "/../config/bootstrap.php";
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thêm người dùng - Quản trị</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../public/css/style.css">
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

    <?php include VIEWS_PATH . "admin-nav.php"; ?>

    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-user-plus me-2"></i>Thêm người dùng mới</h5>
                    </div>
                    <div class="card-body p-4">
                        <?php if (isset($_GET['error'])): ?>
                            <div class="alert alert-danger"><?=htmlspecialchars($_GET['error'])?></div>
                        <?php endif; ?>
                        <?php if (isset($_GET['success'])): ?>
                            <div class="alert alert-success"><?=htmlspecialchars($_GET['success'])?></div>
                        <?php endif; ?>

                        <form action="../app/controllers/add-user.php" method="post">
                            <div class="mb-3">
                                <label class="form-label"><i class="fas fa-user me-1"></i>Họ và tên</label>
                                <input type="text" class="form-control" name="full_name" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label"><i class="fas fa-envelope me-1"></i>Email</label>
                                <input type="email" class="form-control" name="email" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label"><i class="fas fa-lock me-1"></i>Mật khẩu</label>
                                <input type="password" class="form-control" name="password" required minlength="6">
                                <small class="text-muted">Tối thiểu 6 ký tự</small>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label"><i class="fas fa-phone me-1"></i>Điện thoại</label>
                                    <input type="tel" class="form-control" name="phone">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label"><i class="fas fa-wallet me-1"></i>Số dư ban đầu (VNĐ)</label>
                                    <input type="number" class="form-control" name="balance" value="0" min="0" step="1000">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label"><i class="fas fa-map-marker-alt me-1"></i>Địa chỉ</label>
                                <textarea class="form-control" name="address" rows="2"></textarea>
                            </div>

                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i>Lưu người dùng
                                </button>
                                <a href="admin-users.php" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left me-1"></i>Quay lại
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>


