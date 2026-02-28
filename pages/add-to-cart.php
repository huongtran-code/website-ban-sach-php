<?php
session_start();
require_once __DIR__ . "/../config/bootstrap.php";
include MODELS_PATH . "func-cart.php";

// Check if user is logged in
if (!isset($_SESSION['customer_id'])) {
    header("Location: login.php?error=Vui lòng đăng nhập để mua hàng");
    exit;
}

if (isset($_GET['id'])) {
    $book_id = (int)$_GET['id'];
    $user_id = $_SESSION['customer_id'];
    $quantity = isset($_GET['qty']) ? max(1, (int)$_GET['qty']) : 1;
    
    // Xác định trang redirect - ưu tiên redirect parameter, sau đó là HTTP_REFERER, cuối cùng là index.php
    $redirect_base = 'index.php';
    $redirect_params = [];
    
    if (isset($_GET['redirect']) && !empty($_GET['redirect'])) {
        $redirect_base = $_GET['redirect'];
    } elseif (isset($_SERVER['HTTP_REFERER']) && !empty($_SERVER['HTTP_REFERER'])) {
        // Lấy URL từ referer
        $referer = $_SERVER['HTTP_REFERER'];
        $parsed = parse_url($referer);
        
        // Lấy path từ referer
        if (isset($parsed['path'])) {
            $path = $parsed['path'];
            // Lấy tên file từ path
            $filename = basename($path);
            
            // Nếu có filename và là file PHP hợp lệ
            if ($filename && preg_match('/\.php$/', $filename)) {
                $redirect_base = $filename;
                
                // Thêm query params nếu có (trừ success và error)
                if (isset($parsed['query'])) {
                    parse_str($parsed['query'], $redirect_params);
                    unset($redirect_params['success']);
                    unset($redirect_params['error']);
                }
            }
        }
    }
    
    // Hàm xây dựng URL redirect với success/error message
    function build_redirect_url($base, $params, $message_type, $message) {
        $params[$message_type] = $message;
        $query = http_build_query($params);
        return $base . '?' . $query;
    }
    
    try {
        // Validate book exists and has stock
        $stmt = $conn->prepare("SELECT id, title, stock FROM books WHERE id = ?");
        $stmt->execute([$book_id]);
        $book = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$book) {
            header("Location: " . build_redirect_url($redirect_base, $redirect_params, 'error', 'Sách không tồn tại'));
            exit;
        }
        
        if ($book['stock'] <= 0) {
            header("Location: " . build_redirect_url($redirect_base, $redirect_params, 'error', 'Sách hết hàng'));
            exit;
        }
        
        // Add to cart
        $result = add_to_cart($conn, $user_id, $book_id, $quantity);
        
        if ($result['success']) {
            header("Location: " . build_redirect_url($redirect_base, $redirect_params, 'success', $result['message']));
        } else {
            header("Location: " . build_redirect_url($redirect_base, $redirect_params, 'error', $result['message']));
        }
        exit;
    } catch (PDOException $e) {
        header("Location: " . build_redirect_url($redirect_base, $redirect_params, 'error', 'Lỗi database: ' . $e->getMessage()));
        exit;
    } catch (Exception $e) {
        header("Location: " . build_redirect_url($redirect_base, $redirect_params, 'error', $e->getMessage()));
        exit;
    }
}

header("Location: index.php");
exit;
