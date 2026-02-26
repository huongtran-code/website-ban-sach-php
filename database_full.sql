-- =====================================================
-- ONLINE BOOK STORE - DATABASE DUY NHẤT
-- File này chứa: cấu trúc bảng (PK, FK), dữ liệu mẫu, cấu hình
-- Chạy file này để thiết lập database từ đầu (không cần file .sql khác)
-- =====================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+07:00";
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- Tạo database
CREATE DATABASE IF NOT EXISTS `online_book_store_db`
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `online_book_store_db`;

-- =====================================================
-- 1. BẢNG ADMIN
-- =====================================================
DROP TABLE IF EXISTS `admin`;
CREATE TABLE `admin` (
  `id` int NOT NULL AUTO_INCREMENT,
  `full_name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tài khoản admin mặc định: admin@admin.com / admin123
INSERT INTO `admin` (`id`, `full_name`, `email`, `password`) VALUES
(1, 'Admin', 'admin@admin.com', '$2y$12$Rw75E2E765Derhpcn2z1puTndPoDsfkRUVZz.j/MiI/TTfCpy2yIa');

-- =====================================================
-- 2. BẢNG USERS (Khách hàng)
-- =====================================================
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `full_name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` text NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text,
  `balance` decimal(15,2) DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `reset_token` varchar(64) DEFAULT NULL,
  `reset_token_expires` datetime DEFAULT NULL,
  `total_spent` decimal(12,2) DEFAULT '0.00' COMMENT 'Tổng tiền đã mua',
  `membership_level` varchar(20) DEFAULT 'normal' COMMENT 'Hạng thành viên: normal, silver, gold, diamond',
  `is_banned` tinyint(1) NOT NULL DEFAULT '0',
  `ban_reason` varchar(255) DEFAULT NULL,
  `banned_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 3. BẢNG AUTHORS (Tác giả)
-- =====================================================
DROP TABLE IF EXISTS `authors`;
CREATE TABLE `authors` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 4. BẢNG CATEGORIES (Danh mục)
-- =====================================================
DROP TABLE IF EXISTS `categories`;
CREATE TABLE `categories` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 5. BẢNG BOOKS (Sách)
-- =====================================================
DROP TABLE IF EXISTS `books`;
CREATE TABLE `books` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `author_id` int NOT NULL,
  `description` text NOT NULL,
  `category_id` int NOT NULL,
  `cover` varchar(255) NOT NULL,
  `file` varchar(255) NOT NULL,
  `stock` int DEFAULT '10',
  `price` decimal(10,2) DEFAULT '50000.00',
  `is_new` tinyint DEFAULT '1',
  `is_bestseller` tinyint DEFAULT '0',
  `is_promotion` tinyint DEFAULT '0',
  `discount_percent` int DEFAULT '0',
  `view_count` int DEFAULT '0',
  `review_count` int DEFAULT '0',
  `average_rating` decimal(3,2) DEFAULT '0.00',
  `return_days` int DEFAULT '7',
  `is_rentable` tinyint(1) DEFAULT '0' COMMENT 'Có cho thuê không',
  `rental_price` decimal(10,2) DEFAULT '0.00' COMMENT 'Giá thuê mặc định',
  `rental_duration` int DEFAULT '7' COMMENT 'Số ngày thuê mặc định',
  `summary` text COMMENT 'Tóm tắt nội dung chi tiết',
  `table_of_contents` text COMMENT 'Mục lục sách',
  `publisher` varchar(255) DEFAULT NULL COMMENT 'Nhà xuất bản',
  `publication_year` int DEFAULT NULL COMMENT 'Năm xuất bản',
  `pages` int DEFAULT NULL COMMENT 'Số trang',
  `isbn` varchar(20) DEFAULT NULL COMMENT 'Mã ISBN',
  `language` varchar(50) DEFAULT 'Tiếng Việt' COMMENT 'Ngôn ngữ',
  `format` enum('hardcopy','ebook','both') DEFAULT 'both' COMMENT 'Định dạng sách',
  `sample_content` text COMMENT 'Nội dung mẫu (trích đoạn)',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 6. BẢNG CART (Giỏ hàng)
-- =====================================================
DROP TABLE IF EXISTS `cart`;
CREATE TABLE `cart` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `book_id` int NOT NULL,
  `quantity` int DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `promotion_id` int DEFAULT NULL COMMENT 'ID chương trình khuyến mãi khi thêm vào giỏ',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 7. BẢNG WISHLIST (Yêu thích)
-- =====================================================
DROP TABLE IF EXISTS `wishlist`;
CREATE TABLE `wishlist` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `book_id` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 8. BẢNG ORDERS (Đơn hàng)
-- =====================================================
DROP TABLE IF EXISTS `orders`;
CREATE TABLE `orders` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `status` varchar(20) DEFAULT 'pending',
  `payment_method` enum('balance','cod','online') NOT NULL DEFAULT 'balance',
  `payment_channel` varchar(50) DEFAULT 'balance' COMMENT 'Kênh thanh toán (balance, cod, momo_demo, zalopay_demo, card_demo)',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 9. BẢNG ORDER_ITEMS (Chi tiết đơn hàng)
-- =====================================================
DROP TABLE IF EXISTS `order_items`;
CREATE TABLE `order_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `order_id` int NOT NULL,
  `book_id` int NOT NULL,
  `quantity` int NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `book_type` varchar(20) DEFAULT 'hardcopy',
  `shipping_address` text,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 10. BẢNG TRANSACTIONS (Giao dịch tài chính)
-- =====================================================
DROP TABLE IF EXISTS `transactions`;
CREATE TABLE `transactions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `type` varchar(20) NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `description` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 11. BẢNG REVIEWS (Đánh giá sách)
-- =====================================================
DROP TABLE IF EXISTS `reviews`;
CREATE TABLE `reviews` (
  `id` int NOT NULL AUTO_INCREMENT,
  `book_id` int NOT NULL,
  `user_id` int NOT NULL,
  `rating` int NOT NULL DEFAULT '5',
  `comment` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 12. BẢNG RENTALS (Thuê sách)
-- =====================================================
DROP TABLE IF EXISTS `rentals`;
CREATE TABLE `rentals` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `book_id` int NOT NULL,
  `price` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT 'Giá thuê đã thu',
  `start_date` datetime NOT NULL COMMENT 'Thời gian bắt đầu thuê',
  `end_date` datetime NOT NULL COMMENT 'Thời gian kết thúc thuê',
  `status` enum('active','expired','returned','cancelled') NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `auto_extend` tinyint(1) DEFAULT '0' COMMENT 'Tự động gia hạn',
  `late_count` int DEFAULT '0' COMMENT 'Số lần trễ hạn',
  `returned_at` datetime DEFAULT NULL COMMENT 'Ngày trả sách',
  `extend_count` int DEFAULT '0' COMMENT 'Số lần gia hạn',
  PRIMARY KEY (`id`),
  KEY `idx_rentals_user` (`user_id`),
  KEY `idx_rentals_book` (`book_id`),
  KEY `idx_rentals_status` (`status`),
  CONSTRAINT `fk_rentals_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_rentals_book` FOREIGN KEY (`book_id`) REFERENCES `books` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 13. BẢNG COUPONS (Mã giảm giá)
-- =====================================================
DROP TABLE IF EXISTS `coupons`;
CREATE TABLE `coupons` (
  `id` int NOT NULL AUTO_INCREMENT,
  `code` varchar(50) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `discount_percent` int NOT NULL,
  `discount_type` enum('percent','freeship') DEFAULT 'percent' COMMENT 'Loại giảm giá',
  `apply_to_promotion_only` tinyint DEFAULT '1',
  `apply_type` varchar(20) DEFAULT 'all' COMMENT 'all, category, book, promotion',
  `apply_to_ids` text COMMENT 'JSON array of category_ids or book_ids',
  `is_active` tinyint DEFAULT '1',
  `usage_limit` int DEFAULT NULL COMMENT 'Số lượt sử dụng tối đa, NULL = vô tận',
  `usage_count` int DEFAULT '0' COMMENT 'Tổng số lượt đã sử dụng',
  `max_usage_per_user` int DEFAULT NULL COMMENT 'Số lần tối đa mỗi user, NULL = vô tận',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `expires_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 14. BẢNG COUPON_USAGE (Theo dõi sử dụng mã)
-- =====================================================
DROP TABLE IF EXISTS `coupon_usage`;
CREATE TABLE `coupon_usage` (
  `id` int NOT NULL AUTO_INCREMENT,
  `coupon_id` int NOT NULL,
  `user_id` int NOT NULL,
  `usage_count` int DEFAULT '0' COMMENT 'Số lần user này đã dùng mã này',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_coupon_user` (`coupon_id`, `user_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `coupon_usage_ibfk_1` FOREIGN KEY (`coupon_id`) REFERENCES `coupons` (`id`) ON DELETE CASCADE,
  CONSTRAINT `coupon_usage_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 15. BẢNG PROMOTIONS (Chương trình khuyến mãi)
-- =====================================================
DROP TABLE IF EXISTS `promotions`;
CREATE TABLE `promotions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL COMMENT 'Tên chương trình',
  `description` text COMMENT 'Mô tả chương trình',
  `discount_percent` int NOT NULL DEFAULT '0' COMMENT '% giảm giá',
  `start_date` datetime NOT NULL COMMENT 'Ngày bắt đầu',
  `end_date` datetime NOT NULL COMMENT 'Ngày kết thúc',
  `is_active` tinyint(1) DEFAULT '1' COMMENT 'Đang hoạt động',
  `banner_image` varchar(255) DEFAULT NULL COMMENT 'Ảnh banner',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 16. BẢNG PROMOTION_BOOKS (Sách trong khuyến mãi)
-- =====================================================
DROP TABLE IF EXISTS `promotion_books`;
CREATE TABLE `promotion_books` (
  `id` int NOT NULL AUTO_INCREMENT,
  `promotion_id` int NOT NULL,
  `book_id` int NOT NULL,
  `custom_discount_percent` int DEFAULT NULL COMMENT 'Giảm giá riêng cho sách này',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_promo_book` (`promotion_id`, `book_id`),
  KEY `book_id` (`book_id`),
  CONSTRAINT `promotion_books_ibfk_1` FOREIGN KEY (`promotion_id`) REFERENCES `promotions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `promotion_books_ibfk_2` FOREIGN KEY (`book_id`) REFERENCES `books` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 17. BẢNG CHAT_MESSAGES (Tin nhắn chat)
-- =====================================================
DROP TABLE IF EXISTS `chat_messages`;
CREATE TABLE `chat_messages` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL COMMENT 'ID của user (NULL nếu là admin)',
  `admin_id` int DEFAULT NULL COMMENT 'ID của admin (NULL nếu là user)',
  `message` text NOT NULL,
  `is_admin` tinyint(1) DEFAULT '0' COMMENT '1 = tin nhắn từ admin, 0 = từ user',
  `is_read` tinyint(1) DEFAULT '0' COMMENT 'Đã đọc chưa',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `message_type` varchar(20) DEFAULT 'text' COMMENT 'text hoặc image',
  `image_url` varchar(255) DEFAULT NULL COMMENT 'Đường dẫn ảnh nếu message_type = image',
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_admin_id` (`admin_id`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `chat_messages_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `chat_messages_ibfk_2` FOREIGN KEY (`admin_id`) REFERENCES `admin` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 18. BẢNG CHAT_SESSIONS (Phiên chat)
-- =====================================================
DROP TABLE IF EXISTS `chat_sessions`;
CREATE TABLE `chat_sessions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `status` enum('active','closed') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 19. BẢNG DOWNLOAD_HISTORY (Lịch sử tải sách)
-- =====================================================
DROP TABLE IF EXISTS `download_history`;
CREATE TABLE `download_history` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `book_id` int NOT NULL,
  `order_id` int NOT NULL,
  `download_count` int DEFAULT '0',
  `max_downloads` int DEFAULT '3',
  `last_download_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `book_id` (`book_id`),
  KEY `order_id` (`order_id`),
  CONSTRAINT `download_history_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `download_history_ibfk_2` FOREIGN KEY (`book_id`) REFERENCES `books` (`id`) ON DELETE CASCADE,
  CONSTRAINT `download_history_ibfk_3` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 20. BẢNG SETTINGS (Cấu hình hệ thống)
-- =====================================================
DROP TABLE IF EXISTS `settings`;
CREATE TABLE `settings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL COMMENT 'Tên cấu hình',
  `setting_value` text COMMENT 'Giá trị cấu hình',
  `description` varchar(255) DEFAULT NULL COMMENT 'Mô tả',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =====================================================
-- DỮ LIỆU MẪU
-- =====================================================

-- Danh mục sách
INSERT INTO `categories` (`name`) VALUES
('Văn học Việt Nam'),
('Văn học nước ngoài'),
('Tiểu thuyết'),
('Tâm lý - Kỹ năng sống'),
('Kinh tế'),
('Thiếu nhi'),
('Manga - Comic'),
('Khoa học'),
('Lịch sử'),
('Sách giáo khoa');

-- Tác giả
INSERT INTO `authors` (`name`) VALUES
('Nguyễn Nhật Ánh'),
('Paulo Coelho'),
('Dale Carnegie'),
('Nguyễn Ngọc Tư'),
('Haruki Murakami'),
('Robert Kiyosaki'),
('J.K. Rowling'),
('Gosho Aoyama'),
('Yuval Noah Harari'),
('Ngô Bảo Châu');

-- Sách mẫu: Văn học Việt Nam (category_id = 1)
INSERT INTO `books` (`title`, `author_id`, `description`, `category_id`, `cover`, `file`, `price`, `stock`, `is_new`, `is_bestseller`, `is_promotion`, `discount_percent`) VALUES
('Tôi thấy hoa vàng trên cỏ xanh', 1, 'Câu chuyện về tuổi thơ ở miền quê Việt Nam, với những kỷ niệm đẹp và cảm động.', 1, 'toi_thay_hoa_vang.jpg', 'toi_thay_hoa_vang.pdf', 85000, 50, 1, 1, 0, 0),
('Cho tôi xin một vé đi tuổi thơ', 1, 'Hành trình trở về tuổi thơ qua những trang sách đầy cảm xúc.', 1, 'cho_toi_xin_ve.jpg', 'cho_toi_xin_ve.pdf', 90000, 45, 1, 1, 1, 10),
('Mắt biếc', 1, 'Câu chuyện tình yêu đầy cảm động và lãng mạn.', 1, 'mat_biec.jpg', 'mat_biec.pdf', 80000, 40, 0, 1, 0, 0),
('Cô gái đến từ hôm qua', 1, 'Truyện ngắn về tình yêu và cuộc sống.', 1, 'co_gai_hom_qua.jpg', 'co_gai_hom_qua.pdf', 75000, 35, 0, 0, 0, 0),
('Ngồi khóc trên cây', 1, 'Câu chuyện về tình bạn và tình yêu tuổi học trò.', 1, 'ngoi_khoc.jpg', 'ngoi_khoc.pdf', 82000, 42, 1, 0, 1, 15),
('Kính vạn hoa', 1, 'Bộ truyện về những câu chuyện vui nhộn của học sinh.', 1, 'kinh_van_hoa.jpg', 'kinh_van_hoa.pdf', 70000, 38, 0, 0, 0, 0),
('Bảy bước tới mùa hè', 1, 'Hành trình của những đứa trẻ trong mùa hè đầy kỷ niệm.', 1, 'bay_buoc.jpg', 'bay_buoc.pdf', 88000, 48, 1, 1, 0, 0),
('Con chó nhỏ mang giỏ hoa hồng', 1, 'Câu chuyện cảm động về tình bạn giữa con người và động vật.', 1, 'con_cho.jpg', 'con_cho.pdf', 76000, 33, 0, 0, 1, 12),
('Lá nằm trong lá', 1, 'Truyện ngắn về cuộc sống và những điều bình dị.', 1, 'la_nam_trong_la.jpg', 'la_nam_trong_la.pdf', 79000, 36, 0, 0, 0, 0),
('Đảo mộng mơ', 1, 'Câu chuyện về những giấc mơ và khát vọng tuổi trẻ.', 1, 'dao_mong_mo.jpg', 'dao_mong_mo.pdf', 83000, 44, 1, 0, 0, 0);

-- Sách mẫu: Văn học nước ngoài (category_id = 2)
INSERT INTO `books` (`title`, `author_id`, `description`, `category_id`, `cover`, `file`, `price`, `stock`, `is_new`, `is_bestseller`, `is_promotion`, `discount_percent`) VALUES
('Nhà giả kim', 2, 'Hành trình tìm kiếm kho báu và ý nghĩa cuộc sống.', 2, 'nha_gia_kim.jpg', 'nha_gia_kim.pdf', 95000, 60, 1, 1, 0, 0),
('Veronika quyết chết', 2, 'Câu chuyện về cuộc sống và cái chết đầy triết lý.', 2, 'veronika.jpg', 'veronika.pdf', 88000, 55, 1, 1, 1, 10),
('O Alquimista', 2, 'Bản tiếng Bồ Đào Nha của Nhà giả kim.', 2, 'alquimista.jpg', 'alquimista.pdf', 92000, 50, 0, 0, 0, 0),
('Brida', 2, 'Câu chuyện về một phụ nữ tìm kiếm ý nghĩa cuộc sống.', 2, 'brida.jpg', 'brida.pdf', 87000, 48, 0, 0, 0, 0),
('Quỷ dữ và cô bé Prym', 2, 'Tiểu thuyết về thiện và ác trong con người.', 2, 'quy_du.jpg', 'quy_du.pdf', 90000, 52, 1, 0, 1, 15),
('Năm phút', 2, 'Tập truyện ngắn về những khoảnh khắc ý nghĩa.', 2, 'nam_phut.jpg', 'nam_phut.pdf', 85000, 45, 0, 0, 0, 0),
('Những kẻ mộng mơ', 2, 'Câu chuyện về những người theo đuổi giấc mơ.', 2, 'ke_mong_mo.jpg', 'ke_mong_mo.pdf', 93000, 58, 1, 1, 0, 0),
('Hippie', 2, 'Hành trình của một thế hệ tìm kiếm tự do.', 2, 'hippie.jpg', 'hippie.pdf', 89000, 50, 0, 0, 1, 12),
('Aleph', 2, 'Hành trình tâm linh qua không gian và thời gian.', 2, 'aleph.jpg', 'aleph.pdf', 91000, 54, 1, 0, 0, 0),
('Adultery', 2, 'Câu chuyện về sự phản bội và tìm lại chính mình.', 2, 'adultery.jpg', 'adultery.pdf', 86000, 47, 0, 0, 0, 0);

-- Sách mẫu: Tiểu thuyết (category_id = 3)
INSERT INTO `books` (`title`, `author_id`, `description`, `category_id`, `cover`, `file`, `price`, `stock`, `is_new`, `is_bestseller`, `is_promotion`, `discount_percent`) VALUES
('Rừng Na Uy', 5, 'Tiểu thuyết về tuổi trẻ, tình yêu và mất mát.', 3, 'rung_na_uy.jpg', 'rung_na_uy.pdf', 120000, 70, 1, 1, 0, 0),
('Kafka bên bờ biển', 5, 'Câu chuyện kỳ lạ về một cậu bé và những điều bí ẩn.', 3, 'kafka.jpg', 'kafka.pdf', 115000, 65, 1, 1, 1, 10),
('1Q84', 5, 'Tiểu thuyết khoa học viễn tưởng đầy hấp dẫn.', 3, '1q84.jpg', '1q84.pdf', 130000, 75, 1, 1, 0, 0),
('Biên niên ký chim vặn dây cót', 5, 'Câu chuyện về những điều kỳ lạ và bí ẩn.', 3, 'chim_van_day.jpg', 'chim_van_day.pdf', 110000, 60, 0, 0, 0, 0),
('Phía nam biên giới, phía tây mặt trời', 5, 'Tiểu thuyết về tình yêu và ký ức.', 3, 'phia_nam.jpg', 'phia_nam.pdf', 105000, 58, 0, 0, 1, 15),
('Sputnik Sweetheart', 5, 'Câu chuyện về tình yêu và sự cô đơn.', 3, 'sputnik.jpg', 'sputnik.pdf', 108000, 62, 1, 0, 0, 0),
('Nhảy múa, nhảy múa, nhảy múa', 5, 'Tiểu thuyết về cuộc sống đô thị hiện đại.', 3, 'nhay_mua.jpg', 'nhay_mua.pdf', 112000, 68, 0, 1, 0, 0),
('Người tình Sputnik', 5, 'Câu chuyện về những mối quan hệ phức tạp.', 3, 'nguoi_tinh.jpg', 'nguoi_tinh.pdf', 107000, 61, 0, 0, 1, 12),
('Sau nửa đêm', 5, 'Tiểu thuyết về những điều kỳ lạ xảy ra sau nửa đêm.', 3, 'sau_nua_dem.jpg', 'sau_nua_dem.pdf', 109000, 64, 1, 0, 0, 0),
('Lắng nghe gió hát', 5, 'Tiểu thuyết đầu tay của Haruki Murakami.', 3, 'lang_nghe_gio.jpg', 'lang_nghe_gio.pdf', 103000, 56, 0, 0, 0, 0);

-- Sách mẫu: Tâm lý - Kỹ năng sống (category_id = 4)
INSERT INTO `books` (`title`, `author_id`, `description`, `category_id`, `cover`, `file`, `price`, `stock`, `is_new`, `is_bestseller`, `is_promotion`, `discount_percent`) VALUES
('Đắc nhân tâm', 3, 'Nghệ thuật thu phục lòng người và thành công trong cuộc sống.', 4, 'dac_nhan_tam.jpg', 'dac_nhan_tam.pdf', 100000, 80, 1, 1, 0, 0),
('Quẳng gánh lo đi và vui sống', 3, 'Cách vượt qua lo âu và sống hạnh phúc hơn.', 4, 'quang_ganh_lo.jpg', 'quang_ganh_lo.pdf', 95000, 75, 1, 1, 1, 10),
('Nghệ thuật nói trước công chúng', 3, 'Kỹ năng thuyết trình và giao tiếp hiệu quả.', 4, 'nghe_thuat_noi.jpg', 'nghe_thuat_noi.pdf', 90000, 70, 0, 0, 0, 0),
('Làm chủ tư duy thay đổi vận mệnh', 3, 'Cách suy nghĩ tích cực để thay đổi cuộc sống.', 4, 'lam_chu_tu_duy.jpg', 'lam_chu_tu_duy.pdf', 92000, 72, 1, 0, 0, 0),
('Bí quyết thành công', 3, 'Những nguyên tắc vàng để đạt được thành công.', 4, 'bi_quyet_thanh_cong.jpg', 'bi_quyet_thanh_cong.pdf', 88000, 68, 0, 0, 1, 15),
('Nghệ thuật lãnh đạo', 3, 'Kỹ năng lãnh đạo và quản lý hiệu quả.', 4, 'nghe_thuat_lanh_dao.jpg', 'nghe_thuat_lanh_dao.pdf', 93000, 73, 1, 0, 0, 0),
('Cách sống hạnh phúc', 3, 'Bí quyết để có cuộc sống hạnh phúc và ý nghĩa.', 4, 'cach_song_happy.jpg', 'cach_song_happy.pdf', 87000, 67, 0, 0, 0, 0),
('Nghệ thuật giao tiếp', 3, 'Kỹ năng giao tiếp và xây dựng mối quan hệ.', 4, 'nghe_thuat_giao_tiep.jpg', 'nghe_thuat_giao_tiep.pdf', 91000, 71, 0, 1, 0, 0),
('Tự tin và thành công', 3, 'Xây dựng sự tự tin để đạt được thành công.', 4, 'tu_tin_thanh_cong.jpg', 'tu_tin_thanh_cong.pdf', 89000, 69, 1, 0, 1, 12),
('Nghệ thuật thuyết phục', 3, 'Cách thuyết phục người khác một cách hiệu quả.', 4, 'nghe_thuat_thuyet_phuc.jpg', 'nghe_thuat_thuyet_phuc.pdf', 94000, 74, 0, 0, 0, 0);

-- Sách mẫu: Kinh tế (category_id = 5)
INSERT INTO `books` (`title`, `author_id`, `description`, `category_id`, `cover`, `file`, `price`, `stock`, `is_new`, `is_bestseller`, `is_promotion`, `discount_percent`) VALUES
('Cha giàu cha nghèo', 6, 'Bài học về tài chính và đầu tư từ hai người cha.', 5, 'cha_giau.jpg', 'cha_giau.pdf', 110000, 85, 1, 1, 0, 0),
('Dạy con làm giàu', 6, 'Hướng dẫn về tài chính và đầu tư cho thế hệ trẻ.', 5, 'day_con_lam_giau.jpg', 'day_con_lam_giau.pdf', 105000, 80, 1, 1, 1, 10),
('Nhà đầu tư thông minh', 6, 'Chiến lược đầu tư thông minh và hiệu quả.', 5, 'nha_dau_tu.jpg', 'nha_dau_tu.pdf', 100000, 75, 0, 0, 0, 0),
('Tại sao người giàu ngày càng giàu', 6, 'Bí mật của những người giàu có.', 5, 'tai_sao_giau.jpg', 'tai_sao_giau.pdf', 108000, 82, 1, 0, 0, 0),
('Cách kiếm tiền của người giàu', 6, 'Phương pháp kiếm tiền và quản lý tài chính.', 5, 'cach_kiem_tien.jpg', 'cach_kiem_tien.pdf', 102000, 77, 0, 0, 1, 15),
('Đầu tư bất động sản', 6, 'Hướng dẫn đầu tư bất động sản hiệu quả.', 5, 'dau_tu_bds.jpg', 'dau_tu_bds.pdf', 107000, 81, 1, 0, 0, 0),
('Tự do tài chính', 6, 'Con đường dẫn đến tự do tài chính.', 5, 'tu_do_tai_chinh.jpg', 'tu_do_tai_chinh.pdf', 104000, 78, 0, 0, 0, 0),
('Quản lý tiền bạc', 6, 'Kỹ năng quản lý tài chính cá nhân.', 5, 'quan_ly_tien.jpg', 'quan_ly_tien.pdf', 101000, 76, 0, 1, 0, 0),
('Đầu tư cổ phiếu', 6, 'Hướng dẫn đầu tư chứng khoán cho người mới.', 5, 'dau_tu_co_phieu.jpg', 'dau_tu_co_phieu.pdf', 106000, 80, 1, 0, 1, 12),
('Tư duy triệu phú', 6, 'Cách suy nghĩ của những người thành công.', 5, 'tu_duy_trieu_phu.jpg', 'tu_duy_trieu_phu.pdf', 103000, 79, 0, 0, 0, 0);

-- Sách mẫu: Thiếu nhi (category_id = 6)
INSERT INTO `books` (`title`, `author_id`, `description`, `category_id`, `cover`, `file`, `price`, `stock`, `is_new`, `is_bestseller`, `is_promotion`, `discount_percent`) VALUES
('Harry Potter và Hòn đá phù thủy', 7, 'Câu chuyện về cậu bé phù thủy và cuộc phiêu lưu kỳ diệu.', 6, 'hp1.jpg', 'hp1.pdf', 150000, 100, 1, 1, 0, 0),
('Harry Potter và Phòng chứa bí mật', 7, 'Cuộc phiêu lưu tiếp theo của Harry Potter.', 6, 'hp2.jpg', 'hp2.pdf', 145000, 95, 1, 1, 1, 10),
('Harry Potter và Tù nhân Azkaban', 7, 'Harry gặp lại người cha đỡ đầu.', 6, 'hp3.jpg', 'hp3.pdf', 148000, 98, 1, 1, 0, 0),
('Harry Potter và Chiếc cốc lửa', 7, 'Giải đấu Tam Pháp Thuật đầy nguy hiểm.', 6, 'hp4.jpg', 'hp4.pdf', 152000, 102, 1, 1, 0, 0),
('Harry Potter và Hội Phượng Hoàng', 7, 'Cuộc chiến chống lại Chúa tể Voldemort.', 6, 'hp5.jpg', 'hp5.pdf', 147000, 97, 0, 0, 1, 15),
('Harry Potter và Hoàng tử lai', 7, 'Bí mật về quá khứ của Voldemort.', 6, 'hp6.jpg', 'hp6.pdf', 149000, 99, 1, 0, 0, 0),
('Harry Potter và Bảo bối Tử thần', 7, 'Trận chiến cuối cùng với Voldemort.', 6, 'hp7.jpg', 'hp7.pdf', 151000, 101, 1, 1, 0, 0),
('Fantastic Beasts', 7, 'Câu chuyện về thế giới phù thủy trước thời Harry Potter.', 6, 'fantastic.jpg', 'fantastic.pdf', 144000, 94, 0, 0, 0, 0),
('Quidditch Through the Ages', 7, 'Hướng dẫn về môn thể thao phù thủy.', 6, 'quidditch.jpg', 'quidditch.pdf', 143000, 93, 0, 0, 1, 12),
('The Tales of Beedle the Bard', 7, 'Những câu chuyện cổ tích trong thế giới phù thủy.', 6, 'beedle.jpg', 'beedle.pdf', 146000, 96, 1, 0, 0, 0);

-- Sách mẫu: Manga - Comic (category_id = 7)
INSERT INTO `books` (`title`, `author_id`, `description`, `category_id`, `cover`, `file`, `price`, `stock`, `is_new`, `is_bestseller`, `is_promotion`, `discount_percent`) VALUES
('Thám tử lừng danh Conan - Tập 1', 8, 'Câu chuyện về thám tử nhí Conan và những vụ án ly kỳ.', 7, 'conan1.jpg', 'conan1.pdf', 35000, 200, 1, 1, 0, 0),
('Thám tử lừng danh Conan - Tập 2', 8, 'Tiếp tục những vụ án hấp dẫn của Conan.', 7, 'conan2.jpg', 'conan2.pdf', 35000, 195, 1, 1, 1, 10),
('Thám tử lừng danh Conan - Tập 3', 8, 'Những vụ án mới đầy thử thách.', 7, 'conan3.jpg', 'conan3.pdf', 35000, 190, 1, 0, 0, 0),
('Thám tử lừng danh Conan - Tập 4', 8, 'Cuộc chiến với tổ chức đen.', 7, 'conan4.jpg', 'conan4.pdf', 35000, 185, 0, 0, 0, 0),
('Thám tử lừng danh Conan - Tập 5', 8, 'Những manh mối quan trọng được tiết lộ.', 7, 'conan5.jpg', 'conan5.pdf', 35000, 180, 1, 0, 1, 15),
('Thám tử lừng danh Conan - Tập 6', 8, 'Vụ án liên quan đến quá khứ.', 7, 'conan6.jpg', 'conan6.pdf', 35000, 175, 0, 0, 0, 0),
('Thám tử lừng danh Conan - Tập 7', 8, 'Cuộc đối đầu với kẻ thù nguy hiểm.', 7, 'conan7.jpg', 'conan7.pdf', 35000, 170, 1, 1, 0, 0),
('Thám tử lừng danh Conan - Tập 8', 8, 'Bí mật về thuốc teo nhỏ.', 7, 'conan8.jpg', 'conan8.pdf', 35000, 165, 0, 0, 1, 12),
('Thám tử lừng danh Conan - Tập 9', 8, 'Những đồng minh mới xuất hiện.', 7, 'conan9.jpg', 'conan9.pdf', 35000, 160, 1, 0, 0, 0),
('Thám tử lừng danh Conan - Tập 10', 8, 'Trận chiến cuối cùng sắp đến.', 7, 'conan10.jpg', 'conan10.pdf', 35000, 155, 0, 0, 0, 0);

-- Sách mẫu: Khoa học (category_id = 8)
INSERT INTO `books` (`title`, `author_id`, `description`, `category_id`, `cover`, `file`, `price`, `stock`, `is_new`, `is_bestseller`, `is_promotion`, `discount_percent`) VALUES
('Sapiens: Lược sử loài người', 9, 'Lịch sử tiến hóa của loài người.', 8, 'sapiens.jpg', 'sapiens.pdf', 180000, 90, 1, 1, 0, 0),
('Homo Deus: Lược sử tương lai', 9, 'Dự đoán về tương lai của loài người.', 8, 'homo_deus.jpg', 'homo_deus.pdf', 175000, 85, 1, 1, 1, 10),
('21 bài học cho thế kỷ 21', 9, 'Những thách thức và cơ hội của thế kỷ 21.', 8, '21_bai_hoc.jpg', '21_bai_hoc.pdf', 170000, 80, 1, 0, 0, 0),
('Lược sử thời gian', 9, 'Khám phá về vũ trụ và thời gian.', 8, 'luoc_su_thoi_gian.jpg', 'luoc_su_thoi_gian.pdf', 165000, 75, 0, 0, 0, 0),
('Vũ trụ trong vỏ hạt dẻ', 9, 'Giải thích về vật lý lượng tử và vũ trụ.', 8, 'vu_tru.jpg', 'vu_tru.pdf', 172000, 82, 1, 0, 1, 15),
('Lược sử vũ trụ', 9, 'Câu chuyện về sự hình thành của vũ trụ.', 8, 'luoc_su_vu_tru.jpg', 'luoc_su_vu_tru.pdf', 168000, 78, 0, 0, 0, 0),
('Trí tuệ nhân tạo', 9, 'Tương lai của AI và tác động đến nhân loại.', 8, 'tri_tue_nhan_tao.jpg', 'tri_tue_nhan_tao.pdf', 174000, 83, 1, 1, 0, 0),
('Sinh học và tiến hóa', 9, 'Khám phá về sự sống và tiến hóa.', 8, 'sinh_hoc.jpg', 'sinh_hoc.pdf', 169000, 79, 0, 0, 1, 12),
('Khoa học và tôn giáo', 9, 'Mối quan hệ giữa khoa học và tôn giáo.', 8, 'khoa_hoc_ton_giao.jpg', 'khoa_hoc_ton_giao.pdf', 171000, 81, 1, 0, 0, 0),
('Tương lai của nhân loại', 9, 'Dự đoán về tương lai của loài người.', 8, 'tuong_lai.jpg', 'tuong_lai.pdf', 167000, 77, 0, 0, 0, 0);

-- Sách mẫu: Lịch sử (category_id = 9)
INSERT INTO `books` (`title`, `author_id`, `description`, `category_id`, `cover`, `file`, `price`, `stock`, `is_new`, `is_bestseller`, `is_promotion`, `discount_percent`) VALUES
('Lịch sử Việt Nam', 10, 'Tổng quan về lịch sử Việt Nam từ cổ đại đến hiện đại.', 9, 'lich_su_vn.jpg', 'lich_su_vn.pdf', 140000, 70, 1, 1, 0, 0),
('Các triều đại Việt Nam', 10, 'Lịch sử các triều đại phong kiến Việt Nam.', 9, 'trieu_dai.jpg', 'trieu_dai.pdf', 135000, 65, 1, 1, 1, 10),
('Chiến tranh Việt Nam', 10, 'Lịch sử cuộc chiến tranh chống Mỹ.', 9, 'chien_tranh.jpg', 'chien_tranh.pdf', 138000, 68, 1, 0, 0, 0),
('Văn hóa Việt Nam', 10, 'Khám phá văn hóa truyền thống Việt Nam.', 9, 'van_hoa.jpg', 'van_hoa.pdf', 132000, 63, 0, 0, 0, 0),
('Đại Việt sử ký', 10, 'Bộ sử ký quan trọng của Việt Nam.', 9, 'dai_viet.jpg', 'dai_viet.pdf', 137000, 67, 1, 0, 1, 15),
('Lịch sử thế giới', 10, 'Tổng quan lịch sử thế giới.', 9, 'lich_su_tg.jpg', 'lich_su_tg.pdf', 136000, 66, 0, 0, 0, 0),
('Cách mạng tháng Tám', 10, 'Lịch sử cuộc cách mạng giành độc lập.', 9, 'cm_thang_tam.jpg', 'cm_thang_tam.pdf', 134000, 64, 0, 1, 0, 0),
('Hồ Chí Minh - Tiểu sử', 10, 'Cuộc đời và sự nghiệp của Chủ tịch Hồ Chí Minh.', 9, 'ho_chi_minh.jpg', 'ho_chi_minh.pdf', 139000, 69, 1, 0, 1, 12),
('Lịch sử Đảng Cộng sản', 10, 'Lịch sử hình thành và phát triển của Đảng.', 9, 'lich_su_dang.jpg', 'lich_su_dang.pdf', 133000, 62, 0, 0, 0, 0),
('Di tích lịch sử Việt Nam', 10, 'Khám phá các di tích lịch sử quan trọng.', 9, 'di_tich.jpg', 'di_tich.pdf', 141000, 71, 1, 0, 0, 0);

-- Sách mẫu: Sách giáo khoa (category_id = 10)
INSERT INTO `books` (`title`, `author_id`, `description`, `category_id`, `cover`, `file`, `price`, `stock`, `is_new`, `is_bestseller`, `is_promotion`, `discount_percent`) VALUES
('Toán học lớp 10', 10, 'Sách giáo khoa Toán học lớp 10 chương trình mới.', 10, 'toan_10.jpg', 'toan_10.pdf', 50000, 500, 1, 1, 0, 0),
('Văn học lớp 10', 10, 'Sách giáo khoa Ngữ văn lớp 10.', 10, 'van_10.jpg', 'van_10.pdf', 48000, 480, 1, 1, 1, 10),
('Vật lý lớp 10', 10, 'Sách giáo khoa Vật lý lớp 10.', 10, 'vat_ly_10.jpg', 'vat_ly_10.pdf', 49000, 490, 1, 0, 0, 0),
('Hóa học lớp 10', 10, 'Sách giáo khoa Hóa học lớp 10.', 10, 'hoa_10.jpg', 'hoa_10.pdf', 47000, 470, 0, 0, 0, 0),
('Sinh học lớp 10', 10, 'Sách giáo khoa Sinh học lớp 10.', 10, 'sinh_10.jpg', 'sinh_10.pdf', 51000, 510, 1, 0, 1, 15),
('Lịch sử lớp 10', 10, 'Sách giáo khoa Lịch sử lớp 10.', 10, 'su_10.jpg', 'su_10.pdf', 46000, 460, 0, 0, 0, 0),
('Địa lý lớp 10', 10, 'Sách giáo khoa Địa lý lớp 10.', 10, 'dia_10.jpg', 'dia_10.pdf', 52000, 520, 1, 1, 0, 0),
('Tiếng Anh lớp 10', 10, 'Sách giáo khoa Tiếng Anh lớp 10.', 10, 'anh_10.jpg', 'anh_10.pdf', 55000, 550, 0, 0, 1, 12),
('GDCD lớp 10', 10, 'Sách giáo khoa Giáo dục công dân lớp 10.', 10, 'gdcd_10.jpg', 'gdcd_10.pdf', 45000, 450, 1, 0, 0, 0),
('Tin học lớp 10', 10, 'Sách giáo khoa Tin học lớp 10.', 10, 'tin_10.jpg', 'tin_10.pdf', 53000, 530, 0, 0, 0, 0);

-- =====================================================
-- CẬP NHẬT NỘI DUNG CHI TIẾT CHO SÁCH
-- =====================================================

-- Kính vạn hoa - Nguyễn Nhật Ánh
UPDATE `books` SET 
  `summary` = 'Kính Vạn Hoa là bộ truyện dài nổi tiếng của nhà văn Nguyễn Nhật Ánh, kể về cuộc sống học đường đầy màu sắc của nhóm bạn trẻ gồm: Quý ròm, Tiểu Long, Hạnh, nhỏ Hạnh và nhiều nhân vật thú vị khác.

Câu chuyện xoay quanh những tình huống dở khóc dở cười, những trò nghịch ngợm của tuổi học trò, những bài học về tình bạn, tình thầy trò và cả những rung động đầu đời.

Với giọng văn hài hước, dí dỏm đặc trưng của Nguyễn Nhật Ánh, Kính Vạn Hoa đã trở thành tác phẩm gắn liền với tuổi thơ của nhiều thế hệ độc giả Việt Nam.',
  `table_of_contents` = 'PHẦN 1: NGÀY ĐẦU TIÊN
- Chương 1: Quý ròm tới trường
- Chương 2: Gặp gỡ nhỏ Hạnh
- Chương 3: Lớp học mới

PHẦN 2: NHỮNG NGÀY VUI
- Chương 4: Trò chơi vương quốc
- Chương 5: Bài kiểm tra bất ngờ
- Chương 6: Picnic cuối tuần

PHẦN 3: NHỮNG RẮC RỐI
- Chương 7: Tiểu Long gặp họa
- Chương 8: Bí mật của Hạnh
- Chương 9: Giải cứu bạn bè

PHẦN 4: KẾT THÚC NĂM HỌC
- Chương 10: Kỳ thi cuối kỳ
- Chương 11: Lễ tổng kết
- Chương 12: Hẹn gặp lại',
  `publisher` = 'Nhà xuất bản Trẻ',
  `publication_year` = 2020,
  `pages` = 256,
  `isbn` = '978-604-1-18567-8',
  `language` = 'Tiếng Việt',
  `format` = 'both',
  `sample_content` = 'CHƯƠNG 1: QUÝ RÒM TỚI TRƯỜNG

Sáng sớm, tiếng chuông báo thức vang lên, Quý ròm vội vàng bật dậy. Hôm nay là ngày đầu tiên của năm học mới - năm học mà cậu chờ đợi suốt cả mùa hè.

"Quý ơi, dậy chưa con?" - Tiếng mẹ vọng từ dưới nhà bếp.

"Dạ, con dậy rồi ạ!" - Quý ròm đáp, trong lúc tay chân vẫn đang loay hoay với bộ đồng phục mới toanh.

Trường Tiểu học Ánh Dương nằm cuối con đường làng, cách nhà Quý chừng mười lăm phút đi bộ. Nhưng với đôi chân tràn đầy năng lượng của Quý ròm, quãng đường ấy chỉ mất có bảy phút.

Vừa bước vào cổng trường, Quý đã nhìn thấy Tiểu Long đang đứng chờ dưới gốc cây phượng già.

"Quý ơi, lớp mình năm nay có bạn mới đấy!" - Tiểu Long hét to, vẫy tay lia lịa.

"Bạn mới? Con trai hay con gái?"

"Con gái! Mà nghe nói xinh lắm!"

Hai đứa nhìn nhau, rồi cùng cười phá lên. Năm học mới hứa hẹn sẽ có nhiều điều thú vị đang chờ đợi phía trước...'
WHERE `title` = 'Kính vạn hoa';

-- Tôi thấy hoa vàng trên cỏ xanh
UPDATE `books` SET 
  `summary` = 'Tôi thấy hoa vàng trên cỏ xanh là tiểu thuyết nổi tiếng của nhà văn Nguyễn Nhật Ánh, lấy bối cảnh miền quê Việt Nam những năm 1980. Tác phẩm kể về tuổi thơ của hai anh em Thiều và Tường cùng những người bạn trong một ngôi làng nhỏ.

Câu chuyện xoay quanh tình anh em, tình bạn, và những rung động đầu đời trong sáng. Qua ngòi bút tinh tế của Nguyễn Nhật Ánh, độc giả được sống lại những ký ức đẹp về tuổi thơ với đồng ruộng, con trâu, và những trò chơi dân gian.

Tác phẩm đã được chuyển thể thành phim điện ảnh cùng tên năm 2015, gặt hái nhiều thành công về doanh thu và giải thưởng.',
  `table_of_contents` = 'PHẦN MỘT: MÙA HẠ
- Những ngày hè oi ả
- Đám bạn làng quê
- Mùi cỏ khô
- Con trâu nhà ông Sáu

PHẦN HAI: NHỮNG RUNG ĐỘNG
- Cô bé mắt nâu
- Bức thư đầu tiên
- Hoa cúc vàng
- Đêm trăng rằm

PHẦN BA: BIẾN CỐ
- Cơn bão lớn
- Chia ly
- Nỗi nhớ
- Trở về

PHẦN KẾT: HOA VÀNG TRÊN CỎ XANH
- Mười năm sau
- Gặp lại
- Điều còn mãi',
  `publisher` = 'Nhà xuất bản Trẻ',
  `publication_year` = 2010,
  `pages` = 378,
  `isbn` = '978-604-1-09876-5',
  `language` = 'Tiếng Việt',
  `format` = 'both',
  `sample_content` = 'CHƯƠNG MỞ ĐẦU

Tôi không nhớ mình đã đọc ở đâu câu này: "Tuổi thơ chẳng có gì đặc biệt, chỉ đặc biệt khi ta đánh mất nó."

Tôi sinh ra và lớn lên ở một làng quê nghèo miền Trung, nơi có những cánh đồng lúa chín vàng mỗi độ thu về, có dòng sông trong vắt uốn lượn quanh làng, và có tiếng ve râm ran suốt những ngày hè oi ả.

Nhà tôi ở cuối làng, cách bờ sông chừng trăm mét. Cha mẹ tôi làm ruộng, quanh năm bán mặt cho đất, bán lưng cho trời. Tôi có một người em trai tên Tường, nhỏ hơn tôi hai tuổi.

Thằng Tường từ nhỏ đã ốm yếu, hay đau bệnh. Mẹ tôi thường nói với cha: "Thằng Tường sinh ra thiếu tháng, nên nó yếu hơn thằng Thiều." Vì vậy, mẹ thường dành cho Tường phần cơm nhiều hơn, miếng thịt to hơn. Tôi không ghen tị, vì tôi biết mẹ thương cả hai đứa như nhau.

Ngày ấy, cuộc sống tuy nghèo khó nhưng chúng tôi vẫn hạnh phúc...'
WHERE `title` = 'Tôi thấy hoa vàng trên cỏ xanh';

-- Mắt biếc
UPDATE `books` SET 
  `summary` = 'Mắt Biếc là một trong những tác phẩm đặc sắc nhất của nhà văn Nguyễn Nhật Ánh, kể về mối tình đơn phương kéo dài suốt cả cuộc đời của Ngạn dành cho Hà Lan - cô bé có đôi mắt biếc huyền bí.

Câu chuyện bắt đầu từ những ngày thơ ấu ở làng Đo Đo, khi Ngạn và Hà Lan còn là hai đứa trẻ hồn nhiên, vô tư. Tình yêu của Ngạn lớn dần theo năm tháng, nhưng Hà Lan - người con gái xinh đẹp với đôi mắt biếc - lại hướng về một cuộc sống khác xa xôi.

Tác phẩm là bài ca buồn về tình yêu đơn phương, về sự hy sinh thầm lặng và về những điều không bao giờ nói ra được thành lời.',
  `table_of_contents` = 'PHẦN 1: LÀNG ĐO ĐO
- Đôi mắt biếc
- Những ngày thơ dại
- Cây sầu đông đầu làng
- Mùi hoa thiên lý

PHẦN 2: THÀNH PHỐ
- Ngày Hà Lan đi
- Những bức thư
- Chờ đợi
- Tin buồn

PHẦN 3: TRỞ VỀ
- Người đàn bà xa lạ
- Trà My
- Vòng tròn số phận
- Đôi mắt biếc của con',
  `publisher` = 'Nhà xuất bản Trẻ',
  `publication_year` = 1990,
  `pages` = 286,
  `isbn` = '978-604-1-08765-2',
  `language` = 'Tiếng Việt',
  `format` = 'both',
  `sample_content` = 'CHƯƠNG 1: ĐÔI MẮT BIẾC

Lần đầu tiên tôi gặp Hà Lan, đó là một buổi chiều mùa thu, khi tôi và lũ bạn đang chơi bắn bi dưới gốc sầu đông đầu làng.

Hà Lan đi qua, tay xách cái làn, trong làn đựng mấy quả chuối chín. Cô bé đi rất chậm, vừa đi vừa nhìn đám con trai chúng tôi bằng đôi mắt tò mò.

Đôi mắt ấy - tôi sẽ không bao giờ quên được - xanh biếc như màu nước biển những ngày trời trong. Đôi mắt ấy trong veo, long lanh, và buồn một nỗi buồn khó tả.

"Bọn mày nhìn gì?" - Thằng Hùng gắt.

Hà Lan không trả lời, cô bé chỉ mỉm cười rồi tiếp tục bước đi.

Từ hôm đó, tôi bắt đầu để ý đến Hà Lan. Cô bé mới chuyển đến làng Đo Đo, sống với bà ngoại trong căn nhà nhỏ cuối đường.

Tôi hay tìm cớ đi ngang qua nhà bà ngoại Hà Lan, chỉ để được nhìn thấy cô bé với đôi mắt biếc ấy một lần...'
WHERE `title` = 'Mắt biếc';

-- Nhà giả kim
UPDATE `books` SET 
  `summary` = 'Nhà Giả Kim (The Alchemist) là tiểu thuyết nổi tiếng nhất của nhà văn Brazil Paulo Coelho, đã được dịch ra hơn 80 ngôn ngữ và bán được hơn 150 triệu bản trên toàn thế giới.

Câu chuyện kể về Santiago - một cậu bé chăn cừu người Tây Ban Nha - người đã từ bỏ cuộc sống bình yên để theo đuổi giấc mơ tìm kiếm kho báu ở Kim tự tháp Ai Cập.

Trong hành trình của mình, Santiago gặp nhiều người thầy: từ vị vua già đến nhà giả kim huyền bí. Mỗi cuộc gặp gỡ đều mang đến cho cậu những bài học quý giá về cuộc sống, về tình yêu, và về việc theo đuổi "Huyền thoại cá nhân" của mỗi người.',
  `table_of_contents` = 'PHẦN MỘT: GIẤC MƠ
- Giấc mơ lặp lại
- Người thông dịch giấc mơ
- Vị vua già
- Bán đàn cừu

PHẦN HAI: HÀNH TRÌNH
- Qua eo biển
- Cửa hàng pha lê
- Sa mạc Sahara
- Đoàn lữ hành

PHẦN BA: LINH HỒN THẾ GIỚI
- Ốc đảo
- Cô gái sa mạc
- Nhà giả kim
- Bài học cuối cùng

PHẦN KẾT: KHO BÁU
- Kim tự tháp
- Sự thật
- Quay về',
  `publisher` = 'Nhà xuất bản Văn học',
  `publication_year` = 2013,
  `pages` = 224,
  `isbn` = '978-604-32-1234-5',
  `language` = 'Tiếng Việt',
  `format` = 'both',
  `sample_content` = 'LỜI MỞ ĐẦU

Nhà giả kim cầm cuốn sách một tác giả nào đó viết. Cuốn sách không có bìa, nhưng ông nhận ra tên tác giả: Oscar Wilde.

Khi ông lật trang sách, một câu chuyện thu hút sự chú ý của ông. Đó là về Narcissus, một chàng trai trẻ đẹp thường ngày đến ngồi bên hồ nước để ngắm vẻ đẹp của chính mình.

Chàng say mê vẻ đẹp của mình đến nỗi một hôm ngã xuống hồ và chết đuối. Trên mặt đất nơi chàng ngã, một bông hoa mọc lên, và người ta đặt tên nó là hoa thuỷ tiên (narcissus).

Nhưng câu chuyện không kết thúc ở đó. Khi Narcissus chết, các nữ thần rừng đến bên hồ nước và thấy nước hồ đã biến thành những giọt nước mắt mặn.

"Tại sao ngươi khóc?" các nữ thần hỏi.

"Ta khóc vì Narcissus," hồ nước trả lời.

"Chúng ta không ngạc nhiên khi ngươi khóc thương Narcissus," các nữ thần nói, "vì dù chúng ta luôn đuổi theo chàng trong rừng, chỉ có ngươi mới có cơ hội ngắm nhìn vẻ đẹp của chàng thật gần."

"Nhưng... Narcissus đẹp ư?" hồ nước hỏi.

"Ai có thể biết điều đó hơn ngươi?" các nữ thần ngạc nhiên hỏi lại. "Chàng ta quỳ bên bờ hồ ngươi mỗi ngày mà!"

Hồ nước im lặng một lát, rồi trả lời:

"Ta khóc vì Narcissus, nhưng ta không bao giờ nhận thấy Narcissus đẹp. Ta khóc vì mỗi lần chàng quỳ bên bờ hồ, ta có thể nhìn thấy vẻ đẹp của chính mình trong đáy mắt chàng."'
WHERE `title` = 'Nhà giả kim';

-- Đắc nhân tâm
UPDATE `books` SET 
  `summary` = 'Đắc Nhân Tâm (How to Win Friends and Influence People) là cuốn sách kỹ năng sống kinh điển của Dale Carnegie, xuất bản lần đầu năm 1936 và vẫn là sách bán chạy nhất mọi thời đại với hơn 30 triệu bản.

Cuốn sách trình bày những nguyên tắc cơ bản trong giao tiếp và ứng xử, giúp người đọc:
- Được yêu mến và có ảnh hưởng với mọi người
- Thuyết phục người khác theo cách nghĩ của mình
- Thay đổi người khác mà không gây khó chịu hay oán giận
- Trở thành nhà lãnh đạo hiệu quả

Với những ví dụ thực tế và nguyên tắc dễ áp dụng, Đắc Nhân Tâm đã thay đổi cuộc đời của hàng triệu người trên thế giới.',
  `table_of_contents` = 'PHẦN 1: NGHỆ THUẬT ỨNG XỬ CƠ BẢN
- Nguyên tắc 1: Đừng chỉ trích, lên án hay phàn nàn
- Nguyên tắc 2: Thành thật khen ngợi và biết ơn
- Nguyên tắc 3: Khơi gợi nhu cầu của người khác

PHẦN 2: SÁU CÁCH ĐỂ ĐƯỢC YÊU MẾN
- Nguyên tắc 1: Thật sự quan tâm đến người khác
- Nguyên tắc 2: Mỉm cười
- Nguyên tắc 3: Ghi nhớ tên người khác
- Nguyên tắc 4: Biết lắng nghe
- Nguyên tắc 5: Nói về sở thích của họ
- Nguyên tắc 6: Làm cho họ cảm thấy quan trọng

PHẦN 3: NGHỆ THUẬT THUYẾT PHỤC
- Tranh luận đúng cách
- Tôn trọng ý kiến người khác
- Thừa nhận sai lầm
- Bắt đầu thân thiện

PHẦN 4: NGHỆ THUẬT LÃNH ĐẠO
- Khen trước khi góp ý
- Gợi ý thay vì ra lệnh
- Để người khác giữ thể diện
- Khuyến khích tiến bộ',
  `publisher` = 'Nhà xuất bản Tổng hợp TP.HCM',
  `publication_year` = 2016,
  `pages` = 320,
  `isbn` = '978-604-58-4567-8',
  `language` = 'Tiếng Việt',
  `format` = 'both',
  `sample_content` = 'PHẦN MỘT: NGHỆ THUẬT ỨNG XỬ CƠ BẢN

CHƯƠNG 1: ĐỪNG CHỈ TRÍCH, LÊN ÁN HAY PHÀN NÀN

Ngày 7 tháng 5 năm 1931, cuộc săn đuổi tội phạm ly kỳ nhất trong lịch sử New York đã kết thúc. Sau nhiều tuần truy lùng, "Two Gun" Crowley - kẻ giết người không hề biết run tay - cuối cùng đã bị bắt giữ tại căn hộ của tình nhân mình ở West End Avenue.

Cảnh sát đã nổ 150 viên đạn vào căn hộ trước khi bắt được hắn. Crowley, với khẩu súng trong tay, đã viết một bức thư trong lúc đạn bay như mưa bên ngoài.

Bức thư viết: "Trong bộ quần áo tôi mặc là một trái tim mệt mỏi nhưng tốt lành - một trái tim sẽ không làm hại ai."

Một kẻ giết người mà vẫn tự coi mình là người tốt!

Vậy chúng ta có thể học được gì từ điều này?

Đừng bao giờ chỉ trích ai, vì bất kỳ kẻ ngốc nào cũng có thể chỉ trích, lên án và phàn nàn - và hầu hết kẻ ngốc đều làm vậy.

Nhưng cần có tính cách và sự tự chủ để thấu hiểu và tha thứ.

Benjamin Franklin, một trong những nhà ngoại giao khéo léo nhất nước Mỹ, đã nói: "Tôi sẽ không nói xấu bất kỳ ai, và sẽ nói tất cả những điều tốt đẹp mà tôi biết về mọi người."'
WHERE `title` = 'Đắc nhân tâm';

-- Harry Potter và Hòn đá phù thủy
UPDATE `books` SET 
  `summary` = 'Harry Potter và Hòn đá Phù thủy là tập đầu tiên trong bộ truyện Harry Potter huyền thoại của J.K. Rowling. Xuất bản năm 1997, cuốn sách đã mở ra cánh cửa đưa hàng triệu độc giả đến với thế giới phù thủy kỳ diệu.

Câu chuyện bắt đầu khi Harry Potter - cậu bé mồ côi sống với gia đình dì dượng khắc nghiệt - phát hiện mình là một phù thủy. Vào sinh nhật 11 tuổi, Harry nhận được thư mời nhập học trường Hogwarts - ngôi trường phù thủy danh tiếng nhất.

Tại đây, Harry kết bạn với Ron và Hermione, và cùng họ khám phá bí mật về Hòn đá Phù thủy - viên đá có thể biến mọi kim loại thành vàng và tạo ra thuốc trường sinh.',
  `table_of_contents` = 'Chương 1: Cậu bé sống sót
Chương 2: Tấm kính biến mất
Chương 3: Những bức thư từ hư không
Chương 4: Người gác cổng chìa khóa
Chương 5: Hẻm Xéo
Chương 6: Hành trình từ sân ga 9¾
Chương 7: Chiếc Nón Phân loại
Chương 8: Thầy giáo Bào chế Thuốc
Chương 9: Cuộc đấu nửa đêm
Chương 10: Halloween
Chương 11: Quidditch
Chương 12: Tấm gương Erised
Chương 13: Nicolas Flamel
Chương 14: Norbert - Con rồng Na Uy
Chương 15: Khu rừng cấm
Chương 16: Qua cửa sập
Chương 17: Người hai mặt',
  `publisher` = 'Nhà xuất bản Trẻ',
  `publication_year` = 2016,
  `pages` = 366,
  `isbn` = '978-604-1-07654-3',
  `language` = 'Tiếng Việt',
  `format` = 'both',
  `sample_content` = 'CHƯƠNG 1: CẬU BÉ SỐNG SÓT

Ông bà Dursley, số 4 đường Privet, luôn tự hào nói rằng họ hoàn toàn bình thường, cảm ơn nhiều. Họ là những người cuối cùng mà người ta có thể liên tưởng đến bất cứ điều gì kỳ lạ hay bí ẩn, bởi vì họ không tin vào những chuyện vớ vẩn như vậy.

Ông Dursley là giám đốc một công ty tên Grunnings chuyên sản xuất máy khoan. Ông là người to béo, hầu như không có cổ, nhưng lại có bộ ria mép rất to. Còn bà Dursley thì gầy, tóc vàng và cổ dài gần gấp đôi người bình thường, rất tiện cho việc nhòm ngó hàng xóm qua hàng rào. Vợ chồng Dursley có một đứa con trai tên Dudley, và theo họ thì không có đứa trẻ nào tuyệt vời hơn.

Gia đình Dursley có tất cả những gì họ muốn, nhưng họ cũng có một bí mật, và nỗi sợ lớn nhất của họ là có ai đó phát hiện ra bí mật ấy. Họ nghĩ họ không thể chịu nổi nếu ai đó biết về gia đình Potter. Bà Potter là em gái bà Dursley, nhưng hai chị em đã không gặp nhau nhiều năm nay; thực ra, bà Dursley giả vờ như mình không có em gái, bởi vì em gái bà và ông chồng vô tích sự của cô ta hoàn toàn không giống với gia đình Dursley.

Gia đình Dursley rùng mình khi nghĩ đến những gì hàng xóm sẽ nói nếu gia đình Potter xuất hiện trên phố. Gia đình Dursley biết rằng gia đình Potter cũng có một đứa con trai nhỏ, nhưng họ thậm chí còn chưa bao giờ nhìn thấy cậu bé. Đứa bé này là một lý do nữa để tránh xa gia đình Potter; họ không muốn Dudley kết bạn với một đứa trẻ như vậy.'
WHERE `title` = 'Harry Potter và Hòn đá phù thủy';

-- Cha giàu cha nghèo
UPDATE `books` SET 
  `summary` = 'Cha Giàu Cha Nghèo (Rich Dad Poor Dad) là cuốn sách tài chính cá nhân bán chạy nhất mọi thời đại của Robert Kiyosaki. Xuất bản năm 1997, cuốn sách đã thay đổi cách hàng triệu người nghĩ về tiền bạc.

Kiyosaki kể về hai người cha: "cha nghèo" - cha ruột của ông, một người có học thức cao nhưng gặp khó khăn tài chính; và "cha giàu" - cha của người bạn, người không có bằng cấp nhưng trở thành một trong những người giàu nhất Hawaii.

Cuốn sách phá vỡ những quan niệm sai lầm về tiền bạc và đầu tư, đồng thời trình bày những bài học quý giá về cách người giàu suy nghĩ và hành động với tiền.',
  `table_of_contents` = 'GIỚI THIỆU: Có một nhu cầu

CHƯƠNG 1: Cha giàu, cha nghèo
CHƯƠNG 2: Bài học số 1 - Người giàu không làm việc vì tiền
CHƯƠNG 3: Bài học số 2 - Tại sao phải dạy kiến thức tài chính?
CHƯƠNG 4: Bài học số 3 - Hãy chú tâm vào công việc của mình
CHƯƠNG 5: Bài học số 4 - Lịch sử thuế và quyền lực của công ty
CHƯƠNG 6: Bài học số 5 - Người giàu sáng tạo ra tiền
CHƯƠNG 7: Bài học số 6 - Làm việc để học, đừng làm việc vì tiền

CHƯƠNG 8: Vượt qua trở ngại
CHƯƠNG 9: Bắt đầu
CHƯƠNG 10: Vẫn muốn nhiều hơn? Đây là một số việc cần làm

LỜI KẾT: Làm thế nào để trả 7.000 đô la học phí đại học chỉ với 7.000 đô la',
  `publisher` = 'Nhà xuất bản Trẻ',
  `publication_year` = 2018,
  `pages` = 336,
  `isbn` = '978-604-1-12345-6',
  `language` = 'Tiếng Việt',
  `format` = 'both',
  `sample_content` = 'CHƯƠNG 1: CHA GIÀU, CHA NGHÈO

Tôi có hai người cha, một người giàu và một người nghèo. Một người có bằng cấp cao và thông minh. Ông có bằng tiến sĩ và hoàn thành bốn năm đại học trong vòng chưa đầy hai năm. Sau đó, ông tiếp tục học tại Stanford, Chicago và Northwestern, tất cả đều bằng học bổng. Người cha kia thậm chí còn chưa học hết lớp 8.

Cả hai người đều thành công trong sự nghiệp, làm việc chăm chỉ suốt đời. Cả hai đều kiếm được thu nhập đáng kể. Thế nhưng một người luôn gặp khó khăn về tài chính. Người kia trở thành một trong những người giàu nhất Hawaii. Một người qua đời để lại hàng chục triệu đô la cho gia đình, các tổ chức từ thiện và nhà thờ. Người kia chỉ để lại các hóa đơn chưa thanh toán.

Cả hai người đều cho tôi lời khuyên, nhưng họ không cho cùng một lời khuyên. Cả hai đều tin tưởng mạnh mẽ vào giáo dục nhưng không khuyên tôi học những môn giống nhau.

Nếu tôi chỉ có một người cha, tôi sẽ phải chấp nhận hoặc từ chối lời khuyên của ông. Việc có hai người cha cho tôi sự lựa chọn về những quan điểm trái ngược: của một người giàu và của một người nghèo.

Thay vì đơn giản chấp nhận hoặc từ chối cái này hay cái kia, tôi thấy mình suy nghĩ nhiều hơn, so sánh, rồi chọn cho mình...'
WHERE `title` = 'Cha giàu cha nghèo';

-- Rừng Na Uy
UPDATE `books` SET 
  `summary` = 'Rừng Na Uy (Norwegian Wood) là tiểu thuyết nổi tiếng nhất của nhà văn Nhật Bản Haruki Murakami, xuất bản năm 1987. Tác phẩm đã bán được hơn 10 triệu bản và được dịch ra nhiều ngôn ngữ.

Câu chuyện kể về Toru Watanabe - một sinh viên đại học tại Tokyo những năm cuối thập niên 1960. Trên chuyến bay, khi nghe bài hát "Norwegian Wood" của Beatles, Toru hồi tưởng lại quãng thời gian tuổi trẻ đầy biến động.

Toru bị giằng xé giữa tình yêu với Naoko - người yêu của người bạn thân đã tự tử, và Midori - cô gái sôi nổi, tràn đầy sức sống. Rừng Na Uy là tác phẩm về tình yêu, mất mát, và sự trưởng thành.',
  `table_of_contents` = 'Chương 1
Chương 2
Chương 3
Chương 4
Chương 5
Chương 6
Chương 7
Chương 8
Chương 9
Chương 10
Chương 11',
  `publisher` = 'Nhà xuất bản Hội Nhà văn',
  `publication_year` = 2006,
  `pages` = 428,
  `isbn` = '978-604-77-1234-5',
  `language` = 'Tiếng Việt',
  `format` = 'both',
  `sample_content` = 'CHƯƠNG 1

Tôi ba mươi bảy tuổi, và đang ngồi trên ghế máy bay Boeing 747. Chiếc máy bay khổng lồ xuyên qua những đám mây dày, bắt đầu hạ xuống sân bay Hamburg. Mưa tháng Mười một lạnh lẽo phủ lên mặt đất một màu xám u ám, biến những thứ tưởng như chỉ có trong các bức tranh Flemish - những người công nhân trong áo mưa, lá cờ ướt sũng trên nóc tòa nhà sân bay, một chiếc xe tải BMV quảng cáo - thành những hình ảnh nhạt nhòa.

Cặp loa trên trần máy bay bắt đầu phát nhạc nhẹ, và giai điệu của bài hát "Norwegian Wood" của Beatles vang lên. Và như mọi khi, giai điệu ấy làm tôi bối rối. Không, "bối rối" không phải là từ chính xác. Có lẽ từ "đau đớn" sẽ gần hơn. Một nỗi đau mơ hồ len lỏi vào trái tim tôi và xiết chặt lấy nó.

Tôi nói với cô tiếp viên hàng không, và cô mang cho tôi hai ly cocktail. Tôi uống cả hai ly, rồi đưa tay che mặt. Những người xung quanh có lẽ nghĩ tôi đang sợ máy bay hạ cánh. Nhưng không phải vậy.

Bài hát kết thúc, nhưng tôi vẫn không thể cử động. Tôi ngồi yên, mắt nhắm nghiền, tay vẫn che mặt. Và tôi nghĩ về Naoko.

Đã gần hai mươi năm kể từ lần cuối tôi gặp cô ấy. Suốt thời gian ấy, tôi đã quên đi nhiều điều. Nhưng vào lúc này, ký ức ùa về với độ chi tiết đáng kinh ngạc, như thể mọi thứ mới chỉ xảy ra ngày hôm qua.'
WHERE `title` = 'Rừng Na Uy';

-- Sapiens
UPDATE `books` SET 
  `summary` = 'Sapiens: Lược sử loài người (A Brief History of Humankind) là cuốn sách phi hư cấu của giáo sư lịch sử Yuval Noah Harari, xuất bản năm 2011. Cuốn sách đã trở thành hiện tượng toàn cầu với hơn 20 triệu bản bán ra.

Harari khám phá lịch sử của loài người từ thời kỳ đồ đá cho đến hiện đại, giải thích cách Homo sapiens trở thành loài thống trị hành tinh. Ông phân tích ba cuộc cách mạng lớn đã định hình lịch sử nhân loại:
- Cách mạng Nhận thức (70.000 năm trước)
- Cách mạng Nông nghiệp (12.000 năm trước)
- Cách mạng Khoa học (500 năm trước)

Sapiens là cuốn sách thay đổi cách chúng ta nhìn nhận về chính mình và thế giới.',
  `table_of_contents` = 'PHẦN 1: CÁCH MẠNG NHẬN THỨC
- Chương 1: Một loài động vật không quan trọng
- Chương 2: Cây tri thức
- Chương 3: Một ngày trong cuộc đời Adam và Eve
- Chương 4: Trận đại hồng thủy

PHẦN 2: CÁCH MẠNG NÔNG NGHIỆP
- Chương 5: Vụ lừa đảo lớn nhất lịch sử
- Chương 6: Xây dựng kim tự tháp
- Chương 7: Bộ nhớ quá tải
- Chương 8: Không có công lý trong lịch sử

PHẦN 3: SỰ THỐNG NHẤT CỦA LOÀI NGƯỜI
- Chương 9: Mũi tên của lịch sử
- Chương 10: Mùi tiền
- Chương 11: Tầm nhìn của đế chế
- Chương 12: Luật của tôn giáo
- Chương 13: Bí mật của thành công

PHẦN 4: CÁCH MẠNG KHOA HỌC
- Chương 14: Khám phá sự thiếu hiểu biết
- Chương 15: Cuộc hôn nhân giữa khoa học và đế quốc
- Chương 16: Tín điều tư bản
- Chương 17: Bánh xe công nghiệp
- Chương 18: Một cuộc cách mạng vĩnh cửu
- Chương 19: Và họ sống hạnh phúc mãi mãi
- Chương 20: Kết thúc của Homo Sapiens',
  `publisher` = 'Nhà xuất bản Thế giới',
  `publication_year` = 2017,
  `pages` = 560,
  `isbn` = '978-604-77-3456-7',
  `language` = 'Tiếng Việt',
  `format` = 'both',
  `sample_content` = 'CHƯƠNG 1: MỘT LOÀI ĐỘNG VẬT KHÔNG QUAN TRỌNG

Khoảng 13,5 tỷ năm trước, vật chất, năng lượng, thời gian và không gian ra đời trong cái được gọi là Vụ Nổ Lớn. Câu chuyện về những đặc tính cơ bản này của vũ trụ được gọi là vật lý.

Khoảng 300.000 năm sau khi xuất hiện, vật chất và năng lượng bắt đầu kết hợp thành các cấu trúc phức tạp, được gọi là nguyên tử, rồi các nguyên tử kết hợp thành phân tử. Câu chuyện về các nguyên tử, phân tử và tương tác của chúng được gọi là hóa học.

Khoảng 3,8 tỷ năm trước, trên một hành tinh có tên Trái Đất, một số phân tử nhất định kết hợp với nhau để tạo thành những cấu trúc đặc biệt lớn và phức tạp gọi là sinh vật. Câu chuyện về các sinh vật được gọi là sinh học.

Khoảng 70.000 năm trước, các sinh vật thuộc loài Homo sapiens bắt đầu hình thành những cấu trúc thậm chí còn phức tạp hơn gọi là văn hóa. Sự phát triển tiếp theo của những nền văn hóa này được gọi là lịch sử.

Ba cuộc cách mạng quan trọng đã định hình tiến trình lịch sử: Cách mạng Nhận thức đã khởi động lịch sử khoảng 70.000 năm trước. Cách mạng Nông nghiệp đẩy nhanh lịch sử khoảng 12.000 năm trước. Cách mạng Khoa học bắt đầu chỉ 500 năm trước, có thể đã chấm dứt lịch sử và khởi đầu một điều gì đó hoàn toàn khác biệt. Cuốn sách này kể câu chuyện về cách ba cuộc cách mạng ấy đã ảnh hưởng đến con người và các sinh vật đồng hành của họ.'
WHERE `title` = 'Sapiens: Lược sử loài người';

-- Conan tập 1
UPDATE `books` SET 
  `summary` = 'Thám Tử Lừng Danh Conan là bộ manga trinh thám nổi tiếng của tác giả Gosho Aoyama, bắt đầu xuất bản từ năm 1994. Đây là một trong những bộ manga bán chạy nhất mọi thời đại.

Tập 1 giới thiệu Shinichi Kudo - thám tử học sinh thiên tài 17 tuổi. Trong một lần điều tra, cậu bị tổ chức Áo Đen ép uống thuốc độc APTX 4869. Thay vì chết, Shinichi bị teo nhỏ thành cậu bé 7 tuổi.

Lấy tên giả là Conan Edogawa, cậu sống tại nhà thám tử Kogoro Mori và bí mật điều tra tổ chức Áo Đen, đồng thời giải quyết nhiều vụ án ly kỳ.',
  `table_of_contents` = 'FILE 1: Thám tử lừng danh bị teo nhỏ
FILE 2: Cậu bé tên Conan Edogawa
FILE 3: Vụ án bắt cóc con gái tỷ phú
FILE 4: Sáu bức tượng Napoleon
FILE 5: Vụ án tại nhà hàng Nhật
FILE 6: Bóng ma công viên giải trí
FILE 7: Án mạng tàu lượn siêu tốc',
  `publisher` = 'Nhà xuất bản Kim Đồng',
  `publication_year` = 2010,
  `pages` = 184,
  `isbn` = '978-604-1-56789-0',
  `language` = 'Tiếng Việt',
  `format` = 'both',
  `sample_content` = 'FILE 1: THÁM TỬ LỪNG DANH BỊ TEO NHỎ

"Kẻ thủ ác chỉ có một! Chính là người mà không ai ngờ tới - người quản gia!"

Shinichi Kudo - thám tử học sinh 17 tuổi nổi tiếng toàn nước Nhật - vừa phá xong một vụ án giết người ly kỳ. Báo chí bu quanh cậu như ruồi.

"Kudo! Cậu nghĩ sao về nickname Thám tử Heisei của mình?"

"Thám tử của thời đại Heisei? Nghe thú vị đấy!" - Shinichi mỉm cười tự tin.

Tối hôm đó, Shinichi hẹn đi công viên giải trí Tropical Land với Ran Mori - cô bạn gái thân thiết từ nhỏ và cũng là con gái của thám tử tư Kogoro Mori.

"Ran, cậu có tin ma không?"

"Sao đột nhiên hỏi vậy?"

"Tớ vừa thấy hai gã đàn ông mặc đồ đen rất đáng ngờ đi vào đường hầm kia..."

Tò mò, Shinichi lẻn đi theo. Cậu không ngờ đây là quyết định sẽ thay đổi hoàn toàn cuộc đời mình. Khi bị phát hiện đang nghe lén giao dịch phi pháp, một trong hai gã áo đen đã đập mạnh vào sau gáy Shinichi.

"Cho nó uống thuốc thử nghiệm mới đi, Gin!"

"APTX 4869... Thuốc này không để lại dấu vết trong cơ thể. Hoàn hảo để phi tang chứng cứ."

Khi tỉnh dậy, Shinichi phát hiện mình đã bị teo nhỏ thành đứa trẻ 7 tuổi!

"Cái gì thế này?! Tớ... tớ đã biến thành trẻ con sao?!"'
WHERE `title` = 'Thám tử lừng danh Conan - Tập 1';

-- Cập nhật thêm thông tin cho các sách còn lại (publisher, year, pages, format)
UPDATE `books` SET 
  `publisher` = 'Nhà xuất bản Trẻ',
  `publication_year` = 2015,
  `pages` = 220,
  `format` = 'both'
WHERE `publisher` IS NULL AND `category_id` = 1;

UPDATE `books` SET 
  `publisher` = 'Nhà xuất bản Văn học',
  `publication_year` = 2018,
  `pages` = 280,
  `format` = 'both'
WHERE `publisher` IS NULL AND `category_id` = 2;

UPDATE `books` SET 
  `publisher` = 'Nhà xuất bản Hội Nhà văn',
  `publication_year` = 2019,
  `pages` = 350,
  `format` = 'both'
WHERE `publisher` IS NULL AND `category_id` = 3;

UPDATE `books` SET 
  `publisher` = 'Nhà xuất bản Tổng hợp TP.HCM',
  `publication_year` = 2020,
  `pages` = 300,
  `format` = 'both'
WHERE `publisher` IS NULL AND `category_id` = 4;

UPDATE `books` SET 
  `publisher` = 'Nhà xuất bản Lao động',
  `publication_year` = 2021,
  `pages` = 320,
  `format` = 'both'
WHERE `publisher` IS NULL AND `category_id` = 5;

UPDATE `books` SET 
  `publisher` = 'Nhà xuất bản Kim Đồng',
  `publication_year` = 2019,
  `pages` = 400,
  `format` = 'both'
WHERE `publisher` IS NULL AND `category_id` = 6;

UPDATE `books` SET 
  `publisher` = 'Nhà xuất bản Kim Đồng',
  `publication_year` = 2022,
  `pages` = 180,
  `format` = 'hardcopy'
WHERE `publisher` IS NULL AND `category_id` = 7;

UPDATE `books` SET 
  `publisher` = 'Nhà xuất bản Thế giới',
  `publication_year` = 2020,
  `pages` = 450,
  `format` = 'both'
WHERE `publisher` IS NULL AND `category_id` = 8;

UPDATE `books` SET 
  `publisher` = 'Nhà xuất bản Chính trị Quốc gia',
  `publication_year` = 2018,
  `pages` = 380,
  `format` = 'both'
WHERE `publisher` IS NULL AND `category_id` = 9;

UPDATE `books` SET 
  `publisher` = 'Nhà xuất bản Giáo dục',
  `publication_year` = 2023,
  `pages` = 200,
  `format` = 'hardcopy'
WHERE `publisher` IS NULL AND `category_id` = 10;

-- Cấu hình hệ thống
INSERT INTO `settings` (`setting_key`, `setting_value`, `description`) VALUES
('cod_fee_percent', '2', 'Phí COD (% giá trị đơn hàng sau giảm giá)'),
('momo_qr_url', 'uploads/qr/momo_qr.jpeg', 'URL ảnh QR thanh toán MoMo (demo)'),
('zalopay_qr_url', 'uploads/qr/zalopay_qr.jpeg', 'URL ảnh QR thanh toán ZaloPay (demo)'),
('rental_auto_extend', '1', 'Tự động gia hạn thuê sách'),
('rental_max_late', '1', 'Số lần trễ hạn tối đa'),
('rental_late_fee_percent', '100', 'Phí phạt trễ hạn (%)');

-- Mã giảm giá mẫu
INSERT INTO `coupons` (`code`, `description`, `discount_percent`, `discount_type`, `apply_type`, `usage_limit`, `is_active`) VALUES
('SALE10', 'Giảm 10% giá trị đơn hàng', 10, 'percent', 'all', NULL, 1),
('FREESHIP', 'Miễn phí vận chuyển', 0, 'freeship', 'all', NULL, 1);

-- User mẫu (mật khẩu: admin123)
INSERT INTO `users` (`full_name`, `email`, `password`, `phone`, `balance`) VALUES
('Nguyễn Văn A', 'user1@gmail.com', '$2y$12$Rw75E2E765Derhpcn2z1puTndPoDsfkRUVZz.j/MiI/TTfCpy2yIa', '0901234567', 500000),
('Trần Thị B', 'user2@gmail.com', '$2y$12$Rw75E2E765Derhpcn2z1puTndPoDsfkRUVZz.j/MiI/TTfCpy2yIa', '0912345678', 300000),
('Lê Văn C', 'user3@gmail.com', '$2y$12$Rw75E2E765Derhpcn2z1puTndPoDsfkRUVZz.j/MiI/TTfCpy2yIa', '0923456789', 1000000);

-- =====================================================
-- BỔ SUNG KHÓA NGOẠI (FK) CÒN THIẾU
-- =====================================================
ALTER TABLE `books`
  ADD CONSTRAINT `fk_books_author` FOREIGN KEY (`author_id`) REFERENCES `authors` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_books_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE;

ALTER TABLE `cart`
  ADD CONSTRAINT `fk_cart_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_cart_book` FOREIGN KEY (`book_id`) REFERENCES `books` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_cart_promotion` FOREIGN KEY (`promotion_id`) REFERENCES `promotions` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE `wishlist`
  ADD CONSTRAINT `fk_wishlist_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_wishlist_book` FOREIGN KEY (`book_id`) REFERENCES `books` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `orders`
  ADD CONSTRAINT `fk_orders_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE;

ALTER TABLE `order_items`
  ADD CONSTRAINT `fk_order_items_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_order_items_book` FOREIGN KEY (`book_id`) REFERENCES `books` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE;

ALTER TABLE `transactions`
  ADD CONSTRAINT `fk_transactions_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE `reviews`
  ADD CONSTRAINT `fk_reviews_book` FOREIGN KEY (`book_id`) REFERENCES `books` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_reviews_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `chat_sessions`
  ADD CONSTRAINT `fk_chat_sessions_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

-- =====================================================
SET FOREIGN_KEY_CHECKS = 1;
COMMIT;
-- =====================================================
-- HOÀN TẤT! Database đã sẵn sàng sử dụng.
-- Tài khoản Admin: admin@admin.com / admin123
-- Tài khoản User mẫu: user1@gmail.com / admin123
-- =====================================================
