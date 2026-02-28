<?php
session_start();
require_once __DIR__ . "/../config/bootstrap.php";
include MODELS_PATH . "func-book.php";

$book_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$book = get_book_by_id($conn, $book_id);

if (!$book) {
    header("Location: index.php?error=Không tìm thấy sách");
    exit;
}

$pdf_token = md5(session_id() . $book['id'] . date('Y-m-d'));
$pdf_url = 'serve-pdf.php?id=' . $book['id'] . '&token=' . $pdf_token;

$stock = isset($book['stock']) ? (int)$book['stock'] : 0;
$price = isset($book['price']) ? (float)$book['price'] : 0;
$discount = isset($book['discount_percent']) ? (int)$book['discount_percent'] : 0;
$final_price = $discount > 0 ? $price * (100 - $discount) / 100 : $price;
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Xem trước: <?=htmlspecialchars($book['title'])?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
        body {
            overflow: hidden;
            margin: 0;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        }
        .preview-container {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100vh;
            background: #525252;
            z-index: 9999;
        }
        .preview-header {
            background: #2c2c2c;
            color: white;
            padding: 10px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .preview-content {
            height: calc(100vh - 60px);
            overflow: auto;
            position: relative;
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
            user-select: none;
        }
        .pdf-watermark {
            position: absolute;
            top: 0; left: 0; width: 100%; height: 100%;
            pointer-events: none; z-index: 10;
            background: repeating-linear-gradient(-45deg, transparent, transparent 200px, rgba(255,255,255,0.03) 200px, rgba(255,255,255,0.03) 201px);
        }
        #pdfCanvasContainer {
            display: flex; flex-direction: column; align-items: center; gap: 10px; padding: 20px;
        }
        #pdfCanvasContainer canvas {
            max-width: 100%; box-shadow: 0 4px 20px rgba(0,0,0,0.3);
        }
        .pdf-nav {
            display: flex; gap: 10px; align-items: center; color: white;
        }
        .pdf-nav button {
            background: rgba(255,255,255,0.15); border: 1px solid rgba(255,255,255,0.3);
            color: white; padding: 6px 14px; border-radius: 6px; cursor: pointer; transition: all 0.2s;
        }
        .pdf-nav button:hover { background: rgba(255,255,255,0.25); }
        .pdf-nav button:disabled { opacity: 0.4; cursor: not-allowed; }
        .pdf-nav span { font-size: 14px; }
        #devtoolsWarning {
            display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.95); z-index: 99999; justify-content: center; align-items: center;
            color: white; font-size: 24px; text-align: center; flex-direction: column; gap: 20px;
        }
        #devtoolsWarning i { font-size: 60px; color: #dc3545; }
        @media print { body, * { display: none !important; } }
        
        /* Buy Now CTA Banner */
        .preview-buy-cta {
            display: none;
            background: linear-gradient(135deg, #C92127 0%, #ff6b6b 50%, #C92127 100%);
            background-size: 200% 200%;
            animation: gradientShift 3s ease infinite;
            padding: 30px 20px; text-align: center; border-radius: 16px; margin: 20px;
            box-shadow: 0 8px 32px rgba(201, 33, 39, 0.4);
            position: relative; overflow: hidden;
        }
        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        .preview-buy-cta::before {
            content: ''; position: absolute; top: -50%; left: -50%; width: 200%; height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 60%);
            animation: shine 4s ease-in-out infinite;
        }
        @keyframes shine {
            0%, 100% { transform: translateX(-30%) translateY(-30%); }
            50% { transform: translateX(30%) translateY(30%); }
        }
        .preview-buy-cta .cta-icon { font-size: 48px; margin-bottom: 12px; animation: bounce 2s ease infinite; }
        @keyframes bounce { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-8px); } }
        .preview-buy-cta h3 { color: white; font-size: 22px; font-weight: 700; margin-bottom: 8px; position: relative; z-index: 1; }
        .preview-buy-cta p { color: rgba(255,255,255,0.9); font-size: 14px; margin-bottom: 16px; position: relative; z-index: 1; }
        .preview-buy-cta .btn-buy-now {
            display: inline-block; background: white; color: #C92127; padding: 12px 36px;
            border-radius: 50px; font-weight: 700; font-size: 16px; text-decoration: none;
            transition: all 0.3s; box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            position: relative; z-index: 1; animation: pulse 2s ease-in-out infinite;
        }
        @keyframes pulse {
            0%, 100% { transform: scale(1); box-shadow: 0 4px 15px rgba(0,0,0,0.2); }
            50% { transform: scale(1.05); box-shadow: 0 6px 25px rgba(0,0,0,0.3); }
        }
        .preview-buy-cta .btn-buy-now:hover { background: #ffd700; color: #333; transform: scale(1.08); }
        .preview-buy-cta .btn-buy-now i { margin-right: 8px; }
        .preview-buy-cta.show { display: block; animation: fadeInUp 0.6s ease-out, gradientShift 3s ease infinite; }
        @keyframes fadeInUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }
        .preview-limit-info { color: rgba(255,255,255,0.6); font-size: 12px; margin-top: 12px; position: relative; z-index: 1; }
    </style>
