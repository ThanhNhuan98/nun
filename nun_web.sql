-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Máy chủ: 127.0.0.1
-- Thời gian đã tạo: Th5 02, 2026 lúc 06:42 PM
-- Phiên bản máy phục vụ: 10.4.32-MariaDB
-- Phiên bản PHP: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Cơ sở dữ liệu: `nun_web`
--

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `driver_penalties`
--

CREATE TABLE `driver_penalties` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `driver_id` bigint(20) UNSIGNED NOT NULL,
  `penalty_type` enum('cancellation','late_delivery','no_response','customer_complaint','traffic_violation') NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `reason` varchar(500) NOT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `is_paid` tinyint(1) NOT NULL DEFAULT 0,
  `paid_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `driver_profiles`
--

CREATE TABLE `driver_profiles` (
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `vehicle_type` enum('bike','motorbike','car','van') NOT NULL DEFAULT 'motorbike',
  `license_plate` varchar(30) DEFAULT NULL,
  `current_lat` decimal(10,7) DEFAULT NULL,
  `current_lng` decimal(10,7) DEFAULT NULL,
  `is_online` tinyint(1) NOT NULL DEFAULT 0,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'approved',
  `rating_avg` decimal(3,2) NOT NULL DEFAULT 0.00,
  `balance` decimal(12,2) NOT NULL DEFAULT 0.00,
  `max_concurrent_orders` tinyint(3) UNSIGNED NOT NULL DEFAULT 3,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `max_total_weight` decimal(8,2) NOT NULL DEFAULT 100.00 COMMENT 'Maximum total weight driver can carry (kg)',
  `current_load` decimal(8,2) NOT NULL DEFAULT 0.00 COMMENT 'Current total weight being carried (kg)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `driver_profiles`
--

INSERT INTO `driver_profiles` (`user_id`, `vehicle_type`, `license_plate`, `current_lat`, `current_lng`, `is_online`, `status`, `rating_avg`, `balance`, `max_concurrent_orders`, `created_at`, `updated_at`, `max_total_weight`, `current_load`) VALUES
(3, 'motorbike', NULL, 16.5624219, 107.5570046, 1, 'approved', 4.80, 11735925.00, 3, '2026-05-02 09:59:59', '2026-05-02 13:07:02', 100.00, 0.00),
(9, 'motorbike', NULL, 16.4591012, 107.5815904, 1, 'approved', 5.00, 150741.00, 3, '2026-05-02 09:59:59', '2026-05-02 09:59:59', 100.00, 0.00);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `driver_reviews`
--

CREATE TABLE `driver_reviews` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `order_id` bigint(20) UNSIGNED NOT NULL,
  `customer_id` bigint(20) UNSIGNED NOT NULL,
  `driver_id` bigint(20) UNSIGNED NOT NULL,
  `rating` tinyint(3) UNSIGNED NOT NULL,
  `comment` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ;

--
-- Đang đổ dữ liệu cho bảng `driver_reviews`
--

INSERT INTO `driver_reviews` (`id`, `order_id`, `customer_id`, `driver_id`, `rating`, `comment`, `created_at`) VALUES
(1, 18, 1, 3, 5, '', '2026-04-01 20:06:37'),
(2, 22, 1, 3, 5, 'fbvdfb', '2026-04-01 20:16:34'),
(3, 23, 1, 3, 5, '', '2026-04-01 23:47:16'),
(4, 20, 1, 3, 1, '', '2026-04-01 23:52:30'),
(5, 19, 1, 3, 5, '', '2026-04-01 23:55:45'),
(6, 24, 1, 3, 5, '', '2026-04-02 00:00:04'),
(7, 34, 1, 3, 5, '', '2026-04-02 22:11:20'),
(8, 27, 1, 3, 5, '', '2026-04-02 23:16:50'),
(9, 38, 1, 9, 5, 'Giao hàng đúng hẹn, Tài xế thân thiện, Hàng hóa nguyên vẹn, Lái xe an toàn', '2026-04-05 06:19:05'),
(10, 35, 1, 3, 5, '', '2026-04-12 01:36:38'),
(11, 33, 1, 3, 5, '', '2026-04-19 06:15:08'),
(12, 32, 1, 3, 5, '', '2026-04-19 06:15:15'),
(13, 50, 1, 3, 5, '', '2026-04-19 16:51:29'),
(14, 43, 1, 3, 5, '', '2026-04-24 07:25:14'),
(15, 55, 1, 3, 5, '', '2026-04-24 16:23:25'),
(16, 45, 1, 3, 5, 'Giao hàng cực nhanh, Tài xế thân thiện, Hàng hóa nguyên vẹn, Lái xe an toàn, Hỗ trợ nhiệt tình', '2026-04-25 05:19:29'),
(17, 61, 1, 3, 5, 'Giao hàng cực nhanh, Tài xế thân thiện, Lái xe an toàn', '2026-04-25 05:19:56'),
(18, 60, 1, 3, 5, 'Giao hàng cực nhanh, Tài xế thân thiện, Hàng hóa nguyên vẹn, Lái xe an toàn, Hỗ trợ nhiệt tình', '2026-04-25 05:20:20'),
(19, 42, 1, 3, 4, 'Tài xế thân thiện, Giao đúng giờ, Bảo quản hàng cẩn thận', '2026-05-01 10:15:02');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `notifications`
--

CREATE TABLE `notifications` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(190) NOT NULL,
  `message` text NOT NULL,
  `type` varchar(40) NOT NULL DEFAULT 'system',
  `link` varchar(500) DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `title`, `message`, `type`, `link`, `is_read`, `created_at`) VALUES
(1, 3, 'Nhan don thanh cong', 'Ban vua nhan don NUN177699415736 va co the bat dau giao hang.', 'order_assigned', '/driver/tracking?id=57', 1, '2026-04-24 02:19:22'),
(2, 1, 'Don hang da duoc tao', 'Don NUN177699766216 da duoc khoi tao va dang cho he thong dieu phoi tai xe.', 'order_created', '/user/tracking?id=58', 1, '2026-04-24 02:27:42'),
(3, 3, 'Nhan don thanh cong', 'Ban vua nhan don NUN177699766216 va co the bat dau giao hang.', 'order_assigned', '/driver/tracking?id=58', 1, '2026-04-24 02:40:44'),
(4, 4, 'Don da co tai xe nhan', 'Don NUN177699766216 da duoc tai xe nhan va san sang xu ly.', 'driver_assigned', '/admin/order-detail?id=58', 1, '2026-04-24 02:40:44'),
(5, 1, 'Don da co tai xe nhan', 'Don NUN177699766216 da duoc tai xe nhan va dang cho lay hang.', 'driver_assigned', '/user/tracking?id=58', 1, '2026-04-24 02:40:44'),
(6, 1, 'Don hang da duoc tao', 'Don NUN177699967495 da duoc khoi tao va dang cho he thong dieu phoi tai xe.', 'order_created', '/user/tracking?id=59', 1, '2026-04-24 03:01:14'),
(7, 4, 'Don moi cho tai xe', 'Don NUN177699967495 vua duoc tao va dang cho tai xe nhan.', 'order_created', '/admin/order-detail?id=59', 1, '2026-04-24 03:01:14'),
(8, 3, 'Nhan don thanh cong', 'Ban vua nhan don NUN177699967495 va co the bat dau giao hang.', 'order_assigned', '/driver/tracking?id=59', 1, '2026-04-24 03:02:32'),
(9, 4, 'Don da co tai xe nhan', 'Don NUN177699967495 da duoc tai xe nhan va san sang xu ly.', 'driver_assigned', '/admin/order-detail?id=59', 1, '2026-04-24 03:02:32'),
(10, 1, 'Don da co tai xe nhan', 'Don NUN177699967495 da duoc tai xe nhan va dang cho lay hang.', 'driver_assigned', '/user/tracking?id=59', 1, '2026-04-24 03:02:32'),
(11, 3, 'Nhan don thanh cong', 'Ban vua nhan don NUN177699415133 va co the bat dau giao hang.', 'order_assigned', '/driver/tracking?id=56', 1, '2026-04-24 13:37:43'),
(12, 4, 'Don da co tai xe nhan', 'Don NUN177699415133 da duoc tai xe nhan va san sang xu ly.', 'driver_assigned', '/admin/order-detail?id=56', 1, '2026-04-24 13:37:43'),
(13, 1, 'Don hang dang giao', 'Don NUN177699766216 dang duoc giao den nguoi nhan.', 'order_shipping', '/user/tracking?id=58', 1, '2026-04-24 13:41:45'),
(14, 1, 'Don hang da hoan thanh', 'Don NUN177699766216 da giao thanh cong. Ban co the danh gia tai xe trong lich su.', 'order_completed', '/user/review?id=58', 1, '2026-04-24 13:41:48'),
(15, 1, 'Don hang dang giao', 'Don NUN177537763825 dang duoc giao den nguoi nhan.', 'order_shipping', '/user/tracking?id=42', 1, '2026-04-24 13:42:46'),
(16, 1, 'Don hang da hoan thanh', 'Don NUN177537763825 da giao thanh cong. Ban co thu danh gia tai xe trong lich su.', 'order_completed', '/user/review?id=42', 1, '2026-04-24 13:42:51'),
(17, 3, 'Yeu cau goi lai', 'Ban vua nhan duoc yeu cau lien he nhanh tu khach hang trong don NUN177537763825.', 'order_call', '/driver/tracking?id=42', 1, '2026-04-24 16:14:00'),
(18, 1, 'Don hang da duoc tao', 'Don NUN177708791288 da duoc khoi tao va dang cho he thong dieu phoi tai xe.', 'order_created', '/user/tracking?id=60', 1, '2026-04-25 03:31:53'),
(19, 4, 'Don moi cho tai xe', 'Don NUN177708791288 vua duoc tao va dang cho tai xe nhan.', 'order_created', '/admin/order-detail?id=60', 1, '2026-04-25 03:31:53'),
(20, 1, 'Don hang da duoc tao', 'Don NUN177708803559 da duoc khoi tao va dang cho ban xac nhan thanh toan online.', 'payment_pending', '/user/tracking?id=61', 1, '2026-04-25 03:33:56'),
(21, 1, 'Thanh toan thanh cong', 'Don NUN177708803559 da xac nhan thanh toan online va dang tim tai xe.', 'payment_paid', '/user/tracking?id=61', 1, '2026-04-25 03:34:28'),
(22, 4, 'Don moi cho tai xe', 'Don NUN177708803559 vua duoc tao va dang cho tai xe nhan.', 'order_created', '/admin/order-detail?id=61', 1, '2026-04-25 03:34:28'),
(23, 1, 'Don hang dang giao', 'Don NUN177557879349 dang duoc giao den nguoi nhan.', 'order_shipping', '/user/tracking?id=45', 1, '2026-04-25 03:37:04'),
(24, 1, 'Yeu cau goi lai', 'Ban vua nhan duoc yeu cau lien he nhanh tu tai xe trong don NUN177557879349.', 'order_call', '/user/tracking?id=45', 1, '2026-04-25 03:37:16'),
(25, 1, 'Don hang da hoan thanh', 'Don NUN177557879349 da giao thanh cong. Ban co the danh gia tai xe trong lich su.', 'order_completed', '/user/review?id=45', 1, '2026-04-25 03:38:31'),
(26, 1, 'Tin nhan noi bo moi', 'Ban co tin nhan moi trong don NUN177557879349.', 'order_chat', NULL, 1, '2026-04-25 03:38:44'),
(27, 4, 'Don hang co khieu nai', 'Don NUN177557879349 vua phat sinh khieu nai moi.', 'order_dispute', '/admin/order-detail?id=45', 1, '2026-04-25 03:40:15'),
(28, 1, 'Cap nhat xu ly khieu nai', 'Khieu nai cua don NUN177557879349 da duoc cap nhat: open.', 'order_dispute', NULL, 1, '2026-04-25 03:40:58'),
(29, 3, 'Cap nhat xu ly khieu nai', 'Khieu nai cua don NUN177557879349 da duoc cap nhat: open.', 'order_dispute', NULL, 1, '2026-04-25 03:40:58'),
(30, 1, 'Don hang dang giao', 'Don NUN177615819333 dang duoc giao den nguoi nhan.', 'order_shipping', '/user/tracking?id=46', 1, '2026-04-25 03:42:11'),
(31, 1, 'Don hang dang chuyen hoan', 'Don NUN177615819333 khong giao duoc va dang duoc chuyen hoan ve nguoi gui.', 'order_returning', '/user/tracking?id=46', 1, '2026-04-25 03:42:26'),
(32, 4, 'Don hang dang chuyen hoan', 'Don NUN177615819333 da chuyen sang luong hoan tra.', 'order_returning', '/admin/order-detail?id=46', 1, '2026-04-25 03:42:26'),
(33, 1, 'Yeu cau goi lai', 'Ban vua nhan duoc yeu cau lien he nhanh tu tai xe trong don NUN177615819333.', 'order_call', '/user/tracking?id=46', 1, '2026-04-25 03:42:42'),
(34, 1, 'Don hang da chuyen hoan', 'Don NUN177615819333 da duoc hoan ve nguoi gui.', 'order_returned', '/user/history', 1, '2026-04-25 03:42:46'),
(35, 4, 'Don hang da hoan ve', 'Don NUN177615819333 da ket thuc theo luong chuyen hoan.', 'order_returned', '/admin/order-detail?id=46', 1, '2026-04-25 03:42:46'),
(36, 1, 'Tai xe da huy don', 'Don NUN177518034971 da bi tai xe huy. Ly do: Xe gap su co/Tai nan.', 'order_cancelled', '/user/history', 1, '2026-04-25 03:43:07'),
(37, 1, 'Tai xe da huy don', 'Don NUN177518027955 da bi tai xe huy. Ly do: Xe gap su co/Tai nan.', 'order_cancelled', '/user/history', 1, '2026-04-25 03:43:35'),
(38, 1, 'Tai xe da huy don', 'Don NUN177518026165 da bi tai xe huy. Ly do: Xe gap su co/Tai nan.', 'order_cancelled', '/user/history', 1, '2026-04-25 03:44:58'),
(39, 1, 'Don hang da hoan thanh', 'Don NUN177511693075 da giao thanh cong. Ban co the danh gia tai xe trong lich su.', 'order_completed', '/user/review?id=28', 1, '2026-04-25 03:45:23'),
(40, 3, 'Nhan don thanh cong', 'Ban vua nhan don NUN177708803559 va co the bat dau giao hang.', 'order_assigned', '/driver/tracking?id=61', 1, '2026-04-25 03:45:49'),
(41, 4, 'Don da co tai xe nhan', 'Don NUN177708803559 da duoc tai xe nhan va san sang xu ly.', 'driver_assigned', '/admin/order-detail?id=61', 1, '2026-04-25 03:45:49'),
(42, 1, 'Don da co tai xe nhan', 'Don NUN177708803559 da duoc tai xe nhan va dang cho lay hang.', 'driver_assigned', '/user/tracking?id=61', 1, '2026-04-25 03:45:49'),
(43, 3, 'Nhan don thanh cong', 'Ban vua nhan don NUN177708791288 va co the bat dau giao hang.', 'order_assigned', '/driver/tracking?id=60', 1, '2026-04-25 03:45:58'),
(44, 4, 'Don da co tai xe nhan', 'Don NUN177708791288 da duoc tai xe nhan va san sang xu ly.', 'driver_assigned', '/admin/order-detail?id=60', 1, '2026-04-25 03:45:58'),
(45, 1, 'Don da co tai xe nhan', 'Don NUN177708791288 da duoc tai xe nhan va dang cho lay hang.', 'driver_assigned', '/user/tracking?id=60', 1, '2026-04-25 03:45:58'),
(46, 1, 'Yeu cau goi lai', 'Ban vua nhan duoc yeu cau lien he nhanh tu tai xe trong don NUN177708791288.', 'order_call', '/user/tracking?id=60', 1, '2026-04-25 03:46:14'),
(47, 1, 'Don hang dang giao', 'Don NUN177708791288 dang duoc giao den nguoi nhan.', 'order_shipping', '/user/tracking?id=60', 1, '2026-04-25 03:47:14'),
(48, 1, 'Don hang da hoan thanh', 'Don NUN177708791288 da giao thanh cong. Ban co the danh gia tai xe trong lich su.', 'order_completed', '/user/review?id=60', 1, '2026-04-25 03:47:28'),
(49, 1, 'Don hang dang giao', 'Don NUN177708803559 dang duoc giao den nguoi nhan.', 'order_shipping', '/user/tracking?id=61', 1, '2026-04-25 03:48:11'),
(50, 1, 'Don hang da hoan thanh', 'Don NUN177708803559 da giao thanh cong. Ban co the danh gia tai xe trong lich su.', 'order_completed', '/user/review?id=61', 1, '2026-04-25 03:48:21'),
(51, 1, 'Don hang da duoc tao', 'Don NUN177708904367 da duoc khoi tao va dang cho ban xac nhan thanh toan online.', 'payment_pending', '/user/tracking?id=62', 1, '2026-04-25 03:50:44'),
(52, 3, 'Yêu cầu gọi lại', 'Bạn vừa nhận được yêu cầu liên hệ nhanh từ khách hàng trong đơn NUN177477286127.', 'order_call', '/driver/tracking?id=18', 1, '2026-04-25 05:18:40'),
(53, 1, 'Tin nhắn nội bộ mới', 'Bạn có tin nhắn mới trong đơn NUN177477286127.', 'order_chat', NULL, 1, '2026-04-25 07:47:53'),
(54, 1, 'Đơn hàng đã được tạo', 'Đơn NUN177711007254 đã được khởi tạo và đang chờ hệ thống điều phối tài xế.', 'order_created', '/user/tracking?id=63', 1, '2026-04-25 09:41:13'),
(55, 4, 'Đơn mới cho tài xế', 'Đơn NUN177711007254 vừa được tạo và đang chờ tài xế nhận.', 'order_created', '/admin/order-detail?id=63', 1, '2026-04-25 09:41:13'),
(56, 1, 'Đơn hàng đã được tạo', 'Đơn NUN177711136985 đã được khởi tạo và đang chờ hệ thống điều phối tài xế.', 'order_created', '/user/tracking?id=64', 1, '2026-04-25 10:02:50'),
(57, 4, 'Đơn mới cho tài xế', 'Đơn NUN177711136985 vừa được tạo và đang chờ tài xế nhận.', 'order_created', '/admin/order-detail?id=64', 1, '2026-04-25 10:02:50'),
(58, 1, 'Đơn hàng đã được tạo', 'Đơn NUN177711146166 đã được khởi tạo và đang chờ hệ thống điều phối tài xế.', 'order_created', '/user/tracking?id=65', 1, '2026-04-25 10:04:21'),
(59, 4, 'Đơn mới cho tài xế', 'Đơn NUN177711146166 vừa được tạo và đang chờ tài xế nhận.', 'order_created', '/admin/order-detail?id=65', 1, '2026-04-25 10:04:21'),
(60, 3, 'Nhận đơn thành công', 'Bạn vừa nhận đơn NUN177711146166 và có thể bắt đầu giao hàng.', 'order_assigned', '/driver/tracking?id=65', 1, '2026-04-25 10:04:49'),
(61, 4, 'Đơn đã có tài xế nhận', 'Đơn NUN177711146166 đã được tài xế nhận và sẵn sàng xử lý.', 'driver_assigned', '/admin/order-detail?id=65', 1, '2026-04-25 10:04:49'),
(62, 1, 'Đơn đã có tài xế nhận', 'Đơn NUN177711146166 đã được tài xế nhận và đang chờ lấy hàng.', 'driver_assigned', '/user/tracking?id=65', 1, '2026-04-25 10:04:49'),
(63, 1, 'Đơn hàng đã được tạo', 'Đơn NUN177711159242 đã được khởi tạo và đang chờ hệ thống điều phối tài xế.', 'order_created', '/user/tracking?id=66', 1, '2026-04-25 10:06:33'),
(64, 4, 'Đơn mới cho tài xế', 'Đơn NUN177711159242 vừa được tạo và đang chờ tài xế nhận.', 'order_created', '/admin/order-detail?id=66', 1, '2026-04-25 10:06:33'),
(65, 1, 'Tài xế đã hủy đơn', 'Đơn NUN177711146166 đã bị tài xế hủy. Ly do: Xe gặp sự cố/Tai nạn.', 'order_cancelled', '/user/history', 1, '2026-04-25 10:45:50'),
(66, 3, 'Nhan don thanh cong', 'Ban vua nhan don NUN177711007254 va co the bat dau giao hang.', 'order_assigned', '/driver/tracking?id=63', 1, '2026-04-26 05:52:57'),
(67, 4, 'Don da co tai xe nhan', 'Don NUN177711007254 da duoc tai xe nhan va san sang xu ly.', 'driver_assigned', '/admin/order-detail?id=63', 1, '2026-04-26 05:52:57'),
(68, 1, 'Don da co tai xe nhan', 'Don NUN177711007254 da duoc tai xe nhan va dang cho lay hang.', 'driver_assigned', '/user/tracking?id=63', 1, '2026-04-26 05:52:57'),
(69, 1, 'Don hang da duoc tao', 'Don NUN177718371642 da duoc khoi tao va dang cho he thong dieu phoi tai xe.', 'order_created', '/user/tracking?id=67', 1, '2026-04-26 06:08:37'),
(70, 4, 'Don moi cho tai xe', 'Don NUN177718371642 vua duoc tao va dang cho tai xe nhan.', 'order_created', '/admin/order-detail?id=67', 1, '2026-04-26 06:08:37'),
(71, 6, 'Don hang da duoc tao', 'Don NUN177718376595 da duoc khoi tao va dang cho he thong dieu phoi tai xe.', 'order_created', '/user/tracking?id=68', 1, '2026-04-26 06:09:26'),
(72, 4, 'Don moi cho tai xe', 'Don NUN177718376595 vua duoc tao va dang cho tai xe nhan.', 'order_created', '/admin/order-detail?id=68', 1, '2026-04-26 06:09:26'),
(73, 6, 'Don hang da duoc tao', 'Don NUN177718383279 da duoc khoi tao va dang cho ban xac nhan thanh toan online.', 'payment_pending', '/user/tracking?id=69', 1, '2026-04-26 06:10:33'),
(74, 6, 'Thanh toan thanh cong', 'Don NUN177718383279 da xac nhan thanh toan online va dang tim tai xe.', 'payment_paid', '/user/tracking?id=69', 1, '2026-04-26 06:10:36'),
(75, 4, 'Don moi cho tai xe', 'Don NUN177718383279 vua duoc tao va dang cho tai xe nhan.', 'order_created', '/admin/order-detail?id=69', 1, '2026-04-26 06:10:36'),
(76, 9, 'Nhận đơn thành công', 'Bạn vừa nhận đơn NUN177718383279 và có thể bắt đầu giao hàng.', 'order_assigned', '/driver/tracking?id=69', 1, '2026-04-27 03:18:31'),
(77, 4, 'Đơn đã có tài xế nhận', 'Đơn NUN177718383279 đã được tài xế nhận và sẵn sàng xử lý.', 'driver_assigned', '/admin/order-detail?id=69', 1, '2026-04-27 03:18:31'),
(78, 6, 'Đơn đã có tài xế nhận', 'Đơn NUN177718383279 đã được tài xế nhận và đang chờ lấy hàng.', 'driver_assigned', '/user/tracking?id=69', 1, '2026-04-27 03:18:31'),
(79, 6, 'Đơn hàng đang giao', 'Đơn NUN177718383279 đang được giao đến người nhận.', 'order_shipping', '/user/tracking?id=69', 1, '2026-04-27 03:18:45'),
(80, 6, 'Đơn hàng đã hoàn thành', 'Đơn NUN177718383279 đã giao thành công. Bạn có thể đánh giá tài xế trong lịch sử.', 'order_completed', '/user/review?id=69', 1, '2026-04-27 03:19:03'),
(81, 1, 'Đơn hàng đã được tạo', 'Đơn NUN177727678569 đã được khởi tạo và đang chờ hệ thống điều phối tài xế.', 'order_created', '/user/tracking?id=73', 1, '2026-04-27 07:59:46'),
(82, 4, 'Đơn mới cho tài xế', 'Đơn NUN177727678569 vừa được tạo và đang chờ tài xế nhận.', 'order_created', '/admin/order-detail?id=73', 1, '2026-04-27 07:59:46'),
(83, 1, 'Đơn hàng đã được tạo', 'Đơn NUN177727684543 đã được khởi tạo và đang chờ bạn xác nhận thanh toán online.', 'payment_pending', '/user/tracking?id=74', 1, '2026-04-27 08:00:46'),
(84, 1, 'Thanh toán thành công', 'Đơn NUN177727684543 đã xác nhận thanh toán online và đang tìm tài xế.', 'payment_paid', '/user/tracking?id=74', 1, '2026-04-27 08:00:50'),
(85, 4, 'Đơn mới cho tài xế', 'Đơn NUN177727684543 vừa được tạo và đang chờ tài xế nhận.', 'order_created', '/admin/order-detail?id=74', 1, '2026-04-27 08:00:50'),
(86, 9, 'Nhận đơn thành công', 'Bạn vừa nhận đơn NUN177727684543 và có thể bắt đầu giao hàng.', 'order_assigned', '/driver/tracking?id=74', 1, '2026-04-28 01:40:06'),
(87, 4, 'Đơn đã có tài xế nhận', 'Đơn NUN177727684543 đã được tài xế nhận và sẵn sàng xử lý.', 'driver_assigned', '/admin/order-detail?id=74', 1, '2026-04-28 01:40:06'),
(88, 1, 'Đơn đã có tài xế nhận', 'Đơn NUN177727684543 đã được tài xế nhận và đang chờ lấy hàng.', 'driver_assigned', '/user/tracking?id=74', 1, '2026-04-28 01:40:06'),
(89, 1, 'Đã hủy đơn hàng', 'Đơn NUN177727684543 đã được hủy thành công. Lý do: Thay đổi ý định.', 'order_cancelled', '/user/orders', 1, '2026-04-28 02:41:33'),
(90, 9, 'Khách hàng đã hủy đơn', 'Đơn NUN177727684543 đã bị khách hàng hủy. Lý do: Thay đổi ý định.', 'order_cancelled', '/driver/history', 1, '2026-04-28 02:41:33'),
(91, 9, 'Tin nhắn nội bộ mới', 'Bạn có tin nhắn mới trong đơn NUN177727684543.', 'order_chat', NULL, 1, '2026-04-28 02:43:19'),
(92, 9, 'Yêu cầu gọi lại', 'Bạn vừa nhận được yêu cầu liên hệ nhanh từ khách hàng trong đơn NUN177727684543.', 'order_call', '/driver/tracking?id=74', 1, '2026-04-28 02:43:22'),
(93, 4, 'Đơn hàng có khiếu nại', 'Đơn NUN177727684543 vừa phát sinh khiếu nại mới.', 'order_dispute', '/admin/order-detail?id=74', 1, '2026-04-28 02:43:45'),
(94, 6, 'Đơn hàng đã được tạo', 'Đơn NUN177734768788 đã được khởi tạo và đang chờ bạn xác nhận thanh toán online.', 'payment_pending', '/user/tracking?id=75', 1, '2026-04-28 03:41:27'),
(95, 6, 'Thanh toán thành công', 'Đơn NUN177734768788 đã xác nhận thanh toán online và đang tìm tài xế.', 'payment_paid', '/user/tracking?id=75', 1, '2026-04-28 03:41:51'),
(96, 4, 'Đơn mới cho tài xế', 'Đơn NUN177734768788 vừa được tạo và đang chờ tài xế nhận.', 'order_created', '/admin/order-detail?id=75', 1, '2026-04-28 03:41:51'),
(97, 6, 'Đơn hàng đã được tạo', 'Đơn NUN177734847277 đã được khởi tạo và đang chờ bạn xác nhận thanh toán online.', 'payment_pending', '/user/tracking?id=76', 0, '2026-04-28 03:54:33'),
(98, 6, 'Thanh toán thành công', 'Đơn NUN177734847277 đã xác nhận thanh toán online và đang tìm tài xế.', 'payment_paid', '/user/tracking?id=76', 0, '2026-04-28 03:54:48'),
(99, 4, 'Đơn mới cho tài xế', 'Đơn NUN177734847277 vừa được tạo và đang chờ tài xế nhận.', 'order_created', '/admin/order-detail?id=76', 1, '2026-04-28 03:54:48'),
(100, 6, 'Đơn hàng đã được tạo', 'Đơn NUN177734873558 đã được khởi tạo và đang chờ bạn xác nhận thanh toán online.', 'payment_pending', '/user/tracking?id=77', 0, '2026-04-28 03:58:56'),
(101, 6, 'Thanh toán thành công', 'Đơn NUN177734873558 đã xác nhận thanh toán online và đang tìm tài xế.', 'payment_paid', '/user/tracking?id=77', 0, '2026-04-28 03:58:58'),
(102, 4, 'Đơn mới cho tài xế', 'Đơn NUN177734873558 vừa được tạo và đang chờ tài xế nhận.', 'order_created', '/admin/order-detail?id=77', 1, '2026-04-28 03:58:58'),
(103, 4, 'Có đơn hàng mới', 'Khách hàng nhuan1 vừa tạo đơn hàng #NUN177769094859.', 'system', '/admin/orders?search=NUN177769094859', 1, '2026-05-02 10:02:28'),
(104, 13, 'Có đơn hàng mới', 'Khách hàng nhuan1 vừa tạo đơn hàng #NUN177769094859.', 'system', '/admin/orders?search=NUN177769094859', 0, '2026-05-02 10:02:28');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `orders`
--

CREATE TABLE `orders` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `customer_id` bigint(20) UNSIGNED NOT NULL,
  `tracking_code` varchar(40) NOT NULL,
  `status` enum('pending','awaiting_payment','searching_driver','accepted','picking_up','in_transit','shipping','completed','returning','returned','disputed','cancelled') NOT NULL DEFAULT 'pending',
  `note` text DEFAULT NULL,
  `scheduled_at` datetime DEFAULT NULL,
  `is_archived` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `orders`
--

INSERT INTO `orders` (`id`, `customer_id`, `tracking_code`, `status`, `note`, `scheduled_at`, `is_archived`, `created_at`, `updated_at`) VALUES
(18, 1, 'NUN177477286127', 'completed', NULL, NULL, 0, '2026-03-29 08:27:41', '2026-04-24 23:20:53'),
(19, 1, 'NUN177477310496', 'completed', NULL, NULL, 0, '2026-03-29 08:31:44', '2026-03-29 15:31:44'),
(20, 1, 'NUN177494966837', 'completed', NULL, NULL, 0, '2026-03-31 09:34:28', '2026-04-22 16:14:16'),
(21, 1, 'NUN177495051829', 'completed', NULL, NULL, 0, '2026-03-31 09:48:38', '2026-04-22 16:14:12'),
(22, 1, 'NUN177502921357', 'completed', NULL, NULL, 0, '2026-04-01 07:40:13', '2026-04-01 14:40:13'),
(23, 1, 'NUN177510839394', 'completed', NULL, NULL, 0, '2026-04-02 05:39:53', '2026-04-02 12:39:53'),
(24, 1, 'NUN177511310155', 'completed', NULL, NULL, 0, '2026-04-02 06:58:21', '2026-04-02 13:58:21'),
(25, 1, 'NUN177511358251', 'completed', NULL, NULL, 0, '2026-04-02 07:06:22', '2026-04-02 14:06:22'),
(26, 1, 'NUN177511388862', 'completed', NULL, NULL, 0, '2026-04-02 07:11:28', '2026-04-22 16:13:04'),
(27, 1, 'NUN177511496394', 'completed', NULL, NULL, 0, '2026-04-02 07:29:23', '2026-04-22 16:12:54'),
(28, 1, 'NUN177511693075', 'completed', NULL, NULL, 0, '2026-04-02 08:02:10', '2026-04-25 10:45:23'),
(29, 1, 'NUN177518026165', 'cancelled', NULL, NULL, 1, '2026-04-03 01:37:41', '2026-04-27 21:47:18'),
(30, 1, 'NUN177518027955', 'cancelled', NULL, NULL, 1, '2026-04-03 01:37:59', '2026-04-27 21:47:12'),
(31, 1, 'NUN177518034971', 'cancelled', NULL, NULL, 0, '2026-04-03 01:39:09', '2026-04-25 10:43:07'),
(32, 1, 'NUN177518053334', 'completed', NULL, NULL, 0, '2026-04-03 01:42:13', '2026-04-22 16:12:39'),
(33, 1, 'NUN177518157529', 'completed', NULL, NULL, 0, '2026-04-03 01:59:35', '2026-04-22 16:12:35'),
(34, 1, 'NUN177518288846', 'completed', NULL, NULL, 0, '2026-04-03 02:21:28', '2026-04-22 16:12:35'),
(35, 1, 'NUN177519351332', 'completed', NULL, NULL, 0, '2026-04-03 05:18:33', '2026-04-22 16:12:34'),
(36, 1, 'NUN177520264239', 'cancelled', NULL, NULL, 0, '2026-04-03 07:50:42', '2026-04-22 16:12:28'),
(37, 6, 'NUN177520288915', 'cancelled', NULL, NULL, 0, '2026-04-03 07:54:49', '2026-04-22 16:12:23'),
(38, 1, 'NUN177520610685', 'completed', NULL, NULL, 0, '2026-04-03 08:48:26', '2026-04-22 16:12:16'),
(40, 1, 'NUN177537441285', 'completed', NULL, NULL, 0, '2026-04-05 07:33:32', '2026-04-24 20:37:07'),
(42, 1, 'NUN177537763825', 'completed', NULL, NULL, 0, '2026-04-05 08:27:18', '2026-04-24 20:42:51'),
(43, 1, 'NUN177557778614', 'completed', NULL, NULL, 0, '2026-04-07 16:03:06', '2026-04-22 16:23:46'),
(45, 1, 'NUN177557879349', 'completed', NULL, NULL, 0, '2026-04-07 16:19:53', '2026-04-25 10:38:31'),
(46, 1, 'NUN177615819333', 'returned', NULL, NULL, 0, '2026-04-14 09:16:33', '2026-04-25 10:42:46'),
(49, 1, 'NUN177660888634', 'completed', NULL, NULL, 0, '2026-04-19 14:28:06', '2026-04-22 16:10:55'),
(50, 1, 'NUN177660943543', 'completed', NULL, NULL, 0, '2026-04-19 14:37:15', '2026-04-22 16:10:54'),
(55, 1, 'NUN177684903674', 'completed', NULL, NULL, 0, '2026-04-22 09:10:36', '2026-04-22 16:21:43'),
(56, 12, 'NUN177699415133', 'completed', NULL, NULL, 0, '2026-04-24 01:29:11', '2026-04-24 20:37:51'),
(57, 12, 'NUN177699415736', 'completed', NULL, NULL, 0, '2026-04-24 01:29:17', '2026-04-24 22:01:48'),
(58, 1, 'NUN177699766216', 'completed', NULL, NULL, 0, '2026-04-24 02:27:42', '2026-04-24 20:41:48'),
(59, 1, 'NUN177699967495', 'completed', NULL, NULL, 0, '2026-04-24 03:01:14', '2026-04-24 20:36:15'),
(60, 1, 'NUN177708791288', 'completed', '', NULL, 0, '2026-04-25 03:31:53', '2026-04-25 10:47:28'),
(61, 1, 'NUN177708803559', 'completed', '', NULL, 0, '2026-04-25 03:33:56', '2026-04-25 10:48:21'),
(62, 1, 'NUN177708904367', 'awaiting_payment', '', NULL, 0, '2026-04-25 03:50:44', '2026-04-25 17:03:28'),
(63, 1, 'NUN177711007254', 'in_transit', '', NULL, 0, '2026-04-25 09:41:13', '2026-05-01 11:41:08'),
(64, 1, 'NUN177711136985', 'in_transit', '', NULL, 0, '2026-04-25 10:02:50', '2026-05-01 12:39:26'),
(65, 1, 'NUN177711146166', 'cancelled', '', NULL, 0, '2026-04-25 10:04:21', '2026-04-25 17:45:50'),
(66, 1, 'NUN177711159242', 'searching_driver', '', NULL, 0, '2026-04-25 10:06:33', '2026-04-25 17:06:33'),
(67, 1, 'NUN177718371642', 'accepted', '', NULL, 0, '2026-04-26 06:08:37', '2026-05-01 11:54:07'),
(68, 6, 'NUN177718376595', 'accepted', '', NULL, 0, '2026-04-26 06:09:26', '2026-05-01 11:54:07'),
(69, 6, 'NUN177718383279', 'completed', '', NULL, 0, '2026-04-26 06:10:33', '2026-04-27 10:19:03'),
(73, 1, 'NUN177727678569', 'accepted', '', NULL, 0, '2026-04-27 07:59:46', '2026-05-01 11:54:07'),
(74, 1, 'NUN177727684543', 'cancelled', '[Khách hàng hủy: Thay đổi ý định]', NULL, 0, '2026-04-27 08:00:46', '2026-04-28 09:41:33'),
(75, 6, 'NUN177734768788', 'accepted', '', NULL, 0, '2026-04-28 03:41:27', '2026-05-01 11:54:31'),
(76, 6, 'NUN177734847277', 'accepted', '', NULL, 0, '2026-04-28 03:54:33', '2026-05-01 11:54:31'),
(77, 6, 'NUN177734873558', 'searching_driver', '', NULL, 0, '2026-04-28 03:58:56', '2026-04-28 10:58:58'),
(79, 1, 'NUN177761648589', 'searching_driver', '', NULL, 0, '2026-05-01 06:21:25', '2026-05-01 13:21:25'),
(80, 1, 'NUN177761742161', 'searching_driver', '', NULL, 0, '2026-05-01 06:37:01', '2026-05-01 13:41:43'),
(81, 1, 'NUN177769094859', 'searching_driver', '', '2026-05-02 10:02:00', 0, '2026-05-02 10:02:28', '2026-05-02 10:02:28');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `order_addresses`
--

CREATE TABLE `order_addresses` (
  `order_id` bigint(20) UNSIGNED NOT NULL,
  `sender_name` varchar(120) NOT NULL,
  `sender_phone` varchar(30) NOT NULL,
  `sender_address` varchar(500) NOT NULL,
  `sender_lat` decimal(10,7) DEFAULT NULL,
  `sender_lng` decimal(10,7) DEFAULT NULL,
  `receiver_name` varchar(120) NOT NULL,
  `receiver_phone` varchar(30) NOT NULL,
  `receiver_address` varchar(500) NOT NULL,
  `receiver_lat` decimal(10,7) DEFAULT NULL,
  `receiver_lng` decimal(10,7) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `order_addresses`
--

INSERT INTO `order_addresses` (`order_id`, `sender_name`, `sender_phone`, `sender_address`, `sender_lat`, `sender_lng`, `receiver_name`, `receiver_phone`, `receiver_address`, `receiver_lat`, `receiver_lng`, `created_at`, `updated_at`) VALUES
(18, 'Nhuan', '0389125092', 'Thuringia, Đức', 50.9014721, 11.0377839, 'Thanh', '0345121349', 'Nandgaon, SH25, Nandgaon, Nandgaon Taluka, Nashik District, Maharashtra, 423106, Ấn Độ', 20.3094018, 74.6597622, '2026-05-02 09:59:59', '2026-05-02 09:59:59'),
(19, 'Nhuan', '0389125092', 'Argentina', -34.9964963, -64.9672817, 'Thanh', '0345121349', 'Nandgaon, SH25, Nandgaon, Nandgaon Taluka, Nashik District, Maharashtra, 423106, Ấn Độ', 20.3094018, 74.6597622, '2026-05-02 09:59:59', '2026-05-02 09:59:59'),
(20, 'Nhuan', '0389125092', '16.449888167632263, 107.57076031857511', 16.4498882, 107.5707136, 'Thanh', '0345121349', '16.42748414686034, 107.6784689792055', 16.4274841, 99.9999999, '2026-05-02 09:59:59', '2026-05-02 09:59:59'),
(21, 'Nhuan', '0389125092', '16.507301915080802, 107.50776739362028', 16.5073019, 107.5076768, 'Thanh', '0345121349', '16.464514596008804, 107.64169359244396', 16.4645146, 99.9999999, '2026-05-02 09:59:59', '2026-05-02 09:59:59'),
(22, 'Nhuan', '0389125092', 'Trường Đại học Kinh tế - Đại học Huế, Phùng Hưng, Đông Ba, Phường Phú Xuân, Thành phố Huế, 54000, Việt Nam', 16.4762105, 99.9999999, 'Thanh', '0345121349', 'Trường Đại học Khoa học - Đại học Huế, 77, Nguyễn Huệ, Phú Nhuận, Huế, Phường Thuận Hoá, Thành phố Huế, 54000, Việt Nam', 16.4597874, 99.9999999, '2026-05-02 09:59:59', '2026-05-02 09:59:59'),
(23, 'Nhuan', '0389125092', 'Lê Lợi', 16.4686791, 99.9999999, 'Thanh', '0345121349', '47 Nguyễn Thái Học Huế', 16.4680367, 99.9999999, '2026-05-02 09:59:59', '2026-05-02 09:59:59'),
(24, 'Nhuan', '0389125092', 'chùa thiên mụ', 20.2339497, 99.9999999, 'Thanh', '0345121349', 'Quốc lộ 37B', 20.2339497, 99.9999999, '2026-05-02 09:59:59', '2026-05-02 09:59:59'),
(25, 'Nhuan', '0389125092', 'Chùa Thiên Mụ, Nguyễn Phúc Nguyên, Phường Kim Long, Thành phố Huế, 54000, Việt Nam', 16.4537195, 99.9999999, 'Thanh', '0345121349', 'Trường Đại học Khoa học - Đại học Huế, 77, Nguyễn Huệ, Phú Nhuận, Huế, Phường Thuận Hoá, Thành phố Huế, 54000, Việt Nam', 16.4597874, 99.9999999, '2026-05-02 09:59:59', '2026-05-02 09:59:59'),
(26, 'Nhuan', '0389125092', 'Chùa Thiên Mụ, Đường Văn Thánh, Phường Kim Long, Thành phố Huế, 54000, Việt Nam', 16.4537195, 107.5804977, 'Thanh', '0345121349', 'Trường Đại học Khoa học - Đại học Huế, 77, Nguyễn Huệ, Phú Nhuận, Huế, Phường Thuận Hoá, Thành phố Huế, 54000, Việt Nam', 16.4597874, 107.5926169, '2026-05-02 09:59:59', '2026-05-02 09:59:59'),
(27, 'Nhuan', '0389125092', 'Chùa Thiên Mụ, Nguyễn Phúc Nguyên, Phường Kim Long, Thành phố Huế, 54000, Việt Nam', 16.4537195, 107.5447797, 'Thanh', '0345121349', 'Trường Đại học Khoa học - Đại học Huế, 77, Nguyễn Huệ, Phú Nhuận, Huế, Phường Thuận Hoá, Thành phố Huế, 54000, Việt Nam', 16.4597874, 107.5926169, '2026-05-02 09:59:59', '2026-05-02 09:59:59'),
(28, 'Nhuan', '0389125092', 'Chùa Thiên Mụ, Nguyễn Phúc Nguyên, Phường Kim Long, Thành phố Huế, 54000, Việt Nam', 16.4537195, 107.5445808, 'Thanh', '0345121349', 'Trường Đại học Khoa học - Đại học Huế, 77, Nguyễn Huệ, Phú Nhuận, Huế, Phường Thuận Hoá, Thành phố Huế, 54000, Việt Nam', 16.4597874, 107.5926169, '2026-05-02 09:59:59', '2026-05-02 09:59:59'),
(29, 'Nhuan', '0389125092', 'Chùa Thiên Mụ, Nguyễn Phúc Nguyên, Phường Kim Long, Thành phố Huế, 54000, Việt Nam', 16.4537195, 107.5445808, 'Thanh', '0345121349', 'Trường Đại học Khoa học - Đại học Huế, 77, Nguyễn Huệ, Phú Nhuận, Huế, Phường Thuận Hoá, Thành phố Huế, 54000, Việt Nam', 16.4597874, 107.5946528, '2026-05-02 09:59:59', '2026-05-02 09:59:59'),
(30, 'Nhuan', '0389125092', 'chùa thiên mụ', 16.4537195, 107.5445808, 'Thanh', '0345121349', 'Trường Đại học Khoa học', 16.4597874, 99.9999999, '2026-05-02 09:59:59', '2026-05-02 09:59:59'),
(31, 'Nhuan', '0389125092', 'Chùa Thiên Mụ, Nguyễn Phúc Nguyên, Phường Kim Long, Thành phố Huế, 54000, Việt Nam', 16.4537195, 107.5447797, 'Thanh', '0345121349', 'Hoàng Thành, Road 2, Đông Ba, Phường Phú Xuân, Thành phố Huế, 54000, Việt Nam', 16.4689726, 107.5781266, '2026-05-02 09:59:59', '2026-05-02 09:59:59'),
(32, 'Nhuan', '0389125092', 'Chùa Thiên Mụ, Nguyễn Phúc Nguyên, Phường Kim Long, Thành phố Huế, 54000, Việt Nam', 16.4537195, 107.5447797, 'Thanh', '0345121349', 'Hoàng Thành, Road 2, Đông Ba, Phường Phú Xuân, Thành phố Huế, 54000, Việt Nam', 16.4689726, 107.5781266, '2026-05-02 09:59:59', '2026-05-02 09:59:59'),
(33, 'Nhuan', '0389125092', 'Chùa Thiên Mụ, Nguyễn Phúc Nguyên, Phường Kim Long, Thành phố Huế, 54000, Việt Nam', 16.4537195, 107.5445808, 'Thanh', '0345121349', 'Hoàng Thành, Road 2, Đông Ba, Phường Phú Xuân, Thành phố Huế, 54000, Việt Nam', 16.4689726, 107.5781266, '2026-05-02 09:59:59', '2026-05-02 09:59:59'),
(34, 'Nhuan', '0389125092', 'Chùa Thiên Mụ, Nguyễn Phúc Nguyên, Phường Kim Long, Thành phố Huế, 54000, Việt Nam', 16.4537195, 107.5445808, 'Thanh', '0345121349', 'Hoàng Thành, Road 2, Đông Ba, Phường Phú Xuân, Thành phố Huế, 54000, Việt Nam', 16.4689726, 107.5781266, '2026-05-02 09:59:59', '2026-05-02 09:59:59'),
(35, 'Nhuan', '0389125092', 'Chùa Thiên Mụ, Nguyễn Phúc Nguyên, Phường Kim Long, Thành phố Huế, 54000, Việt Nam', 16.4537195, 107.5445808, 'Thanh', '0345121349', 'Hoàng Thành, Road 2, Đông Ba, Phường Phú Xuân, Thành phố Huế, 54000, Việt Nam', 16.4689726, 107.5803653, '2026-05-02 09:59:59', '2026-05-02 09:59:59'),
(36, 'Nhuan', '0389125092', 'Chùa Thiên Mụ, Đường Văn Thánh, Phường Kim Long, Thành phố Huế, 54000, Việt Nam', 16.4537195, 107.5611243, 'Thanh', '0345121349', 'Hoàng Thành, Road 2, Đông Ba, Phường Phú Xuân, Thành phố Huế, 54000, Việt Nam', 16.4689726, 107.5781266, '2026-05-02 09:59:59', '2026-05-02 09:59:59'),
(37, 'Nhuan', '0389125092', 'Hồ Hoàn Kiếm, Phường Hoàn Kiếm, Hà Nội, 11024, Việt Nam', 21.0288313, 105.8540410, 'Thanh', '0345121349', 'Hoàng Thành, Road 2, Đông Ba, Phường Phú Xuân, Thành phố Huế, 54000, Việt Nam', 16.4689726, 107.5781266, '2026-05-02 09:59:59', '2026-05-02 09:59:59'),
(38, 'Nhuan', '0389125092', 'chùa thiên mụ', 21.0258020, 99.9999999, 'Thanh', '0345121349', 'Hoàng Thành, Road 2, Đông Ba, Phường Phú Xuân, Thành phố Huế, 54000, Việt Nam', 16.4689726, 107.5794784, '2026-05-02 09:59:59', '2026-05-02 09:59:59'),
(40, 'Nhuan', '0389125092', 'chùa thiên mụ', 16.4537195, 107.5445808, 'Thanh', '0345121349', 'Trường Đại học Khoa học - Đại học Huế, 77, Nguyễn Huệ, Phú Nhuận, Huế, Phường Thuận Hoá, Thành phố Huế, 54000, Việt Nam', 16.4597874, 107.5926169, '2026-05-02 09:59:59', '2026-05-02 09:59:59'),
(42, 'Nhuan', '0389125092', 'Hồ Đắc Di, Phường An Cựu, Phường An Cựu', 16.4431524, 107.6041478, 'Thanh', '0345121349', '48 đống đa Huế', 20.3873448, 99.9999999, '2026-05-02 09:59:59', '2026-05-02 09:59:59'),
(43, 'Nhuan', '0389125092', 'Trường Đại học Luật - Đại học Huế, Võ Văn Kiệt, Thủy Dương, Phường Thanh Thủy, Phường An Cựu, Thành phố Huế, 54000, Việt Nam', 16.4374193, 107.6114534, 'Thanh', '0345121349', 'Trường Đại Học Khoa Học Huế', 16.4455537, 107.5926169, '2026-05-02 09:59:59', '2026-05-02 09:59:59'),
(45, 'Nhuan', '0389125092', 'chùa thiên mụ', 16.4537195, 99.9999999, 'Thanh', '0345121349', 'Hoàng Thành, Road 2, Đông Ba, Phường Phú Xuân, Thành phố Huế, 54000, Việt Nam', 16.4689726, 99.9999999, '2026-05-02 09:59:59', '2026-05-02 09:59:59'),
(46, 'Nhuan', '0389125092', 'Chùa Thiên Mụ, Nguyễn Phúc Nguyên, Phường Kim Long, Thành phố Huế, 54000, Việt Nam', 16.4539280, 99.9999999, 'Thanh', '0345121349', 'Trường Đại học Khoa học - Đại học Huế, 77, Nguyễn Huệ, Phú Nhuận, Huế, Phường Thuận Hoá, Thành phố Huế, 54000, Việt Nam', 16.4597874, 99.9999999, '2026-05-02 09:59:59', '2026-05-02 09:59:59'),
(49, 'Nhuan', '0389125092', 'Hồ Đắc Di, Phường An Cựu, Phường An Cựu', 16.4431524, 107.6041478, 'Thanh', '0345121349', 'Trường Đại học Khoa học - Đại học Huế, 77, Nguyễn Huệ, Phú Nhuận, Huế, Phường Thuận Hoá, Thành phố Huế, 54000, Việt Nam', 16.4597874, 107.5926169, '2026-05-02 09:59:59', '2026-05-02 09:59:59'),
(50, 'Nhuan', '0389125092', 'Hồ Đắc Di, Phường An Cựu, Phường An Cựu', 16.4431524, 107.6041478, 'Thanh', '0345121349', '77 Nguyễn Huệ, Phú Nhuận, Huế', 16.4589267, 107.5926169, '2026-05-02 09:59:59', '2026-05-02 09:59:59'),
(55, 'Nhuan', '0389125092', 'Trường Đại học Luật - Đại học Huế, Võ Văn Kiệt, Thủy Dương, Phường An Cựu, Phường Thanh Thủy, Thành phố Huế, 54000, Việt Nam', 16.4387380, 107.6090555, 'Thanh', '0345121349', 'Hoàng Thành, Road 2, Đông Ba, Phường Phú Xuân, Thành phố Huế, 54000, Việt Nam', 16.4689726, 107.5781266, '2026-05-02 09:59:59', '2026-05-02 09:59:59'),
(56, 'Nhuan', '0389125092', 'Đường Văn Thánh, Phường Kim Long, Thành phố Huế, 54000, Việt Nam', 16.4519785, 107.5373865, 'Thanh', '0345121349', 'Trường Đại học Khoa học - Đại học Huế, 77, Nguyễn Huệ, Phú Nhuận, Huế, Phường Thuận Hoá, Thành phố Huế, 54000, Việt Nam', 16.4597874, 107.5926169, '2026-05-02 09:59:59', '2026-05-02 09:59:59'),
(57, 'Nhuan', '0389125092', 'Đường Văn Thánh, Phường Kim Long, Thành phố Huế, 54000, Việt Nam', 16.4519785, 107.5373865, 'Thanh', '0345121349', 'Trường Đại học Khoa học - Đại học Huế, 77, Nguyễn Huệ, Phú Nhuận, Huế, Phường Thuận Hoá, Thành phố Huế, 54000, Việt Nam', 16.4597874, 107.5926169, '2026-05-02 09:59:59', '2026-05-02 09:59:59'),
(58, 'Test Sender', '0901234567', '123 Test Street, Hue', 16.4637000, 107.5909000, 'Test Receiver', '0912345678', '456 Test Road, Hue', 16.4700000, 107.6000000, '2026-05-02 09:59:59', '2026-05-02 09:59:59'),
(59, 'nhuan1', '0389125092', 'Trường Đại học Luật - Đại học Huế, Võ Văn Kiệt, Thủy Dương, Phường An Cựu, Thành phố Huế, 54000, Việt Nam', 16.4387380, 107.6090555, 'Thanh', '0345121349', 'Trường Đại học Khoa học - Đại học Huế, 77, Nguyễn Huệ, Phú Nhuận, Huế, Phường Thuận Hoá, Thành phố Huế, 54000, Việt Nam', 16.4597874, 107.5926169, '2026-05-02 09:59:59', '2026-05-02 09:59:59'),
(60, 'nhuan1', '0345121349', 'Đông Ba, Đông Ba, Phường Phú Xuân, Phường Phú Xuân', 16.4737472, 107.5803653, 'Ho Cam', '0910987654', 'Thôn An Xuân Đông, Xã Quảng Điền, Xã Quảng Điền, Thành phố Huế', 16.5643025, 107.5503158, '2026-05-02 09:59:59', '2026-05-02 09:59:59'),
(61, 'nhuan1', '0123451239', 'Tùng Thiện Vương, Tùng Thiện Vương, Phường Vỹ Dạ', 16.4798351, 107.5935269, 'Ho Cam', '0389125092', 'Thôn An Xuân Đông, Xã Quảng Điền, Thành phố Huế', 16.5643025, 107.5503158, '2026-05-02 09:59:59', '2026-05-02 09:59:59'),
(62, 'nhuan1', '0123451239', 'Tùng Thiện Vương, Tùng Thiện Vương, Phường Vỹ Dạ', 16.4828395, 107.6028292, 'Ho Cam', '0910987654', 'Thôn An Xuân Đông, Xã Quảng Điền, Thành phố Huế', 16.5643025, 107.5503158, '2026-05-02 09:59:59', '2026-05-02 09:59:59'),
(63, 'nhuan1', '0123451239', 'Tùng Thiện Vương, Tùng Thiện Vương, Phường Vỹ Dạ', 16.4828395, 107.6028292, 'Ho Cam', '0389125092', 'Thôn An Xuân Đông, Xã Quảng Điền, Thành phố Huế', 16.5643025, 107.5503158, '2026-05-02 09:59:59', '2026-05-02 09:59:59'),
(64, 'nhuan1', '0910987654', 'Sân Đại Triều Nghi, Đông Ba, Phường Phú Xuân', 16.4578173, 107.5856747, 'Ho Cam', '0910987654', 'Thôn An Xuân Đông, Xã Quảng Điền, Thành phố Huế', 16.5643025, 107.5503158, '2026-05-02 09:59:59', '2026-05-02 09:59:59'),
(65, 'nhuan1', '0123451239', 'Trần Hưng Đạo, Đông Ba, Phường Phú Xuân', 16.4721462, 107.5876190, 'Ho Cam', '0910987654', 'Thôn An Xuân Đông, Xã Quảng Điền, Thành phố Huế', 16.5643025, 107.5503158, '2026-05-02 09:59:59', '2026-05-02 09:59:59'),
(66, 'nhuan1', '0123451239', 'Võ Văn Kiệt, Thủy Dương, Phường An Cựu, Thành phố Huế', 16.4760759, 107.6704725, 'Ho Cam', '0345678912', 'Thôn An Xuân Đông, Xã Quảng Điền, Thành phố Huế', 16.5643025, 107.5503158, '2026-05-02 09:59:59', '2026-05-02 09:59:59'),
(67, 'nhuan1', '0123451239', 'Đường Vũ Ngọc Phan, Đường Vũ Ngọc Phan, Phường Thủy Xuân, Thành phố Huế', 16.4412021, 107.5661264, 'Ho Cam', '0389125092', '48 Đống Đa, Phú Nhuận, Phường Thuận Hoá, Thành phố Huế', 16.4605592, 107.5919665, '2026-05-02 09:59:59', '2026-05-02 09:59:59'),
(68, 'Nhuan', '0345678912', 'Đường Vũ Ngọc Phan, Đường Vũ Ngọc Phan, Phường Thủy Xuân, Thành phố Huế', 16.4412021, 107.5661264, 'Ho Cam', '0528672939', '48 Đống Đa, Phú Nhuận, Phường Thuận Hoá, Thành phố Huế', 16.4605592, 107.5919665, '2026-05-02 09:59:59', '2026-05-02 09:59:59'),
(69, 'Nhuan', '0345678912', 'Đường Vũ Ngọc Phan, Đường Vũ Ngọc Phan, Phường Thủy Xuân, Thành phố Huế', 16.4559802, 107.5741760, 'Ho Cam', '0389125092', '48 Đống Đa, Phú Nhuận, Phường Thuận Hoá, Thành phố Huế', 16.4605592, 107.5919665, '2026-05-02 09:59:59', '2026-05-02 09:59:59'),
(73, 'nhuan1', '0123451239', 'Đường Vũ Ngọc Phan, Đường Vũ Ngọc Phan, Phường Thủy Xuân, Thành phố Huế', 16.4412021, 107.5661264, 'Ho Cam', '0345678912', '48 Đống Đa, Phú Nhuận, Phường Thuận Hoá, Thành phố Huế', 16.4605592, 107.5919665, '2026-05-02 09:59:59', '2026-05-02 09:59:59'),
(74, 'nhuan1', '0123451239', 'An Đông, An Đông, Phường An Cựu, Thành phố Huế', 16.4562253, 107.6082571, 'Ho Cam', '0345678912', '48 Đống Đa, Phú Nhuận, Phường Thuận Hoá, Thành phố Huế', 16.4605592, 107.5919665, '2026-05-02 09:59:59', '2026-05-02 09:59:59'),
(75, 'Nhuan', '0345678912', '06 lê lợi, Vĩnh Ninh, Phường Thuận Hoá, Huế', 16.4579142, 107.5796607, 'Hoàng Nhật Hào', '0123456789', '77 Nguyễn Huệ, Phú Nhuận, Phường Thuận Hoá, Huế', 16.4597874, 107.5926169, '2026-05-02 09:59:59', '2026-05-02 09:59:59'),
(76, 'Nhuan', '0345678912', '177 Trần Hưng Đạo, Đông Ba, Phường Phú Xuân, Huế', 16.4687234, 107.5854870, 'Hoàng Nhật Hào', '0123456789', '100 Nguyễn Huệ, Phú Nhuận, Phường Thuận Hoá, Phường Thuận Hoá', 16.4581855, 107.5909898, '2026-05-02 09:59:59', '2026-05-02 09:59:59'),
(77, 'Nhuan', '0345678912', '1000 Lý Nhân Tông, Đường Lý Nhân Tông, Phường Kim Trà, Huế', 16.5066040, 107.5078017, 'Hoàng Nhật Hào', '0123456789', '100 Nguyễn Huệ, Phú Nhuận, Phường Thuận Hoá, Huế', 16.4581855, 107.5909898, '2026-05-02 09:59:59', '2026-05-02 09:59:59'),
(79, 'nhuan1', '01', 'Thông An Xuân Đông , Xã Quảng Điền ,Huế', 16.4562250, 107.6082570, 'Thanh', '0389125092', '48 Đống Đa ,Huế', 16.4605590, 107.5919660, '2026-05-02 09:59:59', '2026-05-02 09:59:59'),
(80, 'nhuan1', '01', 'Thôn An Xuân Đông , Xã Quảng Điền ,Huế', 16.4562250, 107.6082570, 'Ho Cam', '0389125092', '48 Đống Đa, Phú Nhuận, Phường Thuận Hoá, Thành phố Huế', 16.4605590, 107.5919660, '2026-05-02 09:59:59', '2026-05-02 09:59:59'),
(81, 'nhuan1', '01', 'Thôn An Xuân Đông , Xã Quảng Điền ,Huế', 16.4562250, 107.6082570, 'Ho Cam', '0389125092', '48 Đống Đa, Phú Nhuận, Phường Thuận Hoá, Thành phố Huế', 16.4605590, 107.5919660, '2026-05-02 10:02:28', '2026-05-02 10:02:28');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `order_chats`
--

CREATE TABLE `order_chats` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `receiver_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `order_chats`
--

