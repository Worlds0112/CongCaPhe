-- Migration: Thêm cột cost_price vào bảng products
-- Ngày: 22/12/2025  
-- Người thực hiện: Danh (Người 3)

-- Backup database trước khi chạy migration này!
-- phpMyAdmin: Export -> SQL -> Lưu file backup

-- 1. Thêm cột cost_price (giá vốn)
ALTER TABLE `products` 
ADD COLUMN `cost_price` DECIMAL(10,2) DEFAULT 0 
AFTER `original_price`;

-- 2. Cập nhật cost_price = original_price (tạm thời)
-- Để có dữ liệu ban đầu
UPDATE `products` 
SET `cost_price` = `original_price`;

-- 3. Xem kết quả
SELECT id, name, price, original_price, cost_price, stock 
FROM products 
LIMIT 10;

-- Lưu ý:
-- - original_price: Giá gốc (để hiển thị so sánh)
-- - cost_price: Giá vốn thực tế (để tính lợi nhuận)
-- - price: Giá bán

-- Công thức lợi nhuận:
-- Lợi nhuận 1 món = quantity × (price - cost_price)
