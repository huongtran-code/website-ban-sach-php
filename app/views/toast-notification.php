<?php
// Toast Notification Component - Hiển thị thông báo success/error từ URL params
if (isset($_GET['success']) || isset($_GET['error'])):
?>
<div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 9999;">
    <?php if (isset($_GET['success'])): ?>
    <div id="successToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-header bg-success text-white">
            <i class="fas fa-check-circle me-2"></i>
            <strong class="me-auto">Thành công</strong>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
        <div class="toast-body">
            <?=htmlspecialchars(urldecode($_GET['success']))?>
        </div>
    </div>
    <?php endif; ?>
    
    <?php if (isset($_GET['error'])): ?>
    <div id="errorToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-header bg-danger text-white">
            <i class="fas fa-exclamation-circle me-2"></i>
            <strong class="me-auto">Lỗi</strong>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
        <div class="toast-body">
            <?=htmlspecialchars(urldecode($_GET['error']))?>
        </div>
    </div>
    <?php endif; ?>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        setTimeout(function() {
            <?php if (isset($_GET['success'])): ?>
            const successToast = document.getElementById('successToast');
            if (successToast) {
                const bsToast = new bootstrap.Toast(successToast, { delay: 3000, animation: true });
                bsToast.show();
            }
            <?php endif; ?>
            
            <?php if (isset($_GET['error'])): ?>
            const errorToast = document.getElementById('errorToast');
            if (errorToast) {
                const bsToast = new bootstrap.Toast(errorToast, { delay: 5000, animation: true });
                bsToast.show();
            }
            <?php endif; ?>
        }, 100);
    });
</script>
<?php endif; ?>




