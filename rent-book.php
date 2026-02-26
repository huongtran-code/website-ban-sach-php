<?php
session_start();
include "db_conn.php";
include "php/func-book.php";
include "php/func-user.php";
include "php/func-rental.php";
include "php/func-transaction.php";

if (!isset($_SESSION['customer_id'])) {
    header("Location: login.php?error=Vui lòng đăng nhập để thuê sách");
    exit;
}

$book_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$book = get_book_by_id($conn, $book_id);

if (!$book) {
    header("Location: index.php?error=Không tìm thấy sách");
    exit;
}

$user_id = $_SESSION['customer_id'];
$user = get_user_by_id($conn, $user_id);

// Kiểm tra đã thuê chưa
if (is_book_rented_by_user($conn, $user_id, $book_id)) {
    header("Location: my-rentals.php?error=Bạn đang thuê sách này rồi");
    exit;
}

// Kiểm tra quá hạn và khóa tài khoản
$lock_status = check_overdue_lock_status($conn, $user_id);
if ($lock_status['status'] == 'locked') {
     // Store data for locked screen
     $_SESSION['locked_user_id'] = $user_id;
     $_SESSION['locked_reason'] = $lock_status['reason'];
     $_SESSION['locked_amount'] = $lock_status['shortfall'];
     $_SESSION['locked_penalty'] = $lock_status['penalty'];
     $_SESSION['locked_balance'] = $lock_status['balance'];
     
     header("Location: locked.php");
     exit;
}

// Thiết lập mặc định: số ngày cơ sở + giá thuê cơ sở
// Nếu sách có cấu hình riêng (rental_duration, rental_price) thì dùng, ngược lại mặc định 7 ngày = 30% giá sách
$default_days = (isset($book['rental_duration']) && $book['rental_duration'] > 0) ? (int)$book['rental_duration'] : 7;
$base_rental_price = (isset($book['rental_price']) && $book['rental_price'] > 0)
    ? (float)$book['rental_price']
    : round(((float)$book['price']) * 0.3, -3);

// Số ngày được chọn (từ form), nếu chưa chọn thì dùng mặc định
$selected_days = isset($_POST['rental_days'])
    ? max(1, min(365, (int)$_POST['rental_days']))
    : $default_days;

// Tính giá theo số ngày: tỷ lệ với base_rental_price theo default_days
$price_per_day = $base_rental_price / max(1, $default_days);
// Làm tròn theo 1.000đ gần nhất
$rental_price = round($price_per_day * $selected_days, -3);

