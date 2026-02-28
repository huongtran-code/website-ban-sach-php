<?php
/**
 * Entry Point - Router đơn giản
 * Tất cả request sẽ được chuyển hướng về đây qua .htaccess
 */

// Lấy tên trang từ query string
$page = isset($_GET['page']) ? $_GET['page'] : 'index';

// Loại bỏ các ký tự nguy hiểm
$page = preg_replace('/[^a-zA-Z0-9_-]/', '', $page);

// Đường dẫn tới file page
$pageFile = __DIR__ . '/../pages/' . $page . '.php';

// Kiểm tra file tồn tại và include
if (file_exists($pageFile)) {
    include $pageFile;
} else {
    // Trang 404
    http_response_code(404);
    ?>
    <!DOCTYPE html>
    <html lang="vi">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>404 - Không tìm thấy trang</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.1/dist/css/bootstrap.min.css" rel="stylesheet">
        <style>
            body {
                background: linear-gradient(135deg, #e31837 0%, #c41430 100%);
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .error-container {
                text-align: center;
                color: white;
            }
            .error-code {
                font-size: 8rem;
                font-weight: bold;
            }
            .error-message {
                font-size: 1.5rem;
                margin-bottom: 2rem;
            }
            .btn-home {
                background: white;
                color: #e31837;
                padding: 12px 30px;
                border-radius: 30px;
                text-decoration: none;
                font-weight: bold;
            }
            .btn-home:hover {
                background: #f8f8f8;
                color: #c41430;
            }
        </style>
    </head>
    <body>
        <div class="error-container">
            <div class="error-code">404</div>
            <div class="error-message">Trang bạn tìm kiếm không tồn tại</div>
            <a href="../pages/index.php" class="btn-home">
                <i class="fas fa-home me-2"></i>Về trang chủ
            </a>
        </div>
    </body>
    </html>
    <?php
}
