<?php
include "db_conn.php";

// Tạo thư mục nếu chưa tồn tại
if (!is_dir('uploads/cover')) {
    mkdir('uploads/cover', 0777, true);
}

function downloadImage($url, $savePath) {
    $ch = curl_init($url);
    $fp = fopen($savePath, 'wb');
    
    curl_setopt($ch, CURLOPT_FILE, $fp);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
    
    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    curl_close($ch);
    fclose($fp);
    
    if ($httpCode == 200 && file_exists($savePath) && filesize($savePath) > 0) {
        return true;
    }
    
    if (file_exists($savePath)) {
        unlink($savePath);
    }
    
    return false;
}

function getBookCoverFromGoogleBooks($title, $author = '') {
    $query = urlencode($title . ' ' . $author);
    $url = "https://www.googleapis.com/books/v1/volumes?q=" . $query . "&maxResults=1";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode == 200 && $response) {
        $data = json_decode($response, true);
        if (isset($data['items'][0]['volumeInfo']['imageLinks']['thumbnail'])) {
            $imageUrl = $data['items'][0]['volumeInfo']['imageLinks']['thumbnail'];
            // Thay đổi kích thước thành lớn hơn
            $imageUrl = str_replace('zoom=1', 'zoom=3', $imageUrl);
            $imageUrl = str_replace('&edge=curl', '', $imageUrl);
            return $imageUrl;
        }
        if (isset($data['items'][0]['volumeInfo']['imageLinks']['smallThumbnail'])) {
            $imageUrl = $data['items'][0]['volumeInfo']['imageLinks']['smallThumbnail'];
            $imageUrl = str_replace('zoom=5', 'zoom=3', $imageUrl);
            return $imageUrl;
        }
    }
    
    return null;
}

function getBookCoverFromOpenLibrary($title, $author = '') {
    $query = urlencode($title);
    $url = "https://openlibrary.org/search.json?title=" . $query . "&limit=1";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode == 200 && $response) {
        $data = json_decode($response, true);
        if (isset($data['docs'][0]['cover_i'])) {
            $coverId = $data['docs'][0]['cover_i'];
            return "https://covers.openlibrary.org/b/id/{$coverId}-L.jpg";
        }
    }
    
    return null;
}

