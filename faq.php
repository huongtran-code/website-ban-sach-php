<?php session_start(); include "db_conn.php"; ?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Câu Hỏi Thường Gặp - Nhà Sách Online</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <?php include "php/header.php"; ?>

    <div class="container py-5">
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <h2 class="mb-4"><i class="fas fa-question-circle text-primary"></i> Câu hỏi thường gặp</h2>
                
                <div class="accordion" id="faqAccordion">
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                                Làm thế nào để đặt hàng?
                            </button>
                        </h2>
                        <div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Để đặt hàng, bạn chỉ cần: Chọn sách → Thêm vào giỏ hàng → Tiến hành thanh toán → Nhập thông tin giao hàng → Xác nhận đơn hàng.
                            </div>
                        </div>
                    </div>
                    
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                                Thời gian giao hàng là bao lâu?
                            </button>
                        </h2>
                        <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                <ul>
                                    <li>Nội thành TP.HCM, Hà Nội: 1-2 ngày</li>
                                    <li>Các tỉnh thành khác: 3-5 ngày</li>
                                    <li>Vùng sâu vùng xa: 5-7 ngày</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                                Có thể đổi trả sách không?
                            </button>
                        </h2>
                        <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Có, bạn có thể đổi trả trong vòng 7 ngày kể từ khi nhận hàng nếu sách bị lỗi in ấn hoặc không đúng sản phẩm đã đặt.
                            </div>
                        </div>
                    </div>
                    
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq4">
                                Phí vận chuyển được tính như thế nào?
                            </button>
                        </h2>
                        <div id="faq4" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                <ul>
                                    <li>Đơn hàng từ 300.000đ: Miễn phí vận chuyển</li>
                                    <li>Đơn hàng dưới 300.000đ: 25.000đ - 35.000đ tùy khu vực</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq5">
                                Làm sao để nạp tiền vào tài khoản?
                            </button>
                        </h2>
                        <div id="faq5" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Bạn có thể nạp tiền qua các hình thức: Chuyển khoản ngân hàng, Ví MoMo, ZaloPay, VNPay. Sau khi nạp, số dư sẽ được cập nhật trong vòng 5-15 phút.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include "php/footer.php"; ?>
</body>
</html>
