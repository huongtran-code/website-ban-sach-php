<?php

function get_setting($conn, $key, $default = null) {
    $stmt = $conn->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
    $stmt->execute([$key]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result ? $result['setting_value'] : $default;
}

function update_setting($conn, $key, $value, $description = null) {
    $stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value, description) 
                           VALUES (?, ?, ?) 
                           ON DUPLICATE KEY UPDATE setting_value = ?, description = ?");
    return $stmt->execute([$key, $value, $description, $value, $description]);
}

function get_all_settings($conn) {
    $stmt = $conn->prepare("SELECT * FROM settings ORDER BY setting_key");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


