<?php  
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_email'])) {
    header("Location: login.php");
    exit;
}

require_once __DIR__ . "/../config/bootstrap.php";
include MODELS_PATH . "func-user.php";

$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
$user = $user_id > 0 ? get_user_by_id($conn, $user_id) : null;

if (!$user) {
    header("Location: admin-users.php?error=Không tìm thấy người dùng");
    exit;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nạp tiền người dùng - Admin</title>
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
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-plus-circle me-2"></i>
                            Nạp tiền cho: <?=htmlspecialchars($user['full_name'])?> (<?=htmlspecialchars($user['email'])?>)
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label text-muted">Số dư hiện tại</label>
                            <div class="fs-4 fw-bold text-success">
                                <?=number_format($user['balance'], 0, ',', '.')?>đ
                            </div>
                        </div>

                        <form action="../app/controllers/add-deposit.php" method="post">
                            <input type="hidden" name="user_id" value="<?=$user['id']?>">

                            <div class="mb-3">
                                <label class="form-label">Số tiền nạp</label>
                                <div class="input-group">
                                    <input type="number" name="amount" class="form-control" min="1000" step="1000" required>
                                    <span class="input-group-text">đ</span>
                                </div>
                                <small class="text-muted">Tối thiểu 1.000đ, nên dùng bội số 1.000đ.</small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Ghi chú</label>
                                <input type="text" name="description" class="form-control" placeholder="VD: Nạp qua MoMo, nạp tay cho khách...">
                            </div>

                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-save me-1"></i>Nạp tiền
                                </button>
                                <a href="admin-users.php" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left me-1"></i>Quay lại danh sách
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


