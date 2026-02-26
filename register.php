<?php  
session_start();

if (isset($_SESSION['customer_id'])) {
    header("Location: index.php");
    exit;
}

include "db_conn.php";
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng ký - Nhà Sách Online</title>
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
        .register-card {
            background: #fff;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.2);
            padding: 40px;
            width: 100%;
            max-width: 500px;
        }
    </style>
</head>
<body>
    <div class="register-card">
        <div class="text-center mb-4">
            <div style="font-size: 50px;">📚</div>
            <h2 class="text-primary">Đăng ký tài khoản</h2>
        </div>
        
        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i><?=htmlspecialchars($_GET['error'])?></div>
        <?php endif; ?>

        <form method="POST" action="php/register.php">
            <div class="mb-3">
                <label class="form-label"><i class="fas fa-user me-2"></i>Họ và tên</label>
                <input type="text" name="full_name" class="form-control" required>
            </div>
            
            <div class="mb-3">
                <label class="form-label"><i class="fas fa-envelope me-2"></i>Email</label>
                <input type="email" name="email" class="form-control" required>
            </div>
            
            <div class="mb-3">
                <label class="form-label"><i class="fas fa-phone me-2"></i>Số điện thoại</label>
                <input type="tel" name="phone" class="form-control">
            </div>
            
            <div class="mb-3">
                <label class="form-label"><i class="fas fa-lock me-2"></i>Mật khẩu</label>
                <input type="password" name="password" class="form-control" minlength="6" required>
            </div>
            
            <div class="mb-3">
                <label class="form-label"><i class="fas fa-lock me-2"></i>Xác nhận mật khẩu</label>
                <input type="password" name="confirm_password" class="form-control" minlength="6" required>
            </div>
            
            <button type="submit" class="btn btn-primary w-100 py-3">
                <i class="fas fa-user-plus me-2"></i>Đăng ký
            </button>
        </form>
        
        <div class="text-center mt-4">
            <p>Đã có tài khoản? <a href="login.php">Đăng nhập</a></p>
            <a href="index.php"><i class="fas fa-arrow-left me-2"></i>Quay lại trang chủ</a>
        </div>
    </div>
</body>
</html>
