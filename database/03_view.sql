-- =====================================================
-- ONLINE BOOK STORE - VIEWS (Nếu cần)
-- File 3/3: Chứa CREATE VIEW statements
-- =====================================================

USE `online_book_store_db`;

-- Hiện tại chưa có VIEW nào được định nghĩa
-- Có thể thêm sau nếu cần

-- Ví dụ VIEW thống kê bán hàng:
-- CREATE OR REPLACE VIEW v_book_sales AS
-- SELECT 
--     b.id,
--     b.title,
--     b.price,
--     COALESCE(SUM(oi.quantity), 0) AS total_sold,
--     COALESCE(SUM(oi.quantity * oi.price), 0) AS total_revenue
-- FROM books b
-- LEFT JOIN order_items oi ON b.id = oi.book_id
-- LEFT JOIN orders o ON oi.order_id = o.id AND o.status IN ('delivered', 'shipped')
-- GROUP BY b.id;

-- =====================================================
-- END OF FILE
-- =====================================================