INSERT INTO `order_chats` (`id`, `order_id`, `sender_id`, `receiver_id`, `message`, `is_read`, `created_at`) VALUES
(1, 64, 3, 1, 'alo', 0, '2026-05-02 11:38:30');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `order_deliveries`
--

CREATE TABLE `order_deliveries` (
  `order_id` bigint(20) UNSIGNED NOT NULL,
  `driver_id` bigint(20) UNSIGNED DEFAULT NULL,
  `batch_code` varchar(50) DEFAULT NULL,
  `accepted_at` datetime DEFAULT NULL,
  `picked_up_at` datetime DEFAULT NULL,
  `delivered_at` datetime DEFAULT NULL,
  `cancelled_at` datetime DEFAULT NULL,
  `proof_image` varchar(500) DEFAULT NULL,
  `cancel_reason` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `order_deliveries`
--

INSERT INTO `order_deliveries` (`order_id`, `driver_id`, `batch_code`, `accepted_at`, `picked_up_at`, `delivered_at`, `cancelled_at`, `proof_image`, `cancel_reason`, `created_at`, `updated_at`) VALUES
(28, 3, NULL, NULL, NULL, '2026-04-25 10:45:23', NULL, 'https://res.cloudinary.com/dvz3tllqa/image/upload/v1777088723/nun_web/proofs/proof_28_1777088719_12194.jpg', NULL, '2026-05-02 10:00:00', '2026-05-02 10:00:00'),
(29, 3, NULL, NULL, NULL, NULL, '2026-04-27 21:47:18', NULL, 'Xe gap su co/Tai nan', '2026-05-02 10:00:00', '2026-05-02 10:00:00'),
(30, 3, NULL, NULL, NULL, NULL, '2026-04-27 21:47:12', NULL, 'Xe gap su co/Tai nan', '2026-05-02 10:00:00', '2026-05-02 10:00:00'),
(31, 3, NULL, NULL, NULL, NULL, '2026-04-25 10:43:07', NULL, 'Xe gap su co/Tai nan', '2026-05-02 10:00:00', '2026-05-02 10:00:00'),
(34, 3, NULL, NULL, '2026-04-03 09:21:00', NULL, NULL, NULL, NULL, '2026-05-02 10:00:00', '2026-05-02 10:00:00'),
(35, 3, NULL, NULL, '2026-04-03 12:18:00', NULL, NULL, NULL, NULL, '2026-05-02 10:00:00', '2026-05-02 10:00:00'),
(36, 3, NULL, NULL, '2026-04-03 14:50:00', NULL, '2026-04-22 16:12:28', NULL, NULL, '2026-05-02 10:00:00', '2026-05-02 10:00:00'),
(37, 3, NULL, NULL, '2026-04-03 14:54:00', NULL, '2026-04-22 16:12:23', NULL, NULL, '2026-05-02 10:00:00', '2026-05-02 10:00:00'),
(38, 9, NULL, NULL, '2026-04-03 15:48:00', NULL, NULL, NULL, NULL, '2026-05-02 10:00:00', '2026-05-02 10:00:00'),
(40, 3, NULL, NULL, '2026-04-05 14:33:00', NULL, NULL, NULL, NULL, '2026-05-02 10:00:00', '2026-05-02 10:00:00'),
(42, 3, NULL, NULL, '2026-04-05 15:27:00', NULL, NULL, NULL, NULL, '2026-05-02 10:00:00', '2026-05-02 10:00:00'),
(43, 3, NULL, NULL, '2026-04-07 23:02:00', NULL, NULL, NULL, NULL, '2026-05-02 10:00:00', '2026-05-02 10:00:00'),
(45, 3, NULL, NULL, '2026-04-07 23:19:00', '2026-04-25 10:38:31', NULL, 'https://res.cloudinary.com/dvz3tllqa/image/upload/v1777088310/nun_web/proofs/proof_45_1777088306_74533.jpg', NULL, '2026-05-02 10:00:00', '2026-05-02 10:00:00'),
(46, 3, NULL, NULL, '2026-04-14 16:16:00', NULL, NULL, NULL, 'Nguoi nhan khong nghe may', '2026-05-02 10:00:00', '2026-05-02 10:00:00'),
(49, 3, NULL, NULL, '2026-04-19 21:27:00', NULL, NULL, NULL, NULL, '2026-05-02 10:00:00', '2026-05-02 10:00:00'),
(50, 3, NULL, NULL, '2026-04-19 21:37:00', NULL, NULL, NULL, NULL, '2026-05-02 10:00:00', '2026-05-02 10:00:00'),
(55, 3, NULL, NULL, '2026-04-22 16:10:00', NULL, NULL, NULL, NULL, '2026-05-02 10:00:00', '2026-05-02 10:00:00'),
(56, 3, NULL, NULL, '2026-04-24 08:28:00', NULL, NULL, NULL, NULL, '2026-05-02 10:00:00', '2026-05-02 10:00:00'),
(57, 3, NULL, NULL, '2026-04-24 08:28:00', '2026-04-24 22:01:48', NULL, 'https://res.cloudinary.com/dvz3tllqa/image/upload/v1777042907/nun_web/proofs/proof_57_1777042901_50337.jpg', NULL, '2026-05-02 10:00:00', '2026-05-02 10:00:00'),
(58, 3, NULL, NULL, '2026-04-24 06:27:00', NULL, NULL, NULL, NULL, '2026-05-02 10:00:00', '2026-05-02 10:00:00'),
(59, 3, NULL, NULL, '2026-04-24 10:01:00', NULL, NULL, NULL, NULL, '2026-05-02 10:00:00', '2026-05-02 10:00:00'),
(60, 3, 'BATCH-20260425054552-152', NULL, '2026-04-25 10:31:00', '2026-04-25 10:47:28', NULL, 'https://res.cloudinary.com/dvz3tllqa/image/upload/v1777088847/nun_web/proofs/proof_60_1777088846_20538.jpg', NULL, '2026-05-02 10:00:00', '2026-05-02 10:00:00'),
(61, 3, 'BATCH-20260425054552-152', NULL, '2026-04-25 10:33:00', '2026-04-25 10:48:21', NULL, 'https://res.cloudinary.com/dvz3tllqa/image/upload/v1777088900/nun_web/proofs/proof_61_1777088899_10862.jpg', NULL, '2026-05-02 10:00:00', '2026-05-02 10:00:00'),
(62, NULL, NULL, NULL, '2026-04-25 10:49:00', NULL, NULL, NULL, NULL, '2026-05-02 10:00:00', '2026-05-02 10:00:00'),
(63, 3, NULL, NULL, '2026-04-08 16:41:00', NULL, NULL, NULL, NULL, '2026-05-02 10:00:00', '2026-05-02 10:00:00'),
(64, 3, NULL, NULL, '2026-04-25 17:02:00', NULL, NULL, NULL, NULL, '2026-05-02 10:00:00', '2026-05-02 10:00:00'),
(65, 3, 'BATCH-20260425120454-397', NULL, '2026-04-25 17:04:00', NULL, '2026-04-25 17:45:50', NULL, 'Xe gặp sự cố/Tai nạn', '2026-05-02 10:00:00', '2026-05-02 10:00:00'),
(66, NULL, NULL, NULL, '2026-04-25 17:06:00', NULL, NULL, NULL, NULL, '2026-05-02 10:00:00', '2026-05-02 10:00:00'),
(67, 3, NULL, NULL, '2026-04-26 13:07:00', NULL, NULL, NULL, NULL, '2026-05-02 10:00:00', '2026-05-02 10:00:00'),
(68, 3, NULL, NULL, '2026-04-26 13:08:00', NULL, NULL, NULL, NULL, '2026-05-02 10:00:00', '2026-05-02 10:00:00'),
(69, 9, NULL, NULL, '2026-04-26 13:09:00', '2026-04-27 10:19:03', NULL, 'https://res.cloudinary.com/dvz3tllqa/image/upload/v1777259943/nun_web/proofs/proof_69_1777259938_50177.jpg', NULL, '2026-05-02 10:00:00', '2026-05-02 10:00:00'),
(73, 3, NULL, NULL, '2026-04-27 14:59:00', NULL, NULL, NULL, NULL, '2026-05-02 10:00:00', '2026-05-02 10:00:00'),
(74, 9, NULL, NULL, '2026-04-27 14:59:00', NULL, '2026-04-28 09:41:33', NULL, 'Thay đổi ý định', '2026-05-02 10:00:00', '2026-05-02 10:00:00'),
(75, 3, NULL, NULL, '2026-04-28 10:38:00', NULL, NULL, NULL, NULL, '2026-05-02 10:00:00', '2026-05-02 10:00:00'),
(76, 3, NULL, NULL, '2026-04-28 10:55:00', NULL, NULL, NULL, NULL, '2026-05-02 10:00:00', '2026-05-02 10:00:00'),
(77, NULL, NULL, NULL, '2026-04-28 10:59:00', NULL, NULL, NULL, NULL, '2026-05-02 10:00:00', '2026-05-02 10:00:00'),
(81, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-05-02 10:02:28', '2026-05-02 10:02:28');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `order_disputes`
--

CREATE TABLE `order_disputes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `order_id` bigint(20) UNSIGNED NOT NULL,
  `reported_by` bigint(20) UNSIGNED DEFAULT NULL,
  `issue_type` varchar(255) NOT NULL,
  `status` enum('open','in_review','resolved','rejected','closed') NOT NULL DEFAULT 'open',
  `resolution_note` text DEFAULT NULL,
  `resolved_by` bigint(20) UNSIGNED DEFAULT NULL,
  `resolved_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `order_disputes`
--

INSERT INTO `order_disputes` (`id`, `order_id`, `reported_by`, `issue_type`, `status`, `resolution_note`, `resolved_by`, `resolved_at`, `created_at`, `updated_at`) VALUES
(1, 45, 1, 'driver_attitude: tài xế láo', 'open', '', 4, NULL, '2026-04-25 03:40:15', '2026-05-02 10:00:00'),
(2, 74, 1, 'driver_attitude: thái độ xấu', 'open', NULL, NULL, NULL, '2026-04-28 02:43:45', '2026-05-02 10:00:00');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `order_finances`
--

CREATE TABLE `order_finances` (
  `order_id` bigint(20) UNSIGNED NOT NULL,
  `shipping_fee` decimal(12,2) NOT NULL DEFAULT 0.00,
  `payment_method` enum('cash','transfer','bank_transfer','wallet') NOT NULL DEFAULT 'cash',
  `payment_status` enum('pending','unpaid','paid','refunded') NOT NULL DEFAULT 'pending',
  `paid_at` datetime DEFAULT NULL,
  `refunded_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `order_finances`
--

INSERT INTO `order_finances` (`order_id`, `shipping_fee`, `payment_method`, `payment_status`, `paid_at`, `refunded_at`, `created_at`, `updated_at`) VALUES
(18, 32178076.00, 'cash', 'unpaid', NULL, NULL, '2026-05-02 09:59:59', '2026-05-02 09:59:59'),
(19, 78775178.00, 'cash', 'unpaid', NULL, NULL, '2026-05-02 09:59:59', '2026-05-02 09:59:59'),
(20, 5.00, 'cash', 'unpaid', NULL, NULL, '2026-05-02 09:59:59', '2026-05-02 09:59:59'),
(21, 100000.00, 'cash', 'unpaid', NULL, NULL, '2026-05-02 09:59:59', '2026-05-02 09:59:59'),
(22, 573366.00, 'cash', 'unpaid', NULL, NULL, '2026-05-02 09:59:59', '2026-05-02 09:59:59'),
(23, 11571.00, 'cash', 'unpaid', NULL, NULL, '2026-05-02 09:59:59', '2026-05-02 09:59:59'),
(24, 11000.00, 'cash', 'unpaid', NULL, NULL, '2026-05-02 09:59:59', '2026-05-02 09:59:59'),
(25, 0.00, 'cash', 'unpaid', NULL, NULL, '2026-05-02 09:59:59', '2026-05-02 09:59:59'),
(26, 0.00, 'cash', 'unpaid', NULL, NULL, '2026-05-02 09:59:59', '2026-05-02 09:59:59'),
(27, 26500.00, 'cash', 'unpaid', NULL, NULL, '2026-05-02 09:59:59', '2026-05-02 09:59:59'),
(28, 0.00, 'cash', 'paid', '2026-04-25 10:45:23', NULL, '2026-05-02 09:59:59', '2026-05-02 09:59:59'),
(29, 0.00, 'cash', 'unpaid', NULL, NULL, '2026-05-02 09:59:59', '2026-05-02 09:59:59'),
(30, 0.00, 'cash', 'unpaid', NULL, NULL, '2026-05-02 09:59:59', '2026-05-02 09:59:59'),
(31, 0.00, 'cash', 'unpaid', NULL, NULL, '2026-05-02 09:59:59', '2026-05-02 09:59:59'),
(32, 0.00, 'cash', 'unpaid', NULL, NULL, '2026-05-02 09:59:59', '2026-05-02 09:59:59'),
(33, 22876.00, 'cash', 'unpaid', NULL, NULL, '2026-05-02 09:59:59', '2026-05-02 09:59:59'),
(34, 22876.00, 'cash', 'unpaid', NULL, NULL, '2026-05-02 09:59:59', '2026-05-02 09:59:59'),
(35, 30376.00, 'cash', 'unpaid', NULL, NULL, '2026-05-02 09:59:59', '2026-05-02 09:59:59'),
(36, 22876.00, 'cash', 'unpaid', NULL, NULL, '2026-05-02 09:59:59', '2026-05-02 09:59:59'),
(37, 1626750.00, 'cash', 'unpaid', NULL, NULL, '2026-05-02 09:59:59', '2026-05-02 09:59:59'),
(38, 1625825.00, 'cash', 'unpaid', NULL, NULL, '2026-05-02 09:59:59', '2026-05-02 09:59:59'),
(40, 0.00, 'cash', 'unpaid', NULL, NULL, '2026-05-02 09:59:59', '2026-05-02 09:59:59'),
(42, 1574118.00, 'cash', 'unpaid', NULL, NULL, '2026-05-02 09:59:59', '2026-05-02 09:59:59'),
(43, 19042.00, 'cash', 'unpaid', NULL, NULL, '2026-05-02 09:59:59', '2026-05-02 09:59:59'),
(45, 24376.00, 'cash', 'paid', '2026-04-25 10:38:31', NULL, '2026-05-02 09:59:59', '2026-05-02 09:59:59'),
(46, 26083.00, 'cash', 'unpaid', NULL, NULL, '2026-05-02 09:59:59', '2026-05-02 09:59:59'),
(49, 19163.00, 'cash', 'unpaid', NULL, NULL, '2026-05-02 09:59:59', '2026-05-02 09:59:59'),
(50, 18948.00, 'cash', 'unpaid', NULL, NULL, '2026-05-02 09:59:59', '2026-05-02 09:59:59'),
(55, 26629.00, 'cash', 'unpaid', NULL, NULL, '2026-05-02 09:59:59', '2026-05-02 09:59:59'),
(56, 30360.00, 'cash', 'unpaid', NULL, NULL, '2026-05-02 09:59:59', '2026-05-02 09:59:59'),
(57, 30360.00, 'cash', 'paid', '2026-04-24 22:01:48', NULL, '2026-05-02 09:59:59', '2026-05-02 09:59:59'),
(58, 23100.00, 'cash', 'unpaid', NULL, NULL, '2026-05-02 09:59:59', '2026-05-02 09:59:59'),
(59, 23260.00, 'cash', 'unpaid', NULL, NULL, '2026-05-02 09:59:59', '2026-05-02 09:59:59'),
(60, 56327.00, 'cash', 'paid', '2026-04-25 05:47:28', NULL, '2026-05-02 09:59:59', '2026-05-02 09:59:59'),
(61, 59316.00, 'transfer', 'paid', '2026-04-25 05:48:21', NULL, '2026-05-02 09:59:59', '2026-05-02 09:59:59'),
(62, 59977.00, 'transfer', 'unpaid', NULL, NULL, '2026-05-02 09:59:59', '2026-05-02 09:59:59'),
(63, 59977.00, 'bank_transfer', 'pending', NULL, NULL, '2026-05-02 09:59:59', '2026-05-02 09:59:59'),
(64, 63131.00, 'cash', 'unpaid', NULL, NULL, '2026-05-02 09:59:59', '2026-05-02 09:59:59'),
(65, 57751.00, 'cash', 'unpaid', NULL, NULL, '2026-05-02 09:59:59', '2026-05-02 09:59:59'),
(66, 82199.00, 'cash', 'unpaid', NULL, NULL, '2026-05-02 09:59:59', '2026-05-02 09:59:59'),
(67, 37424.00, 'cash', 'unpaid', NULL, NULL, '2026-05-02 09:59:59', '2026-05-02 09:59:59'),
(68, 37424.00, 'cash', 'unpaid', NULL, NULL, '2026-05-02 09:59:59', '2026-05-02 09:59:59'),
(69, 46297.00, 'transfer', 'paid', '2026-04-27 05:19:03', NULL, '2026-05-02 09:59:59', '2026-05-02 09:59:59'),
(73, 35642.00, 'cash', 'unpaid', NULL, NULL, '2026-05-02 09:59:59', '2026-05-02 09:59:59'),
(74, 22215.00, 'transfer', 'paid', '2026-04-27 10:00:50', NULL, '2026-05-02 09:59:59', '2026-05-02 09:59:59'),
(75, 19046.00, 'transfer', 'paid', '2026-04-28 05:41:51', NULL, '2026-05-02 09:59:59', '2026-05-02 09:59:59'),
(76, 37898.00, 'transfer', 'paid', '2026-04-28 10:54:48', NULL, '2026-05-02 09:59:59', '2026-05-02 09:59:59'),
(77, 82859.00, 'transfer', 'paid', '2026-04-28 10:58:58', NULL, '2026-05-02 09:59:59', '2026-05-02 09:59:59'),
(79, 25000.00, 'cash', 'unpaid', NULL, NULL, '2026-05-02 09:59:59', '2026-05-02 09:59:59'),
(80, 25000.00, 'transfer', 'paid', NULL, NULL, '2026-05-02 09:59:59', '2026-05-02 09:59:59'),
(81, 47086.00, 'cash', 'pending', NULL, NULL, '2026-05-02 10:02:28', '2026-05-02 10:02:28');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `order_status_history`
--

CREATE TABLE `order_status_history` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `order_id` bigint(20) UNSIGNED NOT NULL,
  `status` varchar(40) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `order_status_history`
--

INSERT INTO `order_status_history` (`id`, `order_id`, `status`, `description`, `created_at`) VALUES
(1, 57, 'shipping', 'Cap nhat trang thai boi he thong/quan tri.', '2026-04-24 15:01:21'),
(2, 57, 'completed', 'Tai xe da giao hang thanh cong va upload bang chung.', '2026-04-24 15:01:48'),
(3, 60, 'searching_driver', 'Don hang duoc tao va dang tim tai xe.', '2026-04-25 03:31:53'),
(4, 61, 'awaiting_payment', 'Don hang duoc tao va dang cho xac nhan thanh toan online.', '2026-04-25 03:33:56'),
(5, 61, 'searching_driver', 'Thanh toan online thanh cong. Don hang san sang dieu phoi tai xe.', '2026-04-25 03:34:28'),
(6, 45, 'shipping', 'Cap nhat trang thai boi he thong/quan tri.', '2026-04-25 03:37:04'),
(7, 45, 'completed', 'Tai xe da giao hang thanh cong va upload bang chung.', '2026-04-25 03:38:31'),
(8, 46, 'shipping', 'Cap nhat trang thai boi he thong/quan tri.', '2026-04-25 03:42:11'),
(9, 46, 'returning', 'Don hang chuyen hoan. Ly do: Nguoi nhan khong nghe may', '2026-04-25 03:42:26'),
(10, 46, 'returned', 'Tai xe da hoan hang ve nguoi gui.', '2026-04-25 03:42:46'),
(11, 31, 'cancelled', 'Tai xe huy don. Ly do: Xe gap su co/Tai nan', '2026-04-25 03:43:07'),
(12, 30, 'cancelled', 'Tai xe huy don. Ly do: Xe gap su co/Tai nan', '2026-04-25 03:43:35'),
(13, 29, 'cancelled', 'Tai xe huy don. Ly do: Xe gap su co/Tai nan', '2026-04-25 03:44:58'),
(14, 28, 'completed', 'Tai xe da giao hang thanh cong va upload bang chung.', '2026-04-25 03:45:23'),
(15, 61, 'accepted', 'Tai xe da nhan don hang.', '2026-04-25 03:45:49'),
(16, 60, 'accepted', 'Tai xe da nhan don hang.', '2026-04-25 03:45:58'),
(17, 60, 'shipping', 'Cap nhat trang thai boi he thong/quan tri.', '2026-04-25 03:47:13'),
(18, 60, 'completed', 'Tai xe da giao hang thanh cong va upload bang chung.', '2026-04-25 03:47:28'),
(19, 61, 'shipping', 'Cap nhat trang thai boi he thong/quan tri.', '2026-04-25 03:48:11'),
(20, 61, 'completed', 'Tai xe da giao hang thanh cong va upload bang chung.', '2026-04-25 03:48:21'),
(21, 62, 'awaiting_payment', 'Don hang duoc tao va dang cho xac nhan thanh toan online.', '2026-04-25 03:50:44'),
(22, 63, 'searching_driver', 'Đơn hàng được tạo và đang tìm tài xế.', '2026-04-25 09:41:13'),
(23, 64, 'searching_driver', 'Đơn hàng được tạo và đang tìm tài xế.', '2026-04-25 10:02:50'),
(24, 65, 'searching_driver', 'Đơn hàng được tạo và đang tìm tài xế.', '2026-04-25 10:04:21'),
(25, 65, 'accepted', 'Tài xế đã nhận đơn hàng.', '2026-04-25 10:04:49'),
(26, 66, 'searching_driver', 'Đơn hàng được tạo và đang tìm tài xế.', '2026-04-25 10:06:33'),
(27, 65, 'cancelled', 'Tài xế hủy đơn. Lý do: Xe gặp sự cố/Tai nạn', '2026-04-25 10:45:50'),
(28, 63, 'accepted', 'Tai xe da nhan don hang.', '2026-04-26 05:52:57'),
(29, 67, 'searching_driver', 'Don hang duoc tao va dang tim tai xe.', '2026-04-26 06:08:37'),
(30, 68, 'searching_driver', 'Don hang duoc tao va dang tim tai xe.', '2026-04-26 06:09:26'),
(31, 69, 'awaiting_payment', 'Don hang duoc tao va dang cho xac nhan thanh toan online.', '2026-04-26 06:10:33'),
(32, 69, 'searching_driver', 'Thanh toan online thanh cong. Don hang san sang dieu phoi tai xe.', '2026-04-26 06:10:36'),
(33, 69, 'accepted', 'Tài xế đã nhận đơn hàng.', '2026-04-27 03:18:31'),
(34, 69, 'shipping', 'Cập nhật trạng thái bởi hệ thống/quản trị.', '2026-04-27 03:18:45'),
(35, 69, 'completed', 'Tài xế đã giao hàng thành công và upload bằng chứng.', '2026-04-27 03:19:03'),
(36, 73, 'searching_driver', 'Don hang duoc tao va dang tim tai xe.', '2026-04-27 07:59:46'),
(37, 74, 'awaiting_payment', 'Don hang duoc tao va dang cho xac nhan thanh toan online.', '2026-04-27 08:00:46'),
(38, 74, 'searching_driver', 'Thanh toán online thành công. Đơn hàng sẵn sàng điều phối tài xế.', '2026-04-27 08:00:50'),
(39, 74, 'accepted', 'Tài xế đã nhận đơn hàng.', '2026-04-28 01:40:06'),
(40, 74, 'cancelled', 'Khách hàng hủy đơn. Lý do: Thay đổi ý định', '2026-04-28 02:41:33'),
(41, 75, 'awaiting_payment', 'Don hang duoc tao va dang cho xac nhan thanh toan online.', '2026-04-28 03:41:27'),
(42, 75, 'searching_driver', 'Thanh toán online thành công. Đơn hàng sẵn sàng điều phối tài xế.', '2026-04-28 03:41:51'),
(43, 76, 'awaiting_payment', 'Don hang duoc tao va dang cho xac nhan thanh toan online.', '2026-04-28 03:54:33'),
(44, 76, 'searching_driver', 'Thanh toán online thành công. Đơn hàng sẵn sàng điều phối tài xế.', '2026-04-28 03:54:48'),
(45, 77, 'awaiting_payment', 'Don hang duoc tao va dang cho xac nhan thanh toan online.', '2026-04-28 03:58:56'),
(46, 77, 'searching_driver', 'Thanh toán online thành công. Đơn hàng sẵn sàng điều phối tài xế.', '2026-04-28 03:58:58'),
(48, 63, 'picking_up', 'Tài xế đã cập nhật trạng thái đơn hàng thành: picking_up', '2026-05-01 04:41:06'),
(49, 63, 'in_transit', 'Tài xế đã cập nhật trạng thái đơn hàng thành: in_transit', '2026-05-01 04:41:08'),
(50, 73, 'accepted', 'Tài xế đã nhận đơn hàng và đang di chuyển đến điểm lấy hàng.', '2026-05-01 04:54:07'),
(51, 67, 'accepted', 'Tài xế đã nhận đơn hàng và đang di chuyển đến điểm lấy hàng.', '2026-05-01 04:54:07'),
(52, 68, 'accepted', 'Tài xế đã nhận đơn hàng và đang di chuyển đến điểm lấy hàng.', '2026-05-01 04:54:07'),
(53, 76, 'accepted', 'Tài xế đã nhận đơn hàng và đang di chuyển đến điểm lấy hàng.', '2026-05-01 04:54:31'),
(54, 64, 'accepted', 'Tài xế đã nhận đơn hàng và đang di chuyển đến điểm lấy hàng.', '2026-05-01 04:54:31'),
(55, 75, 'accepted', 'Tài xế đã nhận đơn hàng và đang di chuyển đến điểm lấy hàng.', '2026-05-01 04:54:31'),
(56, 64, 'picking_up', 'Tài xế đã cập nhật trạng thái đơn hàng thành: picking_up', '2026-05-01 05:39:09'),
(57, 64, 'in_transit', 'Tài xế đã cập nhật trạng thái đơn hàng thành: in_transit', '2026-05-01 05:39:26'),
(58, 79, 'searching_driver', 'Đơn hàng vừa được tạo và đang tìm tài xế.', '2026-05-01 06:21:25'),
(59, 80, 'awaiting_payment', 'Đơn hàng đang chờ thanh toán online.', '2026-05-01 06:37:01'),
(60, 80, 'searching_driver', 'Khách hàng đã thanh toán thành công. Bắt đầu tìm tài xế.', '2026-05-01 06:41:43'),
(61, 81, 'searching_driver', 'Đơn hàng vừa được tạo và đang tìm tài xế.', '2026-05-02 10:02:28');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `otp_code` varchar(10) NOT NULL,
  `otp_expires_at` datetime NOT NULL,
  `reset_token` varchar(100) DEFAULT NULL,
  `token_expires_at` datetime DEFAULT NULL,
  `is_used` tinyint(1) NOT NULL DEFAULT 0,
  `used_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `settings`
--

CREATE TABLE `settings` (
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `settings`
--

INSERT INTO `settings` (`setting_key`, `setting_value`, `updated_at`) VALUES
('app_name', 'NUN Express', '2026-05-02 09:48:38'),
('driver_commission_percentage', '15', '2026-05-02 20:34:05'),
('max_concurrent_orders_per_driver', '3', '2026-05-02 20:34:05'),
('max_total_weight_per_driver', '100', '2026-05-02 20:34:05'),
('no_show_threshold_for_ban', '3', '2026-05-02 20:34:05'),
('penalty_multiplier', '1.5', '2026-05-02 20:34:05'),
('platform_fee_per_order', '5000', '2026-05-02 09:48:38'),
('violation_fine_amount', '50000', '2026-05-02 20:34:05');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(120) NOT NULL,
  `email` varchar(190) DEFAULT NULL,
  `phone` varchar(30) NOT NULL,
  `password` varchar(255) NOT NULL,
  `avatar` varchar(500) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `role` enum('user','driver','admin') NOT NULL DEFAULT 'user',
  `is_verified` tinyint(1) NOT NULL DEFAULT 1,
  `verification_token` varchar(20) DEFAULT NULL,
  `is_blocked` tinyint(1) NOT NULL DEFAULT 0,
  `blocked_reason` varchar(255) DEFAULT NULL,
  `blocked_by` bigint(20) UNSIGNED DEFAULT NULL,
  `blocked_at` datetime DEFAULT NULL,
  `failed_delivery_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `trust_score` decimal(5,2) NOT NULL DEFAULT 100.00,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `violation_count` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Number of violations (cancellations, no-shows, etc)',
  `penalty_amount` decimal(12,2) NOT NULL DEFAULT 0.00 COMMENT 'Total penalties/fines amount',
  `no_show_count` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Number of times customer did not pick up'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `phone`, `password`, `avatar`, `address`, `role`, `is_verified`, `verification_token`, `is_blocked`, `blocked_reason`, `blocked_by`, `blocked_at`, `failed_delivery_count`, `trust_score`, `created_at`, `updated_at`, `violation_count`, `penalty_amount`, `no_show_count`) VALUES
(1, 'nhuan1', 'nhuan1', '01', '$2y$10$N.4K9gKX.KUpXOy9m01qe.FDINOX5HRAHchxn1q8y1/ySt2kB.7Fa', 'https://res.cloudinary.com/dvz3tllqa/image/upload/v1776994031/nun_web/avatars/avatar_1_1776994029_20957.webp', '', 'user', 1, NULL, 0, NULL, NULL, NULL, 1, 100.00, '2026-03-23 02:01:41', '2026-04-25 03:42:46', 0, 0.00, 0),
(3, 'nhuan2', 'nhuan2', '02', '$2y$10$DabqC73TnkZQOpeHyV41/ufkgwQqHbc5uUgelZg.jMCMn305sCkjG', 'default-avatar.png', NULL, 'driver', 1, NULL, 0, NULL, NULL, NULL, 0, 100.00, '2026-03-23 02:02:41', '2026-04-24 16:12:48', 0, 0.00, 0),
(4, 'nhuan3', 'nhuan3', '03', '$2y$10$nuVQLg9kW9HpgukUSZfS.eThMfEzw.e//LQHxAY/WTAP4szHo0UEa', 'default-avatar.png', NULL, 'admin', 1, NULL, 0, NULL, NULL, NULL, 0, 100.00, '2026-03-23 02:03:13', '2026-04-28 07:01:24', 0, 0.00, 0),
(6, 'Nhuan', 'nhuan1111h@gmail.com', '0345678912', '$2y$10$IJKo5O97DFi6a5CNTgU7W.S1U//DnahIeg30cMzEWDgz6OyhHyZ8u', 'default-avatar.png', NULL, 'user', 1, NULL, 0, NULL, NULL, NULL, 0, 100.00, '2026-04-03 00:10:03', '2026-04-03 00:10:03', 0, 0.00, 0),
(9, 'Ho Cam', 'nhuan2@gmail.com', '0910987654', '$2y$10$vr8dvjGG/W/cPRy6fpLj/ed8emeJ50nXlkp2jXQ/p9tsmjldh9STS', 'default-avatar.png', NULL, 'driver', 1, NULL, 0, NULL, NULL, NULL, 0, 100.00, '2026-04-03 00:56:53', '2026-04-03 00:56:53', 0, 0.00, 0),
(11, 'Nhuân', 'nhuan8145@gmail.com', '0345121349', '$2y$10$GQ8UdJM/50qn1xUMPhNau.x4TdnlWOZkgXi9UexIyewoyWEkZAe76', 'default-avatar.png', NULL, 'user', 1, NULL, 0, NULL, NULL, NULL, 0, 100.00, '2026-04-12 01:17:13', '2026-04-12 01:17:13', 0, 0.00, 0),
(12, 'Khach vang lai', NULL, 'GUEST000001', '$2y$10$EY/HZS8Vmm892HUzFgEEpeV.N66v2S5S2QTYJubNOrkzEs5Ng1Kbu', 'default-avatar.png', NULL, 'user', 1, NULL, 1, NULL, NULL, NULL, 0, 100.00, '2026-04-24 01:29:11', '2026-04-30 10:40:56', 0, 0.00, 0),
(13, 'Chi do', 'nhuan11111h@gmail.com', '096612961', '$2y$10$NYrQvjBvX26XLeiwu682GeWqoTPXIYdts1el4idEbtvsnQELBGEZm', 'default-avatar.png', NULL, 'admin', 1, NULL, 0, NULL, NULL, NULL, 0, 100.00, '2026-04-26 07:28:41', '2026-04-30 10:11:37', 0, 0.00, 0),
(26, 'Ho Cam', 'nhuannhuan440@gmail.com', '0345678934', '$2y$10$DzkWKlFMJp2ClfzPcDSGCu3VeVacuATkvOLcY7BbzbQVrBjR5m8wi', 'default-avatar.png', NULL, 'user', 0, '675300', 0, NULL, NULL, NULL, 0, 100.00, '2026-04-30 15:36:08', '2026-04-30 15:36:08', 0, 0.00, 0),
(27, 'Ho Cam', 'nhuan111111h@gmail.com', '0345688888', '$2y$10$CiXb.YuodOLN9zTS9qr0MOdoXWp0A5JeBXvFBJP5gx1LLFUzK6AUO', 'default-avatar.png', NULL, 'user', 1, NULL, 0, NULL, NULL, NULL, 0, 100.00, '2026-05-01 03:13:56', '2026-05-01 03:15:31', 0, 0.00, 0),
(28, 'thanh nhuan', 'nhuan1111111h@gmail.com', '0345123146', '$2y$10$FxwOXaIDNL8gVyoKuwROu.qSUdcRdzvmwVY4iPuSNPt2NV83o1X9e', '/assets/images/default-avatar.png', NULL, 'user', 0, '942711', 0, NULL, NULL, NULL, 0, 100.00, '2026-05-02 14:15:52', '2026-05-02 14:15:52', 0, 0.00, 0);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `wallet_transactions`
--

CREATE TABLE `wallet_transactions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `driver_id` bigint(20) UNSIGNED NOT NULL,
  `order_id` bigint(20) UNSIGNED DEFAULT NULL,
  `amount` decimal(12,2) NOT NULL,
  `type` enum('deposit','platform_fee','refund','adjustment') NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `balance_after` decimal(12,2) NOT NULL DEFAULT 0.00,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `wallet_transactions`
--

INSERT INTO `wallet_transactions` (`id`, `driver_id`, `order_id`, `amount`, `type`, `description`, `balance_after`, `created_at`) VALUES
(1, 3, 57, -6072.00, 'platform_fee', 'Tru hoa hong 20% cho don hang #57', -6072.00, '2026-04-24 15:01:48'),
(2, 3, NULL, 1000000.00, 'deposit', 'Dieu chinh boi admin', 993928.00, '2026-04-24 16:15:23'),
(3, 3, 45, -4875.00, 'platform_fee', 'Tru hoa hong 20% cho don hang #45', 989053.00, '2026-04-25 03:38:31'),
(4, 3, 60, -11265.00, 'platform_fee', 'Tru hoa hong 20% cho don hang #60', 977788.00, '2026-04-25 03:47:28'),
(5, 3, 61, -11863.00, 'platform_fee', 'Tru hoa hong 20% cho don hang #61', 965925.00, '2026-04-25 03:48:21'),
(6, 3, NULL, 50000.00, 'deposit', 'Tài xế tự nạp tiền (Demo)', 1015925.00, '2026-04-26 05:22:37'),
(7, 3, NULL, 750000.00, 'deposit', 'Tài xế tự nạp tiền (Demo)', 1765925.00, '2026-04-26 05:49:00'),
(8, 3, NULL, 10000000.00, 'deposit', 'Tài xế tự nạp tiền (Demo)', 11765925.00, '2026-04-26 05:49:13'),
(9, 9, 69, -9259.00, 'platform_fee', 'Trừ hoa hồng 20% cho đơn hàng #69', -9259.00, '2026-04-27 03:19:03'),
(10, 9, NULL, 50000.00, 'deposit', 'Tài xế tự nạp tiền (Demo)', 40741.00, '2026-04-28 01:39:10'),
(11, 9, NULL, 110000.00, 'deposit', 'Tài xế tự nạp tiền (Demo)', 150741.00, '2026-04-28 01:39:17');

--
-- Chỉ mục cho các bảng đã đổ
--

--
-- Chỉ mục cho bảng `driver_penalties`
--
ALTER TABLE `driver_penalties`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_driver_penalties_driver` (`driver_id`,`created_at`),
  ADD KEY `idx_driver_penalties_paid` (`is_paid`),
  ADD KEY `fk_driver_penalties_creator` (`created_by`);

--
-- Chỉ mục cho bảng `driver_profiles`
--
ALTER TABLE `driver_profiles`
  ADD PRIMARY KEY (`user_id`),
  ADD KEY `idx_driver_profiles_location` (`current_lat`,`current_lng`),
  ADD KEY `idx_driver_profiles_online` (`is_online`,`status`);

--
-- Chỉ mục cho bảng `driver_reviews`
--
ALTER TABLE `driver_reviews`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_driver_reviews_order` (`order_id`),
  ADD KEY `idx_driver_reviews_customer` (`customer_id`),
  ADD KEY `idx_driver_reviews_driver` (`driver_id`,`created_at`);

--
-- Chỉ mục cho bảng `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_notifications_user` (`user_id`,`is_read`,`created_at`);

--
-- Chỉ mục cho bảng `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_orders_tracking_code` (`tracking_code`),
  ADD KEY `idx_orders_customer` (`customer_id`),
  ADD KEY `idx_orders_status` (`status`,`is_archived`),
  ADD KEY `idx_orders_created_at` (`created_at`);

--
-- Chỉ mục cho bảng `order_addresses`
--
ALTER TABLE `order_addresses`
  ADD PRIMARY KEY (`order_id`),
  ADD KEY `idx_order_addresses_sender` (`sender_phone`),
  ADD KEY `idx_order_addresses_receiver` (`receiver_phone`);

--
-- Chỉ mục cho bảng `order_chats`
--
ALTER TABLE `order_chats`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`);

--
-- Chỉ mục cho bảng `order_deliveries`
--
ALTER TABLE `order_deliveries`
  ADD PRIMARY KEY (`order_id`),
  ADD KEY `idx_order_deliveries_driver` (`driver_id`),
  ADD KEY `idx_order_deliveries_batch` (`batch_code`);

--
-- Chỉ mục cho bảng `order_disputes`
--
ALTER TABLE `order_disputes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_order_disputes_order` (`order_id`),
  ADD KEY `idx_order_disputes_status` (`status`),
  ADD KEY `idx_order_disputes_reporter` (`reported_by`),
  ADD KEY `idx_order_disputes_resolver` (`resolved_by`);

--
-- Chỉ mục cho bảng `order_finances`
--
ALTER TABLE `order_finances`
  ADD PRIMARY KEY (`order_id`),
  ADD KEY `idx_order_finances_payment` (`payment_method`,`payment_status`);

--
-- Chỉ mục cho bảng `order_status_history`
--
ALTER TABLE `order_status_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_order_status_history_order` (`order_id`,`created_at`);

--
-- Chỉ mục cho bảng `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_password_reset_tokens_user` (`user_id`,`created_at`),
  ADD KEY `idx_password_reset_tokens_otp` (`otp_code`),
  ADD KEY `idx_password_reset_tokens_used` (`is_used`);

--
-- Chỉ mục cho bảng `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`setting_key`);

--
-- Chỉ mục cho bảng `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_users_phone` (`phone`),
  ADD UNIQUE KEY `uk_users_email` (`email`),
  ADD KEY `idx_users_role` (`role`),
  ADD KEY `idx_users_blocked` (`is_blocked`),
  ADD KEY `idx_users_blocked_by` (`blocked_by`);

--
-- Chỉ mục cho bảng `wallet_transactions`
--
ALTER TABLE `wallet_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_wallet_transactions_driver` (`driver_id`,`created_at`),
  ADD KEY `idx_wallet_transactions_order` (`order_id`);

--
-- AUTO_INCREMENT cho các bảng đã đổ
--

--
-- AUTO_INCREMENT cho bảng `driver_penalties`
--
ALTER TABLE `driver_penalties`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `driver_reviews`
--
ALTER TABLE `driver_reviews`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=105;

--
-- AUTO_INCREMENT cho bảng `orders`
--
ALTER TABLE `orders`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=82;

--
-- AUTO_INCREMENT cho bảng `order_chats`
--
ALTER TABLE `order_chats`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT cho bảng `order_disputes`
--
ALTER TABLE `order_disputes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT cho bảng `order_status_history`
--
ALTER TABLE `order_status_history`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=62;

--
-- AUTO_INCREMENT cho bảng `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT cho bảng `wallet_transactions`
--
ALTER TABLE `wallet_transactions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- Các ràng buộc cho các bảng đã đổ
--

--
-- Các ràng buộc cho bảng `driver_penalties`
--
ALTER TABLE `driver_penalties`
  ADD CONSTRAINT `fk_driver_penalties_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_driver_penalties_driver` FOREIGN KEY (`driver_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `driver_profiles`
--
ALTER TABLE `driver_profiles`
  ADD CONSTRAINT `fk_driver_profiles_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `driver_reviews`
--
ALTER TABLE `driver_reviews`
  ADD CONSTRAINT `fk_driver_reviews_customer` FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_driver_reviews_driver` FOREIGN KEY (`driver_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_driver_reviews_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `fk_notifications_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `fk_orders_customer` FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `order_addresses`
--
ALTER TABLE `order_addresses`
  ADD CONSTRAINT `fk_order_addresses_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `order_deliveries`
--
ALTER TABLE `order_deliveries`
  ADD CONSTRAINT `fk_order_deliveries_driver` FOREIGN KEY (`driver_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_order_deliveries_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `order_disputes`
--
ALTER TABLE `order_disputes`
  ADD CONSTRAINT `fk_order_disputes_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_order_disputes_reporter` FOREIGN KEY (`reported_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_order_disputes_resolver` FOREIGN KEY (`resolved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `order_finances`
--
ALTER TABLE `order_finances`
  ADD CONSTRAINT `fk_order_finances_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `order_status_history`
--
ALTER TABLE `order_status_history`
  ADD CONSTRAINT `fk_order_status_history_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD CONSTRAINT `fk_password_reset_tokens_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_users_blocked_by` FOREIGN KEY (`blocked_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `wallet_transactions`
--
ALTER TABLE `wallet_transactions`
  ADD CONSTRAINT `fk_wallet_transactions_driver` FOREIGN KEY (`driver_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_wallet_transactions_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
