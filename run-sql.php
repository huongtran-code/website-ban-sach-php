<?php
// File để chạy SQL updates
include "db_conn.php";

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chạy SQL Updates</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 10px; }
        .success { color: green; }
        .warning { color: orange; }
        .error { color: red; }
        h2 { color: #333; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Đang chạy SQL updates...</h2>

        <?php
        try {
            // Đọc và chạy database_download_update.sql
            $sql_file = "database_download_update.sql";
            if (file_exists($sql_file)) {
                echo "<h3>Đang chạy: $sql_file</h3>";
                $sql_content = file_get_contents($sql_file);
                
                // Loại bỏ USE statement và comments
                $sql_content = preg_replace('/USE\s+\w+;/i', '', $sql_content);
                $sql_content = preg_replace('/--.*$/m', '', $sql_content);
                
                // Tách các câu lệnh SQL
                $statements = array_filter(
                    array_map('trim', explode(';', $sql_content)),
                    function($stmt) {
                        return !empty(trim($stmt));
                    }
                );
                
                foreach ($statements as $statement) {
                    $statement = trim($statement);
                    if (!empty($statement)) {
                        try {
                            $conn->exec($statement);
                            echo "<p class='success'>✓ Thành công: " . htmlspecialchars(substr($statement, 0, 80)) . "...</p>";
                        } catch (PDOException $e) {
                            $error_msg = $e->getMessage();
                            // Bỏ qua lỗi nếu column/table đã tồn tại
                            if (strpos($error_msg, 'Duplicate column') !== false || 
                                strpos($error_msg, 'already exists') !== false ||
                                strpos($error_msg, 'Duplicate key') !== false) {
                                echo "<p class='warning'>⚠ Đã tồn tại (bỏ qua): " . htmlspecialchars(substr($statement, 0, 60)) . "...</p>";
                            } else {
                                echo "<p class='error'>✗ Lỗi: " . htmlspecialchars($error_msg) . "</p>";
                                echo "<p class='error'>Câu lệnh: " . htmlspecialchars(substr($statement, 0, 100)) . "...</p>";
                            }
                        }
                    }
                }
            }
            
            // Chạy sample_data.sql
            $sql_file2 = "sample_data.sql";
            if (file_exists($sql_file2)) {
                echo "<br><h3>Đang chạy: $sql_file2</h3>";
                $sql_content2 = file_get_contents($sql_file2);
                
                // Loại bỏ USE statement và comments
                $sql_content2 = preg_replace('/USE\s+\w+;/i', '', $sql_content2);
                $sql_content2 = preg_replace('/--.*$/m', '', $sql_content2);
                
                // Tách các câu lệnh SQL
                $statements2 = array_filter(
                    array_map('trim', explode(';', $sql_content2)),
                    function($stmt) {
                        return !empty(trim($stmt));
                    }
                );
                
                foreach ($statements2 as $statement) {
                    $statement = trim($statement);
                    if (!empty($statement)) {
                        try {
                            $conn->exec($statement);
                            echo "<p class='success'>✓ Thành công: " . htmlspecialchars(substr($statement, 0, 80)) . "...</p>";
                        } catch (PDOException $e) {
                            $error_msg = $e->getMessage();
                            // Bỏ qua lỗi duplicate
                            if (strpos($error_msg, 'Duplicate entry') !== false ||
                                strpos($error_msg, 'Duplicate key') !== false) {
                                echo "<p class='warning'>⚠ Dữ liệu đã tồn tại (bỏ qua): " . htmlspecialchars(substr($statement, 0, 60)) . "...</p>";
                            } else {
                                echo "<p class='error'>✗ Lỗi: " . htmlspecialchars($error_msg) . "</p>";
                            }
                        }
                    }
                }
            }
            
            echo "<br><h3 class='success'>✓ Hoàn thành!</h3>";
            echo "<p><a href='admin.php'>Về trang Admin</a> | <a href='index.php'>Về trang chủ</a></p>";
            
        } catch (Exception $e) {
            echo "<p class='error'>Lỗi: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
        ?>
    </div>
</body>
</html>


