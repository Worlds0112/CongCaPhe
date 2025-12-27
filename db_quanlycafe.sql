-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 27, 2025 at 04:39 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `db_quanlycafe`
--

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`) VALUES
(1, 'Chè đổi mới'),
(2, 'Đặc sản tại Cộng'),
(3, 'Cà phê Việt Nam'),
(4, 'Trà shan tuyết'),
(5, 'Đồ uống địa phương'),
(6, 'Trái cây - Tươi trẻ'),
(7, 'Sữa chua tuyết'),
(8, 'Topping'),
(9, 'Đồ ăn chơi');

-- --------------------------------------------------------

--
-- Table structure for table `funds`
--

CREATE TABLE `funds` (
  `id` int(11) NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `note` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `funds`
--

INSERT INTO `funds` (`id`, `amount`, `note`, `created_at`) VALUES
(1, 30000000.00, '', '2025-12-27 01:50:56'),
(2, 100000000.00, '', '2025-12-27 02:57:59'),
(3, 1.00, '', '2025-12-27 03:11:12');

-- --------------------------------------------------------

--
-- Table structure for table `inventory_history`
--

CREATE TABLE `inventory_history` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `note` text DEFAULT NULL,
  `import_price` decimal(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `inventory_history`
--

INSERT INTO `inventory_history` (`id`, `product_id`, `quantity`, `created_at`, `note`, `import_price`) VALUES
(1, 45, 38, '2025-12-09 15:27:32', 'Cập nhật thủ công (Sửa sản phẩm)', 8000.00),
(2, 45, 38, '2025-12-09 15:30:16', 'Cập nhật thủ công (Sửa sản phẩm)', 8000.00),
(3, 45, 38, '2025-12-09 15:32:32', 'Cập nhật thủ công (Sửa sản phẩm)', 8000.00),
(4, 42, 5, '2025-12-09 15:34:08', 'Cập nhật thủ công (Sửa sản phẩm)', 8000.00),
(5, 32, 14, '2025-12-09 15:34:33', 'Cập nhật thủ công (Sửa sản phẩm)', 20000.00),
(6, 31, 18, '2025-12-11 12:57:05', 'Cập nhật thủ công (Sửa sản phẩm)', 16500.00),
(7, 42, 11, '2025-12-11 12:57:27', 'Cập nhật thủ công (Sửa sản phẩm)', 8000.00),
(8, 24, -1, '2025-12-11 15:04:37', 'Bán hàng - Đơn #27', 0.00),
(9, 24, -1, '2025-12-11 15:04:37', 'Bán hàng - Đơn #27', 0.00),
(10, 4, -1, '2025-12-11 15:05:06', 'Bán hàng - Đơn #28', 20000.00),
(11, 30, 4, '2025-12-11 15:06:10', 'Nhập hàng nhanh', 0.00),
(12, 8, 2, '2025-12-11 15:06:10', 'Nhập hàng nhanh', 11000.00),
(13, 18, 7, '2025-12-11 15:06:10', 'Nhập hàng nhanh', 16500.00),
(14, 25, 16, '2025-12-15 05:37:02', 'Nhập hàng nhanh', 17000.00),
(15, 27, 13, '2025-12-15 05:37:05', 'Nhập hàng nhanh', 11000.00),
(16, 15, 15, '2025-12-15 05:37:09', 'Nhập hàng nhanh', 16500.00),
(17, 11, 10, '2025-12-15 05:37:14', 'Nhập hàng nhanh', 11000.00),
(18, 23, -1, '2025-12-15 05:37:57', 'Bán hàng - Đơn #29', 0.00),
(19, 39, -1, '2025-12-15 05:38:04', 'Bán hàng - Đơn #30', 800.00),
(20, 42, -1, '2025-12-15 05:38:24', 'Bán hàng - Đơn #31', 8000.00),
(21, 41, -1, '2025-12-15 05:38:24', 'Bán hàng - Đơn #31', 7000.00),
(22, 24, -1, '2025-12-15 05:38:24', 'Bán hàng - Đơn #31', 0.00),
(23, 46, -1, '2025-12-22 01:46:26', 'Bán hàng - Đơn #32', 16500.00),
(24, 22, 11, '2025-12-22 01:53:20', 'Nhập hàng nhanh', 0.00),
(25, 25, 50, '2025-12-22 01:54:52', 'Nhập hàng nhanh', 17000.00),
(26, 27, 50, '2025-12-22 01:54:52', 'Nhập hàng nhanh', 11000.00),
(27, 46, -1, '2025-12-27 00:39:40', 'Bán hàng - Đơn #33', 16500.00),
(30, 46, -1, '2025-12-27 00:46:58', 'Bán hàng - Đơn #36', 16500.00),
(31, 50, -1, '2025-12-27 00:46:58', 'Topping (kèm đơn #36)', 0.00),
(32, 51, -1, '2025-12-27 00:46:58', 'Topping (kèm đơn #36)', 0.00),
(33, 46, -1, '2025-12-27 01:06:26', 'Bán hàng - Đơn #37', 16500.00),
(34, 51, -1, '2025-12-27 01:06:26', 'Topping (kèm đơn #37)', 0.00),
(35, 50, -1, '2025-12-27 01:06:26', 'Topping (kèm đơn #37)', 0.00),
(36, 39, 100, '2025-12-27 01:16:12', 'Nhập hàng nhanh', 800.00),
(37, 38, 951, '2025-12-27 01:17:04', 'Nhập hàng nhanh', 800.00),
(38, 39, 852, '2025-12-27 01:17:04', 'Nhập hàng nhanh', 800.00),
(39, 47, 10, '2025-12-27 01:34:12', '', 16500.00);

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL COMMENT 'Nhân viên tạo đơn',
  `order_date` datetime NOT NULL DEFAULT current_timestamp(),
  `total_amount` int(11) NOT NULL,
  `total_cost` decimal(15,0) NOT NULL DEFAULT 0,
  `status` enum('pending','paid','cancelled') NOT NULL DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `order_date`, `total_amount`, `total_cost`, `status`) VALUES
(1, 1, '2025-11-16 01:49:57', 55000, 0, 'paid'),
(2, 1, '2025-11-16 01:50:08', 366000, 0, 'paid'),
(3, 2, '2025-11-17 09:10:28', 55000, 0, 'paid'),
(4, 1, '2025-11-17 10:28:43', 165000, 0, 'paid'),
(6, 1, '2025-11-17 11:08:25', 359000, 0, 'paid'),
(8, 1, '2025-12-01 10:11:21', 319000, 0, 'paid'),
(9, 1, '2025-12-01 10:11:35', 456000, 0, 'paid'),
(10, 1, '2025-12-01 10:11:44', 69000, 0, 'paid'),
(11, 1, '2025-12-01 10:11:47', 49000, 0, 'paid'),
(12, 1, '2025-12-01 10:11:50', 35000, 0, 'paid'),
(13, 1, '2025-12-09 14:01:05', 130000, 0, 'paid'),
(14, 8, '2025-12-09 20:36:22', 119000, 0, 'paid'),
(15, 8, '2025-12-09 20:36:46', 258000, 0, 'paid'),
(16, 6, '2025-12-10 10:34:10', 129000, 0, 'paid'),
(17, 5, '2025-12-11 19:55:06', 114000, 0, 'paid'),
(18, 5, '2025-12-11 19:55:26', 224000, 0, 'paid'),
(27, 5, '2025-12-11 22:04:37', 137000, 0, 'paid'),
(28, 5, '2025-12-11 22:05:06', 96000, 0, 'paid'),
(29, 7, '2025-12-15 12:37:57', 112000, 0, 'paid'),
(30, 7, '2025-12-15 12:38:04', 14000, 0, 'paid'),
(31, 7, '2025-12-15 12:38:23', 128000, 0, 'paid'),
(32, 1, '2025-12-22 08:46:26', 55000, 0, 'paid'),
(33, 1, '2025-12-27 07:39:40', 80000, 0, 'paid'),
(36, 1, '2025-12-27 07:46:58', 72000, 0, 'paid'),
(37, 1, '2025-12-27 08:06:26', 72000, 0, 'paid');

-- --------------------------------------------------------

--
-- Table structure for table `order_details`
--

CREATE TABLE `order_details` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` int(11) NOT NULL COMMENT 'Giá tại thời điểm bán',
  `note` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `order_details`
