<?php
// Endpoint tính phí vận chuyển theo khoảng cách địa lý (demo)
// Nhận địa chỉ đầy đủ từ client, gọi API bản đồ (Google Distance Matrix) để lấy distance
// và trả về shipping_fee + distance_km. Nếu lỗi, trả về success=false để client fallback.

session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . "/../../config/database.php";
include __DIR__ . "/../models/func-settings.php";

// Chỉ cho phép khách hàng đã đăng nhập sử dụng
if (!isset($_SESSION['customer_id'])) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập']);
    exit;
}

$address = trim($_POST['address'] ?? '');

if ($address === '') {
    echo json_encode(['success' => false, 'message' => 'Địa chỉ trống']);
    exit;
}

// Địa chỉ kho hàng (có thể lưu trong settings)
$store_address = get_setting($conn, 'store_address', 'TP. Hồ Chí Minh, Việt Nam');

// API key Google (cần tự cấu hình trong settings)
$google_api_key = get_setting($conn, 'google_maps_api_key', '');

if ($google_api_key === '') {
    // Chưa cấu hình API key, fallback cho client
    echo json_encode(['success' => false, 'message' => 'Chưa cấu hình Google Maps API Key']);
    exit;
}

try {
    // Gọi Distance Matrix API (đường đi ô tô)
    $origin      = urlencode($store_address);
    $destination = urlencode($address);
    $url = "https://maps.googleapis.com/maps/api/distancematrix/json?origins={$origin}&destinations={$destination}&mode=driving&language=vi&key={$google_api_key}";

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 10,
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response === false || $httpCode !== 200) {
        echo json_encode(['success' => false, 'message' => 'Không gọi được API bản đồ']);
        exit;
    }

    $data = json_decode($response, true);
    if (!isset($data['rows'][0]['elements'][0]['status']) || $data['rows'][0]['elements'][0]['status'] !== 'OK') {
        echo json_encode(['success' => false, 'message' => 'Không tính được khoảng cách từ địa chỉ']);
        exit;
    }

    $distance_m  = (int) $data['rows'][0]['elements'][0]['distance']['value']; // mét
    $distance_km = $distance_m / 1000.0;

    // Công thức tính phí ship theo khoảng cách (demo)
    $base_fee          = (float) get_setting($conn, 'shipping_base_fee', 20000); // 0–5km
    $extra_fee_per_km  = (float) get_setting($conn, 'shipping_extra_fee_per_km', 3000); // >5km

    $fee = $base_fee;
    if ($distance_km > 5) {
        $extra_km = $distance_km - 5;
        $fee += ceil($extra_km) * $extra_fee_per_km;
    }

    $fee = max(0, round($fee, 0));

    echo json_encode([
        'success'      => true,
        'distance_km'  => round($distance_km, 2),
        'shipping_fee' => $fee,
        'message'      => 'Tính phí ship theo khoảng cách thành công'
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Lỗi nội bộ khi tính phí ship']);
}

