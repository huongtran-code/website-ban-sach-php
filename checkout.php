<?php
session_start();
include "db_conn.php";
include "php/func-cart.php";
include "php/func-user.php";
include "php/func-book.php";
include "php/func-settings.php";

// Lấy phí COD từ settings
$cod_fee_percent = (float)get_setting($conn, 'cod_fee_percent', 2);

if (!isset($_SESSION['customer_id'])) {
    header("Location: login.php?error=Vui lòng đăng nhập để thanh toán");
    exit;
}

$user_id = $_SESSION['customer_id'];
$cart_items = get_cart_items($conn, $user_id);
$cart_total = get_cart_total($conn, $user_id);
$user = get_user_by_id($conn, $user_id);

// Calculate membership discount
$membership_level = $user['membership_level'] ?? 'normal';
$membership_discount_percent = get_membership_discount($membership_level);
$membership_discount_amount = $cart_total * $membership_discount_percent / 100;

if ($cart_items == 0) {
    header("Location: cart.php?error=Giỏ hàng trống");
    exit;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thanh toán - Nhà Sách Online</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <?php include "php/header.php"; ?>

    <div class="container py-4">
        <h2 class="mb-4"><i class="fas fa-credit-card me-2"></i>Thanh toán</h2>

        <div class="row">
            <div class="col-lg-8">
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-shopping-cart me-2"></i>Đơn hàng của bạn</h5>
                    </div>
                    <div class="card-body">
                        <form action="php/process-checkout.php" method="post" id="checkoutForm">
                            <input type="hidden" name="coupon_code" id="coupon_code_input" value="">
                            <input type="hidden" name="payment_channel" id="payment_channel_input" value="balance">
                            <?php foreach ($cart_items as $item): ?>
                                <div class="card mb-3">
                                    <div class="card-body">
                                        <div class="row align-items-center">
                                            <div class="col-md-2">
                                                <img src="uploads/cover/<?=$item['cover']?>" 
                                                     class="img-fluid rounded" 
                                                     onerror="this.src='https://via.placeholder.com/100x130'">
                                            </div>
                                            <div class="col-md-6">
                                                <h5><?=htmlspecialchars($item['title'])?></h5>
                                                <p class="text-muted mb-0">
                                                    Số lượng: <?=$item['quantity']?><br>
                                                    Giá: <?=format_price($item['price'])?>
                                                </p>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label"><strong>Chọn loại sách:</strong></label>
                                                <div class="form-check mb-2">
                                                    <input class="form-check-input book-type" 
                                                           type="radio" 
                                                           name="book_type[<?=$item['book_id']?>]" 
                                                           id="pdf_<?=$item['book_id']?>" 
                                                           value="pdf" 
                                                           checked>
                                                    <label class="form-check-label" for="pdf_<?=$item['book_id']?>">
                                                        <i class="fas fa-file-pdf text-danger"></i> Bản PDF (<?=format_price($item['price'])?>)
                                                    </label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input book-type" 
                                                           type="radio" 
                                                           name="book_type[<?=$item['book_id']?>]" 
                                                           id="hardcopy_<?=$item['book_id']?>" 
                                                           value="hardcopy">
                                                    <label class="form-check-label" for="hardcopy_<?=$item['book_id']?>">
                                                        <i class="fas fa-book text-primary"></i> Bản cứng (<?=format_price($item['price'])?>)
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>

                            <div class="card mt-3">
                                <div class="card-body">
                                    <h5><i class="fas fa-map-marker-alt me-2"></i>Thông tin giao hàng</h5>
                                    <div class="mb-3">
                                        <label class="form-label">Họ và tên</label>
                                        <input type="text" class="form-control" name="full_name" 
                                               value="<?=htmlspecialchars($user['full_name'])?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Số điện thoại</label>
                                        <input type="tel" class="form-control" name="phone" 
                                               value="<?=htmlspecialchars($user['phone'] ?? '')?>" required>
                                    </div>
                                    <div class="mb-3" id="defaultAddressGroup">
                                        <label class="form-label">Địa chỉ giao hàng <span class="text-danger">*</span></label>
                                        
                                        <div class="mb-3">
                                            <label class="form-label small">Số nhà / Số tầng <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" name="house_number" id="house_number" 
                                                   placeholder="VD: Số 123, Tầng 5" required>
                                            <small class="text-muted">Vui lòng nhập số nhà hoặc số tầng</small>
                                        </div>
                                        
                                        <div>
                                            <label class="form-label small">Tỉnh/Thành phố <span class="text-danger">*</span></label>
                                            <select class="form-select form-select-sm mb-3" id="city" name="city" aria-label="Chọn tỉnh thành" required>
                                                <option value="" selected>Chọn tỉnh thành</option>           
                                            </select>
                                        </div>

                                        <div>
                                            <label class="form-label small">Quận/Huyện <span class="text-danger">*</span></label>
                                            <select class="form-select form-select-sm mb-3" id="district" name="district" aria-label="Chọn quận huyện" required>
                                                <option value="" selected>Chọn quận huyện</option>
                                            </select>
                                        </div>
                                        
                                        <div>
                                            <label class="form-label small">Phường/Xã <span class="text-danger">*</span></label>
                                            <select class="form-select form-select-sm" id="ward" name="ward" aria-label="Chọn phường xã" required>
                                                <option value="" selected>Chọn phường xã</option>
                                            </select>
                                        </div>
                                        
                                        <input type="hidden" name="default_address" id="full_address">
                                    </div>
                                    <div class="mb-3" id="shippingRegionSection" style="display: none;">
                                        <div class="alert alert-info mb-0">
                                            <i class="fas fa-info-circle me-2"></i>
                                            <strong>Phí vận chuyển:</strong> <span id="shippingInfo">Sẽ được tính tự động dựa vào địa chỉ giao hàng</span>
                                        </div>
                                        <input type="hidden" name="shipping_region" id="shipping_region" value="hanoi">
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                        <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="fas fa-receipt me-2"></i>Tóm tắt đơn hàng</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Tạm tính:</span>
                            <span><?=format_price($cart_total)?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Phí vận chuyển:</span>
                            <span id="shippingFee">0đ</span>
                        </div>
                        <?php if ($membership_discount_amount > 0): ?>
                            <div class="d-flex justify-content-between mb-2 text-success">
                                <span><i class="fas fa-star me-1"></i>Giảm giá hạng thành viên (<?=get_membership_name($membership_level)?> -<?=$membership_discount_percent?>%):</span>
                                <span><strong>-<?=format_price($membership_discount_amount)?></strong></span>
                            </div>
                        <?php endif; ?>
                        <div class="mb-3">
                            <label class="form-label mb-1">Mã giảm giá</label>
                            <div class="input-group input-group-sm">
                                <input type="text" class="form-control" id="couponInput" placeholder="Nhập mã giảm giá">
                                <button class="btn btn-outline-primary" type="button" id="applyCouponBtn">Áp dụng</button>
                                <button class="btn btn-link text-primary" type="button" id="chooseCouponBtn">
                                    Chọn mã
                                </button>
                            </div>
                            <small class="text-muted d-block mt-1" id="couponMessage">Nhập mã giảm giá hoặc chọn từ danh sách.</small>
                        </div>
                        <div class="d-flex justify-content-between mb-2" id="discountRow" style="display: none;">
                            <span>Giảm giá mã khuyến mãi:</span>
                            <span id="discountAmount">-0đ</span>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between mb-3">
                            <strong>Tổng cộng:</strong>
                            <strong class="text-danger" id="totalAmount"><?=format_price($cart_total)?></strong>
                        </div>
                        <div class="mb-3">
                            <small class="text-muted">
                                <i class="fas fa-wallet me-1"></i>
                                Số dư hiện tại: <strong><?=format_price($user['balance'])?></strong>
                            </small>
                        </div>
                        
                        <!-- Phương thức thanh toán -->
                        <div class="mb-3" id="paymentMethodSection">
                            <label class="form-label"><strong><i class="fas fa-credit-card me-1"></i>Phương thức thanh toán</strong></label>
                            <div class="form-check mb-2">
                                <input class="form-check-input payment-method" type="radio" name="payment_method" id="payment_balance" value="balance" form="checkoutForm" checked>
                                <label class="form-check-label" for="payment_balance">
                                    <i class="fas fa-wallet text-success"></i> Thanh toán bằng số dư tài khoản
                                </label>
                            </div>
            <div class="form-check mb-2">
                <input class="form-check-input payment-method" type="radio" name="payment_method" id="payment_online" value="online" form="checkoutForm">
                <label class="form-check-label" for="payment_online">
                    <i class="fas fa-credit-card text-info"></i> Thanh toán online (VISA / MasterCard / MoMo / ZaloPay)
                    <small class="text-muted d-block">Sử dụng phần Demo thanh toán online bên dưới để chọn hình thức.</small>
                </label>
            </div>
                            <div class="form-check" id="codOption" style="display: none;">
                                <input class="form-check-input payment-method" type="radio" name="payment_method" id="payment_cod" value="cod" form="checkoutForm">
                                <label class="form-check-label" for="payment_cod">
                                    <i class="fas fa-truck text-primary"></i> Thanh toán khi nhận hàng (COD)
                                    <small class="text-muted d-block">Chỉ áp dụng cho sách bản cứng</small>
                                </label>
                            </div>
                        </div>
                        
                        <!-- Demo thanh toán online (không kết nối cổng thật) -->
                        <?php
                        $momo_qr_url = get_setting($conn, 'momo_qr_url', '');
                        $zalopay_qr_url = get_setting($conn, 'zalopay_qr_url', '');
                        ?>
                        <div class="card border-info mb-3" id="demoPaymentSection">
                            <div class="card-header bg-info text-white py-2">
                                <strong><i class="fas fa-laptop-code me-1"></i>Demo thanh toán online</strong>
                                <span class="badge bg-light text-info ms-2">Không trừ tiền thật</span>
                            </div>
                            <div class="card-body">
                                <p class="small text-muted">
                                    Khu vực này chỉ để <strong>mô phỏng</strong> thanh toán bằng thẻ / ví điện tử (VISA, MasterCard, MoMo, ZaloPay).
                                    Chọn hình thức thanh toán bên dưới và nhấn nút xác nhận để hoàn tất đơn hàng.
                                </p>
                                
                                <ul class="nav nav-pills mb-3" id="checkoutDemoTab" role="tablist">
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link active" id="checkout-card-tab" data-bs-toggle="tab" data-bs-target="#checkoutCardTab" type="button" role="tab">
                                            <i class="fas fa-credit-card me-1"></i> VISA / MasterCard
                                        </button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" id="checkout-wallet-tab" data-bs-toggle="tab" data-bs-target="#checkoutWalletTab" type="button" role="tab">
                                            <i class="fas fa-qrcode me-1"></i> MoMo / ZaloPay
                                        </button>
                                    </li>
                                </ul>
                                
                                <div class="tab-content">
                                    <div class="tab-pane fade show active" id="checkoutCardTab" role="tabpanel" aria-labelledby="checkout-card-tab">
                                        <div class="row g-3">
                                            <div class="col-12">
                                                <label class="form-label">Số thẻ</label>
                                                <input type="text" class="form-control" id="checkoutDemoCardNumber" placeholder="1234 5678 9012 3456">
                                            </div>
                                            <div class="col-12">
                                                <label class="form-label">Tên chủ thẻ</label>
                                                <input type="text" class="form-control" id="checkoutDemoCardName" placeholder="NGUYEN VAN A">
                                            </div>
                                            <div class="col-4">
                                                <label class="form-label">Tháng hết hạn</label>
                                                <input type="text" class="form-control" id="checkoutDemoCardExpMonth" placeholder="MM">
                                            </div>
                                            <div class="col-4">
                                                <label class="form-label">Năm hết hạn</label>
                                                <input type="text" class="form-control" id="checkoutDemoCardExpYear" placeholder="YY">
                                            </div>
                                            <div class="col-4">
                                                <label class="form-label">Mã CVC</label>
                                                <input type="text" class="form-control" id="checkoutDemoCardCvc" placeholder="CVC">
                                            </div>
                                        </div>
                                        <button type="button" class="btn btn-outline-primary w-100 mt-3" onclick="demoCardPayment('order')">
                                            <i class="fas fa-check-circle me-1"></i> Giả lập thanh toán thẻ
                                        </button>
                                    </div>
                                    
                                    <div class="tab-pane fade" id="checkoutWalletTab" role="tabpanel" aria-labelledby="checkout-wallet-tab">
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <h6 class="mb-2"><strong>MoMo</strong></h6>
                                                <?php if ($momo_qr_url): ?>
                                                    <img src="<?=htmlspecialchars($momo_qr_url)?>" alt="MoMo QR" class="img-fluid rounded border mb-2">
                                                <?php else: ?>
                                                    <div class="border rounded p-3 text-muted small mb-2">
                                                        Chưa cấu hình QR MoMo trong phần Cài đặt admin.
                                                    </div>
                                                <?php endif; ?>
                                                <button type="button" class="btn btn-outline-primary w-100" onclick="demoWalletPayment('MoMo')">
                                                    <i class="fas fa-check-circle me-1"></i> Xác nhận đã thanh toán MoMo (Demo)
                                                </button>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <h6 class="mb-2"><strong>ZaloPay</strong></h6>
                                                <?php if ($zalopay_qr_url): ?>
                                                    <img src="<?=htmlspecialchars($zalopay_qr_url)?>" alt="ZaloPay QR" class="img-fluid rounded border mb-2">
                                                <?php else: ?>
                                                    <div class="border rounded p-3 text-muted small mb-2">
                                                        Chưa cấu hình QR ZaloPay trong phần Cài đặt admin.
                                                    </div>
                                                <?php endif; ?>
                                                <button type="button" class="btn btn-outline-success w-100" onclick="demoWalletPayment('ZaloPay')">
                                                    <i class="fas fa-check-circle me-1"></i> Xác nhận đã thanh toán ZaloPay (Demo)
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <button type="submit" form="checkoutForm" class="btn btn-success w-100 btn-lg">
                            <i class="fas fa-check me-2"></i>Xác nhận thanh toán
                        </button>
                        <a href="cart.php" class="btn btn-outline-secondary w-100 mt-2">
                            <i class="fas fa-arrow-left me-2"></i>Quay lại giỏ hàng
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal chọn mã giảm giá -->
    <div class="modal fade" id="couponModal" tabindex="-1" aria-labelledby="couponModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-scrollable modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="couponModalLabel">
                        <i class="fas fa-ticket-alt me-2 text-primary"></i>Chọn mã giảm giá
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="couponListLoading" class="text-center text-muted my-3" style="display:none;">
                        <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                        <span class="ms-2">Đang tải danh sách mã giảm giá...</span>
                    </div>
                    <div id="couponListError" class="alert alert-warning d-none"></div>
                    <div id="couponListContainer"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/axios/0.21.1/axios.min.js"></script>
    <script>
        // Load địa chỉ từ API
        var citis = document.getElementById("city");
        var districts = document.getElementById("district");
        var wards = document.getElementById("ward");
        var houseNumber = document.getElementById("house_number");
        var fullAddressInput = document.getElementById("full_address");
        
        var Parameter = {
            url: "https://raw.githubusercontent.com/kenzouno1/DiaGioiHanhChinhVN/master/data.json", 
            method: "GET", 
            responseType: "application/json", 
        };
        var promise = axios(Parameter);
        promise.then(function (result) {
            renderCity(result.data);
        });

        function renderCity(data) {
            for (const x of data) {
                citis.options[citis.options.length] = new Option(x.Name, x.Id);
            }
            citis.onchange = function () {
                district.length = 1;
                ward.length = 1;
                district.innerHTML = '<option value="" selected>Chọn quận huyện</option>';
                ward.innerHTML = '<option value="" selected>Chọn phường xã</option>';
                if(this.value != ""){
                    const result = data.filter(n => n.Id === this.value);

                    for (const k of result[0].Districts) {
                        district.options[district.options.length] = new Option(k.Name, k.Id);
                    }
                }
                updateFullAddress();
                // Tự động tính phí ship dựa vào tỉnh/thành phố
                calculateShippingByCity();
            };
            district.onchange = function () {
                ward.length = 1;
                ward.innerHTML = '<option value="" selected>Chọn phường xã</option>';
                const dataCity = data.filter((n) => n.Id === citis.value);
                if (this.value != "") {
                    const dataWards = dataCity[0].Districts.filter(n => n.Id === this.value)[0].Wards;

                    for (const w of dataWards) {
                        wards.options[wards.options.length] = new Option(w.Name, w.Id);
                    }
                }
                updateFullAddress();
            };
            wards.onchange = function() {
                updateFullAddress();
            };
        }
        
        // Cập nhật địa chỉ đầy đủ
        function updateFullAddress() {
            const house = houseNumber.value.trim();
            const cityText = citis.options[citis.selectedIndex]?.text || '';
            const districtText = districts.options[districts.selectedIndex]?.text || '';
            const wardText = wards.options[wards.selectedIndex]?.text || '';
            
            let fullAddress = '';
            if (house) {
                fullAddress = house;
            }
            if (wardText && wardText !== 'Chọn phường xã') {
                fullAddress += (fullAddress ? ', ' : '') + wardText;
            }
            if (districtText && districtText !== 'Chọn quận huyện') {
                fullAddress += (fullAddress ? ', ' : '') + districtText;
            }
            if (cityText && cityText !== 'Chọn tỉnh thành') {
                fullAddress += (fullAddress ? ', ' : '') + cityText;
            }
            
            fullAddressInput.value = fullAddress;
            
            // Khi đã có địa chỉ tương đối đầy đủ, thử gọi API bản đồ để tính phí ship theo khoảng cách
            if (fullAddress) {
                requestDistanceShipping(fullAddress);
            }
        }
        
        // Tự động tính phí ship dựa vào tỉnh/thành phố
        function calculateShippingByCity() {
            const cityText = citis.options[citis.selectedIndex]?.text || '';
            const shippingRegionInput = document.getElementById('shipping_region');
            const shippingInfoEl = document.getElementById('shippingInfo');
            
            if (!cityText || cityText === 'Chọn tỉnh thành') {
                if (shippingInfoEl) {
                    shippingInfoEl.textContent = 'Sẽ được tính tự động dựa vào địa chỉ giao hàng';
                }
                if (shippingRegionInput) {
                    shippingRegionInput.value = 'hanoi'; // Default
                }
                updateShippingAndTotal();
                return;
            }
            
            // Danh sách các tỉnh/thành phố theo khu vực
            const hanoiCities = ['Thành phố Hà Nội', 'Hà Nội'];
            
            const northCities = [
                'Thành phố Hải Phòng', 'Hải Phòng',
                'Tỉnh Quảng Ninh', 'Quảng Ninh',
                'Tỉnh Bắc Ninh', 'Bắc Ninh',
                'Tỉnh Hải Dương', 'Hải Dương',
                'Tỉnh Hưng Yên', 'Hưng Yên',
                'Tỉnh Thái Bình', 'Thái Bình',
                'Tỉnh Nam Định', 'Nam Định',
                'Tỉnh Ninh Bình', 'Ninh Bình',
                'Tỉnh Vĩnh Phúc', 'Vĩnh Phúc',
                'Tỉnh Bắc Giang', 'Bắc Giang',
                'Tỉnh Phú Thọ', 'Phú Thọ',
                'Tỉnh Thái Nguyên', 'Thái Nguyên',
                'Tỉnh Lạng Sơn', 'Lạng Sơn',
                'Tỉnh Bắc Kạn', 'Bắc Kạn',
                'Tỉnh Cao Bằng', 'Cao Bằng',
                'Tỉnh Tuyên Quang', 'Tuyên Quang',
                'Tỉnh Yên Bái', 'Yên Bái',
                'Tỉnh Lào Cai', 'Lào Cai',
                'Tỉnh Điện Biên', 'Điện Biên',
                'Tỉnh Lai Châu', 'Lai Châu',
                'Tỉnh Sơn La', 'Sơn La',
                'Tỉnh Hòa Bình', 'Hòa Bình'
            ];
            
            const centralCities = [
                'Tỉnh Thanh Hóa', 'Thanh Hóa',
                'Tỉnh Nghệ An', 'Nghệ An',
                'Tỉnh Hà Tĩnh', 'Hà Tĩnh',
                'Tỉnh Quảng Bình', 'Quảng Bình',
                'Tỉnh Quảng Trị', 'Quảng Trị',
                'Tỉnh Thừa Thiên Huế', 'Thừa Thiên Huế',
                'Thành phố Đà Nẵng', 'Đà Nẵng',
                'Tỉnh Quảng Nam', 'Quảng Nam',
                'Tỉnh Quảng Ngãi', 'Quảng Ngãi',
                'Tỉnh Bình Định', 'Bình Định',
                'Tỉnh Phú Yên', 'Phú Yên',
                'Tỉnh Khánh Hòa', 'Khánh Hòa',
                'Tỉnh Ninh Thuận', 'Ninh Thuận',
                'Tỉnh Bình Thuận', 'Bình Thuận',
                'Tỉnh Kon Tum', 'Kon Tum',
                'Tỉnh Gia Lai', 'Gia Lai',
                'Tỉnh Đắk Lắk', 'Đắk Lắk',
                'Tỉnh Đắk Nông', 'Đắk Nông',
                'Tỉnh Lâm Đồng', 'Lâm Đồng'
            ];
            
            // Xác định khu vực
            let region = 'south'; // Default: Miền Nam
            let regionName = 'Miền Nam';
            let shippingFee = 50000;
            
            if (hanoiCities.some(city => cityText.includes(city))) {
                region = 'hanoi';
                regionName = 'Hà Nội';
                shippingFee = 20000;
            } else if (northCities.some(city => cityText.includes(city))) {
                region = 'north';
                regionName = 'Miền Bắc';
                shippingFee = 30000;
            } else if (centralCities.some(city => cityText.includes(city))) {
                region = 'central';
                regionName = 'Miền Trung';
                shippingFee = 40000;
            }
            
            // Cập nhật hidden input và hiển thị
            if (shippingRegionInput) {
                shippingRegionInput.value = region;
            }
            if (shippingInfoEl) {
                shippingInfoEl.innerHTML = `<strong>${regionName}</strong> - Phí ship: <strong>${shippingFee.toLocaleString('vi-VN')}đ</strong>`;
            }
            
            // Cập nhật tổng tiền - gọi trực tiếp để đảm bảo đồng bộ
            updateShippingFeeDisplay(shippingFee);
        }
        
        // Hàm cập nhật hiển thị phí ship trong phần tóm tắt
        function updateShippingFeeDisplay(fee) {
            const shippingFeeEl = document.getElementById('shippingFee');
            const totalAmountEl = document.getElementById('totalAmount');
            const discountRow = document.getElementById('discountRow');
            const discountAmountEl = document.getElementById('discountAmount');
            
            // Áp dụng freeship nếu có
            let displayFee = isFreeship ? 0 : fee;
            
            // Cập nhật hiển thị phí ship
            if (shippingFeeEl) {
                shippingFeeEl.textContent = isFreeship ? '0đ (Freeship)' : formatCurrency(displayFee);
            }
            
            // Tính tổng discount
            const totalDiscount = membershipDiscount + couponDiscountValue;
            
            // Cập nhật hiển thị giảm giá
            if (couponDiscountValue > 0 || isFreeship) {
                if (discountRow) discountRow.style.display = 'flex';
                if (discountAmountEl) {
                    if (isFreeship && couponDiscountValue === 0) {
                        discountAmountEl.textContent = 'Freeship';
                    } else {
                        discountAmountEl.textContent = '-' + formatCurrency(couponDiscountValue);
                    }
                }
            } else {
                if (discountRow) discountRow.style.display = 'none';
                if (discountAmountEl) discountAmountEl.textContent = '-' + formatCurrency(0);
            }
            
            // Tính và cập nhật tổng tiền
            const total = Math.max(0, baseTotal + displayFee - totalDiscount);
            if (totalAmountEl) {
                totalAmountEl.textContent = formatCurrency(total);
            }
        }
        
        // ===== TÍNH PHÍ SHIP THEO KHOẢNG CÁCH (API BẢN ĐỒ) =====
        let distanceShippingFee = null;
        let lastShippingAddress = '';

        function requestDistanceShipping(fullAddress) {
            // Nếu API key chưa cấu hình ở server hoặc lỗi, server sẽ trả success=false → fallback
            // Hạn chế gọi quá nhiều lần nếu địa chỉ không đổi
            if (!fullAddress || fullAddress === lastShippingAddress) {
                return;
            }
            lastShippingAddress = fullAddress;

            const formData = new FormData();
            formData.append('address', fullAddress);

            fetch('php/calc-shipping-distance.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success && typeof data.shipping_fee === 'number') {
                    distanceShippingFee = data.shipping_fee;
                } else {
                    distanceShippingFee = null; // fallback sang tính theo vùng
                }
                updateShippingAndTotal();
            })
            .catch(() => {
                distanceShippingFee = null;
                updateShippingAndTotal();
            });
        }
        
        // Lắng nghe thay đổi số nhà
        houseNumber.addEventListener('input', updateFullAddress);
        houseNumber.addEventListener('change', updateFullAddress);
        
    </script>
    <script>
        const baseTotal = <?=$cart_total?>;
        const membershipDiscount = <?=$membership_discount_amount?>;
        const codFeePercent = <?=$cod_fee_percent?>;
        let couponDiscountValue = 0;
        let isFreeship = false; // Mã freeship
        let appliedCouponCode = ''; // Lưu mã coupon đã áp dụng

        function formatCurrency(amount) {
            return amount.toLocaleString('vi-VN') + 'đ';
        }

        function calculateShipping(region) {
            switch (region) {
                case 'hanoi':
                    return 20000;
                case 'north':
                    return 30000;
                case 'central':
                    return 40000;
                case 'south':
                default:
                    return 50000;
            }
        }

        function updateShippingAndTotal() {
            const shippingRegionSection = document.getElementById('shippingRegionSection');
            const shippingRegionInput = document.getElementById('shipping_region');
            const defaultAddressGroup = document.getElementById('defaultAddressGroup');
            const shippingFeeEl = document.getElementById('shippingFee');
            const totalAmountEl = document.getElementById('totalAmount');
            const discountRow = document.getElementById('discountRow');
            const discountAmountEl = document.getElementById('discountAmount');
            const codOption = document.getElementById('codOption');
            const paymentBalance = document.getElementById('payment_balance');
            const paymentCod = document.getElementById('payment_cod');

            let hasHardcopy = false;
            let hasOnlyHardcopy = true; // Tất cả sách đều là bản cứng
            
            document.querySelectorAll('.book-type').forEach(function (radio) {
                if (radio.checked) {
                    if (radio.value === 'hardcopy') {
                        hasHardcopy = true;
                    } else {
                        hasOnlyHardcopy = false;
                    }
                }
            });

            let shippingFee = 0;

            if (hasHardcopy) {
                // Hiển thị phần chọn khu vực và địa chỉ mặc định khi có bản cứng
                shippingRegionSection.style.display = 'block';
                defaultAddressGroup.style.display = 'block';
                // Đặt required cho các trường địa chỉ
                document.getElementById('house_number').required = true;
                document.getElementById('city').required = true;
                document.getElementById('district').required = true;
                document.getElementById('ward').required = true;
                
                // Lấy khu vực từ hidden input (đã được tính tự động bởi calculateShippingByCity)
                // Nếu chưa có, tính lại dựa vào tỉnh/thành phố đã chọn
                let region = shippingRegionInput.value || 'hanoi';
                const cityText = citis.options[citis.selectedIndex]?.text || '';
                
                // Nếu đã chọn tỉnh/thành phố nhưng region chưa được cập nhật, tính lại
                if (cityText && cityText !== 'Chọn tỉnh thành' && (!shippingRegionInput.value || shippingRegionInput.value === 'hanoi')) {
                    // Tính lại phí ship dựa vào tỉnh/thành phố
                    const hanoiCities = ['Thành phố Hà Nội', 'Hà Nội'];
                    const northCities = [
                        'Thành phố Hải Phòng', 'Hải Phòng', 'Tỉnh Quảng Ninh', 'Quảng Ninh',
                        'Tỉnh Bắc Ninh', 'Bắc Ninh', 'Tỉnh Hải Dương', 'Hải Dương',
                        'Tỉnh Hưng Yên', 'Hưng Yên', 'Tỉnh Thái Bình', 'Thái Bình',
                        'Tỉnh Nam Định', 'Nam Định', 'Tỉnh Ninh Bình', 'Ninh Bình',
                        'Tỉnh Vĩnh Phúc', 'Vĩnh Phúc', 'Tỉnh Bắc Giang', 'Bắc Giang',
                        'Tỉnh Phú Thọ', 'Phú Thọ', 'Tỉnh Thái Nguyên', 'Thái Nguyên',
                        'Tỉnh Lạng Sơn', 'Lạng Sơn', 'Tỉnh Bắc Kạn', 'Bắc Kạn',
                        'Tỉnh Cao Bằng', 'Cao Bằng', 'Tỉnh Tuyên Quang', 'Tuyên Quang',
                        'Tỉnh Yên Bái', 'Yên Bái', 'Tỉnh Lào Cai', 'Lào Cai',
                        'Tỉnh Điện Biên', 'Điện Biên', 'Tỉnh Lai Châu', 'Lai Châu',
                        'Tỉnh Sơn La', 'Sơn La', 'Tỉnh Hòa Bình', 'Hòa Bình'
                    ];
                    const centralCities = [
                        'Tỉnh Thanh Hóa', 'Thanh Hóa', 'Tỉnh Nghệ An', 'Nghệ An',
                        'Tỉnh Hà Tĩnh', 'Hà Tĩnh', 'Tỉnh Quảng Bình', 'Quảng Bình',
                        'Tỉnh Quảng Trị', 'Quảng Trị', 'Tỉnh Thừa Thiên Huế', 'Thừa Thiên Huế',
                        'Thành phố Đà Nẵng', 'Đà Nẵng', 'Tỉnh Quảng Nam', 'Quảng Nam',
                        'Tỉnh Quảng Ngãi', 'Quảng Ngãi', 'Tỉnh Bình Định', 'Bình Định',
                        'Tỉnh Phú Yên', 'Phú Yên', 'Tỉnh Khánh Hòa', 'Khánh Hòa',
                        'Tỉnh Ninh Thuận', 'Ninh Thuận', 'Tỉnh Bình Thuận', 'Bình Thuận',
                        'Tỉnh Kon Tum', 'Kon Tum', 'Tỉnh Gia Lai', 'Gia Lai',
                        'Tỉnh Đắk Lắk', 'Đắk Lắk', 'Tỉnh Đắk Nông', 'Đắk Nông',
                        'Tỉnh Lâm Đồng', 'Lâm Đồng'
                    ];
                    
                    if (hanoiCities.some(city => cityText.includes(city))) {
                        region = 'hanoi';
                    } else if (northCities.some(city => cityText.includes(city))) {
                        region = 'north';
                    } else if (centralCities.some(city => cityText.includes(city))) {
                        region = 'central';
                    } else {
                        region = 'south';
                    }
                    
                    shippingRegionInput.value = region;
                }
                
                shippingFee = calculateShipping(region);
                
                // Hiển thị tùy chọn COD nếu TẤT CẢ sách đều là bản cứng
                if (hasOnlyHardcopy) {
                    codOption.style.display = 'block';
                } else {
                    codOption.style.display = 'none';
                    // Reset về balance nếu đang chọn COD mà có PDF
                    if (paymentCod && paymentCod.checked) {
                        paymentBalance.checked = true;
                    }
                }
            } else {
                // Ẩn phần chọn khu vực và địa chỉ mặc định khi chỉ có PDF
                shippingRegionSection.style.display = 'none';
                defaultAddressGroup.style.display = 'none';
                // Bỏ required cho các trường địa chỉ khi không có bản cứng
                document.getElementById('house_number').required = false;
                document.getElementById('city').required = false;
                document.getElementById('district').required = false;
                document.getElementById('ward').required = false;
                // Ẩn COD option khi chỉ có PDF
                codOption.style.display = 'none';
                // Reset về balance
                if (paymentCod && paymentCod.checked) {
                    paymentBalance.checked = true;
                }
            }

            // Nếu có kết quả tính phí ship theo khoảng cách, ưu tiên dùng
            if (distanceShippingFee !== null && hasHardcopy) {
                shippingFee = distanceShippingFee;
            }

            // Áp dụng freeship nếu có
            if (isFreeship) {
                shippingFee = 0;
            }

            // Cập nhật hiển thị giảm giá
            const totalDiscount = membershipDiscount + couponDiscountValue;
            
            if (couponDiscountValue > 0 || isFreeship) {
                discountRow.style.display = 'flex';
                if (isFreeship && couponDiscountValue === 0) {
                    discountAmountEl.textContent = 'Freeship';
                } else {
                    discountAmountEl.textContent = '-' + formatCurrency(couponDiscountValue);
                }
            } else {
                discountRow.style.display = 'none';
                discountAmountEl.textContent = '-' + formatCurrency(0);
            }

            // Tính phí COD nếu chọn COD (2% giá trị đơn hàng sau giảm giá)
            let codFee = 0;
            const paymentMethod = document.querySelector('input[name="payment_method"]:checked')?.value || 'balance';
            if (paymentMethod === 'cod' && hasHardcopy) {
                // Giá trị đơn hàng sau giảm giá (không tính phí ship)
                const orderValueAfterDiscount = Math.max(0, baseTotal - totalDiscount);
                codFee = Math.round(orderValueAfterDiscount * codFeePercent / 100);
            }
            
            // Cập nhật phí ship - đảm bảo lấy từ hidden input (đã được tính tự động)
            const finalShippingFee = isFreeship ? 0 : shippingFee;
            const totalShippingFee = finalShippingFee + codFee;
            
            // Hiển thị phí ship
            if (isFreeship && hasHardcopy) {
                shippingFeeEl.textContent = '0đ (Freeship)';
            } else if (codFee > 0) {
                shippingFeeEl.innerHTML = formatCurrency(finalShippingFee) + ' <small class="text-muted">+ Phí COD (' + codFeePercent + '%): ' + formatCurrency(codFee) + '</small>';
            } else {
                shippingFeeEl.textContent = formatCurrency(finalShippingFee);
            }
            
            // Tính tổng: (cart_total - discount) + shipping_fee + cod_fee
            const total = Math.max(0, baseTotal - totalDiscount + totalShippingFee);
            totalAmountEl.textContent = formatCurrency(total);
        }

        // Cập nhật ship khi thay đổi loại sách (PDF / bản cứng)
        document.querySelectorAll('.book-type').forEach(function(radio) {
            radio.addEventListener('change', function() {
                updateShippingAndTotal();
            });
        });

        // Không cần event listener cho shipping_region nữa vì đã tự động tính

        // Ẩn/hiện phần Demo thanh toán online
        function updateDemoPaymentVisibility() {
            const demoSection = document.getElementById('demoPaymentSection');
            if (!demoSection) return;

            const method = document.querySelector('input[name="payment_method"]:checked')?.value || 'balance';

            // Chỉ hiển thị form demo khi chọn "Thanh toán online"
            if (method === 'online') {
                demoSection.style.display = 'block';
            } else {
                demoSection.style.display = 'none';
            }
        }
        
        document.querySelectorAll('.payment-method').forEach(function(radio) {
            radio.addEventListener('change', function() {
                updateDemoPaymentVisibility();
                updateShippingAndTotal();
            });
        });
        
        // Khởi tạo trạng thái ban đầu
        updateDemoPaymentVisibility();
        
        // Áp dụng mã giảm giá
        document.getElementById('applyCouponBtn').addEventListener('click', function() {
            const input = document.getElementById('couponInput');
            const code = input.value.trim().toUpperCase();
            const messageEl = document.getElementById('couponMessage');
            const hiddenInput = document.getElementById('coupon_code_input');
            const applyBtn = document.getElementById('applyCouponBtn');

            if (!code) {
                couponDiscountValue = 0;
                hiddenInput.value = '';
                messageEl.textContent = 'Vui lòng nhập mã giảm giá.';
                messageEl.classList.remove('text-success', 'text-danger');
                messageEl.classList.add('text-muted');
                updateShippingAndTotal();
                return;
            }

            // Disable button while checking
            applyBtn.disabled = true;
            applyBtn.textContent = 'Đang kiểm tra...';
            messageEl.textContent = 'Đang kiểm tra mã...';
            messageEl.classList.remove('text-success', 'text-danger', 'text-muted');
            messageEl.classList.add('text-muted');

            // Gọi API kiểm tra mã từ database
            fetch(`php/check-coupon.php?code=${encodeURIComponent(code)}`)
                .then(response => response.json())
                .then(data => {
                    if (data.valid) {
                        couponDiscountValue = data.discount_amount || 0;
                        isFreeship = data.freeship || false;
                        appliedCouponCode = data.code; // Lưu vào biến global
                        document.getElementById('coupon_code_input').value = data.code; // Cập nhật hidden input
                        console.log('Coupon applied, hidden input set to:', data.code, 'freeship:', isFreeship);
                        messageEl.textContent = data.message;
                        messageEl.classList.remove('text-muted', 'text-danger');
                        messageEl.classList.add('text-success');
                    } else {
                        couponDiscountValue = 0;
                        isFreeship = false;
                        appliedCouponCode = '';
                        document.getElementById('coupon_code_input').value = '';
                        messageEl.textContent = data.message || 'Mã giảm giá không hợp lệ.';
                        messageEl.classList.remove('text-muted', 'text-success');
                        messageEl.classList.add('text-danger');
                    }
                    updateShippingAndTotal();
                })
                .catch(error => {
                    console.error('Error:', error);
                    couponDiscountValue = 0;
                    isFreeship = false;
                    appliedCouponCode = '';
                    document.getElementById('coupon_code_input').value = '';
                    messageEl.textContent = 'Có lỗi xảy ra khi kiểm tra mã. Vui lòng thử lại.';
                    messageEl.classList.remove('text-muted', 'text-success');
                    messageEl.classList.add('text-danger');
                    updateShippingAndTotal();
                })
                .finally(() => {
                    applyBtn.disabled = false;
                    applyBtn.textContent = 'Áp dụng';
                });
        });

        // ===== CHỌN MÃ GIẢM GIÁ TỪ DANH SÁCH =====
        const chooseCouponBtn = document.getElementById('chooseCouponBtn');
        const couponModalEl = document.getElementById('couponModal');
        const couponListContainer = document.getElementById('couponListContainer');
        const couponListLoading = document.getElementById('couponListLoading');
        const couponListError = document.getElementById('couponListError');
        let couponModal;

        if (couponModalEl) {
            couponModal = new bootstrap.Modal(couponModalEl);
        }

        if (chooseCouponBtn && couponModal) {
            chooseCouponBtn.addEventListener('click', function() {
                couponListError.classList.add('d-none');
                couponListContainer.innerHTML = '';
                couponListLoading.style.display = 'block';
                couponModal.show();

                fetch('php/get-available-coupons.php')
                    .then(res => res.json())
                    .then(data => {
                        couponListLoading.style.display = 'none';
                        if (!data.success) {
                            couponListError.textContent = data.message || 'Không thể tải danh sách mã giảm giá.';
                            couponListError.classList.remove('d-none');
                            return;
                        }

                        const coupons = data.coupons || [];
                        if (!coupons.length) {
                            couponListError.textContent = 'Không có mã giảm giá phù hợp với giỏ hàng hiện tại.';
                            couponListError.classList.remove('d-none');
                            return;
                        }

                        const listHtml = coupons.map(c => {
                            const typeLabel = c.discount_type === 'freeship'
                                ? '<span class=\"badge bg-success ms-1\">Freeship</span>'
                                : `<span class=\"badge bg-danger ms-1\">-${c.discount_percent}%</span>`;

                            const desc = c.description ? ` - ${c.description}` : '';
                            const amount = c.discount_type === 'freeship'
                                ? 'Miễn phí vận chuyển'
                                : `Giảm khoảng ${c.discount_amount.toLocaleString('vi-VN')}đ`;

                            return `
                                <div class=\"card mb-2\">
                                    <div class=\"card-body d-flex justify-content-between align-items-center\">
                                        <div>
                                            <strong>${c.code}</strong>${typeLabel}
                                            <div class=\"small text-muted\">${amount}${desc}</div>
                                        </div>
                                        <button type=\"button\" class=\"btn btn-sm btn-outline-primary\" data-code=\"${c.code}\">
                                            Dùng mã này
                                        </button>
                                    </div>
                                </div>
                            `;
                        }).join('');

                        couponListContainer.innerHTML = listHtml;

                        // Gắn sự kiện click cho nút \"Dùng mã này\"
                        couponListContainer.querySelectorAll('button[data-code]').forEach(btn => {
                            btn.addEventListener('click', function() {
                                const code = this.getAttribute('data-code');
                                const input = document.getElementById('couponInput');
                                if (input) {
                                    input.value = code;
                                }
                                couponModal.hide();
                                // Gọi lại logic áp dụng mã
                                document.getElementById('applyCouponBtn').click();
                            });
                        });
                    })
                    .catch(() => {
                        couponListLoading.style.display = 'none';
                        couponListError.textContent = 'Có lỗi xảy ra khi tải danh sách mã giảm giá.';
                        couponListError.classList.remove('d-none');
                    });
            });
        }

        // Validate form trước khi submit: nếu có bản cứng thì bắt buộc địa chỉ mặc định
        document.getElementById('checkoutForm').addEventListener('submit', function(e) {
            // Kiểm tra nếu đây là submit từ nút demo thanh toán (đã validate trước đó)
            const isDemoPayment = document.getElementById('checkoutForm').dataset.demoPayment === 'true';
            if (isDemoPayment) {
                // Đã validate trước, cho phép submit
                return true;
            }
            
            // Debug: kiểm tra coupon code
            const couponCode = document.getElementById('coupon_code_input').value;
            console.log('Submitting with coupon_code:', couponCode);
            
            let hasHardcopy = false;

            document.querySelectorAll('.book-type').forEach(function(radio) {
                if (radio.checked && radio.value === 'hardcopy') {
                    hasHardcopy = true;
                }
            });

            if (hasHardcopy) {
                const houseNumber = document.getElementById('house_number').value.trim();
                const city = document.getElementById('city').value;
                const district = document.getElementById('district').value;
                const ward = document.getElementById('ward').value;
                
                if (!houseNumber) {
                    e.preventDefault();
                    alert('Vui lòng nhập số nhà / số tầng');
                    document.getElementById('house_number').focus();
                    return false;
                }
                
                if (!city || city === '') {
                    e.preventDefault();
                    alert('Vui lòng chọn tỉnh/thành phố');
                    document.getElementById('city').focus();
                    return false;
                }
                
                if (!district || district === '') {
                    e.preventDefault();
                    alert('Vui lòng chọn quận/huyện');
                    document.getElementById('district').focus();
                    return false;
                }
                
                if (!ward || ward === '') {
                    e.preventDefault();
                    alert('Vui lòng chọn phường/xã');
                    document.getElementById('ward').focus();
                    return false;
                }
                
                // Cập nhật địa chỉ đầy đủ trước khi submit
                updateFullAddress();
            }
        });

        // Khởi tạo
        updateShippingAndTotal();
        
        // ============ DEMO THANH TOÁN ============
        // Thanh toán thẻ VISA/MasterCard (demo)
        function setPaymentChannel(channel) {
            const input = document.getElementById('payment_channel_input');
            if (input) {
                input.value = channel;
            }
            // Đảm bảo chọn phương thức online nếu user bấm nút demo
            const onlineRadio = document.getElementById('payment_online');
            if (onlineRadio) {
                onlineRadio.checked = true;
            }
            updateDemoPaymentVisibility();
            updateShippingAndTotal();
        }
        
        function demoCardPayment(context) {
            const number = document.getElementById('checkoutDemoCardNumber')?.value.trim();
            const name = document.getElementById('checkoutDemoCardName')?.value.trim();
            const month = document.getElementById('checkoutDemoCardExpMonth')?.value.trim();
            const year = document.getElementById('checkoutDemoCardExpYear')?.value.trim();
            const cvc = document.getElementById('checkoutDemoCardCvc')?.value.trim();
            
            if (!number || !name || !month || !year || !cvc) {
                alert('Vui lòng điền đầy đủ thông tin thẻ');
                return;
            }
            
            // Kiểm tra validation trước
            if (!validateCheckoutForm()) {
                return;
            }
            
            // Hiển thị thông báo thành công và submit form
            if (confirm('✅ Thanh toán thẻ VISA/MasterCard thành công (Demo)!\n\nBấm OK để xác nhận đơn hàng.')) {
                // Đảm bảo chọn phương thức thanh toán online
                const onlineRadio = document.getElementById('payment_online');
                if (onlineRadio) {
                    onlineRadio.checked = true;
                }
                
                // Set payment channel
                const channelInput = document.getElementById('payment_channel_input');
                if (channelInput) {
                    channelInput.value = 'card_demo';
                }
                
                // Đảm bảo các giá trị được set trước khi submit
                setTimeout(function() {
                    const form = document.getElementById('checkoutForm');
                    if (form) {
                        // Đánh dấu đây là submit từ nút demo (đã validate trước đó)
                        form.dataset.demoPayment = 'true';
                        
                        // Submit form
                        form.submit();
                    }
                }, 100);
            }
        }
        
        // Kiểm tra validation trước khi submit
        function validateCheckoutForm() {
            let hasHardcopy = false;
            document.querySelectorAll('.book-type').forEach(function(radio) {
                if (radio.checked && radio.value === 'hardcopy') {
                    hasHardcopy = true;
                }
            });

            if (hasHardcopy) {
                const houseNumber = document.getElementById('house_number')?.value.trim();
                const city = document.getElementById('city')?.value;
                const district = document.getElementById('district')?.value;
                const ward = document.getElementById('ward')?.value;
                
                if (!houseNumber) {
                    alert('Vui lòng nhập số nhà / số tầng');
                    document.getElementById('house_number')?.focus();
                    return false;
                }
                
                if (!city || city === '') {
                    alert('Vui lòng chọn tỉnh/thành phố');
                    document.getElementById('city')?.focus();
                    return false;
                }
                
                if (!district || district === '') {
                    alert('Vui lòng chọn quận/huyện');
                    document.getElementById('district')?.focus();
                    return false;
                }
                
                if (!ward || ward === '') {
                    alert('Vui lòng chọn phường/xã');
                    document.getElementById('ward')?.focus();
                    return false;
                }
                
                // Cập nhật địa chỉ đầy đủ trước khi submit
                updateFullAddress();
            }
            
            return true;
        }
        
        // Thanh toán MoMo / ZaloPay (demo)
        function demoWalletPayment(method) {
            // Kiểm tra validation trước
            if (!validateCheckoutForm()) {
                return;
            }
            
            if (confirm('✅ Thanh toán ' + method + ' thành công (Demo)!\n\nBấm OK để xác nhận đơn hàng.')) {
                // Đảm bảo chọn phương thức thanh toán online
                const onlineRadio = document.getElementById('payment_online');
                if (onlineRadio) {
                    onlineRadio.checked = true;
                }
                
                // Set payment channel
                const channelInput = document.getElementById('payment_channel_input');
                let channelValue = 'wallet_demo';
                if (method === 'MoMo') {
                    channelValue = 'momo_demo';
                } else if (method === 'ZaloPay') {
                    channelValue = 'zalopay_demo';
                }
                
                if (channelInput) {
                    channelInput.value = channelValue;
                }
                
                // Debug
                console.log('Submitting with payment_method: online, payment_channel:', channelValue);
                
                // Đảm bảo các giá trị được set trước khi submit
                setTimeout(function() {
                    const form = document.getElementById('checkoutForm');
                    if (form) {
                        // Kiểm tra lại giá trị trước khi submit
                        const finalPaymentMethod = document.querySelector('input[name="payment_method"]:checked')?.value;
                        const finalChannel = document.getElementById('payment_channel_input')?.value;
                        console.log('Final values - payment_method:', finalPaymentMethod, 'payment_channel:', finalChannel);
                        
                        // Đánh dấu đây là submit từ nút demo (đã validate trước đó)
                        form.dataset.demoPayment = 'true';
                        
                        // Submit form
                        form.submit();
                    } else {
                        console.error('Form not found!');
                        alert('Lỗi: Không tìm thấy form thanh toán. Vui lòng thử lại.');
                    }
                }, 100);
            }
        }
    </script>
</body>
</html>