--

INSERT INTO `order_details` (`id`, `order_id`, `product_id`, `quantity`, `price`, `note`) VALUES
(1, 1, 46, 1, 55000, NULL),
(2, 2, 15, 1, 55000, NULL),
(3, 2, 25, 1, 59000, NULL),
(4, 2, 27, 1, 49000, NULL),
(5, 2, 38, 1, 9000, NULL),
(6, 2, 41, 1, 29000, NULL),
(7, 2, 46, 2, 55000, NULL),
(8, 2, 47, 1, 55000, NULL),
(9, 3, 46, 1, 55000, NULL),
(10, 4, 46, 3, 55000, NULL),
(45, 6, 9, 3, 45000, NULL),
(46, 6, 11, 1, 49000, NULL),
(47, 6, 23, 1, 65000, NULL),
(48, 6, 47, 2, 55000, NULL),
(56, 8, 6, 1, 69000, NULL),
(57, 8, 22, 1, 65000, NULL),
(58, 8, 23, 2, 65000, NULL),
(59, 8, 47, 1, 55000, NULL),
(60, 9, 5, 1, 69000, NULL),
(61, 9, 14, 1, 35000, NULL),
(62, 9, 16, 1, 55000, NULL),
(63, 9, 18, 1, 55000, NULL),
(64, 9, 25, 1, 59000, NULL),
(65, 9, 27, 1, 49000, NULL),
(66, 9, 30, 1, 65000, NULL),
(67, 9, 33, 1, 69000, NULL),
(68, 10, 4, 1, 69000, NULL),
(69, 11, 10, 1, 49000, NULL),
(70, 12, 12, 1, 35000, NULL),
(71, 13, 22, 1, 65000, NULL),
(72, 13, 23, 1, 65000, NULL),
(73, 14, 11, 1, 49000, NULL),
(74, 14, 12, 1, 35000, NULL),
(75, 14, 13, 1, 35000, NULL),
(76, 15, 22, 1, 65000, NULL),
(77, 15, 31, 1, 55000, NULL),
(78, 15, 32, 1, 69000, NULL),
(79, 15, 33, 1, 69000, NULL),
(80, 16, 7, 1, 45000, NULL),
(81, 16, 11, 1, 49000, NULL),
(82, 16, 13, 1, 35000, NULL),
(83, 17, 20, 1, 49000, NULL),
(84, 17, 23, 1, 65000, NULL),
(85, 18, 5, 1, 69000, NULL),
(86, 18, 6, 1, 69000, NULL),
(87, 18, 38, 1, 9000, NULL),
(88, 18, 39, 1, 9000, NULL),
(89, 18, 42, 1, 39000, NULL),
(90, 18, 43, 1, 29000, NULL),
(91, 27, 24, 1, 55000, 'Size: M, Đá: 100%'),
(92, 27, 24, 1, 82000, 'Size: L, Đá: 70%, Topping: Pudding trứng, Hướng dương'),
(93, 28, 4, 1, 96000, 'Size: L, Đá: 30%, Topping: Pudding trứng, Hướng dương'),
(94, 29, 23, 1, 112000, 'Size: L, Đá: 30%, Topping: Trân châu đen, Thạch trái cây, Pudding trứng, Bánh flan, Hướng dương'),
(95, 30, 39, 1, 14000, 'Size: L, Đá: 100%'),
(96, 31, 42, 1, 39000, 'Size: M, Đá: 100%'),
(97, 31, 41, 1, 29000, 'Size: M, Đá: 100%'),
(98, 31, 24, 1, 60000, 'Size: L, Đá: 100%'),
(99, 32, 46, 1, 55000, 'Size: M, Đá: 100%'),
(100, 33, 46, 1, 80000, 'Size: M, Đá: 100%, Topping: Bánh flan, Hướng dương'),
(103, 36, 46, 1, 72000, 'Size: M, Đá: 100%, Topping: Pudding trứng, Bánh flan'),
(104, 37, 46, 1, 72000, 'Size: M, Đá: 100%, Topping: Bánh flan, Pudding trứng');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `price` int(11) NOT NULL,
  `original_price` decimal(10,2) DEFAULT 0.00,
  `stock` int(11) NOT NULL,
  `image` varchar(255) DEFAULT 'default.jpg',
  `description` text DEFAULT NULL,
  `is_locked` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `category_id`, `name`, `price`, `original_price`, `stock`, `image`, `description`, `is_locked`) VALUES