function createPlaceholderImage($title, $savePath) {
    // Tạo ảnh placeholder với GD library
    $width = 300;
    $height = 400;
    
    $image = imagecreatetruecolor($width, $height);
    
    // Màu nền gradient
    $bg1 = imagecolorallocate($image, 227, 24, 55); // Đỏ
    $bg2 = imagecolorallocate($image, 196, 20, 48); // Đỏ đậm
    
    // Vẽ gradient đơn giản
    for ($i = 0; $i < $height; $i++) {
        $ratio = $i / $height;
        $r = (int)(227 - (227 - 196) * $ratio);
        $g = (int)(24 - (24 - 20) * $ratio);
        $b = (int)(55 - (55 - 48) * $ratio);
        $color = imagecolorallocate($image, $r, $g, $b);
        imageline($image, 0, $i, $width, $i, $color);
    }
    
    // Màu chữ trắng
    $textColor = imagecolorallocate($image, 255, 255, 255);
    
    // Vẽ icon sách
    $iconSize = 80;
    $iconX = ($width - $iconSize) / 2;
    $iconY = ($height - $iconSize) / 2 - 40;
    
    // Vẽ hình chữ nhật đơn giản làm icon sách
    imagefilledrectangle($image, $iconX, $iconY, $iconX + $iconSize, $iconY + $iconSize * 0.7, $textColor);
    imagefilledrectangle($image, $iconX + 5, $iconY + 5, $iconX + $iconSize - 5, $iconY + $iconSize * 0.7 - 5, $bg1);
    
    // Vẽ text title (rút gọn)
    $fontSize = 5;
    $text = mb_substr($title, 0, 20, 'UTF-8');
    if (mb_strlen($title, 'UTF-8') > 20) {
        $text .= '...';
    }
    
    $textWidth = imagefontwidth($fontSize) * mb_strlen($text, 'UTF-8');
    $textX = ($width - $textWidth) / 2;
    $textY = $iconY + $iconSize + 20;
    
    imagestring($image, $fontSize, $textX, $textY, $text, $textColor);
    
    // Lưu ảnh
    imagejpeg($image, $savePath, 85);
    imagedestroy($image);
    
    return file_exists($savePath);
}

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tải ảnh bìa sách</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { padding: 20px; background: #f5f5f5; }
        .container { max-width: 1000px; margin: 0 auto; background: white; padding: 20px; border-radius: 10px; }
        .success { color: green; }
        .error { color: red; }
        .info { color: blue; }
        .book-item { padding: 10px; border-bottom: 1px solid #eee; }
        .book-item:last-child { border-bottom: none; }
        img { max-width: 100px; max-height: 150px; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Tải ảnh bìa sách từ internet</h2>
        
        <?php
        // Lấy danh sách sách
        $stmt = $conn->prepare("SELECT b.*, a.name as author_name FROM books b LEFT JOIN authors a ON b.author_id = a.id ORDER BY b.id");
        $stmt->execute();
        $books = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $success_count = 0;
        $error_count = 0;
        $skip_count = 0;
        
        foreach ($books as $book) {
            $cover_filename = $book['cover'];
            $cover_path = 'uploads/cover/' . $cover_filename;
            
            // Nếu đã có ảnh và file tồn tại, bỏ qua
            if (file_exists($cover_path) && filesize($cover_path) > 1000) {
                echo "<div class='book-item'><span class='info'>⏭️ Bỏ qua:</span> <strong>{$book['title']}</strong> - Đã có ảnh</div>";
                $skip_count++;
                continue;
            }
            
            echo "<div class='book-item'>";
            echo "<strong>{$book['title']}</strong> - {$book['author_name']}<br>";
            
            $imageUrl = null;
            $source = '';
            
            // Thử Google Books API trước
            $imageUrl = getBookCoverFromGoogleBooks($book['title'], $book['author_name'] ?? '');
            if ($imageUrl) {
                $source = 'Google Books';
            }
            
            // Nếu không có, thử Open Library
            if (!$imageUrl) {
                $imageUrl = getBookCoverFromOpenLibrary($book['title'], $book['author_name'] ?? '');
                if ($imageUrl) {
                    $source = 'Open Library';
                }
            }
            
            if ($imageUrl) {
                // Tải ảnh về
                if (downloadImage($imageUrl, $cover_path)) {
                    echo "<span class='success'>✓ Đã tải từ {$source}</span> ";
                    echo "<img src='{$cover_path}' alt='{$book['title']}'><br>";
                    $success_count++;
                } else {
                    // Tạo placeholder
                    if (createPlaceholderImage($book['title'], $cover_path)) {
                        echo "<span class='info'>⚠ Không tìm thấy, đã tạo placeholder</span> ";
                        echo "<img src='{$cover_path}' alt='{$book['title']}'><br>";
                        $success_count++;
                    } else {
                        echo "<span class='error'>✗ Lỗi khi tải và tạo ảnh</span><br>";
                        $error_count++;
                    }
                }
            } else {
                // Tạo placeholder
                if (createPlaceholderImage($book['title'], $cover_path)) {
                    echo "<span class='info'>⚠ Không tìm thấy, đã tạo placeholder</span> ";
                    echo "<img src='{$cover_path}' alt='{$book['title']}'><br>";
                    $success_count++;
                } else {
                    echo "<span class='error'>✗ Không tìm thấy ảnh và không thể tạo placeholder</span><br>";
                    $error_count++;
                }
            }
            
            echo "</div>";
            
            // Nghỉ một chút để tránh rate limit
            usleep(500000); // 0.5 giây
        }
        
        echo "<hr>";
        echo "<div class='alert alert-success'>";
        echo "<strong>Hoàn thành!</strong><br>";
        echo "✓ Thành công: {$success_count} sách<br>";
        echo "⏭️ Bỏ qua: {$skip_count} sách (đã có ảnh)<br>";
        if ($error_count > 0) {
            echo "<span class='error'>✗ Lỗi: {$error_count} sách</span><br>";
        }
        echo "</div>";
        echo "<p><a href='index.php' class='btn btn-primary'>Quay lại trang chủ</a></p>";
        ?>
    </div>
</body>
</html>




