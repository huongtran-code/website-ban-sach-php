<!-- Newsletter Section -->
<div class="footer-newsletter">
    <div class="container">
        <h4><i class="fas fa-envelope me-2"></i>ĐĂNG KÝ NHẬN BẢN TIN</h4>
        <form onsubmit="event.preventDefault(); alert('Đăng ký thành công!');">
            <input type="email" placeholder="Nhập địa chỉ email của bạn" required>
            <button type="submit">Đăng ký</button>
        </form>
    </div>
</div>

<!-- Footer Main -->
<footer class="footer">
    <div class="container">
        <div class="footer-content">
            <div class="footer-brand">
                <a href="index.php" class="footer-logo">
                    📚 <span>Nhà Sách Online</span>
                </a>
                <p>
                    Nhà Sách Online - Hệ thống nhà sách trực tuyến hàng đầu Việt Nam.<br>
                    Chuyên cung cấp sách, văn phòng phẩm và đồ chơi giáo dục.
                </p>
                <div class="footer-socials">
                    <a href="#" title="Facebook"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" title="Instagram"><i class="fab fa-instagram"></i></a>
                    <a href="#" title="YouTube"><i class="fab fa-youtube"></i></a>
                    <a href="#" title="Twitter"><i class="fab fa-twitter"></i></a>
                </div>
            </div>
            <div>
                <h4>Dịch vụ</h4>
                <ul>
                    <li><a href="about.php">Giới thiệu</a></li>
                    <li><a href="return-policy.php">Chính sách đổi trả</a></li>
                    <li><a href="shipping.php">Chính sách vận chuyển</a></li>
                    <li><a href="payment-guide.php">Hướng dẫn thanh toán</a></li>
                </ul>
            </div>
            <div>
                <h4>Hỗ trợ</h4>
                <ul>
                    <li><a href="faq.php">Câu hỏi thường gặp</a></li>
                    <li><a href="contact.php">Liên hệ</a></li>
                    <li><a href="#">Hệ thống cửa hàng</a></li>
                </ul>
            </div>
            <div>
                <h4>Liên hệ</h4>
                <ul>
                    <li><i class="fas fa-map-marker-alt me-2 text-fahasa"></i>TP. Hồ Chí Minh</li>
                    <li><i class="fas fa-envelope me-2 text-fahasa"></i>info@nhasach.com</li>
                    <li><i class="fas fa-phone me-2 text-fahasa"></i>1900 6656</li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; 2024 Nhà Sách Online. All rights reserved.</p>
            <div class="footer-payments">
                <span class="text-fahasa">VISA</span>
                <span style="color: #eb001b;">MasterCard</span>
                <span style="color: #a50064;">MoMo</span>
                <span style="color: #1a8917;">ZaloPay</span>
            </div>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.1/dist/js/bootstrap.bundle.min.js"></script>

<?php include __DIR__ . "/toast-notification.php"; ?>

<?php if (isset($_SESSION['customer_id'])): ?>
    <?php include __DIR__ . "/chat-widget.php"; ?>
<?php endif; ?>