(3, 2, 'Cốt dừa cà phê', 59000, 17000.00, 20, '1763229459_cotduacaphe.jpg', '', 0),
(4, 2, 'Cốt dừa gốm xanh', 69000, 20000.00, 15, '1763229450_cotduadauxanh.jpg', '', 0),
(5, 2, 'Cốt dừa đậu xanh', 69000, 20000.00, 2, '1763229442_cotduadauxanh.jpg', '', 0),
(6, 2, 'Cốt dừa cacao', 69000, 20000.00, 13, '1763229434_cotduacacao.jpg', '', 0),
(7, 3, 'Bạc xỉu', 45000, 10500.00, 19, '1763229421_bacxiu.jpg', '', 0),
(8, 3, 'Nâu kem muối', 49000, 11000.00, 16, '1763229411_Nau-kem_600x600px.png', '', 0),
(9, 3, 'Sữa đá Sài Gòn', 45000, 10500.00, 14, '1763229404_sua-da-sai-gon_lon.jpg', '', 0),
(10, 3, 'Vina-Cappu', 49000, 11000.00, 9, '1763229395_vina-cappu.jpg', '', 0),
(11, 3, 'Vina-Latte', 49000, 11000.00, 19, '1763229385_vina-latte.jpg', '', 0),
(12, 3, 'Vina-Cano', 35000, 7500.00, 18, '1763229379_vinacano.jpg', '', 0),
(13, 3, 'Đen', 35000, 7500.00, 22, '1763229368_den-da_lon.jpg', '', 0),
(14, 3, 'Nâu', 35000, 7500.00, 21, '1763229360_nau-da_lon.jpg', '', 0),
(15, 4, 'Shan kem gừng', 55000, 16500.00, 30, '1763229342_shankemgung.jpg', '', 0),
(16, 4, 'Shan nhài sữa', 55000, 16500.00, 14, '1763229333_shannhaisua.jpg', '', 1),
(17, 4, 'Shan gừng tươi', 39000, 8000.00, 19, '1763229324_shangungtuoi.jpg', '', 0),
(18, 4, 'Shan nhài chanh leo', 55000, 16500.00, 18, '1763229312_tra-tac-chanh-leo.jpg', '', 0),
(19, 4, 'Shan cam quế', 49000, 11000.00, 17, '1763229303_tra-cam-que_da.jpg', '', 0),
(20, 4, 'Shan quất mật ong', 49000, 11000.00, 17, '1763229283_tra-quat-mat-ong_da.jpg', '', 0),
(21, 4, 'Shan phê trà', 39000, 8000.00, 22, '1763229268_tra-phe-da.jpg', '', 0),
(22, 5, 'Cacao quế', 65000, 0.00, 15, '1763229252_cacao-que_nong.jpg', '', 0),
(23, 5, 'Hà Nội sấu soda', 65000, 0.00, 15, '1763229245_hanoi-sau-soda.jpg', NULL, 0),
(24, 5, 'Sài Gòn tắc xí muội', 55000, 0.00, 12, '1763229235_sai-gon-tac-xi-muoi.jpg', NULL, 0),
(25, 6, 'Atiso hồng mơ', 59000, 17000.00, 80, '1763229197_atisohongmo.png', '', 1),
(26, 6, 'Chanh leo tuyết', 69000, 20000.00, 12, '1763229207_Passion-Fruit-Freeze.jpg', '', 0),
(27, 6, 'Atiso muối mơ', 49000, 11000.00, 80, '1763229179_atisomuoimo.png', '', 0),
(28, 6, 'Cóc xanh theo mùa', 65000, 0.00, 10, '1763229166_coc-xanh.jpg', NULL, 0),
(29, 6, 'Chanh tươi', 45000, 10500.00, 20, '1763229143_chanh-tuoi.jpg', '', 0),
(30, 6, 'Dưa hấu', 65000, 0.00, 18, '1763229134_dua-hau.jpg', NULL, 0),
(31, 6, 'Chanh leo', 55000, 16500.00, 20, '1763229125_chanh-leo.jpg', '', 0),
(32, 6, 'Cam', 69000, 20000.00, 25, '1763229119_cam.jpg', '', 0),
(33, 6, 'Sinh tố xoài', 69000, 20000.00, 30, '1763229110_sinh-to-xoai.jpg', '', 0),
(34, 7, 'Sữa chua tuyết trái cây', 69000, 20000.00, 12, '1763229050_sua-chua-hoa-qua-.png', '', 1),
(35, 7, 'Sữa chua cacao', 55000, 16500.00, 15, '1763229035_sua-chua-cacao-.png', '', 0),
(36, 7, 'Sữa chua cà phê', 55000, 16500.00, 15, '1763229025_sua-chua-caphe-1.png', '', 0),
(37, 7, 'Sữa chua tuyết', 49000, 11000.00, 20, '1763229013_sua-chua-tuyet-jh.png', '', 0),
(38, 8, 'Kem muối', 9000, 800.00, 1000, '1763228992_Macchiato.png', '', 0),
(39, 8, 'Thạch trà', 9000, 800.00, 1000, '1763228983_thach-tra.jpg', '', 0),
(40, 9, 'Xoài sấy déo', 39000, 8000.00, 20, '1763228940_xoai-say-deo.jpg', '', 0),
(41, 9, 'Hạt hướng dương', 29000, 7000.00, 29, '1763228930_huong-duong.jpg', '', 0),
(42, 9, 'Mì tôm trứng', 39000, 8000.00, 29, '1763228919-Instant-Noodles-w.-Egg.jpg', '', 0),
(43, 9, 'Ngô cay', 29000, 7000.00, 24, '1763228911_ngo-cay.jpg', '', 0),
(44, 9, 'Bánh sừng bò chấm sữa', 30000, 7000.00, 20, '1763228898_banh_sung_bo.jpg', '', 0),
(45, 9, 'Thịt bò khô', 39000, 8000.00, 50, '1763228883_bokho.jpg', '', 0),
(46, 1, 'Chè thạch tổng hợp', 55000, 16500.00, 16, '1763228288_chethachtonghop.jpg', '', 0),
(47, 1, 'Chè xoài đổi mới', 55000, 16500.00, 30, '1763228355_chexoaidoimoi.jpg', '', 1),
(51, 8, 'Bánh flan', 10000, 0.00, 998, '694f323dcbe39.jpg', 'Topping thêm', 0),
(52, 8, 'Trân châu đen', 5000, 0.00, 1000, '694f322d9109c.jpg', 'Topping thêm', 0);

