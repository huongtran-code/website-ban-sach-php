<?php

function get_all_coupons($conn) {
    $stmt = $conn->prepare("SELECT * FROM coupons ORDER BY created_at DESC");
    $stmt->execute();
    $coupons = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return count($coupons) > 0 ? $coupons : 0;
}

function get_active_coupon_by_code($conn, $code) {
    try {
        // Kiểm tra xem cột usage_limit có tồn tại không
        $stmt = $conn->query("SHOW COLUMNS FROM coupons LIKE 'usage_limit'");
        $has_usage_limit = $stmt->rowCount() > 0;
        
        if ($has_usage_limit) {
            $stmt = $conn->prepare("SELECT * FROM coupons WHERE code = ? AND is_active = 1 AND (expires_at IS NULL OR expires_at > NOW())");
        } else {
            // Nếu chưa có cột usage_limit, chỉ select các cột cơ bản
            $stmt = $conn->prepare("SELECT id, code, description, discount_percent, apply_type, apply_to_ids, is_active, expires_at, created_at FROM coupons WHERE code = ? AND is_active = 1 AND (expires_at IS NULL OR expires_at > NOW())");
        }
        $stmt->execute([$code]);
        $coupon = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Nếu chưa có cột usage_limit, set giá trị mặc định
        if ($coupon && !isset($coupon['usage_limit'])) {
            $coupon['usage_limit'] = null;
            $coupon['usage_count'] = 0;
        }
        
        return $coupon;
    } catch (PDOException $e) {
        return false;
    }
}

