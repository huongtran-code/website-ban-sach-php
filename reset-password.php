<?php  
session_start();
include "db_conn.php";

$token = $_GET['token'] ?? '';
$error = $_GET['error'] ?? '';
$valid_token = false;

if (!empty($token)) {
    // Kiểm tra token
    $stmt = $conn->prepare("SELECT id FROM users WHERE reset_token = ? AND reset_token_expires > NOW()");
    $stmt->execute([$token]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        $valid_token = true;
    }
}

// Nếu không có token hợp lệ, redirect về trang quên mật khẩu
if (!$valid_token && empty($error)) {
    header("Location: forgot-password.php?error=Link đặt lại mật khẩu không hợp lệ hoặc đã hết hạn. Vui lòng yêu cầu link mới.");
    exit;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đặt lại mật khẩu - Nhà Sách Online</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background: linear-gradient(135deg, #e31837 0%, #c41430 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .login-container {
            width: 100%;
            max-width: 450px;
            animation: fadeInUp 0.6s ease-out;
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .login-card {
            background: #fff;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.2);
            padding: 45px 40px;
        }
        
        .logo-section {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .logo-icon {
            font-size: 64px;
            margin-bottom: 15px;
            display: inline-block;
        }
        
        .logo-section h2 {
            color: #e31837;
            font-weight: 700;
            font-size: 28px;
            margin-bottom: 5px;
        }
        
        .logo-section p {
            color: #666;
            font-size: 14px;
        }
        
        .form-label {
            color: #333;
            font-weight: 600;
            margin-bottom: 8px;
            font-size: 14px;
        }
        
        .form-control {
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            padding: 12px 15px;
            font-size: 15px;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: #e31837;
            box-shadow: 0 0 0 0.2rem rgba(227, 24, 55, 0.25);
        }
        
        .input-group-icon {
            position: relative;
        }
        
        .input-group-icon i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
            z-index: 10;
        }
        
        .input-group-icon .form-control {
            padding-left: 45px;
        }
        
        .btn-submit {
            border-radius: 10px;
            padding: 14px;
            font-weight: 600;
            font-size: 16px;
            transition: all 0.3s ease;
            border: none;
            box-shadow: 0 4px 15px rgba(227, 24, 55, 0.3);
            background: #e31837;
            color: white;
            width: 100%;
        }
        
        .btn-submit:hover {
            background: #c41430;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(227, 24, 55, 0.4);
        }
        
        .alert {
            border-radius: 10px;
            border: none;
            padding: 12px 15px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="logo-section">
                <div class="logo-icon">🔑</div>
                <h2>Đặt lại mật khẩu</h2>
                <p>Nhập mật khẩu mới của bạn</p>
            </div>
            
            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <?=htmlspecialchars($_GET['error'])?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if ($valid_token): ?>
                <form method="POST" action="php/reset-password.php">
                    <input type="hidden" name="token" value="<?=htmlspecialchars($token)?>">
                    <div class="mb-3">
                        <label class="form-label">
                            <i class="fas fa-lock me-2"></i>Mật khẩu mới
                        </label>
                        <div class="input-group-icon">
                            <i class="fas fa-lock"></i>
                            <input type="password" name="password" class="form-control" placeholder="Nhập mật khẩu mới" required autofocus>
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label">
                            <i class="fas fa-lock me-2"></i>Xác nhận mật khẩu
                        </label>
                        <div class="input-group-icon">
                            <i class="fas fa-lock"></i>
                            <input type="password" name="confirm_password" class="form-control" placeholder="Nhập lại mật khẩu mới" required>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-submit">
                        <i class="fas fa-check me-2"></i>Đặt lại mật khẩu
                    </button>
                </form>
            <?php else: ?>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Link không hợp lệ hoặc đã hết hạn.
                </div>
                <a href="forgot-password.php" class="btn btn-submit">
                    <i class="fas fa-redo me-2"></i>Yêu cầu link mới
                </a>
            <?php endif; ?>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