-- --------------------------------------------------------

--
-- Table structure for table `shift_handovers`
--

CREATE TABLE `shift_handovers` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `note` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `shift_reports`
--

CREATE TABLE `shift_reports` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `shift_code` varchar(20) NOT NULL,
  `report_date` date NOT NULL,
  `system_revenue` decimal(10,2) DEFAULT 0.00,
  `real_cash` decimal(10,2) DEFAULT 0.00,
  `difference` decimal(10,2) DEFAULT 0.00,
  `inventory_notes` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `shift_reports`
--

INSERT INTO `shift_reports` (`id`, `user_id`, `shift_code`, `report_date`, `system_revenue`, `real_cash`, `difference`, `inventory_notes`, `notes`, `created_at`) VALUES
(1, 0, 'chieu', '2025-12-09', 130000.00, 130000.00, 0.00, NULL, 'Hệ thống tự động chốt do nhân viên quên quá 1 tiếng.', '2025-12-09 11:00:00'),
(2, 8, 'toi', '2025-12-09', 377000.00, 377.00, -376623.00, 'Đủ nha', 'Cố lên', '2025-12-09 16:28:53'),
(3, 0, 'sang', '2025-12-10', 129000.00, 129000.00, 0.00, NULL, 'Hệ thống tự động chốt do nhân viên quên quá 1 tiếng.', '2025-12-10 05:00:00'),
(4, 5, 'toi', '2025-12-11', 571000.00, 571000.00, 0.00, 'đủ', 'ok rồi', '2025-12-11 15:08:43'),
(5, 9, 'sang', '2025-12-22', 309000.00, 309000.00, 0.00, 'no', '', '2025-12-22 03:32:08'),
(6, 1, 'sang', '2025-12-27', 224000.00, 224000.00, 0.00, NULL, 'Hệ thống tự động chốt (Quá hạn 15p). NV cuối cùng bán hàng.', '2025-12-27 05:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL COMMENT 'Lưu mật khẩu đã hash',
  `full_name` varchar(255) DEFAULT NULL,
  `role` enum('admin','staff') NOT NULL DEFAULT 'staff',
  `security_code` varchar(50) DEFAULT NULL,
  `shift` varchar(20) DEFAULT 'full',
  `gender` varchar(10) DEFAULT 'Khác',
  `birth_year` int(11) DEFAULT 2000,
  `phone` varchar(20) DEFAULT '',
  `address` text DEFAULT '',
  `avatar` varchar(255) DEFAULT 'default_user.png'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `role`, `security_code`, `shift`, `gender`, `birth_year`, `phone`, `address`, `avatar`) VALUES