function add_coupon($conn, $code, $description, $discount_percent, $apply_type = 'all', $apply_to_ids = null, $expires_at = null, $usage_limit = null, $max_usage_per_user = null, $discount_type = 'percent') {
    // Chuyển đổi apply_to_ids thành JSON nếu là array
    if (is_array($apply_to_ids)) {
        $apply_to_ids = json_encode($apply_to_ids);
    }
    
    // Chuyển đổi usage_limit: empty string hoặc null = NULL (vô tận)
    if ($usage_limit === '' || $usage_limit === null) {
        $usage_limit = null;
    } else {
        $usage_limit = (int)$usage_limit;
    }
    
    // Chuyển đổi max_usage_per_user: empty string hoặc null = NULL (vô tận)
    if ($max_usage_per_user === '' || $max_usage_per_user === null) {
        $max_usage_per_user = null;
    } else {
        $max_usage_per_user = (int)$max_usage_per_user;
    }
    
    $stmt = $conn->prepare("INSERT INTO coupons (code, description, discount_percent, apply_type, apply_to_ids, expires_at, usage_limit, max_usage_per_user, discount_type) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    return $stmt->execute([$code, $description, $discount_percent, $apply_type, $apply_to_ids, $expires_at, $usage_limit, $max_usage_per_user, $discount_type]);
}

function get_user_coupon_usage($conn, $coupon_id, $user_id) {
    try {
        $stmt = $conn->prepare("SELECT usage_count FROM coupon_usage WHERE coupon_id = ? AND user_id = ?");
        $stmt->execute([$coupon_id, $user_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? (int)$result['usage_count'] : 0;
    } catch (PDOException $e) {
        // Nếu bảng chưa tồn tại, trả về 0
        return 0;
    }
}

function increment_coupon_usage($conn, $coupon_id, $user_id) {
    // Tăng tổng số lượt sử dụng của coupon
    $stmt = $conn->prepare("UPDATE coupons SET usage_count = usage_count + 1 WHERE id = ?");
    $stmt->execute([$coupon_id]);
    
    // Tăng số lượt sử dụng của user cho coupon này
    $stmt = $conn->prepare("INSERT INTO coupon_usage (coupon_id, user_id, usage_count) 
                           VALUES (?, ?, 1) 
                           ON DUPLICATE KEY UPDATE usage_count = usage_count + 1");
    $stmt->execute([$coupon_id, $user_id]);
}

function get_coupon_by_id($conn, $id) {
    $stmt = $conn->prepare("SELECT * FROM coupons WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function update_coupon($conn, $id, $code, $description, $discount_percent, $apply_type = 'all', $apply_to_ids = null, $expires_at = null, $usage_limit = null, $max_usage_per_user = null) {
    // Chuyển đổi apply_to_ids thành JSON nếu là array
    if (is_array($apply_to_ids)) {
        $apply_to_ids = json_encode($apply_to_ids);
    }
    
    // Chuyển đổi usage_limit: empty string hoặc null = NULL (vô tận)
    if ($usage_limit === '' || $usage_limit === null) {
        $usage_limit = null;
    } else {
        $usage_limit = (int)$usage_limit;
    }
    
    // Chuyển đổi max_usage_per_user: empty string hoặc null = NULL (vô tận)
    if ($max_usage_per_user === '' || $max_usage_per_user === null) {
        $max_usage_per_user = null;
    } else {
        $max_usage_per_user = (int)$max_usage_per_user;
    }
    
    $stmt = $conn->prepare("UPDATE coupons SET code = ?, description = ?, discount_percent = ?, apply_type = ?, apply_to_ids = ?, expires_at = ?, usage_limit = ?, max_usage_per_user = ? WHERE id = ?");
    return $stmt->execute([$code, $description, $discount_percent, $apply_type, $apply_to_ids, $expires_at, $usage_limit, $max_usage_per_user, $id]);
}

function update_coupon_status($conn, $id, $is_active) {
    $stmt = $conn->prepare("UPDATE coupons SET is_active = ? WHERE id = ?");
    return $stmt->execute([$is_active, $id]);
}

function delete_coupon($conn, $id) {
    $stmt = $conn->prepare("DELETE FROM coupons WHERE id = ?");
    return $stmt->execute([$id]);
}

function is_coupon_applicable($coupon, $cart_items) {
    $apply_type = $coupon['apply_type'] ?? ($coupon['apply_to_promotion_only'] ? 'promotion' : 'all');
    
    // Nếu áp dụng cho tất cả sách
    if ($apply_type === 'all') {
        return ['applicable' => true, 'message' => ''];
    }
    
    // Nếu áp dụng cho sách khuyến mãi
    if ($apply_type === 'promotion') {
        $has_promo_book = false;
        foreach ($cart_items as $item) {
            if ((isset($item['discount_percent']) && $item['discount_percent'] > 0) || 
                (isset($item['is_promotion']) && $item['is_promotion'] == 1)) {
                $has_promo_book = true;
                break;
            }
        }
        if (!$has_promo_book) {
            return ['applicable' => false, 'message' => 'Mã ' . $coupon['code'] . ' chỉ áp dụng cho sách trong mục Khuyến mãi'];
        }
        return ['applicable' => true, 'message' => ''];
    }
    
    // Nếu áp dụng cho danh mục hoặc sách cụ thể
    if ($apply_type === 'category' || $apply_type === 'book') {
        if (empty($coupon['apply_to_ids'])) {
            return ['applicable' => false, 'message' => 'Mã khuyến mãi chưa được cấu hình đúng'];
        }
        
        $apply_ids = json_decode($coupon['apply_to_ids'], true);
        if (!is_array($apply_ids) || empty($apply_ids)) {
            return ['applicable' => false, 'message' => 'Mã khuyến mãi chưa được cấu hình đúng'];
        }
        
        $has_applicable_item = false;
        foreach ($cart_items as $item) {
            if ($apply_type === 'category') {
                // Kiểm tra category_id
                if (in_array($item['category_id'], $apply_ids)) {
                    $has_applicable_item = true;
                    break;
                }
            } else if ($apply_type === 'book') {
                // Kiểm tra book_id
                if (in_array($item['book_id'], $apply_ids)) {
                    $has_applicable_item = true;
                    break;
                }
            }
        }
        
        if (!$has_applicable_item) {
            $type_text = $apply_type === 'category' ? 'danh mục' : 'sách';
            return ['applicable' => false, 'message' => 'Mã ' . $coupon['code'] . ' chỉ áp dụng cho ' . $type_text . ' đã chọn'];
        }
        
        return ['applicable' => true, 'message' => ''];
    }
    
    return ['applicable' => false, 'message' => 'Loại áp dụng không hợp lệ'];
}


