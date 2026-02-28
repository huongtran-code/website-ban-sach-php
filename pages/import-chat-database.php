<?php
require_once __DIR__ . "/../config/bootstrap.php";

$sql_file = "database_chat_update.sql";
$sql_content = file_get_contents($sql_file);

// Tách các câu lệnh SQL
$statements = array_filter(array_map('trim', explode(';', $sql_content)));

foreach ($statements as $stmt) {
    if (!empty($stmt) && stripos($stmt, 'USE ') === false) {
        try {
            $conn->exec($stmt);
            echo "✓ Executed: " . substr($stmt, 0, 60) . "...\n";
        } catch (PDOException $e) {
            // Bỏ qua lỗi duplicate column/table
            if (strpos($e->getMessage(), 'Duplicate column') === false && 
                strpos($e->getMessage(), 'already exists') === false &&
                strpos($e->getMessage(), 'Duplicate key') === false) {
                echo "✗ Error: " . $e->getMessage() . "\n";
            }
        }
    }
}

echo "\n✓ Import completed!\n";
?>