(1, 'admin', '$2y$10$dpvf1zUJWOu3vCSUIuomXOllPuKINKIOVD72xzN3OZmr57vmyjsxS', 'Vũ Thành An', 'admin', '12345', 'full', 'Nam', 2004, '', '', 'user_1765201203_images (1).jpg'),
(2, 'nhanvien', '$2y$10$dfHsbL4zK7PnOH5xTKO4TeYvtt3Ngpkz70DSGCN/BH3n8/2fnrxgS', 'Nhân Viên Bán Hàng', 'staff', '1', 'full', 'Khác', 2000, '', '', 'default_user.png'),
(3, 'LeHuan', '$2y$10$GKNETPmOnoWdQ4eijYomMuynmieW4usMBh/TPRwaQuffQCx.iC0aG', 'Lê Văn Huấn', 'staff', '1', 'sang', 'Nam', 2004, '', '', 'default_user.png'),
(4, 'danh', '$2y$10$5MpOT1J9ndJ3XW0xHg.ooulCT0EpblPkgxtRe6Pjjq11oyq1MKqaS', 'Vũ Công Danh', 'staff', '1', 'chieu', 'Nam', 2004, '', '', 'default_user.png'),
(5, 'hanh', '$2y$10$eah0W27nZN9pLPEdeZHnL.15By0yIeIfxd4PeGOy6FpUfHSSAmYAS', 'Phạm Hải Anh', 'staff', '1', 'toi', 'Nữ', 2004, '', '', 'default_user.png'),
(6, 'mau', '$2y$10$moebSqFatyqsZ7BbEvMRmejx/xDhf.ptfWJl.TZB.EU1djw2HxkYC', 'Nguyễn Mậu Hải Anh', 'staff', '1', 'sang', 'Nam', 2004, '', '', 'default_user.png'),
(7, 'mark', '$2y$10$KlAcP5XIq03S6IbkY9DD3eh075A5tazQ2I1lBvCug5iVFL4zeryOK', 'Lê Vũ Huy', 'staff', '1', 'chieu', 'Nam', 2004, '', '', 'default_user.png'),
(8, 'nghia', '$2y$10$9.kUs.nszpK4IwmKGppnZOh1K38RfHO67Y0ZKnMKHtbJaxwixx3lW', 'Nguyễn Huy Nghĩa', 'staff', '1', 'toi', 'Nam', 2004, '', '', 'default_user.png'),
(9, 'huan', '$2y$10$jf1FsGC4CU6IKl.XMaTVD.NgzeZYtKYGIjZWrk38v4DQSbowlWqiy', 'Lê Văn Huấn', 'staff', '1', 'sang', 'Nam', 2000, '', '', 'default_user.png'),
(10, 'vuan', '$2y$10$gFzYVFHlkdIyhsJEs5fKm.d1yPt8RyifFP5b0.3Sb4hd6CqdoBPYy', 'vu thanh an', 'staff', '1', 'full', 'Nam', 2000, '0362493463', '', 'user_1766366827_69489e6b1f3ef.jpg');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `funds`
--
ALTER TABLE `funds`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `inventory_history`
--
ALTER TABLE `inventory_history`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `order_details`
--
ALTER TABLE `order_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `shift_handovers`
--
ALTER TABLE `shift_handovers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `shift_reports`
--
ALTER TABLE `shift_reports`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `funds`
--
ALTER TABLE `funds`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `inventory_history`
--
ALTER TABLE `inventory_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT for table `order_details`
--
ALTER TABLE `order_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=105;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=55;

--
-- AUTO_INCREMENT for table `shift_handovers`
--
ALTER TABLE `shift_handovers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `shift_reports`
--
ALTER TABLE `shift_reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE NO ACTION ON UPDATE CASCADE;

--
-- Constraints for table `order_details`
--
ALTER TABLE `order_details`
  ADD CONSTRAINT `order_details_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `order_details_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE NO ACTION ON UPDATE CASCADE;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