</head>
<body>
    <div class="preview-container">
        <div class="preview-header">
            <div>
                <i class="fas fa-book me-2"></i>
                <strong><?=htmlspecialchars($book['title'])?></strong>
            </div>
            <div class="pdf-nav">
                <button onclick="prevPage()" id="prevBtn" disabled><i class="fas fa-chevron-left"></i> Trước</button>
                <span id="pageInfo">Trang 1 / ?</span>
                <button onclick="nextPage()" id="nextBtn">Sau <i class="fas fa-chevron-right"></i></button>
            </div>
        </div>
        <div class="preview-content" id="previewContent">
            <div class="pdf-watermark"></div>
            <div id="pdfCanvasContainer">
                <p style="color: white; text-align: center; padding: 40px;">Đang tải sách...</p>
            </div>
            <!-- Buy Now CTA -->
            <div class="preview-buy-cta" id="buyNowCta">
                <div class="cta-icon">📖</div>
                <h3>Bạn đã xem hết phần xem trước!</h3>
                <p>Mua sách để đọc toàn bộ nội dung</p>
                <?php if ($stock > 0): ?>
                    <a href="add-to-cart.php?id=<?=$book['id']?>" class="btn-buy-now">
                        <i class="fas fa-cart-plus"></i>Mua ngay - <?=format_price($final_price)?>
                    </a>
                <?php else: ?>
                    <span class="btn-buy-now" style="background: #ccc; color: #666; cursor: not-allowed; animation: none;">
                        <i class="fas fa-ban"></i>Hết hàng
                    </span>
                <?php endif; ?>
                <div class="preview-limit-info">
                    <i class="fas fa-info-circle me-1"></i>
                    Bạn đã xem <span id="previewPageCount"></span> / <span id="totalPageCount"></span> trang
                </div>
            </div>
            <div id="scrollSentinel" style="height: 1px;"></div>
        </div>
    </div>
    
    <div id="devtoolsWarning">
        <i class="fas fa-shield-alt"></i>
        <div>Vui lòng đóng Developer Tools<br><small>Nội dung được bảo vệ bản quyền</small></div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
    <script>
        pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
        
        let pdfDoc = null, currentPage = 1, totalPages = 0;
        const pdfUrl = '<?=$pdf_url?>';
        
        function getMaxPreviewPages() {
            return Math.max(1, Math.ceil(totalPages / 5));
        }
        
        pdfjsLib.getDocument(pdfUrl).promise.then(function(pdf) {
            pdfDoc = pdf;
            totalPages = pdf.numPages;
            const maxPreview = getMaxPreviewPages();
            document.getElementById('pageInfo').textContent = 'Trang 1 / ' + maxPreview;
            document.getElementById('previewPageCount').textContent = maxPreview;
            document.getElementById('totalPageCount').textContent = totalPages;
            document.getElementById('pdfCanvasContainer').innerHTML = '';
            for (let i = 1; i <= maxPreview; i++) renderPage(i);
            updateNavButtons();
            setupScrollDetection();
        }).catch(function() {
            document.getElementById('pdfCanvasContainer').innerHTML = '<p style="color: white; text-align: center; padding: 40px;">Không thể tải sách.</p>';
        });
        
        function renderPage(pageNum) {
            pdfDoc.getPage(pageNum).then(function(page) {
                const viewport = page.getViewport({ scale: 1.5 });
                const canvas = document.createElement('canvas');
                canvas.setAttribute('data-page', pageNum);
                const ctx = canvas.getContext('2d');
                canvas.height = viewport.height;
                canvas.width = viewport.width;
                page.render({ canvasContext: ctx, viewport: viewport });
                document.getElementById('pdfCanvasContainer').appendChild(canvas);
            });
        }
        
        function prevPage() {
            if (currentPage <= 1) return;
            currentPage--; scrollToPage(currentPage); updateNavButtons();
        }
        function nextPage() {
            if (currentPage >= getMaxPreviewPages()) return;
            currentPage++; scrollToPage(currentPage); updateNavButtons();
        }
        function scrollToPage(n) {
            const c = document.querySelector(`canvas[data-page="${n}"]`);
            if (c) c.scrollIntoView({ behavior: 'smooth', block: 'start' });
            document.getElementById('pageInfo').textContent = 'Trang ' + n + ' / ' + getMaxPreviewPages();
        }
        function updateNavButtons() {
            document.getElementById('prevBtn').disabled = currentPage <= 1;
            document.getElementById('nextBtn').disabled = currentPage >= getMaxPreviewPages();
        }
        
        // Scroll detection for Buy Now CTA
        function setupScrollDetection() {
            const previewEl = document.getElementById('previewContent');
            const sentinel = document.getElementById('scrollSentinel');
            const buyNowCta = document.getElementById('buyNowCta');
            
            const observer = new IntersectionObserver(function(entries) {
                entries.forEach(function(entry) {
                    if (entry.isIntersecting) buyNowCta.classList.add('show');
                });
            }, { root: previewEl, threshold: 0.1 });
            observer.observe(sentinel);
            
            previewEl.addEventListener('scroll', function() {
                if (previewEl.scrollTop + previewEl.clientHeight >= previewEl.scrollHeight - 100) {
                    buyNowCta.classList.add('show');
                }
                const canvases = document.querySelectorAll('#pdfCanvasContainer canvas');
                canvases.forEach(function(c, idx) {
                    const rect = c.getBoundingClientRect();
                    const containerRect = previewEl.getBoundingClientRect();
                    if (rect.top >= containerRect.top && rect.top < containerRect.top + containerRect.height / 2) {
                        currentPage = idx + 1;
                        document.getElementById('pageInfo').textContent = 'Trang ' + currentPage + ' / ' + getMaxPreviewPages();
                        updateNavButtons();
                    }
                });
            });
        }

        // Anti-download protection
        document.addEventListener('keydown', function(e) {
            if (e.keyCode === 123 || e.key === 'F12') { e.preventDefault(); return false; }
            if (e.ctrlKey && e.shiftKey && (e.keyCode === 73 || e.key === 'I')) { e.preventDefault(); return false; }
            if (e.ctrlKey && e.shiftKey && (e.keyCode === 74 || e.key === 'J')) { e.preventDefault(); return false; }
            if (e.ctrlKey && e.shiftKey && (e.keyCode === 67 || e.key === 'C')) { e.preventDefault(); return false; }
            if (e.ctrlKey && (e.keyCode === 85 || e.key === 'u')) { e.preventDefault(); return false; }
            if (e.ctrlKey && (e.keyCode === 83 || e.key === 's')) { e.preventDefault(); return false; }
            if (e.ctrlKey && (e.keyCode === 80 || e.key === 'p')) { e.preventDefault(); return false; }
            if (e.metaKey && (e.key === 's' || e.key === 'p')) { e.preventDefault(); return false; }
            if (e.metaKey && e.shiftKey && (e.key === 'I' || e.key === 'J' || e.key === 'C')) { e.preventDefault(); return false; }
            if (e.metaKey && e.altKey && (e.key === 'i' || e.key === 'I')) { e.preventDefault(); return false; }
        }, true);
        document.addEventListener('contextmenu', function(e) { e.preventDefault(); }, true);
        document.addEventListener('dragstart', function(e) { e.preventDefault(); }, true);
        document.addEventListener('selectstart', function(e) { e.preventDefault(); }, true);
        document.addEventListener('copy', function(e) { e.preventDefault(); }, true);
        document.addEventListener('cut', function(e) { e.preventDefault(); }, true);
        document.addEventListener('keyup', function(e) {
            if (e.keyCode === 44) navigator.clipboard.writeText('').catch(function(){});
        }, true);

        // DevTools detection
        let devtoolsOpen = false;
        const devtoolsWarning = document.getElementById('devtoolsWarning');
        function checkDevTools() {
            const t = 160;
            if (window.outerWidth - window.innerWidth > t || window.outerHeight - window.innerHeight > t) {
                if (!devtoolsOpen) {
                    devtoolsOpen = true;
                    devtoolsWarning.style.display = 'flex';
                    document.querySelectorAll('#pdfCanvasContainer canvas').forEach(function(c) {
                        var ctx = c.getContext('2d');
                        ctx.clearRect(0, 0, c.width, c.height);
                        ctx.fillStyle = '#333'; ctx.fillRect(0, 0, c.width, c.height);
                        ctx.fillStyle = '#fff'; ctx.font = '20px sans-serif'; ctx.textAlign = 'center';
                        ctx.fillText('Vui lòng đóng Developer Tools', c.width/2, c.height/2);
                    });
                }
            } else if (devtoolsOpen) {
                devtoolsOpen = false; devtoolsWarning.style.display = 'none'; location.reload();
            }
        }
        setInterval(checkDevTools, 500);
        (function() {
            setInterval(function() {
                const s = performance.now(); debugger; const e = performance.now();
                if (e - s > 100) { devtoolsOpen = true; devtoolsWarning.style.display = 'flex'; }
            }, 1000);
        })();
        window.addEventListener('beforeprint', function(e) { e.preventDefault(); document.body.style.display = 'none'; });
        window.addEventListener('afterprint', function() { document.body.style.display = ''; });
        document.addEventListener('keydown', function(e) {
            if (e.keyCode === 27) {
                window.close();
                if (window.history.length > 1) window.history.back();
                else window.location.href = 'index.php';
            }
        }, true);
    </script>
</body>
</html>
