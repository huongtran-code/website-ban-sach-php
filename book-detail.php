<?php
session_start();
include "db_conn.php";
include "php/func-book.php";
include "php/func-author.php";
include "php/func-category.php";
include "php/func-review.php";
include "php/func-rental.php";

$book_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$book = get_book_by_id($conn, $book_id);

if (!$book) {
    header("Location: index.php?error=Không tìm thấy sách");
    exit;
}

// Tăng view count
increment_view_count($conn, $book_id);

// Lấy thông tin tác giả và thể loại
$authors = get_all_author($conn);
$categories = get_all_categories($conn);

$author_name = "Không rõ";
$category_name = "Chưa phân loại";

if ($authors != 0) {
    foreach ($authors as $author) {
        if ($author['id'] == $book['author_id']) {
            $author_name = $author['name'];
            break;
        }
    }
}

if ($categories != 0) {
    foreach ($categories as $category) {
        if ($category['id'] == $book['category_id']) {
            $category_name = $category['name'];
            break;
        }
    }
}

$stock = isset($book['stock']) ? (int)$book['stock'] : 0;
$price = isset($book['price']) ? (float)$book['price'] : 0;
$discount = isset($book['discount_percent']) ? (int)$book['discount_percent'] : 0;
$final_price = $discount > 0 ? $price * (100 - $discount) / 100 : $price;

