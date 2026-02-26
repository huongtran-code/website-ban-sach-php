<?php 
session_start();
include "db_conn.php";
include "php/func-book.php";
include "php/func-cart.php";
include "php/func-user.php";

$cart_items = 0;
$cart_total = 0;
$user = null;
$balance_insufficient = false;

if (isset($_SESSION['customer_id'])) {
    $cart_items = get_cart_items($conn, $_SESSION['customer_id']);
    $cart_total = get_cart_total($conn, $_SESSION['customer_id']);
    $user = get_user_by_id($conn, $_SESSION['customer_id']);
    if ($user && $cart_total > 0 && $user['balance'] < $cart_total) {
        $balance_insufficient = true;
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giỏ Hàng - Nhà Sách Online</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <?php include "php/header.php"; ?>

    <div class="container py-4">
        <h2 class="mb-4"><i class="fas fa-shopping-cart text-primary"></i> Giỏ hàng của bạn</h2>

        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle me-2"></i><?=htmlspecialchars($_GET['error'])?>
            </div>
        <?php endif; ?>

        <?php if (!isset($_SESSION['customer_id'])): ?>
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle me-2"></i>
                Vui lòng <a href="login.php">đăng nhập</a> để xem giỏ hàng.
            </div>
        <?php elseif ($cart_items == 0): ?>
            <div class="empty-state text-center py-5">
                <i class="fas fa-shopping-cart" style="font-size: 100px; color: #ddd;"></i>
                <h3 class="mt-4">Giỏ hàng trống</h3>
                <p class="text-muted">Hãy thêm sách vào giỏ hàng để mua sắm!</p>
                <a href="index.php" class="btn btn-primary"><i class="fas fa-arrow-left me-2"></i>Tiếp tục mua sắm</a>
            </div>
        <?php else: ?>
            <div class="card">
                <div class="card-body p-0">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th width="100">Hình ảnh</th>
                                <th>Sản phẩm</th>
                                <th width="120">Giá</th>
                                <th width="100">Số lượng</th>
                                <th width="120">Thành tiền</th>
                                <th width="80">Xóa</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cart_items as $item): ?>
                                <tr>
                                    <td>
                                        <img src="uploads/cover/<?=$item['cover']?>" width="60" class="rounded" onerror="this.src='https://via.placeholder.com/60x80'">
                                    </td>
                                    <td>
                                        <strong><?=htmlspecialchars($item['title'])?></strong>
                                        <?php if ($item['stock'] < $item['quantity']): ?>
                                            <br><small class="text-danger">Chỉ còn <?=$item['stock']?> cuốn</small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php 
                                        $original_price = isset($item['original_price']) ? $item['original_price'] : $item['price'];
                                        $final_price = $item['price']; // Đã được tính với discount trong get_cart_items
                                        if ($original_price > $final_price): 
                                        ?>
                                            <small class="text-muted text-decoration-line-through d-block"><?=format_price($original_price)?></small>
                                        <?php endif; ?>
                                        <strong class="text-danger"><?=format_price($final_price)?></strong>
                                    </td>
                                    <td>
                                        <input type="number" 
                                               class="form-control form-control-sm quantity-input" 
                                               value="<?=$item['quantity']?>" 
                                               min="1" 
                                               max="<?=$item['stock']?>" 
                                               data-book-id="<?=$item['book_id']?>"
                                               data-price="<?=$final_price?>"
                                               style="width: 70px;">
                                    </td>
                                    <td><strong class="subtotal-<?=$item['book_id']?>"><?=format_price($final_price * $item['quantity'])?></strong></td>
                                    <td>
                                        <a href="php/remove-cart.php?id=<?=$item['book_id']?>" class="btn btn-danger btn-sm">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="card-footer">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <a href="index.php" class="btn btn-outline-primary"><i class="fas fa-arrow-left me-2"></i>Tiếp tục mua sắm</a>
                        </div>
                        <div class="col-md-6 text-end">
                            <?php if ($user): ?>
                                <p class="mb-1">
                                    <small class="text-muted">Số dư tài khoản:</small> 
                                    <strong class="<?=$balance_insufficient ? 'text-danger' : 'text-success'?>"><?=format_price($user['balance'])?></strong>
                                </p>
                            <?php endif; ?>
                            <h4>Tổng cộng: <span class="text-danger" id="cartTotal"><?=format_price($cart_total)?></span></h4>
                            
                            <?php if ($balance_insufficient): ?>
                                <div class="alert alert-danger mt-2 mb-2 py-2">
                                    <i class="fas fa-exclamation-triangle me-1"></i>
                                    <strong>Số dư không đủ!</strong> Bạn cần nạp thêm <strong><?=format_price($cart_total - $user['balance'])?></strong>
                                </div>
                                <a href="account.php" class="btn btn-warning btn-lg mt-2">
                                    <i class="fas fa-plus-circle me-2"></i>Nạp tiền ngay
                                </a>
                            <?php else: ?>
                                <a href="checkout.php" class="btn btn-success btn-lg mt-2">
                                    <i class="fas fa-credit-card me-2"></i>Thanh toán
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <?php include "php/footer.php"; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function formatPrice(amount) {
            return amount.toLocaleString('vi-VN') + 'đ';
        }

        // Lắng nghe sự kiện thay đổi số lượng
        document.querySelectorAll('.quantity-input').forEach(function(input) {
            input.addEventListener('change', function() {
                const bookId = this.dataset.bookId;
                const price = parseFloat(this.dataset.price);
                const quantity = parseInt(this.value);
                const subtotalEl = document.querySelector('.subtotal-' + bookId);
                const cartTotalEl = document.getElementById('cartTotal');

                // Cập nhật thành tiền ngay lập tức (client-side)
                const subtotal = price * quantity;
                subtotalEl.textContent = formatPrice(subtotal);

                // Gửi AJAX request để cập nhật database
                const formData = new FormData();
                formData.append('book_id', bookId);
                formData.append('quantity', quantity);

                fetch('php/update-cart.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        // Cập nhật tổng tiền từ server
                        if (cartTotalEl) {
                            cartTotalEl.textContent = formatPrice(data.total);
                        }
                        
                        // Cập nhật lại thành tiền từ server (để đảm bảo chính xác)
                        if (subtotalEl) {
                            subtotalEl.textContent = formatPrice(data.subtotal);
                        }
                    } else {
                        alert(data.message || 'Có lỗi xảy ra');
                        // Reload trang để khôi phục giá trị cũ
                        location.reload();
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Có lỗi xảy ra khi cập nhật giỏ hàng: ' + error.message);
                    location.reload();
                });
            });
        });
    </script>
</body>
</html>