// Xử lý form submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $auto_extend = isset($_POST['auto_extend']) ? 1 : 0;
    
    // Kiểm tra số dư
    if ($user['balance'] < $rental_price) {
        header("Location: rent-book.php?id=$book_id&error=Số dư không đủ. Vui lòng nạp thêm tiền.");
        exit;
    }
    
    try {
        $conn->beginTransaction();
        
        // Trừ tiền
        $stmt = $conn->prepare("UPDATE users SET balance = balance - ? WHERE id = ?");
        $stmt->execute([$rental_price, $user_id]);
        
        // Tạo rental với số ngày đã chọn
        create_rental($conn, $user_id, $book_id, $selected_days, $rental_price, $auto_extend);
        
        // Ghi transaction
        add_transaction($conn, $user_id, 'rental', $rental_price, "Thuê sách: " . $book['title'] . " ($selected_days ngày)");
        add_transaction($conn, null, 'revenue_rental', $rental_price, "Cho thuê sách: " . $book['title']);
        
        $conn->commit();
        
        header("Location: my-rentals.php?success=Thuê sách thành công! Bạn có $selected_days ngày để đọc.");
        exit;
    } catch (Exception $e) {
        $conn->rollBack();
        header("Location: rent-book.php?id=$book_id&error=Có lỗi xảy ra: " . $e->getMessage());
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thuê sách - <?=htmlspecialchars($book['title'])?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <?php include "php/header.php"; ?>

    <div class="container py-4">
        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i><?=htmlspecialchars($_GET['error'])?></div>
        <?php endif; ?>

        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header bg-warning">
                        <h4 class="mb-0"><i class="fas fa-book-reader me-2"></i>Thuê sách</h4>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <img src="uploads/cover/<?=$book['cover']?>" class="img-fluid rounded" onerror="this.src='https://via.placeholder.com/200x280'">
                            </div>
                            <div class="col-md-8">
                                <h3><?=htmlspecialchars($book['title'])?></h3>
                                
                                <?php if ($user['balance'] < $rental_price): ?>
                                    <div class="mb-3">
                                        <label class="form-label"><strong>Thời gian thuê (ngày)</strong></label>
                                        <div class="input-group mb-2" style="max-width: 220px;">
                                            <input type="number" class="form-control" id="rental_days" min="1" max="365" value="<?=$selected_days?>" disabled>
                                            <span class="input-group-text">ngày</span>
                                        </div>
                                        <p class="mb-1"><strong>Giá thuê:</strong> <span class="text-danger fs-4"><?=format_price($rental_price)?></span></p>
                                        <p class="mb-1"><strong>Số dư hiện tại:</strong> 
                                            <span class="text-danger"><?=format_price($user['balance'])?></span>
                                        </p>
                                    </div>
                                    <div class="alert alert-danger">
                                        <i class="fas fa-exclamation-triangle me-2"></i>
                                        Số dư không đủ. Bạn cần nạp thêm <strong><?=format_price($rental_price - $user['balance'])?></strong>
                                    </div>
                                    <a href="account.php" class="btn btn-success">
                                        <i class="fas fa-plus-circle me-2"></i>Nạp tiền
                                    </a>
                                <?php else: ?>
                                    <form method="POST">
                                        <div class="mb-3">
                                            <label class="form-label"><strong>Thời gian thuê (ngày)</strong></label>
                                            <div class="input-group mb-2" style="max-width: 220px;">
                                                <input type="number" class="form-control" id="rental_days" name="rental_days" min="1" max="365" value="<?=$selected_days?>">
                                                <span class="input-group-text">ngày</span>
                                            </div>
                                            <p class="mb-1"><strong>Giá thuê:</strong> <span class="text-danger fs-4" id="rental_price_text"><?=format_price($rental_price)?></span></p>
                                            <p class="mb-1"><strong>Số dư hiện tại:</strong> 
                                                <span class="<?=$user['balance'] >= $rental_price ? 'text-success' : 'text-danger'?>"><?=format_price($user['balance'])?></span>
                                            </p>
                                            <small class="text-muted">
                                                Giá được tính tỷ lệ theo số ngày thuê, dựa trên mức <?=format_price($base_rental_price)?> cho <?=$default_days?> ngày.
                                            </small>
                                        </div>

                                        <div class="alert alert-info">
                                            <i class="fas fa-info-circle me-2"></i>
                                            <strong>Lưu ý:</strong>
                                            <ul class="mb-0 mt-2">
                                                <li>Sau khi thuê, bạn có thể đọc sách PDF trong số ngày đã chọn.</li>
                                                <li>Hết thời gian thuê, quyền đọc sách sẽ tự động hết hạn.</li>
                                            </ul>
                                        </div>
                                        
                                        <div class="d-flex gap-2">
                                            <button type="submit" class="btn btn-warning btn-lg">
                                                <i class="fas fa-check me-2"></i>Xác nhận thuê sách
                                            </button>
                                            <a href="book-detail.php?id=<?=$book_id?>" class="btn btn-outline-secondary btn-lg">
                                                <i class="fas fa-arrow-left me-2"></i>Quay lại
                                            </a>
                                        </div>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include "php/footer.php"; ?>
</body>
<script>
    (function() {
        const daysInput = document.getElementById('rental_days');
        const priceText = document.getElementById('rental_price_text');
        const basePrice = <?=json_encode((float)$base_rental_price)?>;
        const defaultDays = <?=json_encode((int)$default_days)?>;
        
        function formatCurrency(amount) {
            return amount.toLocaleString('vi-VN') + 'đ';
        }
        
        function updatePrice() {
            let days = parseInt(daysInput.value, 10);
            if (isNaN(days) || days <= 0) days = defaultDays;
            if (days > 365) days = 365;
            const perDay = basePrice / (defaultDays || 1);
            let price = perDay * days;
            // Làm tròn 1.000đ gần nhất
            price = Math.round(price / 1000) * 1000;
            priceText.textContent = formatCurrency(price);
        }
        
        if (daysInput && priceText) {
            daysInput.addEventListener('input', updatePrice);
        }
    })();
</script>
</html>