// Lấy đánh giá
$reviews = get_reviews_by_book($conn, $book_id);
$rating_stats = get_book_rating_stats($conn, $book_id);
$average_rating = isset($book['average_rating']) ? (float)$book['average_rating'] : ($rating_stats['average_rating'] ?? 0);
$review_count = isset($book['review_count']) ? (int)$book['review_count'] : ($rating_stats['review_count'] ?? 0);
$has_reviewed = false;
if (isset($_SESSION['customer_id'])) {
    $has_reviewed = has_user_reviewed($conn, $book_id, $_SESSION['customer_id']);
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?=htmlspecialchars($book['title'])?> - Nhà Sách Online</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .book-detail-container {
            display: flex;
            gap: 20px;
            min-height: calc(100vh - 200px);
        }
        .book-info-section {
            flex: 0 0 400px;
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: sticky;
            top: 20px;
            height: fit-content;
            max-height: calc(100vh - 40px);
            overflow-y: auto;
        }
        .preview-section {
            flex: 1;
            background: #525252;
            border-radius: 10px;
            overflow: hidden;
            position: relative;
        }
        .preview-header {
            background: #2c2c2c;
            color: white;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .preview-content {
            height: calc(100vh - 200px);
            overflow: auto;
            position: relative;
        }
        .preview-content iframe {
            width: 100%;
            height: 100%;
            border: none;
        }
        /* Watermark overlay to discourage screenshots */
        .pdf-watermark {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 10;
            background: repeating-linear-gradient(
                -45deg,
                transparent,
                transparent 200px,
                rgba(255,255,255,0.03) 200px,
                rgba(255,255,255,0.03) 201px
            );
        }
        /* PDF canvas container */
        #pdfCanvasContainer {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
            padding: 20px;
        }
        #pdfCanvasContainer canvas {
            max-width: 100%;
            box-shadow: 0 4px 20px rgba(0,0,0,0.3);
        }
        /* PDF navigation */
        .pdf-nav {
            display: flex;
            gap: 10px;
            align-items: center;
            color: white;
        }
        .pdf-nav button {
            background: rgba(255,255,255,0.15);
            border: 1px solid rgba(255,255,255,0.3);
            color: white;
            padding: 6px 14px;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s;
        }
        .pdf-nav button:hover {
            background: rgba(255,255,255,0.25);
        }
        .pdf-nav button:disabled {
            opacity: 0.4;
            cursor: not-allowed;
        }
        .pdf-nav span {
            font-size: 14px;
        }
        /* Content Tabs */
        .content-tabs {
            display: flex;
            gap: 5px;
        }
        .tab-btn {
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.2);
            color: white;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 13px;
            transition: all 0.2s;
        }
        .tab-btn:hover {
            background: rgba(255,255,255,0.2);
        }
        .tab-btn.active {
            background: #C92127;
            border-color: #C92127;
        }
        .tab-content {
            display: none;
            padding: 20px;
            color: white;
            min-height: 400px;
        }
        .tab-content.active {
            display: block;
        }
        /* Book Meta Grid */
        .book-meta-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
            padding: 20px;
            background: rgba(255,255,255,0.05);
            border-radius: 10px;
        }
        .meta-item {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .meta-item i {
            font-size: 20px;
            color: #C92127;
            width: 30px;
            text-align: center;
        }
        .meta-label {
            display: block;
            font-size: 12px;
            color: rgba(255,255,255,0.6);
        }
        .meta-value {
            display: block;
            font-weight: 500;
            color: white;
        }
        /* Summary Section */
        .summary-section {
            margin-bottom: 25px;
        }
        .summary-section h4 {
            color: #C92127;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .summary-text {
            line-height: 1.8;
            color: rgba(255,255,255,0.9);
        }
        /* TOC Content */
        .toc-content {
            line-height: 2;
            color: rgba(255,255,255,0.9);
            white-space: pre-line;
        }
        /* Sample Content */
        .sample-content {
            line-height: 1.9;
            color: rgba(255,255,255,0.9);
            font-size: 15px;
            position: relative;
            max-height: 600px;
            overflow: hidden;
        }
        .sample-fade {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 150px;
            background: linear-gradient(transparent, #525252);
            pointer-events: none;
        }
        /* Empty Content */
        .empty-content {
            text-align: center;
            padding: 60px 20px;
            color: rgba(255,255,255,0.6);
        }
        .empty-content i {
            font-size: 60px;
            margin-bottom: 20px;
            opacity: 0.5;
        }
        .empty-content p {
            font-size: 16px;
            margin-bottom: 10px;
        }
        /* Content CTA */
        .content-cta {
            margin-top: 20px;
            padding: 20px;
            background: rgba(0,0,0,0.2);
            border-radius: 10px;
        }
        .cta-message {
            text-align: center;
            color: rgba(255,255,255,0.8);
            font-size: 14px;
            margin-bottom: 10px;
        }
        /* PDF Nav Bar */
        .pdf-nav-bar {
            display: flex;
            justify-content: center;
            gap: 15px;
            align-items: center;
            padding: 15px;
            background: rgba(0,0,0,0.3);
            color: white;
        }
        .pdf-nav-bar button {
            background: rgba(255,255,255,0.15);
            border: 1px solid rgba(255,255,255,0.3);
            color: white;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s;
        }
        .pdf-nav-bar button:hover {
            background: rgba(255,255,255,0.25);
        }
        .pdf-nav-bar button:disabled {
            opacity: 0.4;
            cursor: not-allowed;
        }
        .content-section h4 {
            color: #C92127;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        @media (max-width: 768px) {
            .content-tabs {
                flex-wrap: wrap;
            }
            .tab-btn {
                font-size: 11px;
                padding: 6px 10px;
            }
            .book-meta-grid {
                grid-template-columns: 1fr 1fr;
            }
        }
        /* DevTools warning overlay */
        #devtoolsWarning {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.95);
            z-index: 99999;
            justify-content: center;
            align-items: center;
            color: white;
            font-size: 24px;
            text-align: center;
            flex-direction: column;
            gap: 20px;
        }
        #devtoolsWarning i {
            font-size: 60px;
            color: #dc3545;
        }
        /* Buy Now CTA Banner */
        .preview-buy-cta {
            display: none;
            background: linear-gradient(135deg, #C92127 0%, #ff6b6b 50%, #C92127 100%);
            background-size: 200% 200%;
            animation: gradientShift 3s ease infinite;
            padding: 30px 20px;
            text-align: center;
            border-radius: 16px;
            margin: 20px;
            box-shadow: 0 8px 32px rgba(201, 33, 39, 0.4);
            position: relative;
            overflow: hidden;
        }
        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        .preview-buy-cta::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 60%);
            animation: shine 4s ease-in-out infinite;
        }
        @keyframes shine {
            0%, 100% { transform: translateX(-30%) translateY(-30%); }
            50% { transform: translateX(30%) translateY(30%); }
        }
        .preview-buy-cta .cta-icon {
            font-size: 48px;
            margin-bottom: 12px;
            animation: bounce 2s ease infinite;
        }
        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-8px); }
        }
        .preview-buy-cta h3 {
            color: white;
            font-size: 22px;
            font-weight: 700;
            margin-bottom: 8px;
            position: relative;
            z-index: 1;
        }
        .preview-buy-cta p {
            color: rgba(255,255,255,0.9);
            font-size: 14px;
            margin-bottom: 16px;
            position: relative;
            z-index: 1;
        }
        .preview-buy-cta .btn-buy-now {
            display: inline-block;
            background: white;
            color: #C92127;
            padding: 12px 36px;
            border-radius: 50px;
            font-weight: 700;
            font-size: 16px;
            text-decoration: none;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            position: relative;
            z-index: 1;
            animation: pulse 2s ease-in-out infinite;
        }
        @keyframes pulse {
            0%, 100% { transform: scale(1); box-shadow: 0 4px 15px rgba(0,0,0,0.2); }
            50% { transform: scale(1.05); box-shadow: 0 6px 25px rgba(0,0,0,0.3); }
        }
        .preview-buy-cta .btn-buy-now:hover {
            background: #ffd700;
            color: #333;
            transform: scale(1.08);
        }
        .preview-buy-cta .btn-buy-now i {
            margin-right: 8px;
        }
        .preview-buy-cta.show {
            display: block;
            animation: fadeInUp 0.6s ease-out, gradientShift 3s ease infinite;
        }
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .preview-limit-info {
            color: rgba(255,255,255,0.6);
            font-size: 12px;
            margin-top: 12px;
            position: relative;
            z-index: 1;
        }
        .book-cover-large {
            width: 100%;
            max-width: 300px;
            margin: 0 auto 20px;
            display: block;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }
        .price-section {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .old-price {
            text-decoration: line-through;
            color: #999;
            font-size: 0.9em;
        }
        .new-price {
            color: #e31837;
            font-size: 1.5em;
            font-weight: bold;
        }
        .info-item {
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        .info-item:last-child {
            border-bottom: none;
        }
        .info-label {
            color: #666;
            font-size: 0.9em;
            margin-bottom: 5px;
        }
        .info-value {
            font-weight: 500;
            color: #333;
        }
        /* Chặn text selection trong preview */
        .preview-content {
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
            user-select: none;
        }
        .rating-input {
            display: flex;
            flex-direction: row-reverse;
            justify-content: flex-end;
            gap: 5px;
        }
        .rating-input input[type="radio"] {
            display: none;
        }
        .rating-input label {
            cursor: pointer;
            color: #ddd;
            font-size: 24px;
            transition: color 0.2s;
        }
        .rating-input label:hover,
        .rating-input label:hover ~ label {
            color: #ffc107;
        }
        .rating-input input[type="radio"]:checked ~ label {
            color: #ffc107;
        }
        .review-item {
            background: #f8f9fa;
        }
        @media (max-width: 992px) {
            .book-detail-container {
                flex-direction: column;
            }
            .book-info-section {
                flex: 1;
                position: relative;
            }
            .preview-section {
                min-height: 600px;
            }
        }
    </style>
</head>
<body>
    <?php include "php/header.php"; ?>

    <div class="container py-4">
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Trang chủ</a></li>
                <li class="breadcrumb-item"><a href="category.php?id=<?=$book['category_id']?>"><?=htmlspecialchars($category_name)?></a></li>
                <li class="breadcrumb-item active"><?=htmlspecialchars($book['title'])?></li>
            </ol>
        </nav>

        <div class="book-detail-container">
            <!-- Thông tin sách bên trái -->
            <div class="book-info-section">
                <img src="uploads/cover/<?=$book['cover']?>" 
                     alt="<?=htmlspecialchars($book['title'])?>" 
                     class="book-cover-large"
                     onerror="this.src='https://via.placeholder.com/300x400?text=No+Cover'">
                
                <h2 class="mb-3"><?=htmlspecialchars($book['title'])?></h2>

                <div class="info-item">
                    <div class="info-label"><i class="fas fa-user-edit me-1"></i>Tác giả</div>
                    <div class="info-value">
                        <a href="author.php?id=<?=$book['author_id']?>" class="text-decoration-none">
                            <?=htmlspecialchars($author_name)?>
                        </a>
                    </div>
                </div>

                <div class="info-item">
                    <div class="info-label"><i class="fas fa-folder me-1"></i>Thể loại</div>
                    <div class="info-value">
                        <a href="category.php?id=<?=$book['category_id']?>" class="text-decoration-none">
                            <?=htmlspecialchars($category_name)?>
                        </a>
                    </div>
                </div>

                <div class="price-section">
                    <?php if ($discount > 0): ?>
                        <div class="mb-2">
                            <span class="old-price"><?=format_price($price)?></span>
                            <span class="badge bg-danger ms-2">-<?=$discount?>%</span>
                        </div>
                    <?php endif; ?>
                    <div class="new-price"><?=format_price($final_price)?></div>
                </div>

                <div class="info-item">
                    <div class="info-label"><i class="fas fa-box me-1"></i>Tình trạng</div>
                    <div class="info-value">
                        <?php if ($stock > 0): ?>
                            <span class="badge bg-success">Còn <?=$stock?> cuốn</span>
                        <?php else: ?>
                            <span class="badge bg-danger">Hết hàng</span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="info-item">
                    <div class="info-label"><i class="fas fa-star me-1"></i>Đánh giá</div>
                    <div class="info-value">
                        <?php if ($review_count > 0): ?>
                            <div class="d-flex align-items-center">
                                <span class="text-warning me-2">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <i class="fas fa-star<?=$i <= round($average_rating) ? '' : '-o'?>"></i>
                                    <?php endfor; ?>
                                </span>
                                <span class="ms-2"><?=number_format($average_rating, 1)?> (<?=$review_count?> đánh giá)</span>
                            </div>
                        <?php else: ?>
                            <span class="text-muted">Chưa có đánh giá</span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="info-item">
                    <div class="info-label"><i class="fas fa-align-left me-1"></i>Mô tả</div>
                    <div class="info-value" style="line-height: 1.6;">
                        <?=nl2br(htmlspecialchars($book['description']))?>
                    </div>
                </div>

                <div class="d-grid gap-2 mt-4">
                    <?php if ($stock > 0): ?>
                        <a href="add-to-cart.php?id=<?=$book['id']?>" class="btn btn-primary btn-lg">
                            <i class="fas fa-cart-plus me-2"></i>Thêm vào giỏ hàng
                        </a>
                    <?php else: ?>
                        <button class="btn btn-secondary btn-lg" disabled>
                            <i class="fas fa-ban me-2"></i>Hết hàng
                        </button>
                    <?php endif; ?>
                    
                    <?php if (isset($_SESSION['customer_id']) && !empty($book['file'])): 
                        $min_rent_price = isset($book['price']) ? max(10000, round($book['price'] * 0.3, -3)) : 10000;
                    ?>
                        <a href="rent-book.php?id=<?=$book['id']?>" class="btn btn-outline-warning btn-lg">
                            <i class="fas fa-book-reader me-2"></i>Thuê sách (từ <?=number_format($min_rent_price, 0, ',', '.')?>đ)
                        </a>
                    <?php endif; ?>
                    
                    <a href="index.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Quay lại
                    </a>
                </div>
            </div>

            <!-- Nội dung chi tiết bên phải -->
            <?php 
                $pdf_token = md5(session_id() . $book['id'] . date('Y-m-d'));
                $pdf_url = 'serve-pdf.php?id=' . $book['id'] . '&token=' . $pdf_token;
                $has_pdf = !empty($book['file']);
                $has_content = !empty($book['summary']) || !empty($book['sample_content']);
            ?>
            <div class="preview-section">
                <div class="preview-header">
                    <div>
                        <i class="fas fa-book me-2"></i>
                        <strong>Chi tiết: <?=htmlspecialchars($book['title'])?></strong>
                    </div>
                    <!-- Tab navigation -->
                    <div class="content-tabs">
                        <button class="tab-btn active" data-tab="summary"><i class="fas fa-info-circle me-1"></i>Tổng quan</button>
                        <button class="tab-btn" data-tab="toc"><i class="fas fa-list me-1"></i>Mục lục</button>
                        <button class="tab-btn" data-tab="sample"><i class="fas fa-book-open me-1"></i>Đọc thử</button>
                        <?php if ($has_pdf): ?>
                        <button class="tab-btn" data-tab="pdf"><i class="fas fa-file-pdf me-1"></i>Xem PDF</button>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="preview-content" id="previewContent">
                    <!-- Tab: Tổng quan -->
                    <div class="tab-content active" id="tab-summary">
                        <div class="content-section">
                            <div class="book-meta-grid">
                                <?php if (!empty($book['publisher'])): ?>
                                <div class="meta-item">
                                    <i class="fas fa-building"></i>
                                    <div>
                                        <span class="meta-label">Nhà xuất bản</span>
                                        <span class="meta-value"><?=htmlspecialchars($book['publisher'])?></span>
                                    </div>
                                </div>
                                <?php endif; ?>
                                <?php if (!empty($book['publication_year'])): ?>
                                <div class="meta-item">
                                    <i class="fas fa-calendar"></i>
                                    <div>
                                        <span class="meta-label">Năm xuất bản</span>
                                        <span class="meta-value"><?=$book['publication_year']?></span>
                                    </div>
                                </div>
                                <?php endif; ?>
                                <?php if (!empty($book['pages'])): ?>
                                <div class="meta-item">
                                    <i class="fas fa-file-alt"></i>
                                    <div>
                                        <span class="meta-label">Số trang</span>
                                        <span class="meta-value"><?=$book['pages']?> trang</span>
                                    </div>
                                </div>
                                <?php endif; ?>
                                <?php if (!empty($book['isbn'])): ?>
                                <div class="meta-item">
                                    <i class="fas fa-barcode"></i>
                                    <div>
                                        <span class="meta-label">ISBN</span>
                                        <span class="meta-value"><?=htmlspecialchars($book['isbn'])?></span>
                                    </div>
                                </div>
                                <?php endif; ?>
                                <?php if (!empty($book['language'])): ?>
                                <div class="meta-item">
                                    <i class="fas fa-globe"></i>
                                    <div>
                                        <span class="meta-label">Ngôn ngữ</span>
                                        <span class="meta-value"><?=htmlspecialchars($book['language'])?></span>
                                    </div>
                                </div>
                                <?php endif; ?>
                                <?php if (!empty($book['format'])): ?>
                                <div class="meta-item">
                                    <i class="fas fa-book"></i>
                                    <div>
                                        <span class="meta-label">Định dạng</span>
                                        <span class="meta-value">
                                            <?php
                                            $formats = [
                                                'hardcopy' => 'Sách giấy',
                                                'ebook' => 'Sách điện tử',
                                                'both' => 'Sách giấy & Điện tử'
                                            ];
                                            echo $formats[$book['format']] ?? 'Sách giấy';
                                            ?>
                                        </span>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="summary-section">
                                <h4><i class="fas fa-align-left me-2"></i>Giới thiệu sách</h4>
                                <?php if (!empty($book['summary'])): ?>
                                    <div class="summary-text"><?=nl2br(htmlspecialchars($book['summary']))?></div>
                                <?php else: ?>
                                    <div class="summary-text"><?=nl2br(htmlspecialchars($book['description']))?></div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Buy CTA -->
                            <div class="content-cta">
                                <?php if ($stock > 0): ?>
                                    <a href="add-to-cart.php?id=<?=$book['id']?>" class="btn btn-danger btn-lg w-100">
                                        <i class="fas fa-cart-plus me-2"></i>Thêm vào giỏ hàng - <?=format_price($final_price)?>
                                    </a>
                                <?php else: ?>
                                    <button class="btn btn-secondary btn-lg w-100" disabled>
                                        <i class="fas fa-ban me-2"></i>Hết hàng
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Tab: Mục lục -->
                    <div class="tab-content" id="tab-toc">
                        <div class="content-section">
                            <h4><i class="fas fa-list me-2"></i>Mục lục</h4>
                            <?php if (!empty($book['table_of_contents'])): ?>
                                <div class="toc-content"><?=nl2br(htmlspecialchars($book['table_of_contents']))?></div>
                            <?php else: ?>
                                <div class="empty-content">
                                    <i class="fas fa-list-alt"></i>
                                    <p>Mục lục chưa được cập nhật cho sách này.</p>
                                    <p class="text-muted">Vui lòng liên hệ để biết thêm chi tiết.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Tab: Đọc thử -->
                    <div class="tab-content" id="tab-sample">
                        <div class="content-section">
                            <h4><i class="fas fa-book-open me-2"></i>Trích đoạn sách</h4>
                            <?php if (!empty($book['sample_content'])): ?>
                                <div class="sample-content"><?=nl2br(htmlspecialchars($book['sample_content']))?></div>
                                <div class="sample-fade"></div>
                                <div class="content-cta mt-4">
                                    <div class="cta-message">
                                        <i class="fas fa-lock me-2"></i>
                                        <span>Mua sách để đọc toàn bộ nội dung</span>
                                    </div>
                                    <?php if ($stock > 0): ?>
                                        <a href="add-to-cart.php?id=<?=$book['id']?>" class="btn btn-danger btn-lg w-100 mt-3">
                                            <i class="fas fa-cart-plus me-2"></i>Mua ngay - <?=format_price($final_price)?>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <div class="empty-content">
                                    <i class="fas fa-book-reader"></i>
                                    <p>Nội dung đọc thử chưa được cập nhật.</p>
                                    <p class="text-muted">Vui lòng xem mô tả sách hoặc liên hệ để biết thêm chi tiết.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <?php if ($has_pdf): ?>
                    <!-- Tab: PDF -->
                    <div class="tab-content" id="tab-pdf">
                        <div class="pdf-nav-bar">
                            <button onclick="prevPage()" id="prevBtn" disabled><i class="fas fa-chevron-left"></i> Trước</button>
                            <span id="pageInfo">Trang 1 / ?</span>
                            <button onclick="nextPage()" id="nextBtn">Sau <i class="fas fa-chevron-right"></i></button>
                        </div>
                        <div class="pdf-watermark"></div>
                        <div id="pdfCanvasContainer">
                            <p style="color: white; text-align: center; padding: 40px;">Nhấn để tải sách...</p>
                        </div>
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
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- DevTools Warning Overlay -->
            <div id="devtoolsWarning">
                <i class="fas fa-shield-alt"></i>
                <div>Vui lòng đóng Developer Tools<br><small>Nội dung được bảo vệ bản quyền</small></div>
            </div>
        </div>

        <!-- Phần đánh giá -->
        <div class="mt-5">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0"><i class="fas fa-star me-2"></i>Đánh giá sách</h4>
                </div>
                <div class="card-body">
                    <?php if (isset($_SESSION['customer_id']) && !$has_reviewed): ?>
                        <!-- Form đánh giá -->
                        <div class="mb-4 p-3 bg-light rounded">
                            <h5>Viết đánh giá của bạn</h5>
                            <form method="POST" action="php/add-review.php">
                                <input type="hidden" name="book_id" value="<?=$book_id?>">
                                <div class="mb-3">
                                    <label class="form-label">Đánh giá sao</label>
                                    <div class="rating-input">
                                        <?php for ($i = 5; $i >= 1; $i--): ?>
                                            <input type="radio" name="rating" id="rating<?=$i?>" value="<?=$i?>" required>
                                            <label for="rating<?=$i?>" class="star-label">
                                                <i class="fas fa-star"></i>
                                            </label>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Nhận xét</label>
                                    <textarea name="comment" class="form-control" rows="3" placeholder="Chia sẻ cảm nhận của bạn về cuốn sách này..."></textarea>
                                </div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-paper-plane me-2"></i>Gửi đánh giá
                                </button>
                            </form>
                        </div>
                    <?php elseif (isset($_SESSION['customer_id']) && $has_reviewed): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>Bạn đã đánh giá sách này rồi.
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-circle me-2"></i>Vui lòng <a href="login.php">đăng nhập</a> để đánh giá sách.
                        </div>
                    <?php endif; ?>

                    <!-- Thống kê đánh giá -->
                    <?php if ($review_count > 0): ?>
                        <div class="mb-4">
                            <div class="row">
                                <div class="col-md-4 text-center">
                                    <div class="display-4 text-warning"><?=number_format($average_rating, 1)?></div>
                                    <div class="mb-2">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="fas fa-star<?=$i <= round($average_rating) ? '' : '-o'?> text-warning"></i>
                                        <?php endfor; ?>
                                    </div>
                                    <div class="text-muted"><?=$review_count?> đánh giá</div>
                                </div>
                                <div class="col-md-8">
                                    <?php
                                    $total = $rating_stats['review_count'] ?? 1;
                                    for ($i = 5; $i >= 1; $i--):
                                        $count = $rating_stats["rating_$i"] ?? 0;
                                        $percent = $total > 0 ? ($count / $total) * 100 : 0;
                                    ?>
                                        <div class="d-flex align-items-center mb-2">
                                            <span class="me-2" style="width: 20px;"><?=$i?> <i class="fas fa-star text-warning"></i></span>
                                            <div class="progress flex-grow-1 me-2" style="height: 20px;">
                                                <div class="progress-bar bg-warning" role="progressbar" style="width: <?=$percent?>%"></div>
                                            </div>
                                            <span class="text-muted" style="width: 40px;"><?=$count?></span>
                                        </div>
                                    <?php endfor; ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Danh sách đánh giá -->
                    <div class="reviews-list">
                        <h5 class="mb-3">Đánh giá từ người dùng</h5>
                        <?php if (count($reviews) > 0): ?>
                            <?php foreach ($reviews as $review): ?>
                                <div class="review-item mb-4 p-3 border rounded">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div>
                                            <strong><?=htmlspecialchars($review['user_name'])?></strong>
                                            <div class="text-warning">
                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                    <i class="fas fa-star<?=$i <= $review['rating'] ? '' : '-o'?>"></i>
                                                <?php endfor; ?>
                                            </div>
                                        </div>
                                        <small class="text-muted"><?=date('d/m/Y H:i', strtotime($review['created_at']))?></small>
                                    </div>
                                    <?php if (!empty($review['comment'])): ?>
                                        <p class="mb-0"><?=nl2br(htmlspecialchars($review['comment']))?></p>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-muted">Chưa có đánh giá nào.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include "php/footer.php"; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.1/dist/js/bootstrap.bundle.min.js"></script>
    <!-- PDF.js library for canvas-based rendering (no download toolbar) -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
    <script>
        // ==================== TAB NAVIGATION ====================
        const tabBtns = document.querySelectorAll('.tab-btn');
        const tabContents = document.querySelectorAll('.tab-content');
        let pdfLoaded = false;
        
        tabBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const targetTab = this.getAttribute('data-tab');
                
                // Update active states
                tabBtns.forEach(b => b.classList.remove('active'));
                tabContents.forEach(c => c.classList.remove('active'));
                
                this.classList.add('active');
                document.getElementById('tab-' + targetTab).classList.add('active');
                
                // Load PDF only when tab is clicked
                if (targetTab === 'pdf' && !pdfLoaded) {
                    loadPDF();
                }
            });
        });
        
        // ==================== PDF.JS RENDERER ====================
        pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
        
        let pdfDoc = null;
        let currentPage = 1;
        let totalPages = 0;
        const pdfUrl = '<?=$pdf_url?>';
        const hasPdf = <?=$has_pdf ? 'true' : 'false'?>;
        
        // Calculate max preview pages = 1/5 of total (minimum 1)
        function getMaxPreviewPages() {
            return Math.max(1, Math.ceil(totalPages / 5));
        }
        
        function loadPDF() {
            if (!hasPdf) return;
            
            document.getElementById('pdfCanvasContainer').innerHTML = '<p style="color: white; text-align: center; padding: 40px;">Đang tải sách...</p>';
            
            pdfjsLib.getDocument(pdfUrl).promise.then(function(pdf) {
                pdfDoc = pdf;
                totalPages = pdf.numPages;
                pdfLoaded = true;
                const maxPreview = getMaxPreviewPages();
                document.getElementById('pageInfo').textContent = 'Trang 1 / ' + maxPreview;
                const previewCount = document.getElementById('previewPageCount');
                const totalCount = document.getElementById('totalPageCount');
                if (previewCount) previewCount.textContent = maxPreview;
                if (totalCount) totalCount.textContent = totalPages;
                document.getElementById('pdfCanvasContainer').innerHTML = '';
                for (let i = 1; i <= maxPreview; i++) {
                    renderPage(i);
                }
                updateNavButtons();
                setupScrollDetection();
            }).catch(function(error) {
                console.error('PDF load error:', error);
                document.getElementById('pdfCanvasContainer').innerHTML = '<div style="color: white; text-align: center; padding: 40px;"><i class="fas fa-exclamation-triangle" style="font-size: 48px; margin-bottom: 20px; opacity: 0.5;"></i><p>Không thể tải file PDF.</p><p style="font-size: 13px; opacity: 0.7;">Vui lòng xem nội dung ở tab "Tổng quan" hoặc "Đọc thử".</p></div>';
            });
        }
        
        function renderPage(pageNum) {
            pdfDoc.getPage(pageNum).then(function(page) {
                const scale = 1.5;
                const viewport = page.getViewport({ scale: scale });
                const canvas = document.createElement('canvas');
                canvas.setAttribute('data-page', pageNum);
                const context = canvas.getContext('2d');
                canvas.height = viewport.height;
                canvas.width = viewport.width;
                
                page.render({
                    canvasContext: context,
                    viewport: viewport
                });
                
                document.getElementById('pdfCanvasContainer').appendChild(canvas);
            });
        }
        
        function prevPage() {
            if (currentPage <= 1) return;
            currentPage--;
            scrollToPage(currentPage);
            updateNavButtons();
        }
        
        function nextPage() {
            const maxPreview = getMaxPreviewPages();
            if (currentPage >= maxPreview) return;
            currentPage++;
            scrollToPage(currentPage);
            updateNavButtons();
        }
        
        function scrollToPage(pageNum) {
            const canvas = document.querySelector(`canvas[data-page="${pageNum}"]`);
            if (canvas) {
                canvas.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
            document.getElementById('pageInfo').textContent = 'Trang ' + pageNum + ' / ' + getMaxPreviewPages();
        }
        
        function updateNavButtons() {
            const maxPreview = getMaxPreviewPages();
            document.getElementById('prevBtn').disabled = currentPage <= 1;
            document.getElementById('nextBtn').disabled = currentPage >= maxPreview;
        }
        
        // ==================== SCROLL DETECTION FOR BUY NOW ====================
        function setupScrollDetection() {
            const previewEl = document.getElementById('previewContent');
            const sentinel = document.getElementById('scrollSentinel');
            const buyNowCta = document.getElementById('buyNowCta');
            
            // Use IntersectionObserver to detect when sentinel comes into view
            const observer = new IntersectionObserver(function(entries) {
                entries.forEach(function(entry) {
                    if (entry.isIntersecting) {
                        buyNowCta.classList.add('show');
                    }
                });
            }, {
                root: previewEl,
                threshold: 0.1
            });
            
            observer.observe(sentinel);
            
            // Also detect scroll position
            previewEl.addEventListener('scroll', function() {
                const scrollTop = previewEl.scrollTop;
                const scrollHeight = previewEl.scrollHeight;
                const clientHeight = previewEl.clientHeight;
                
                // Show CTA when scrolled near bottom (within 100px)
                if (scrollTop + clientHeight >= scrollHeight - 100) {
                    buyNowCta.classList.add('show');
                }
                
                // Update current page based on scroll position
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

        // ==================== ANTI-DOWNLOAD PROTECTION ====================
        
        // Block keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // F12
            if (e.keyCode === 123 || e.key === 'F12') {
                e.preventDefault(); e.stopPropagation(); return false;
            }
            // Ctrl+Shift+I (DevTools)
            if (e.ctrlKey && e.shiftKey && (e.keyCode === 73 || e.key === 'I')) {
                e.preventDefault(); e.stopPropagation(); return false;
            }
            // Ctrl+Shift+J (Console)
            if (e.ctrlKey && e.shiftKey && (e.keyCode === 74 || e.key === 'J')) {
                e.preventDefault(); e.stopPropagation(); return false;
            }
            // Ctrl+Shift+C (Inspect)
            if (e.ctrlKey && e.shiftKey && (e.keyCode === 67 || e.key === 'C')) {
                e.preventDefault(); e.stopPropagation(); return false;
            }
            // Ctrl+U (View Source)
            if (e.ctrlKey && (e.keyCode === 85 || e.key === 'u')) {
                e.preventDefault(); e.stopPropagation(); return false;
            }
            // Ctrl+S (Save)
            if (e.ctrlKey && (e.keyCode === 83 || e.key === 's')) {
                e.preventDefault(); e.stopPropagation(); return false;
            }
            // Ctrl+P (Print)
            if (e.ctrlKey && (e.keyCode === 80 || e.key === 'p')) {
                e.preventDefault(); e.stopPropagation(); return false;
            }
            // Cmd+S, Cmd+P for Mac
            if (e.metaKey && (e.key === 's' || e.key === 'p')) {
                e.preventDefault(); e.stopPropagation(); return false;
            }
            // Cmd+Shift+I/J/C for Mac
            if (e.metaKey && e.shiftKey && (e.key === 'I' || e.key === 'J' || e.key === 'C')) {
                e.preventDefault(); e.stopPropagation(); return false;
            }
            // Cmd+Option+I for Mac DevTools
            if (e.metaKey && e.altKey && (e.key === 'i' || e.key === 'I')) {
                e.preventDefault(); e.stopPropagation(); return false;
            }
        }, true);

        // Block right-click on preview
        const previewContent = document.getElementById('previewContent');
        previewContent.addEventListener('contextmenu', function(e) {
            e.preventDefault(); return false;
        }, true);

        // Block drag
        previewContent.addEventListener('dragstart', function(e) {
            e.preventDefault(); return false;
        }, true);

        // Block text selection
        previewContent.addEventListener('selectstart', function(e) {
            e.preventDefault(); return false;
        }, true);

        // Block copy/cut
        ['copy', 'cut'].forEach(function(evt) {
            previewContent.addEventListener(evt, function(e) {
                e.preventDefault(); return false;
            }, true);
        });

        // Block print screen
        document.addEventListener('keyup', function(e) {
            if (e.keyCode === 44) {
                // Clear clipboard on print screen
                navigator.clipboard.writeText('').catch(function(){});
            }
        }, true);

        // ==================== DEVTOOLS DETECTION ====================
        let devtoolsOpen = false;
        const devtoolsWarning = document.getElementById('devtoolsWarning');
        
        // Method 1: Window size detection
        function checkDevTools() {
            const threshold = 160;
            const widthDiff = window.outerWidth - window.innerWidth;
            const heightDiff = window.outerHeight - window.innerHeight;
            
            if (widthDiff > threshold || heightDiff > threshold) {
                if (!devtoolsOpen) {
                    devtoolsOpen = true;
                    devtoolsWarning.style.display = 'flex';
                    // Clear all canvases
                    document.querySelectorAll('#pdfCanvasContainer canvas').forEach(function(c) {
                        var ctx = c.getContext('2d');
                        ctx.clearRect(0, 0, c.width, c.height);
                        ctx.fillStyle = '#333';
                        ctx.fillRect(0, 0, c.width, c.height);
                        ctx.fillStyle = '#fff';
                        ctx.font = '20px Inter, sans-serif';
                        ctx.textAlign = 'center';
                        ctx.fillText('Vui lòng đóng Developer Tools', c.width/2, c.height/2);
                    });
                }
            } else {
                if (devtoolsOpen) {
                    devtoolsOpen = false;
                    devtoolsWarning.style.display = 'none';
                    // Reload PDF
                    location.reload();
                }
            }
        }
        setInterval(checkDevTools, 500);
        
        // Method 2: Debugger detection
        (function() {
            function detectDebugger() {
                const start = performance.now();
                debugger;
                const end = performance.now();
                if (end - start > 100) {
                    devtoolsOpen = true;
                    devtoolsWarning.style.display = 'flex';
                }
            }
            setInterval(detectDebugger, 1000);
        })();

        // Block printing via CSS
        const printStyle = document.createElement('style');
        printStyle.textContent = '@media print { body { display: none !important; } * { display: none !important; } }';
        document.head.appendChild(printStyle);

        // Block saving page as
        window.addEventListener('beforeprint', function(e) {
            e.preventDefault();
            document.body.style.display = 'none';
        });
        window.addEventListener('afterprint', function() {
            document.body.style.display = '';
        });
    </script>
</body>
</html>

