<?php
/**
 * Bootstrap file - Định nghĩa các đường dẫn cơ bản
 * Include file này ở đầu mỗi trang
 */

// Đường dẫn gốc của project
define('ROOT_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);

// Các đường dẫn thư mục
define('APP_PATH', ROOT_PATH . 'app' . DIRECTORY_SEPARATOR);
define('CONFIG_PATH', ROOT_PATH . 'config' . DIRECTORY_SEPARATOR);
define('PAGES_PATH', ROOT_PATH . 'pages' . DIRECTORY_SEPARATOR);
define('PUBLIC_PATH', ROOT_PATH . 'public' . DIRECTORY_SEPARATOR);
define('STORAGE_PATH', ROOT_PATH . 'storage' . DIRECTORY_SEPARATOR);

// Các đường dẫn con của app
define('CONTROLLERS_PATH', APP_PATH . 'controllers' . DIRECTORY_SEPARATOR);
define('MODELS_PATH', APP_PATH . 'models' . DIRECTORY_SEPARATOR);
define('VIEWS_PATH', APP_PATH . 'views' . DIRECTORY_SEPARATOR);

// Các đường dẫn storage
define('UPLOADS_PATH', STORAGE_PATH . 'uploads' . DIRECTORY_SEPARATOR);
define('CACHE_PATH', STORAGE_PATH . 'cache' . DIRECTORY_SEPARATOR);

// URL base (có thể thay đổi theo môi trường)
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$script_dir = dirname($_SERVER['SCRIPT_NAME']);
$base_url = rtrim($protocol . $host . $script_dir, '/');

// Điều chỉnh base URL nếu đang ở trong thư mục pages
if (strpos($script_dir, '/pages') !== false) {
    $base_url = str_replace('/pages', '', $base_url);
}

define('BASE_URL', $base_url);
define('PUBLIC_URL', BASE_URL . '/public');
define('UPLOADS_URL', BASE_URL . '/storage/uploads');

// Include database connection
require_once CONFIG_PATH . 'database.php';
