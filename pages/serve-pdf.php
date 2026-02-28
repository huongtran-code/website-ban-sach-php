<?php
/**
 * PDF Proxy Server - Serve PDF through PHP to prevent direct access
 * This prevents users from seeing the real file path in the browser
 */
session_start();
require_once __DIR__ . "/../config/bootstrap.php";
include MODELS_PATH . "func-book.php";

$book_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$book_id) {
    http_response_code(403);
    echo "Truy cập bị từ chối";
    exit;
}

// Verify referer - only serve from our own site
$referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
$server_host = $_SERVER['HTTP_HOST'];
if (empty($referer) || strpos($referer, $server_host) === false) {
    http_response_code(403);
    echo "Truy cập bị từ chối - không có referer hợp lệ";
    exit;
}

// Validate session token
$token = isset($_GET['token']) ? $_GET['token'] : '';
$expected_token = md5(session_id() . $book_id . date('Y-m-d'));
if ($token !== $expected_token) {
    http_response_code(403);
    echo "Token không hợp lệ";
    exit;
}

$book = get_book_by_id($conn, $book_id);

if (!$book || empty($book['file'])) {
    http_response_code(404);
    echo "Không tìm thấy sách";
    exit;
}

$file_path = __DIR__ . "/../storage/uploads/files/" . $book['file'];
if (!file_exists($file_path)) {
    http_response_code(404);
    echo "File không tồn tại";
    exit;
}

// Set headers to prevent caching and downloading
header('Content-Type: application/pdf');
header('Content-Disposition: inline'); // inline only, no filename
header('Content-Length: ' . filesize($file_path));
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');
header('X-Content-Type-Options: nosniff');
header('Content-Security-Policy: default-src \'none\'; style-src \'unsafe-inline\'');

// Prevent download by not sending Accept-Ranges
header('Accept-Ranges: none');

readfile($file_path);
exit;
