-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Máy chủ: localhost:3306
-- Thời gian đã tạo: Th12 14, 2025 lúc 11:19 AM
-- Phiên bản máy phục vụ: 10.11.8-MariaDB
-- Phiên bản PHP: 8.4.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Cơ sở dữ liệu: `auroraho_aurorahotelplaza.com`
--
CREATE DATABASE IF NOT EXISTS `auroraho_aurorahotelplaza.com` DEFAULT CHARACTER SET latin1 COLLATE latin1_swedish_ci;
USE `auroraho_aurorahotelplaza.com`;

DELIMITER $$
--
-- Thủ tục
--
CREATE DEFINER=`auroraho`@`localhost` PROCEDURE `sp_calculate_loyalty_points` (IN `p_user_id` INT, IN `p_booking_amount` DECIMAL(10,2))   BEGIN
    DECLARE v_points INT;
    DECLARE v_points_per_vnd DECIMAL(10,4);
    
    -- Get points conversion rate
    SELECT CAST(setting_value AS DECIMAL(10,4)) INTO v_points_per_vnd
    FROM system_settings 
    WHERE setting_key = 'points_per_vnd';
    
    -- Calculate points
    SET v_points = FLOOR(p_booking_amount * v_points_per_vnd);
    
    -- Update user loyalty
    INSERT INTO user_loyalty (user_id, current_points, lifetime_points)
    VALUES (p_user_id, v_points, v_points)
    ON DUPLICATE KEY UPDATE 
        current_points = current_points + v_points,
        lifetime_points = lifetime_points + v_points;
    
    -- Update tier based on lifetime points
    UPDATE user_loyalty ul
    SET tier_id = (
        SELECT tier_id 
        FROM membership_tiers 
        WHERE min_points <= ul.lifetime_points 
        ORDER BY min_points DESC 
        LIMIT 1
    ),
    tier_updated_at = CURRENT_TIMESTAMP
    WHERE user_id = p_user_id;
    
    SELECT v_points as points_earned;
END$$

CREATE DEFINER=`auroraho`@`localhost` PROCEDURE `sp_check_room_availability` (IN `p_room_type_id` INT, IN `p_check_in` DATE, IN `p_check_out` DATE, IN `p_num_rooms` INT)   BEGIN
    SELECT 
        COUNT(DISTINCT r.room_id) as available_rooms
    FROM rooms r
    WHERE r.room_type_id = p_room_type_id
        AND r.status = 'available'
        AND r.room_id NOT IN (
            SELECT DISTINCT room_id 
            FROM bookings 
            WHERE room_id IS NOT NULL
                AND status IN ('confirmed', 'checked_in')
                AND (
                    (check_in_date <= p_check_in AND check_out_date > p_check_in)
                    OR (check_in_date < p_check_out AND check_out_date >= p_check_out)
                    OR (check_in_date >= p_check_in AND check_out_date <= p_check_out)
                )
        )
    HAVING available_rooms >= p_num_rooms;
END$$

CREATE DEFINER=`auroraho`@`localhost` PROCEDURE `sp_generate_booking_code` ()   BEGIN
    DECLARE v_code VARCHAR(20);
    DECLARE v_exists INT;
    
    REPEAT
        SET v_code = CONCAT('BK', DATE_FORMAT(NOW(), '%Y%m%d'), LPAD(FLOOR(RAND() * 10000), 4, '0'));
        SELECT COUNT(*) INTO v_exists FROM bookings WHERE booking_code = v_code;
    UNTIL v_exists = 0 END REPEAT;
    
    SELECT v_code as booking_code;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `activity_logs`
--

CREATE TABLE `activity_logs` (
  `log_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `entity_type` varchar(50) DEFAULT NULL,
  `entity_id` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `activity_logs`
--

INSERT INTO `activity_logs` (`log_id`, `user_id`, `action`, `entity_type`, `entity_id`, `description`, `ip_address`, `user_agent`, `created_at`) VALUES
(0, 0, 'logout', 'user', 0, 'User logged out', '123.31.116.145', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.6 Safari/605.1.15', '2025-12-14 03:50:38'),
(0, 0, 'login', 'user', 0, 'User logged in - Details: {\"email\":\"admin@aurorahotelplaza.com\",\"user_name\":\"Administrator\",\"role\":\"admin\",\"remember_me\":false}', '115.74.225.100', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-14 03:53:06'),
(0, 0, 'login', 'user', 0, 'User logged in - Details: {\"email\":\"admin@aurorahotelplaza.com\",\"user_name\":\"Administrator\",\"role\":\"admin\",\"remember_me\":false}', '123.31.116.145', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.6 Safari/605.1.15', '2025-12-14 03:53:15'),
(0, 0, 'logout', 'user', 0, 'User logged out', '123.31.116.145', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.6 Safari/605.1.15', '2025-12-14 03:53:40'),
(0, 0, 'register', 'user', 0, 'New user registered via google - Details: {\"email\":\"longdev.08@gmail.com\",\"user_name\":\"Long Quang\",\"registration_method\":\"google\"}', '123.31.116.145', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.6 Safari/605.1.15', '2025-12-14 03:53:59'),
(0, 0, 'logout', 'user', 0, 'User logged out', '123.31.116.145', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.6 Safari/605.1.15', '2025-12-14 03:58:44'),
(0, 0, 'login', 'user', 0, 'User logged in - Details: {\"email\":\"admin@aurorahotelplaza.com\",\"user_name\":\"Administrator\",\"role\":\"admin\",\"remember_me\":false}', '123.31.116.145', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.6 Safari/605.1.15', '2025-12-14 03:58:51'),
(0, 0, 'logout', 'user', 0, 'User logged out', '123.31.116.145', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.6 Safari/605.1.15', '2025-12-14 03:59:16'),
(0, 0, 'login', 'user', 0, 'User logged in via google - Details: {\"email\":\"longdev.08@gmail.com\",\"user_name\":\"Long Quang\",\"role\":\"customer\",\"login_method\":\"google\"}', '123.31.116.145', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.6 Safari/605.1.15', '2025-12-14 03:59:36'),
(0, 0, 'logout', 'user', 0, 'User logged out', '123.31.116.145', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.6 Safari/605.1.15', '2025-12-14 03:59:47'),
(0, 0, 'login', 'user', 0, 'User logged in via google - Details: {\"email\":\"longdev.08@gmail.com\",\"user_name\":\"Long Quang\",\"role\":\"customer\",\"login_method\":\"google\"}', '123.31.116.145', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.6 Safari/605.1.15', '2025-12-14 04:00:11'),
(0, 0, 'logout', 'user', 0, 'User logged out', '123.31.116.145', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.6 Safari/605.1.15', '2025-12-14 04:01:05'),
(0, 0, 'login', 'user', 0, 'User logged in - Details: {\"email\":\"admin@aurorahotelplaza.com\",\"user_name\":\"Administrator\",\"role\":\"admin\",\"remember_me\":false}', '123.31.116.145', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.6 Safari/605.1.15', '2025-12-14 04:03:49'),
(0, 0, 'login', 'user', 0, 'User logged in via google - Details: {\"email\":\"longdev.08@gmail.com\",\"user_name\":\"Long Quang\",\"role\":\"customer\",\"login_method\":\"google\"}', '123.31.116.145', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.6 Safari/605.1.15', '2025-12-14 04:06:41'),
(0, 0, 'logout', 'user', 0, 'User logged out', '123.31.116.145', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.6 Safari/605.1.15', '2025-12-14 04:07:57'),
(0, 0, 'register', 'user', 0, 'New user registered via google - Details: {\"email\":\"thuylinh.80902@gmail.com\",\"user_name\":\"Linh\",\"registration_method\":\"google\"}', '123.31.116.145', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.6 Safari/605.1.15', '2025-12-14 04:08:07'),
(0, 0, 'logout', 'user', 0, 'User logged out', '123.31.116.145', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.6 Safari/605.1.15', '2025-12-14 04:12:26'),
(0, 0, 'register', 'user', 0, 'New user registered via google - Details: {\"email\":\"23810067@student.hcmute.edu.vn\",\"user_name\":\"Le Quang Long\",\"registration_method\":\"google\"}', '123.31.116.145', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.6 Safari/605.1.15', '2025-12-14 04:13:48');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `banners`
--

CREATE TABLE `banners` (
  `banner_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `subtitle` varchar(255) DEFAULT NULL,
  `image_desktop` varchar(255) NOT NULL,
  `image_mobile` varchar(255) DEFAULT NULL,
  `link_url` varchar(255) DEFAULT NULL,
  `link_text` varchar(100) DEFAULT NULL,
  `position` enum('hero','sidebar','footer','popup') DEFAULT 'hero',
  `sort_order` int(11) DEFAULT 0,
  `start_date` timestamp NULL DEFAULT NULL,
  `end_date` timestamp NULL DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `blog_categories`
--

CREATE TABLE `blog_categories` (
  `category_id` int(11) NOT NULL,
  `category_name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `blog_categories`
--

INSERT INTO `blog_categories` (`category_id`, `category_name`, `slug`, `description`, `parent_id`, `sort_order`, `created_at`) VALUES
(6, 'Tin tức', 'tin-tuc', 'Tin tức và sự kiện của khách sạn', NULL, 0, '2025-11-17 10:18:49'),
(7, 'Khuyến mãi', 'khuyen-mai', 'Các chương trình khuyến mãi', NULL, 0, '2025-11-17 10:18:49'),
(8, 'Du lịch', 'du-lich', 'Thông tin du lịch và điểm đến', NULL, 0, '2025-11-17 10:18:49'),
(9, 'Ẩm thực', 'am-thuc', 'Món ăn và nhà hàng', NULL, 0, '2025-11-17 10:18:49'),
(10, 'Hướng dẫn', 'huong-dan', 'Hướng dẫn sử dụng dịch vụ', NULL, 0, '2025-11-17 10:18:49');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `blog_comments`
--

CREATE TABLE `blog_comments` (
  `comment_id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `author_name` varchar(255) NOT NULL,
  `author_email` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `status` enum('pending','approved','spam','trash') DEFAULT 'pending',
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `blog_posts`
--

CREATE TABLE `blog_posts` (
  `post_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `excerpt` text DEFAULT NULL,
  `content` longtext NOT NULL,
  `featured_image` varchar(255) DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `author_id` int(11) NOT NULL,
  `status` enum('draft','published','archived') DEFAULT 'draft',
  `views` int(11) DEFAULT 0,
  `published_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `bookings`
--

CREATE TABLE `bookings` (
  `booking_id` int(11) NOT NULL,
  `booking_code` varchar(20) NOT NULL,
  `user_id` int(11) NOT NULL,
  `room_type_id` int(11) NOT NULL,
  `room_id` int(11) DEFAULT NULL,
  `check_in_date` date NOT NULL,
  `check_out_date` date NOT NULL,
  `num_adults` int(11) NOT NULL DEFAULT 1,
  `num_children` int(11) DEFAULT 0,
  `num_rooms` int(11) NOT NULL DEFAULT 1,
  `total_nights` int(11) NOT NULL,
  `room_price` decimal(10,2) NOT NULL,
  `service_charges` decimal(10,2) DEFAULT 0.00,
  `discount_amount` decimal(10,2) DEFAULT 0.00,
  `points_used` int(11) DEFAULT 0,
  `total_amount` decimal(10,2) NOT NULL,
  `special_requests` text DEFAULT NULL,
  `guest_name` varchar(255) NOT NULL,
  `guest_email` varchar(255) NOT NULL,
  `guest_phone` varchar(20) NOT NULL,
  `guest_id_number` varchar(50) DEFAULT NULL,
  `status` enum('pending','confirmed','checked_in','checked_out','cancelled','no_show') DEFAULT 'pending',
  `payment_status` enum('unpaid','partial','paid','refunded') DEFAULT 'unpaid',
  `qr_code` varchar(255) DEFAULT NULL,
  `confirmation_sent` tinyint(1) DEFAULT 0,
  `checked_in_at` timestamp NULL DEFAULT NULL,
  `checked_out_at` timestamp NULL DEFAULT NULL,
  `checked_in_by` int(11) DEFAULT NULL,
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `cancelled_by` int(11) DEFAULT NULL,
  `cancellation_reason` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Bẫy `bookings`
--
DELIMITER $$
CREATE TRIGGER `tr_booking_calculate_total` BEFORE INSERT ON `bookings` FOR EACH ROW BEGIN
    SET NEW.total_amount = (NEW.room_price * NEW.total_nights * NEW.num_rooms) 
                          + NEW.service_charges 
                          - NEW.discount_amount;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `tr_booking_checkin` AFTER UPDATE ON `bookings` FOR EACH ROW BEGIN
    IF NEW.status = 'checked_in' AND OLD.status != 'checked_in' AND NEW.room_id IS NOT NULL THEN
        UPDATE rooms SET status = 'occupied' WHERE room_id = NEW.room_id;
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `tr_booking_status_log` AFTER UPDATE ON `bookings` FOR EACH ROW BEGIN
    IF OLD.status != NEW.status THEN
        INSERT INTO booking_history (booking_id, old_status, new_status, changed_by)
        VALUES (NEW.booking_id, OLD.status, NEW.status, NEW.checked_in_by);
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `booking_history`
--

CREATE TABLE `booking_history` (
  `history_id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `old_status` varchar(50) DEFAULT NULL,
  `new_status` varchar(50) NOT NULL,
  `changed_by` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `chat_conversations`
--

CREATE TABLE `chat_conversations` (
  `conversation_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `staff_id` int(11) DEFAULT NULL,
  `status` enum('open','assigned','closed') DEFAULT 'open',
  `locked_by` int(11) DEFAULT NULL,
  `locked_at` timestamp NULL DEFAULT NULL,
  `last_message_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `chat_messages`
--

CREATE TABLE `chat_messages` (
  `message_id` int(11) NOT NULL,
  `conversation_id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `attachment` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `contact_submissions`
--

CREATE TABLE `contact_submissions` (
  `submission_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `message` text NOT NULL,
  `status` enum('new','in_progress','resolved','closed') DEFAULT 'new',
  `assigned_to` int(11) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `csrf_tokens`
--

CREATE TABLE `csrf_tokens` (
  `token_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `email_logs`
--

CREATE TABLE `email_logs` (
  `email_log_id` int(11) NOT NULL,
  `recipient` varchar(255) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `template` varchar(100) DEFAULT NULL,
  `status` enum('sent','failed','pending') DEFAULT 'pending',
  `error_message` text DEFAULT NULL,
  `sent_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `email_logs`
--

INSERT INTO `email_logs` (`email_log_id`, `recipient`, `subject`, `template`, `status`, `error_message`, `sent_at`, `created_at`) VALUES
(1, 'long.lequang308@gmail.com', 'Xác nhận thanh toán - Mã: BK20251119A1664F', 'booking_confirmation', 'pending', NULL, NULL, '2025-11-19 04:10:55'),
(0, 'quanglong.3824@gmail.com', 'Xác nhận thanh toán - Mã: BK202511267A7286', 'booking_confirmation', 'pending', NULL, NULL, '2025-11-27 09:24:59');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `faqs`
--

CREATE TABLE `faqs` (
  `faq_id` int(11) NOT NULL,
  `question` varchar(255) NOT NULL,
  `answer` text NOT NULL,
  `category` varchar(50) DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `status` enum('active','inactive') DEFAULT 'active',
  `views` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `gallery`
--

CREATE TABLE `gallery` (
  `gallery_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `image_url` varchar(255) NOT NULL,
  `thumbnail_url` varchar(255) DEFAULT NULL,
  `category` varchar(50) DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `status` enum('active','inactive') DEFAULT 'active',
  `uploaded_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `gallery`
--

INSERT INTO `gallery` (`gallery_id`, `title`, `description`, `image_url`, `thumbnail_url`, `category`, `sort_order`, `status`, `uploaded_by`, `created_at`) VALUES
(0, 'Phòng Deluxe', 'Phòng Deluxe sang trọng với đầy đủ tiện nghi', 'assets/img/deluxe/DELUXE-ROOM-AURORA-1.jpg', NULL, 'rooms', 1, 'active', NULL, '2025-12-14 02:33:32'),
(0, 'Phòng Deluxe - Góc nhìn', 'Góc nhìn tổng quan phòng Deluxe', 'assets/img/deluxe/DELUXE-ROOM-AURORA-2.jpg', NULL, 'rooms', 2, 'active', NULL, '2025-12-14 02:33:32'),
(0, 'Phòng Deluxe - Nội thất', 'Nội thất phòng Deluxe hiện đại', 'assets/img/deluxe/DELUXE-ROOM-AURORA-3.jpg', NULL, 'rooms', 3, 'active', NULL, '2025-12-14 02:33:32'),
(0, 'Phòng Deluxe - Giường', 'Giường ngủ êm ái phòng Deluxe', 'assets/img/deluxe/DELUXE-ROOM-AURORA-4.jpg', NULL, 'rooms', 4, 'active', NULL, '2025-12-14 02:33:32'),
(0, 'Phòng Deluxe - Phòng tắm', 'Phòng tắm sang trọng', 'assets/img/deluxe/DELUXE-ROOM-AURORA-5.jpg', NULL, 'rooms', 5, 'active', NULL, '2025-12-14 02:33:32'),
(0, 'Phòng Deluxe - Tiện nghi', 'Tiện nghi đầy đủ trong phòng', 'assets/img/deluxe/DELUXE-ROOM-AURORA-6.jpg', NULL, 'rooms', 6, 'active', NULL, '2025-12-14 02:33:32'),
(0, 'Phòng Deluxe - Giường đôi', 'Phòng Deluxe với giường đôi', 'assets/img/deluxe/DELUXE-ROOM-AURORA-7.jpg', NULL, 'rooms', 7, 'active', NULL, '2025-12-14 02:33:32'),
(0, 'Phòng Deluxe - View', 'View đẹp từ phòng Deluxe', 'assets/img/deluxe/DELUXE-ROOM-AURORA-8.jpg', NULL, 'rooms', 8, 'active', NULL, '2025-12-14 02:33:32'),
(0, 'Phòng Deluxe - Không gian', 'Không gian rộng rãi phòng Deluxe', 'assets/img/deluxe/DELUXE-ROOM-AURORA-9.jpg', NULL, 'rooms', 9, 'active', NULL, '2025-12-14 02:33:32'),
(0, 'Phòng Deluxe - Toàn cảnh', 'Toàn cảnh phòng Deluxe', 'assets/img/deluxe/DELUXE-ROOM-AURORA-10.jpg', NULL, 'rooms', 10, 'active', NULL, '2025-12-14 02:33:32'),
(0, 'Premium Deluxe', 'Phòng Premium Deluxe cao cấp', 'assets/img/premium-deluxe/premium-deluxe-aurora-hotel-1.jpg', NULL, 'rooms', 11, 'active', NULL, '2025-12-14 02:33:32'),
(0, 'Premium Deluxe - Nội thất', 'Nội thất Premium Deluxe', 'assets/img/premium-deluxe/premium-deluxe-aurora-hotel-2.jpg', NULL, 'rooms', 12, 'active', NULL, '2025-12-14 02:33:32'),
(0, 'Premium Deluxe - Giường', 'Giường ngủ Premium Deluxe', 'assets/img/premium-deluxe/premium-deluxe-aurora-hotel-3.jpg', NULL, 'rooms', 13, 'active', NULL, '2025-12-14 02:33:32'),
(0, 'Premium Deluxe - View', 'View từ phòng Premium Deluxe', 'assets/img/premium-deluxe/premium-deluxe-aurora-hotel-5.jpg', NULL, 'rooms', 14, 'active', NULL, '2025-12-14 02:33:32'),
(0, 'Premium Deluxe - Phòng tắm', 'Phòng tắm Premium Deluxe', 'assets/img/premium-deluxe/premium-deluxe-aurora-hotel-6.jpg', NULL, 'rooms', 15, 'active', NULL, '2025-12-14 02:33:32'),
(0, 'Premium Twin', 'Phòng Premium Twin 2 giường', 'assets/img/premium-twin/premium-deluxe-twin-aurora-1.jpg', NULL, 'rooms', 16, 'active', NULL, '2025-12-14 02:33:32'),
(0, 'Premium Twin - Giường đôi', 'Hai giường đơn Premium Twin', 'assets/img/premium-twin/premium-deluxe-twin-aurora-2.jpg', NULL, 'rooms', 17, 'active', NULL, '2025-12-14 02:33:32'),
(0, 'Premium Twin - Nội thất', 'Nội thất phòng Premium Twin', 'assets/img/premium-twin/premium-deluxe-twin-aurora-3.jpg', NULL, 'rooms', 18, 'active', NULL, '2025-12-14 02:33:32'),
(0, 'Phòng VIP', 'Phòng VIP đẳng cấp nhất', 'assets/img/vip/vip-room-aurora-hotel-1.jpg', NULL, 'rooms', 19, 'active', NULL, '2025-12-14 02:33:32'),
(0, 'Phòng VIP - Sang trọng', 'Không gian sang trọng phòng VIP', 'assets/img/vip/vip-room-aurora-hotel-3.jpg', NULL, 'rooms', 20, 'active', NULL, '2025-12-14 02:33:32'),
(0, 'Phòng VIP - Nội thất', 'Nội thất cao cấp phòng VIP', 'assets/img/vip/vip-room-aurora-hotel-4.jpg', NULL, 'rooms', 21, 'active', NULL, '2025-12-14 02:33:32'),
(0, 'Phòng VIP - Phòng khách', 'Phòng khách riêng VIP', 'assets/img/vip/vip-room-aurora-hotel-5.jpg', NULL, 'rooms', 22, 'active', NULL, '2025-12-14 02:33:32'),
(0, 'Phòng VIP - Phòng tắm', 'Phòng tắm VIP sang trọng', 'assets/img/vip/vip-room-aurora-hotel-6.jpg', NULL, 'rooms', 23, 'active', NULL, '2025-12-14 02:33:32'),
(0, 'Căn hộ Studio', 'Căn hộ Studio tiện nghi', 'assets/img/studio-apartment/can-ho-studio-aurora-hotel-1.jpg', NULL, 'apartments', 24, 'active', NULL, '2025-12-14 02:33:32'),
(0, 'Studio - Phòng khách', 'Phòng khách căn hộ Studio', 'assets/img/studio-apartment/can-ho-studio-aurora-hotel-2.jpg', NULL, 'apartments', 25, 'active', NULL, '2025-12-14 02:33:32'),
(0, 'Studio - Bếp', 'Bếp đầy đủ tiện nghi', 'assets/img/studio-apartment/can-ho-studio-aurora-hotel-3.jpg', NULL, 'apartments', 26, 'active', NULL, '2025-12-14 02:33:32'),
(0, 'Căn hộ Family', 'Căn hộ Family rộng rãi', 'assets/img/family-apartment/can-ho-family-aurora-hotel-3.jpg', NULL, 'apartments', 27, 'active', NULL, '2025-12-14 02:33:32'),
(0, 'Family - Phòng ngủ', 'Phòng ngủ căn hộ Family', 'assets/img/family-apartment/can-ho-family-aurora-hotel-5.jpg', NULL, 'apartments', 28, 'active', NULL, '2025-12-14 02:33:32'),
(0, 'Family - Phòng khách', 'Phòng khách căn hộ Family', 'assets/img/family-apartment/can-ho-family-aurora-hotel-6.jpg', NULL, 'apartments', 29, 'active', NULL, '2025-12-14 02:33:32'),
(0, 'Căn hộ Premium', 'Căn hộ Premium cao cấp', 'assets/img/premium-apartment/can-ho-premium-aurora-hotel-1.jpg', NULL, 'apartments', 30, 'active', NULL, '2025-12-14 02:33:32'),
(0, 'Premium - Nội thất', 'Nội thất căn hộ Premium', 'assets/img/premium-apartment/can-ho-premium-aurora-hotel-2.jpg', NULL, 'apartments', 31, 'active', NULL, '2025-12-14 02:33:32'),
(0, 'Premium - Phòng ngủ', 'Phòng ngủ căn hộ Premium', 'assets/img/premium-apartment/can-ho-premium-aurora-hotel-3.jpg', NULL, 'apartments', 32, 'active', NULL, '2025-12-14 02:33:32'),
(0, 'Indochine Family', 'Căn hộ phong cách Indochine', 'assets/img/indochine-family-apartment/indochine-family-apartment-1.jpg', NULL, 'apartments', 33, 'active', NULL, '2025-12-14 02:33:32'),
(0, 'Indochine - Phong cách', 'Phong cách Đông Dương đặc trưng', 'assets/img/indochine-family-apartment/indochine-family-apartment-2.jpg', NULL, 'apartments', 34, 'active', NULL, '2025-12-14 02:33:32'),
(0, 'Indochine - Nội thất', 'Nội thất Indochine tinh tế', 'assets/img/indochine-family-apartment/indochine-family-apartment-3.jpg', NULL, 'apartments', 35, 'active', NULL, '2025-12-14 02:33:32'),
(0, 'Indochine Studio', 'Căn hộ Studio Indochine', 'assets/img/indochine-studio-apartment/indochine-studio-apartment-1.jpg', NULL, 'apartments', 36, 'active', NULL, '2025-12-14 02:33:32'),
(0, 'Modern Studio', 'Căn hộ Studio hiện đại', 'assets/img/modern-studio-apartment/modern-studio-apartment-1.jpg', NULL, 'apartments', 37, 'active', NULL, '2025-12-14 02:33:32'),
(0, 'Modern - Thiết kế', 'Thiết kế hiện đại', 'assets/img/modern-studio-apartment/modern-studio-apartment-2.jpg', NULL, 'apartments', 38, 'active', NULL, '2025-12-14 02:33:32'),
(0, 'Modern Premium', 'Căn hộ Premium hiện đại', 'assets/img/modern-premium-apartment/modern-premium-apartment-1.jpg', NULL, 'apartments', 39, 'active', NULL, '2025-12-14 02:33:32'),
(0, 'Classical Family', 'Căn hộ phong cách cổ điển', 'assets/img/classical-family-apartment/classical-family-apartment1.jpg', NULL, 'apartments', 40, 'active', NULL, '2025-12-14 02:33:32'),
(0, 'Classical - Cổ điển', 'Nét đẹp cổ điển sang trọng', 'assets/img/classical-family-apartment/classical-family-apartment2.jpg', NULL, 'apartments', 41, 'active', NULL, '2025-12-14 02:33:32'),
(0, 'Nhà hàng Aurora', 'Nhà hàng sang trọng Aurora', 'assets/img/restaurant/NHA-HANG-AURORA-HOTEL-1.jpg', NULL, 'restaurant', 42, 'active', NULL, '2025-12-14 02:33:32'),
(0, 'Không gian nhà hàng', 'Không gian ẩm thực đẳng cấp', 'assets/img/restaurant/NHA-HANG-AURORA-HOTEL-2.jpg', NULL, 'restaurant', 43, 'active', NULL, '2025-12-14 02:33:32'),
(0, 'Khu vực ăn uống', 'Khu vực ăn uống thoáng đãng', 'assets/img/restaurant/NHA-HANG-AURORA-HOTEL-3.jpg', NULL, 'restaurant', 44, 'active', NULL, '2025-12-14 02:33:32'),
(0, 'Buffet sáng', 'Buffet sáng phong phú', 'assets/img/restaurant/NHA-HANG-AURORA-HOTEL-4.jpg', NULL, 'restaurant', 45, 'active', NULL, '2025-12-14 02:33:32'),
(0, 'Bàn tiệc', 'Bàn tiệc sang trọng', 'assets/img/restaurant/NHA-HANG-AURORA-HOTEL-5.jpg', NULL, 'restaurant', 46, 'active', NULL, '2025-12-14 02:33:32'),
(0, 'Nội thất nhà hàng', 'Nội thất nhà hàng tinh tế', 'assets/img/restaurant/NHA-HANG-AURORA-HOTEL-6.jpg', NULL, 'restaurant', 47, 'active', NULL, '2025-12-14 02:33:32'),
(0, 'Khu vực VIP', 'Khu vực VIP riêng tư', 'assets/img/restaurant/NHA-HANG-AURORA-HOTEL-7.jpg', NULL, 'restaurant', 48, 'active', NULL, '2025-12-14 02:33:32'),
(0, 'Quầy bar', 'Quầy bar hiện đại', 'assets/img/restaurant/NHA-HANG-AURORA-HOTEL-8.jpg', NULL, 'restaurant', 49, 'active', NULL, '2025-12-14 02:33:32'),
(0, 'Góc nhìn nhà hàng', 'Góc nhìn đẹp nhà hàng', 'assets/img/restaurant/NHA-HANG-AURORA-HOTEL-9.jpg', NULL, 'restaurant', 50, 'active', NULL, '2025-12-14 02:33:32'),
(0, 'Tiệc buffet', 'Tiệc buffet đa dạng', 'assets/img/restaurant/NHA-HANG-AURORA-HOTEL-10.jpg', NULL, 'restaurant', 51, 'active', NULL, '2025-12-14 02:33:32'),
(0, 'Khu vực buffet', 'Khu vực buffet rộng rãi', 'assets/img/restaurant/NHA-HANG-AURORA-HOTEL-11.jpg', NULL, 'restaurant', 52, 'active', NULL, '2025-12-14 02:33:32'),
(0, 'Toàn cảnh nhà hàng', 'Toàn cảnh nhà hàng Aurora', 'assets/img/restaurant/NHA-HANG-AURORA-HOTEL-14.jpg', NULL, 'restaurant', 53, 'active', NULL, '2025-12-14 02:33:32'),
(0, 'Lễ tân', 'Quầy lễ tân chuyên nghiệp', 'assets/img/src/ui/horizontal/Le_tan_Aurora.jpg', NULL, 'facilities', 54, 'active', NULL, '2025-12-14 02:33:32'),
(0, 'Sảnh khách sạn', 'Sảnh đón tiếp sang trọng', 'assets/img/src/ui/horizontal/sanh-khach-san-aurora.jpg', NULL, 'facilities', 55, 'active', NULL, '2025-12-14 02:33:32'),
(0, 'Phòng Studio', 'Phòng Studio tiện nghi', 'assets/img/src/ui/horizontal/phong-studio-khach-san-aurora-bien-hoa.jpg', NULL, 'facilities', 56, 'active', NULL, '2025-12-14 02:33:32'),
(0, 'Phòng Gym', 'Phòng tập Gym hiện đại', 'assets/img/service/gym/GYM-AURORA-HOTEL-1.jpg', NULL, 'facilities', 57, 'active', NULL, '2025-12-14 02:33:32'),
(0, 'Thiết bị Gym', 'Thiết bị tập luyện chất lượng', 'assets/img/service/gym/GYM-AURORA-HOTEL-2.jpg', NULL, 'facilities', 58, 'active', NULL, '2025-12-14 02:33:32'),
(0, 'Khu vực tập luyện', 'Khu vực tập luyện rộng rãi', 'assets/img/service/gym/GYM-AURORA-HOTEL-3.jpg', NULL, 'facilities', 59, 'active', NULL, '2025-12-14 02:33:32'),
(0, 'Hồ bơi', 'Hồ bơi ngoài trời', 'assets/img/service/pool/pool.jpg', NULL, 'facilities', 60, 'active', NULL, '2025-12-14 02:33:32'),
(0, 'Văn phòng cho thuê', 'Văn phòng cho thuê chuyên nghiệp', 'assets/img/service/office/Van-phong-cho-thue-Aurora-1.jpg', NULL, 'facilities', 61, 'active', NULL, '2025-12-14 02:33:32'),
(0, 'Không gian làm việc', 'Không gian làm việc hiện đại', 'assets/img/service/office/Van-phong-cho-thue-Aurora-2.jpg', NULL, 'facilities', 62, 'active', NULL, '2025-12-14 02:33:32'),
(0, 'Phòng họp', 'Phòng họp đầy đủ tiện nghi', 'assets/img/service/office/Van-phong-cho-thue-Aurora-3.jpg', NULL, 'facilities', 63, 'active', NULL, '2025-12-14 02:33:32'),
(0, 'Tiệc cưới Aurora', 'Tiệc cưới sang trọng tại Aurora', 'assets/img/post/wedding/Tiec-cuoi-tai-aurora-1.jpg', NULL, 'events', 64, 'active', NULL, '2025-12-14 02:33:32'),
(0, 'Sảnh tiệc cưới', 'Sảnh tiệc cưới rộng lớn', 'assets/img/post/wedding/Tiec-cuoi-tai-aurora-2.jpg', NULL, 'events', 65, 'active', NULL, '2025-12-14 02:33:32'),
(0, 'Trang trí tiệc cưới', 'Trang trí tiệc cưới tinh tế', 'assets/img/post/wedding/Tiec-cuoi-tai-aurora-3.jpg', NULL, 'events', 66, 'active', NULL, '2025-12-14 02:33:32'),
(0, 'Bàn tiệc cưới', 'Bàn tiệc cưới sang trọng', 'assets/img/post/wedding/Tiec-cuoi-tai-aurora-4.jpg', NULL, 'events', 67, 'active', NULL, '2025-12-14 02:33:32'),
(0, 'Không gian tiệc cưới', 'Không gian tiệc cưới lãng mạn', 'assets/img/post/wedding/Tiec-cuoi-tai-aurora-5.jpg', NULL, 'events', 68, 'active', NULL, '2025-12-14 02:33:32'),
(0, 'Sân khấu tiệc cưới', 'Sân khấu tiệc cưới hoành tráng', 'assets/img/post/wedding/Tiec-cuoi-tai-aurora-6.jpg', NULL, 'events', 69, 'active', NULL, '2025-12-14 02:33:32'),
(0, 'Tiệc cưới sang trọng', 'Tiệc cưới đẳng cấp 5 sao', 'assets/img/post/wedding/Tiec-cuoi-tai-aurora-7.jpg', NULL, 'events', 70, 'active', NULL, '2025-12-14 02:33:32'),
(0, 'Tiệc cưới hoành tráng', 'Tiệc cưới quy mô lớn', 'assets/img/post/wedding/Tiec-cuoi-tai-Aurora-8.jpg', NULL, 'events', 71, 'active', NULL, '2025-12-14 02:33:32'),
(0, 'Tiệc cưới đẳng cấp', 'Tiệc cưới phong cách hiện đại', 'assets/img/post/wedding/Tiec-cuoi-tai-aurora-9.jpg', NULL, 'events', 72, 'active', NULL, '2025-12-14 02:33:32'),
(0, 'Tiệc cưới lãng mạn', 'Tiệc cưới lãng mạn và ấm cúng', 'assets/img/post/wedding/Tiec-cuoi-tai-aurora-10.jpg', NULL, 'events', 73, 'active', NULL, '2025-12-14 02:33:32'),
(0, 'Phòng hội nghị', 'Phòng hội nghị chuyên nghiệp', 'assets/img/src/ui/horizontal/hoi-nghi-khach-san-o-bien-hoa.jpg', NULL, 'events', 74, 'active', NULL, '2025-12-14 02:33:32'),
(0, 'Sự kiện hội nghị', 'Tổ chức sự kiện hội nghị', 'assets/img/src/ui/horizontal/Hoi-nghi-aurora-8.jpg', NULL, 'events', 75, 'active', NULL, '2025-12-14 02:33:32'),
(0, 'Hội nghị Aurora', 'Hội nghị tại Aurora Hotel', 'assets/img/service/meet/Hoi-nghi-aurora-5.jpg', NULL, 'events', 76, 'active', NULL, '2025-12-14 02:33:32'),
(0, 'Phòng họp lớn', 'Phòng họp quy mô lớn', 'assets/img/service/meet/Hoi-nghi-aurora-6.jpg', NULL, 'events', 77, 'active', NULL, '2025-12-14 02:33:32'),
(0, 'Aurora Hotel Plaza', 'Toàn cảnh Aurora Hotel Plaza', 'assets/img/hero-banner/aurora-hotel-bien-hoa-1.jpg', NULL, 'exterior', 78, 'active', NULL, '2025-12-14 02:33:32'),
(0, 'Mặt tiền khách sạn', 'Mặt tiền khách sạn ấn tượng', 'assets/img/hero-banner/aurora-hotel-bien-hoa-2.jpg', NULL, 'exterior', 79, 'active', NULL, '2025-12-14 02:33:32'),
(0, 'Khách sạn về đêm', 'Aurora Hotel lung linh về đêm', 'assets/img/hero-banner/aurora-hotel-bien-hoa-3.jpg', NULL, 'exterior', 80, 'active', NULL, '2025-12-14 02:33:32'),
(0, 'Toàn cảnh Aurora', 'Toàn cảnh khách sạn từ xa', 'assets/img/hero-banner/aurora-hotel-bien-hoa-4.jpg', NULL, 'exterior', 81, 'active', NULL, '2025-12-14 02:33:32'),
(0, 'Cafe Aurora', 'Quán cafe Aurora Hotel', 'assets/img/hero-banner/caffe-aurora-hotel-1.jpg', NULL, 'exterior', 82, 'active', NULL, '2025-12-14 02:33:32');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `membership_tiers`
--

CREATE TABLE `membership_tiers` (
  `tier_id` int(11) NOT NULL,
  `tier_name` varchar(50) NOT NULL,
  `tier_level` int(11) NOT NULL,
  `min_points` int(11) NOT NULL DEFAULT 0,
  `discount_percentage` decimal(5,2) DEFAULT 0.00,
  `benefits` text DEFAULT NULL,
  `color_code` varchar(7) DEFAULT '#000000',
  `icon` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `notifications`
--

CREATE TABLE `notifications` (
  `notification_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` varchar(50) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `link` varchar(255) DEFAULT NULL,
  `icon` varchar(50) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `page_content`
--

CREATE TABLE `page_content` (
  `content_id` int(11) NOT NULL,
  `page_name` varchar(100) NOT NULL,
  `section_name` varchar(100) NOT NULL,
  `content_key` varchar(100) NOT NULL,
  `content_value` text NOT NULL,
  `content_type` enum('text','html','image','json') DEFAULT 'text',
  `language` varchar(5) DEFAULT 'vi',
  `updated_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `password_resets`
--

CREATE TABLE `password_resets` (
  `reset_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `used` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `payments`
--

CREATE TABLE `payments` (
  `payment_id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `payment_method` enum('vnpay','cash','bank_transfer','credit_card') NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `currency` varchar(3) DEFAULT 'VND',
  `transaction_id` varchar(255) DEFAULT NULL,
  `vnpay_response` text DEFAULT NULL,
  `status` enum('pending','completed','failed','refunded') DEFAULT 'pending',
  `paid_at` timestamp NULL DEFAULT NULL,
  `refunded_at` timestamp NULL DEFAULT NULL,
  `refund_amount` decimal(10,2) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `payments`
--

INSERT INTO `payments` (`payment_id`, `booking_id`, `payment_method`, `amount`, `currency`, `transaction_id`, `vnpay_response`, `status`, `paid_at`, `refunded_at`, `refund_amount`, `notes`, `created_at`, `updated_at`) VALUES
(1, 1, 'credit_card', 3900000.00, 'VND', NULL, NULL, 'completed', '2025-11-19 01:01:14', NULL, NULL, '', '2025-11-19 01:01:14', '2025-11-19 01:01:14'),
(2, 2, 'cash', 3000000.00, 'VND', NULL, NULL, 'completed', '2025-11-19 01:40:54', NULL, NULL, NULL, '2025-11-19 01:40:54', '2025-11-19 01:40:54'),
(3, 6, 'vnpay', 4500000.00, 'VND', '15270095', '{\"vnp_Amount\":\"450000000\",\"vnp_BankCode\":\"NCB\",\"vnp_BankTranNo\":\"VNP15270095\",\"vnp_CardType\":\"ATM\",\"vnp_OrderInfo\":\"Thanh toan dat phong BK20251119A1664F\",\"vnp_PayDate\":\"20251119111050\",\"vnp_ResponseCode\":\"00\",\"vnp_TmnCode\":\"ZWJBID1P\",\"vnp_TransactionNo\":\"15270095\",\"vnp_TransactionStatus\":\"00\",\"vnp_TxnRef\":\"BK20251119A1664F\",\"vnp_SecureHash\":\"7539e3f2bb647c4883f91d3716eff27387d84199b1f98728319e26ceb57fefdba6023389b95967f2e4ac468d2a959bf335d4b8d932fcde9734a54317a1d44175\"}', 'completed', '2025-11-19 04:10:55', NULL, NULL, NULL, '2025-11-19 04:10:55', '2025-11-19 04:10:55'),
(0, 0, 'vnpay', 1400000.00, 'VND', '15305547', '{\"vnp_Amount\":\"140000000\",\"vnp_BankCode\":\"NCB\",\"vnp_BankTranNo\":\"VNP15305547\",\"vnp_CardType\":\"ATM\",\"vnp_OrderInfo\":\"Thanh toan dat phong BK202511272C145F\",\"vnp_PayDate\":\"20251127162450\",\"vnp_ResponseCode\":\"00\",\"vnp_TmnCode\":\"ZWJBID1P\",\"vnp_TransactionNo\":\"15305547\",\"vnp_TransactionStatus\":\"00\",\"vnp_TxnRef\":\"BK202511272C145F\",\"vnp_SecureHash\":\"dc96cb1d6e6cde142b9306042620126661b7206ec0327ecea881898185244b917d0e2876ab0b54907b6533edf2c3fe0bf25e4901c6415c344ed1344dc7665319\"}', 'completed', '2025-11-27 09:24:59', NULL, NULL, NULL, '2025-11-27 09:24:59', '2025-11-27 09:24:59');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `points_transactions`
--

CREATE TABLE `points_transactions` (
  `transaction_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `points` int(11) NOT NULL,
  `transaction_type` enum('earn','redeem','expire','adjust') NOT NULL,
  `reference_type` varchar(50) DEFAULT NULL,
  `reference_id` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `promotions`
--

CREATE TABLE `promotions` (
  `promotion_id` int(11) NOT NULL,
  `promotion_code` varchar(50) NOT NULL,
  `promotion_name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `discount_type` enum('percentage','fixed_amount') NOT NULL,
  `discount_value` decimal(10,2) NOT NULL,
  `min_booking_amount` decimal(10,2) DEFAULT NULL,
  `max_discount` decimal(10,2) DEFAULT NULL,
  `usage_limit` int(11) DEFAULT NULL,
  `usage_per_user` int(11) DEFAULT 1,
  `used_count` int(11) DEFAULT 0,
  `applicable_to` enum('all','rooms','apartments','services') DEFAULT 'all',
  `start_date` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `end_date` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `status` enum('active','inactive','expired') DEFAULT 'active',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `promotions`
--

INSERT INTO `promotions` (`promotion_id`, `promotion_code`, `promotion_name`, `description`, `discount_type`, `discount_value`, `min_booking_amount`, `max_discount`, `usage_limit`, `usage_per_user`, `used_count`, `applicable_to`, `start_date`, `end_date`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'TEACHERDAYS2025', 'Mừng ngày nhà giáo Việt Nam', 'Giảm 5% cho đơn tối thiểu 1 triệu, tối đa giảm 250.000vnđ', 'percentage', 5.00, 100000.00, 250000.00, 2, 1, 0, 'rooms', '2025-11-20 10:50:00', '2025-11-21 10:50:00', 'active', 22, '2025-11-18 17:52:30', '2025-11-18 17:52:30');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `promotion_usage`
--

CREATE TABLE `promotion_usage` (
  `usage_id` int(11) NOT NULL,
  `promotion_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `booking_id` int(11) DEFAULT NULL,
  `discount_amount` decimal(10,2) NOT NULL,
  `used_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Bẫy `promotion_usage`
--
DELIMITER $$
CREATE TRIGGER `tr_promotion_usage_increment` AFTER INSERT ON `promotion_usage` FOR EACH ROW BEGIN
    UPDATE promotions 
    SET used_count = used_count + 1 
    WHERE promotion_id = NEW.promotion_id;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `push_subscriptions`
--

CREATE TABLE `push_subscriptions` (
  `subscription_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `endpoint` text NOT NULL,
  `auth_key` varchar(255) DEFAULT NULL,
  `p256dh_key` varchar(255) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `rate_limits`
--

CREATE TABLE `rate_limits` (
  `limit_id` int(11) NOT NULL,
  `identifier` varchar(255) NOT NULL,
  `action` varchar(50) NOT NULL,
  `attempts` int(11) DEFAULT 1,
  `last_attempt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `blocked_until` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `refunds`
--

CREATE TABLE `refunds` (
  `refund_id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `refund_amount` decimal(10,2) NOT NULL,
  `refund_percentage` decimal(5,2) DEFAULT 0.00,
  `processing_fee` decimal(10,2) DEFAULT 0.00,
  `refund_reason` text DEFAULT NULL,
  `refund_status` enum('pending','approved','processing','completed','rejected') DEFAULT 'pending',
  `refund_method` varchar(50) DEFAULT NULL COMMENT 'bank_transfer, cash, original_payment',
  `bank_name` varchar(100) DEFAULT NULL,
  `bank_account` varchar(50) DEFAULT NULL,
  `account_holder` varchar(100) DEFAULT NULL,
  `requested_by` int(11) NOT NULL COMMENT 'User ID who requested refund',
  `approved_by` int(11) DEFAULT NULL COMMENT 'Admin ID who approved',
  `processed_by` int(11) DEFAULT NULL COMMENT 'Admin ID who processed',
  `requested_at` datetime NOT NULL,
  `approved_at` datetime DEFAULT NULL,
  `processed_at` datetime DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `reviews`
--

CREATE TABLE `reviews` (
  `review_id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `room_type_id` int(11) DEFAULT NULL,
  `rating` decimal(2,1) NOT NULL,
  `cleanliness_rating` int(11) DEFAULT NULL,
  `service_rating` int(11) DEFAULT NULL,
  `location_rating` int(11) DEFAULT NULL,
  `value_rating` int(11) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `comment` text DEFAULT NULL,
  `pros` text DEFAULT NULL,
  `cons` text DEFAULT NULL,
  `images` text DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `helpful_count` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `review_responses`
--

CREATE TABLE `review_responses` (
  `response_id` int(11) NOT NULL,
  `review_id` int(11) NOT NULL,
  `staff_id` int(11) NOT NULL,
  `response` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `role_permissions`
--

CREATE TABLE `role_permissions` (
  `permission_id` int(11) NOT NULL,
  `role` enum('admin','receptionist','sale','customer') NOT NULL,
  `module` varchar(50) NOT NULL COMMENT 'bookings, customers, rooms, pricing, etc',
  `action` varchar(50) NOT NULL COMMENT 'view, create, update, delete, etc',
  `allowed` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `role_permissions`
--

INSERT INTO `role_permissions` (`permission_id`, `role`, `module`, `action`, `allowed`, `created_at`) VALUES
(1, 'admin', 'bookings', 'view', 1, '2025-11-19 01:49:08'),
(2, 'admin', 'bookings', 'create', 1, '2025-11-19 01:49:08'),
(3, 'admin', 'bookings', 'update', 1, '2025-11-19 01:49:08'),
(4, 'admin', 'bookings', 'delete', 1, '2025-11-19 01:49:08'),
(5, 'admin', 'bookings', 'confirm', 1, '2025-11-19 01:49:08'),
(6, 'admin', 'bookings', 'cancel', 1, '2025-11-19 01:49:08'),
(7, 'admin', 'bookings', 'assign_room', 1, '2025-11-19 01:49:08'),
(8, 'admin', 'bookings', 'checkin', 1, '2025-11-19 01:49:08'),
(9, 'admin', 'bookings', 'checkout', 1, '2025-11-19 01:49:08'),
(10, 'admin', 'customers', 'view', 1, '2025-11-19 01:49:08'),
(11, 'admin', 'customers', 'create', 1, '2025-11-19 01:49:08'),
(12, 'admin', 'customers', 'update', 1, '2025-11-19 01:49:08'),
(13, 'admin', 'customers', 'delete', 1, '2025-11-19 01:49:08'),
(14, 'admin', 'rooms', 'view', 1, '2025-11-19 01:49:08'),
(15, 'admin', 'rooms', 'create', 1, '2025-11-19 01:49:08'),
(16, 'admin', 'rooms', 'update', 1, '2025-11-19 01:49:08'),
(17, 'admin', 'rooms', 'delete', 1, '2025-11-19 01:49:08'),
(18, 'admin', 'pricing', 'view', 1, '2025-11-19 01:49:08'),
(19, 'admin', 'pricing', 'update', 1, '2025-11-19 01:49:08'),
(20, 'admin', 'promotions', 'view', 1, '2025-11-19 01:49:08'),
(21, 'admin', 'promotions', 'create', 1, '2025-11-19 01:49:08'),
(22, 'admin', 'promotions', 'update', 1, '2025-11-19 01:49:08'),
(23, 'admin', 'promotions', 'delete', 1, '2025-11-19 01:49:08'),
(24, 'admin', 'loyalty', 'view', 1, '2025-11-19 01:49:08'),
(25, 'admin', 'loyalty', 'update', 1, '2025-11-19 01:49:08'),
(26, 'admin', 'loyalty', 'adjust_points', 1, '2025-11-19 01:49:08'),
(27, 'admin', 'payments', 'view', 1, '2025-11-19 01:49:08'),
(28, 'admin', 'payments', 'confirm', 1, '2025-11-19 01:49:08'),
(29, 'admin', 'reports', 'view', 1, '2025-11-19 01:49:08'),
(30, 'admin', 'settings', 'view', 1, '2025-11-19 01:49:08'),
(31, 'admin', 'settings', 'update', 1, '2025-11-19 01:49:08'),
(32, 'admin', 'permissions', 'manage', 1, '2025-11-19 01:49:08'),
(33, 'receptionist', 'bookings', 'view', 1, '2025-11-19 01:49:08'),
(34, 'receptionist', 'bookings', 'create', 1, '2025-11-19 01:49:08'),
(35, 'receptionist', 'bookings', 'update', 1, '2025-11-19 01:49:08'),
(36, 'receptionist', 'bookings', 'confirm', 1, '2025-11-19 01:49:08'),
(37, 'receptionist', 'bookings', 'cancel', 1, '2025-11-19 01:49:08'),
(38, 'receptionist', 'bookings', 'assign_room', 1, '2025-11-19 01:49:08'),
(39, 'receptionist', 'bookings', 'checkin', 1, '2025-11-19 01:49:08'),
(40, 'receptionist', 'bookings', 'checkout', 1, '2025-11-19 01:49:08'),
(41, 'receptionist', 'customers', 'view', 1, '2025-11-19 01:49:08'),
(42, 'receptionist', 'customers', 'create', 1, '2025-11-19 01:49:08'),
(43, 'receptionist', 'customers', 'update', 1, '2025-11-19 01:49:08'),
(44, 'receptionist', 'rooms', 'view', 1, '2025-11-19 01:49:08'),
(45, 'receptionist', 'rooms', 'update', 1, '2025-11-19 01:49:08'),
(46, 'receptionist', 'pricing', 'view', 1, '2025-11-19 01:49:08'),
(47, 'receptionist', 'promotions', 'view', 0, '2025-11-19 01:49:08'),
(48, 'receptionist', 'loyalty', 'view', 0, '2025-11-19 01:49:08'),
(49, 'receptionist', 'payments', 'view', 1, '2025-11-19 01:49:08'),
(50, 'receptionist', 'payments', 'confirm', 1, '2025-11-19 01:49:08'),
(51, 'receptionist', 'reports', 'view', 0, '2025-11-19 01:49:08'),
(52, 'sale', 'bookings', 'view', 1, '2025-11-19 01:49:08'),
(53, 'sale', 'bookings', 'create', 1, '2025-11-19 01:49:08'),
(54, 'sale', 'bookings', 'update', 1, '2025-11-19 01:49:08'),
(55, 'sale', 'bookings', 'confirm', 1, '2025-11-19 01:49:08'),
(56, 'sale', 'bookings', 'cancel', 1, '2025-11-19 01:49:08'),
(57, 'sale', 'customers', 'view', 1, '2025-11-19 01:49:08'),
(58, 'sale', 'customers', 'create', 1, '2025-11-19 01:49:08'),
(59, 'sale', 'customers', 'update', 1, '2025-11-19 01:49:08'),
(60, 'sale', 'rooms', 'view', 1, '2025-11-19 01:49:08'),
(61, 'sale', 'pricing', 'view', 1, '2025-11-19 01:49:08'),
(62, 'sale', 'pricing', 'update', 1, '2025-11-19 01:49:08'),
(63, 'sale', 'promotions', 'view', 1, '2025-11-19 01:49:08'),
(64, 'sale', 'promotions', 'create', 1, '2025-11-19 01:49:08'),
(65, 'sale', 'promotions', 'update', 1, '2025-11-19 01:49:08'),
(66, 'sale', 'loyalty', 'view', 1, '2025-11-19 01:49:08'),
(67, 'sale', 'loyalty', 'adjust_points', 1, '2025-11-19 01:49:08'),
(68, 'sale', 'payments', 'view', 1, '2025-11-19 01:49:08'),
(69, 'sale', 'reports', 'view', 1, '2025-11-19 01:49:08'),
(70, 'customer', 'bookings', 'view', 1, '2025-11-19 01:49:08'),
(71, 'customer', 'bookings', 'create', 1, '2025-11-19 01:49:08'),
(72, 'customer', 'bookings', 'cancel', 1, '2025-11-19 01:49:08'),
(73, 'customer', 'profile', 'view', 1, '2025-11-19 01:49:08'),
(74, 'customer', 'profile', 'update', 1, '2025-11-19 01:49:08'),
(75, 'customer', 'loyalty', 'view', 1, '2025-11-19 01:49:08'),
(76, 'customer', 'payments', 'view', 1, '2025-11-19 01:49:08'),
(77, 'sale', 'bookings', 'assign_room', 0, '2025-11-19 01:57:31'),
(79, 'receptionist', 'loyalty', 'adjust_points', 0, '2025-11-19 01:58:40');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `rooms`
--

CREATE TABLE `rooms` (
  `room_id` int(11) NOT NULL,
  `room_type_id` int(11) NOT NULL,
  `room_number` varchar(20) NOT NULL,
  `floor` int(11) DEFAULT NULL,
  `building` varchar(50) DEFAULT NULL,
  `status` enum('available','occupied','maintenance','cleaning') DEFAULT 'available',
  `notes` text DEFAULT NULL,
  `last_cleaned` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `rooms`
--

INSERT INTO `rooms` (`room_id`, `room_type_id`, `room_number`, `floor`, `building`, `status`, `notes`, `last_cleaned`, `created_at`, `updated_at`) VALUES
(1, 1, '701', 7, 'Main', 'available', NULL, NULL, '2025-11-18 17:31:29', '2025-12-01 09:15:12'),
(2, 1, '702', 7, 'Main', 'available', NULL, NULL, '2025-11-18 17:31:29', '2025-12-01 09:15:06'),
(3, 1, '703', 7, 'Main', 'available', NULL, NULL, '2025-11-18 17:31:29', '2025-12-01 09:14:49'),
(4, 1, '704', 7, 'Main', 'available', NULL, NULL, '2025-11-18 17:31:29', '2025-11-22 07:56:46'),
(5, 1, '705', 7, 'Main', 'available', NULL, NULL, '2025-11-18 17:31:29', '2025-11-18 17:31:29'),
(7, 1, '707', 7, 'Main', 'available', NULL, NULL, '2025-11-18 17:31:29', '2025-11-18 17:31:29'),
(8, 1, '708', 7, 'Main', 'available', NULL, NULL, '2025-11-18 17:31:29', '2025-11-18 17:31:29'),
(9, 1, '709', 7, 'Main', 'available', NULL, NULL, '2025-11-18 17:31:29', '2025-11-18 17:31:29'),
(10, 1, '710', 7, 'Main', 'available', NULL, NULL, '2025-11-18 17:31:29', '2025-11-18 17:31:29'),
(11, 2, '711', 7, 'Main', 'available', NULL, NULL, '2025-11-18 17:31:29', '2025-11-18 17:31:29'),
(12, 2, '712', 7, 'Main', 'available', NULL, NULL, '2025-11-18 17:31:29', '2025-11-22 07:57:12'),
(13, 2, '714', 7, 'Main', 'available', NULL, NULL, '2025-11-18 17:31:29', '2025-11-18 17:31:29'),
(14, 2, '715', 7, 'Main', 'available', NULL, NULL, '2025-11-18 17:31:29', '2025-11-18 17:31:29'),
(15, 2, '716', 7, 'Main', 'available', NULL, NULL, '2025-11-18 17:31:29', '2025-11-18 17:31:29'),
(16, 2, '717', 7, 'Main', 'available', NULL, NULL, '2025-11-18 17:31:29', '2025-11-18 17:31:29'),
(17, 2, '718', 7, 'Main', 'available', NULL, NULL, '2025-11-18 17:31:29', '2025-11-18 17:31:29'),
(18, 2, '719', 7, 'Main', 'available', NULL, NULL, '2025-11-18 17:31:29', '2025-11-18 17:31:29'),
(19, 2, '720', 7, 'Main', 'available', NULL, NULL, '2025-11-18 17:31:29', '2025-11-18 17:31:29'),
(20, 3, '801', 8, 'Main', 'available', NULL, NULL, '2025-11-18 17:31:29', '2025-11-18 17:31:29'),
(21, 3, '802', 8, 'Main', 'available', NULL, NULL, '2025-11-18 17:31:29', '2025-11-18 17:31:29'),
(22, 3, '803', 8, 'Main', 'available', NULL, NULL, '2025-11-18 17:31:29', '2025-11-18 17:31:29'),
(23, 3, '804', 8, 'Main', 'available', NULL, NULL, '2025-11-18 17:31:29', '2025-11-18 17:31:29'),
(24, 3, '805', 8, 'Main', 'available', NULL, NULL, '2025-11-18 17:31:29', '2025-11-18 17:31:29'),
(25, 3, '806', 8, 'Main', 'available', NULL, NULL, '2025-11-18 17:31:29', '2025-11-18 17:31:29'),
(26, 3, '807', 8, 'Main', 'available', NULL, NULL, '2025-11-18 17:31:29', '2025-11-18 17:31:29'),
(27, 3, '808', 8, 'Main', 'available', NULL, NULL, '2025-11-18 17:31:29', '2025-11-18 17:31:29'),
(28, 3, '809', 8, 'Main', 'available', NULL, NULL, '2025-11-18 17:31:29', '2025-11-18 17:31:29'),
(29, 3, '810', 8, 'Main', 'available', NULL, NULL, '2025-11-18 17:31:29', '2025-11-18 17:31:29'),
(30, 4, '811', 8, 'Main', 'occupied', NULL, NULL, '2025-11-18 17:31:29', '2025-11-19 04:10:18'),
(31, 4, '812', 8, 'Main', 'available', NULL, NULL, '2025-11-18 17:31:29', '2025-11-18 17:31:29'),
(32, 4, '814', 8, 'Main', 'available', NULL, NULL, '2025-11-18 17:31:29', '2025-11-18 17:31:29'),
(33, 4, '815', 8, 'Main', 'available', NULL, NULL, '2025-11-18 17:31:29', '2025-11-18 17:31:29'),
(34, 4, '816', 8, 'Main', 'available', NULL, NULL, '2025-11-18 17:31:29', '2025-11-18 17:31:29'),
(35, 4, '817', 8, 'Main', 'available', NULL, NULL, '2025-11-18 17:31:29', '2025-11-18 17:31:29'),
(36, 4, '818', 8, 'Main', 'available', NULL, NULL, '2025-11-18 17:31:29', '2025-11-18 17:31:29'),
(37, 4, '819', 8, 'Main', 'available', NULL, NULL, '2025-11-18 17:31:29', '2025-11-18 17:31:29'),
(38, 5, '901', 9, 'Main', 'available', NULL, NULL, '2025-11-18 17:31:29', '2025-11-18 17:31:29'),
(39, 5, '902', 9, 'Main', 'available', NULL, NULL, '2025-11-18 17:31:29', '2025-11-18 17:31:29'),
(40, 5, '903', 9, 'Main', 'available', NULL, NULL, '2025-11-18 17:31:29', '2025-11-18 17:31:29'),
(41, 5, '904', 9, 'Main', 'available', NULL, NULL, '2025-11-18 17:31:29', '2025-11-18 17:31:29'),
(42, 5, '905', 9, 'Main', 'available', NULL, NULL, '2025-11-18 17:31:29', '2025-11-18 17:31:29'),
(43, 5, '906', 9, 'Main', 'available', NULL, NULL, '2025-11-18 17:31:29', '2025-11-18 17:31:29'),
(44, 5, '907', 9, 'Main', 'available', NULL, NULL, '2025-11-18 17:31:29', '2025-11-18 17:31:29'),
(45, 5, '908', 9, 'Main', 'available', NULL, NULL, '2025-11-18 17:31:29', '2025-11-18 17:31:29'),
(46, 5, '909', 9, 'Main', 'available', NULL, NULL, '2025-11-18 17:31:29', '2025-11-18 17:31:29'),
(47, 5, '910', 9, 'Main', 'available', NULL, NULL, '2025-11-18 17:31:29', '2025-11-18 17:31:29'),
(48, 4, '911', 9, 'Main', 'available', NULL, NULL, '2025-11-18 17:31:29', '2025-11-18 18:27:55'),
(49, 6, '912', 9, 'Main', 'available', NULL, NULL, '2025-11-18 17:31:29', '2025-11-18 17:31:29'),
(50, 6, '914', 9, 'Main', 'available', NULL, NULL, '2025-11-18 17:31:29', '2025-11-18 17:31:29'),
(51, 6, '915', 9, 'Main', 'available', NULL, NULL, '2025-11-18 17:31:29', '2025-11-18 17:31:29'),
(52, 6, '916', 9, 'Main', 'available', NULL, NULL, '2025-11-18 17:31:29', '2025-11-18 17:31:29'),
(53, 6, '917', 9, 'Main', 'available', NULL, NULL, '2025-11-18 17:31:29', '2025-11-18 17:31:29'),
(54, 6, '918', 9, 'Main', 'available', NULL, NULL, '2025-11-18 17:31:29', '2025-11-18 17:31:29'),
(55, 6, '919', 9, 'Main', 'available', NULL, NULL, '2025-11-18 17:31:29', '2025-11-18 17:31:29'),
(56, 6, '920', 9, 'Main', 'available', NULL, NULL, '2025-11-18 17:31:29', '2025-11-18 17:31:29'),
(57, 6, '921', 9, 'Main', 'available', NULL, NULL, '2025-11-18 17:31:29', '2025-11-18 17:31:29'),
(58, 6, '922', 9, 'Main', 'available', NULL, NULL, '2025-11-18 17:31:29', '2025-11-18 17:31:29'),
(59, 4, '923', 9, 'Main', 'available', NULL, NULL, '2025-11-18 17:31:29', '2025-11-18 18:28:04'),
(60, 7, '1001', 10, 'Main', 'available', NULL, NULL, '2025-11-18 17:31:29', '2025-11-18 17:31:29'),
(61, 7, '1002', 10, 'Main', 'available', NULL, NULL, '2025-11-18 17:31:29', '2025-11-18 17:31:29'),
(62, 7, '1003', 10, 'Main', 'available', NULL, NULL, '2025-11-18 17:31:29', '2025-11-18 17:31:29'),
(63, 7, '1004', 10, 'Main', 'available', NULL, NULL, '2025-11-18 17:31:29', '2025-11-18 17:31:29'),
(64, 7, '1005', 10, 'Main', 'available', NULL, NULL, '2025-11-18 17:31:29', '2025-11-18 17:31:29'),
(65, 7, '1006', 10, 'Main', 'available', NULL, NULL, '2025-11-18 17:31:29', '2025-11-18 17:31:29'),
(66, 7, '1007', 10, 'Main', 'available', NULL, NULL, '2025-11-18 17:31:29', '2025-11-18 17:31:29'),
(67, 7, '1008', 10, 'Main', 'available', NULL, NULL, '2025-11-18 17:31:29', '2025-11-18 17:31:29'),
(68, 7, '1009', 10, 'Main', 'available', NULL, NULL, '2025-11-18 17:31:29', '2025-11-18 17:31:29'),
(69, 7, '1010', 10, 'Main', 'available', NULL, NULL, '2025-11-18 17:31:29', '2025-11-18 17:31:29'),
(70, 7, '1011', 10, 'Main', 'available', NULL, NULL, '2025-11-18 17:31:29', '2025-11-18 17:31:29'),
(71, 8, '1012', 10, 'Main', 'available', NULL, NULL, '2025-11-18 17:31:29', '2025-11-18 17:31:29'),
(72, 8, '1014', 10, 'Main', 'available', NULL, NULL, '2025-11-18 17:31:29', '2025-11-18 17:31:29'),
(73, 8, '1015', 10, 'Main', 'available', NULL, NULL, '2025-11-18 17:31:29', '2025-11-18 17:31:29'),
(74, 8, '1016', 10, 'Main', 'available', NULL, NULL, '2025-11-18 17:31:29', '2025-11-18 17:31:29'),
(75, 8, '1017', 10, 'Main', 'available', NULL, NULL, '2025-11-18 17:31:29', '2025-11-18 17:31:29'),
(76, 8, '1018', 10, 'Main', 'available', NULL, NULL, '2025-11-18 17:31:29', '2025-11-18 17:31:29'),
(77, 8, '1019', 10, 'Main', 'available', NULL, NULL, '2025-11-18 17:31:29', '2025-11-18 17:31:29'),
(78, 8, '1020', 10, 'Main', 'available', NULL, NULL, '2025-11-18 17:31:29', '2025-11-18 17:31:29'),
(79, 8, '1021', 10, 'Main', 'available', NULL, NULL, '2025-11-18 17:31:29', '2025-11-18 17:31:29'),
(80, 8, '1022', 10, 'Main', 'available', NULL, NULL, '2025-11-18 17:31:29', '2025-11-18 17:31:29'),
(81, 8, '1023', 10, 'Main', 'available', NULL, NULL, '2025-11-18 17:31:29', '2025-11-18 17:31:29'),
(82, 9, '1101', 11, 'Main', 'available', NULL, NULL, '2025-11-18 17:31:29', '2025-11-18 17:31:29'),
(83, 9, '1102', 11, 'Main', 'available', NULL, NULL, '2025-11-18 17:31:29', '2025-11-18 17:31:29'),
(84, 9, '1103', 11, 'Main', 'available', NULL, NULL, '2025-11-18 17:31:29', '2025-11-18 17:31:29'),
(85, 9, '1104', 11, 'Main', 'available', NULL, NULL, '2025-11-18 17:31:29', '2025-11-18 17:31:29'),
(86, 9, '1105', 11, 'Main', 'available', NULL, NULL, '2025-11-18 17:31:29', '2025-11-18 17:31:29'),
(87, 9, '1106', 11, 'Main', 'available', NULL, NULL, '2025-11-18 17:31:29', '2025-11-18 17:31:29'),
(88, 9, '1107', 11, 'Main', 'available', NULL, NULL, '2025-11-18 17:31:29', '2025-11-18 17:31:29'),
(89, 9, '1108', 11, 'Main', 'available', NULL, NULL, '2025-11-18 17:31:29', '2025-11-18 17:31:29'),
(90, 9, '1109', 11, 'Main', 'available', NULL, NULL, '2025-11-18 17:31:29', '2025-11-18 17:31:29'),
(91, 9, '1110', 11, 'Main', 'available', NULL, NULL, '2025-11-18 17:31:29', '2025-11-18 17:31:29'),
(92, 9, '1111', 11, 'Main', 'available', NULL, NULL, '2025-11-18 17:31:29', '2025-11-18 17:31:29'),
(93, 10, '1112', 11, 'Main', 'available', NULL, NULL, '2025-11-18 17:31:29', '2025-11-18 17:31:29'),
(94, 10, '1114', 11, 'Main', 'available', NULL, NULL, '2025-11-18 17:31:29', '2025-11-18 17:31:29'),
(95, 10, '1115', 11, 'Main', 'available', NULL, NULL, '2025-11-18 17:31:29', '2025-11-18 17:31:29'),
(96, 10, '1116', 11, 'Main', 'available', NULL, NULL, '2025-11-18 17:31:29', '2025-11-18 17:31:29'),
(97, 10, '1117', 11, 'Main', 'available', NULL, NULL, '2025-11-18 17:31:29', '2025-11-18 17:31:29'),
(98, 10, '1118', 11, 'Main', 'available', NULL, NULL, '2025-11-18 17:31:29', '2025-11-18 17:31:29'),
(99, 10, '1119', 11, 'Main', 'available', NULL, NULL, '2025-11-18 17:31:29', '2025-11-18 17:31:29'),
(100, 10, '1120', 11, 'Main', 'available', NULL, NULL, '2025-11-18 17:31:29', '2025-11-18 17:31:29'),
(101, 10, '1121', 11, 'Main', 'available', NULL, NULL, '2025-11-18 17:31:29', '2025-11-18 17:31:29'),
(102, 10, '1122', 11, 'Main', 'available', NULL, NULL, '2025-11-18 17:31:29', '2025-11-18 17:31:29'),
(103, 10, '1123', 11, 'Main', 'available', NULL, NULL, '2025-11-18 17:31:29', '2025-11-18 17:31:29'),
(104, 11, '1201', 12, 'Main', 'available', NULL, NULL, '2025-11-18 17:31:29', '2025-11-18 17:31:29'),
(105, 11, '1202', 12, 'Main', 'available', NULL, NULL, '2025-11-18 17:31:29', '2025-11-18 17:31:29'),
(106, 11, '1203', 12, 'Main', 'available', NULL, NULL, '2025-11-18 17:31:29', '2025-11-18 17:31:29'),
(107, 11, '1204', 12, 'Main', 'available', NULL, NULL, '2025-11-18 17:31:29', '2025-11-18 17:31:29'),
(108, 11, '1205', 12, 'Main', 'available', NULL, NULL, '2025-11-18 17:31:29', '2025-11-18 17:31:29'),
(109, 11, '1206', 12, 'Main', 'available', NULL, NULL, '2025-11-18 17:31:29', '2025-11-18 17:31:29'),
(110, 11, '1207', 12, 'Main', 'available', NULL, NULL, '2025-11-18 17:31:29', '2025-11-18 17:31:29'),
(111, 11, '1208', 12, 'Main', 'available', NULL, NULL, '2025-11-18 17:31:29', '2025-11-18 17:31:29'),
(112, 11, '1209', 12, 'Main', 'available', NULL, NULL, '2025-11-18 17:31:29', '2025-11-18 17:31:29'),
(113, 11, '1210', 12, 'Main', 'available', NULL, NULL, '2025-11-18 17:31:29', '2025-11-18 17:31:29'),
(114, 12, '1211', 12, 'Main', 'available', NULL, NULL, '2025-11-18 17:31:29', '2025-11-18 17:31:29'),
(115, 12, '1212', 12, 'Main', 'available', NULL, NULL, '2025-11-18 17:31:29', '2025-11-18 17:31:29'),
(116, 12, '1214', 12, 'Main', 'available', NULL, NULL, '2025-11-18 17:31:29', '2025-11-18 17:31:29'),
(117, 12, '1215', 12, 'Main', 'available', NULL, NULL, '2025-11-18 17:31:29', '2025-11-18 17:31:29'),
(118, 12, '1216', 12, 'Main', 'available', NULL, NULL, '2025-11-18 17:31:29', '2025-11-18 17:31:29'),
(119, 13, '1217', 12, 'Main', 'available', NULL, NULL, '2025-11-18 17:31:29', '2025-11-18 17:31:29'),
(120, 13, '1218', 12, 'Main', 'available', NULL, NULL, '2025-11-18 17:31:29', '2025-11-18 17:31:29'),
(121, 13, '1219', 12, 'Main', 'available', NULL, NULL, '2025-11-18 17:31:29', '2025-11-18 17:31:29'),
(122, 13, '1220', 12, 'Main', 'available', NULL, NULL, '2025-11-18 17:31:29', '2025-11-18 17:31:29');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `room_pricing`
--

CREATE TABLE `room_pricing` (
  `pricing_id` int(11) NOT NULL,
  `room_type_id` int(11) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `pricing_type` enum('special','seasonal','promotion') DEFAULT 'special',
  `description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `room_types`
--

CREATE TABLE `room_types` (
  `room_type_id` int(11) NOT NULL,
  `type_name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `category` enum('room','apartment') NOT NULL DEFAULT 'room',
  `description` text DEFAULT NULL,
  `short_description` varchar(255) DEFAULT NULL,
  `max_occupancy` int(11) NOT NULL DEFAULT 2,
  `size_sqm` decimal(10,2) DEFAULT NULL,
  `bed_type` varchar(100) DEFAULT NULL,
  `amenities` text DEFAULT NULL,
  `images` text DEFAULT NULL,
  `thumbnail` varchar(255) DEFAULT NULL,
  `base_price` decimal(10,2) NOT NULL,
  `weekend_price` decimal(10,2) DEFAULT NULL,
  `holiday_price` decimal(10,2) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `room_types`
--

INSERT INTO `room_types` (`room_type_id`, `type_name`, `slug`, `category`, `description`, `short_description`, `max_occupancy`, `size_sqm`, `bed_type`, `amenities`, `images`, `thumbnail`, `base_price`, `weekend_price`, `holiday_price`, `status`, `sort_order`, `created_at`, `updated_at`) VALUES
(1, 'Deluxe', 'deluxe', 'room', 'Phòng Deluxe rộng 35m² được thiết kế hiện đại với giường King size cao cấp, tầm nhìn thành phố tuyệt đẹp. Phòng được trang bị đầy đủ tiện nghi như TV màn hình phẳng, minibar, két an toàn và phòng tắm riêng với vòi sen massage. Đây là lựa chọn hoàn hảo cho cặp đôi hoặc khách công tác.', 'Không gian sang trọng với đầy đủ tiện nghi hiện đại', 2, 35.00, '1 Giường King', 'WiFi miễn phí,TV màn hình phẳng,Minibar,Két an toàn,Điều hòa,Phòng tắm riêng,Vòi sen massage,Máy sấy tóc,Đồ vệ sinh cá nhân,Bàn làm việc,Điện thoại,Dép đi trong phòng,Áo choàng tắm', '/assets/img/deluxe/DELUXE-ROOM-AURORA-1.jpg,/assets/img/deluxe/DELUXE-ROOM-AURORA-2.jpg,/assets/img/deluxe/DELUXE-ROOM-AURORA-3.jpg', '/assets/img/deluxe/DELUXE-ROOM-AURORA-1.jpg', 1400000.00, 2200000.00, 2500000.00, 'active', 1, '2025-11-18 17:31:03', '2025-11-25 04:26:03'),
(2, 'Premium Deluxe', 'premium-deluxe', 'room', 'Phòng Premium Deluxe 40m² mang đến trải nghiệm cao cấp hơn với không gian rộng rãi, giường King size sang trọng và khu vực tiếp khách riêng biệt. Phòng có tầm nhìn panorama tuyệt đẹp ra thành phố, được trang bị đầy đủ tiện nghi hiện đại bao gồm TV thông minh, hệ thống âm thanh cao cấp và phòng tắm với bồn tắm nằm.', 'Không gian cao cấp với tầm nhìn panorama tuyệt đẹp', 2, 40.00, '1 Giường King', 'WiFi miễn phí,TV thông minh,Hệ thống âm thanh,Minibar cao cấp,Két an toàn,Điều hòa,Phòng tắm với bồn tắm,Vòi sen massage,Máy sấy tóc,Đồ vệ sinh cao cấp,Bàn làm việc,Khu vực tiếp khách,Máy pha cà phê,Áo choàng tắm cao cấp', '/assets/img/premium-deluxe/premium-deluxe-aurora-hotel-1.jpg,/assets/img/premium-deluxe/premium-deluxe-aurora-hotel-2.jpg', '/assets/img/premium-deluxe/premium-deluxe-aurora-hotel-1.jpg', 1500000.00, 3000000.00, 3500000.00, 'active', 2, '2025-11-18 17:31:03', '2025-11-25 04:24:18'),
(3, 'Premium Twin', 'premium-twin', 'room', 'Phòng Premium Twin 38m² được thiết kế đặc biệt cho nhóm bạn hoặc gia đình nhỏ với 2 giường đơn cao cấp. Không gian hiện đại, thoáng đãng với đầy đủ tiện nghi như TV màn hình phẳng, minibar, két an toàn và phòng tắm riêng với vòi sen massage. Phòng có tầm nhìn đẹp ra thành phố.', 'Lý tưởng cho nhóm bạn hoặc gia đình với 2 giường đơn', 2, 38.00, '2 Giường Đơn', 'WiFi miễn phí,TV màn hình phẳng,Minibar,Két an toàn,Điều hòa,Phòng tắm riêng,Vòi sen massage,Máy sấy tóc,Đồ vệ sinh cá nhân,Bàn làm việc,Điện thoại,Dép đi trong phòng,Áo choàng tắm', '/assets/img/premium-twin/premium-deluxe-twin-aurora-1.jpg,/assets/img/premium-twin/premium-deluxe-twin-aurora-2.jpg', '/assets/img/premium-twin/premium-deluxe-twin-aurora-1.jpg', 2200000.00, 2700000.00, 3000000.00, 'active', 3, '2025-11-18 17:31:03', '2025-11-25 04:24:18'),
(4, 'VIP Suite', 'vip-suite', 'room', 'VIP Suite 60m² là đỉnh cao của sự sang trọng với phòng ngủ riêng biệt, phòng khách rộng rãi và phòng tắm cao cấp với bồn tắm Jacuzzi. Giường King size đặc biệt, tầm nhìn panorama 180 độ ra thành phố. Được trang bị đầy đủ tiện nghi 5 sao bao gồm TV thông minh, hệ thống âm thanh Bose, minibar cao cấp và dịch vụ butler 24/7.', 'Đỉnh cao sang trọng với không gian riêng tư tuyệt đối', 3, 60.00, '1 Giường King + Sofa bed', 'WiFi miễn phí,TV thông minh 55 inch,Hệ thống âm thanh Bose,Minibar cao cấp,Két an toàn điện tử,Điều hòa thông minh,Phòng tắm với Jacuzzi,Vòi sen massage,Máy sấy tóc Dyson,Đồ vệ sinh Hermes,Bàn làm việc executive,Phòng khách riêng,Máy pha cà phê Nespresso,Áo choàng tắm cao cấp,Dịch vụ butler 24/7', '/assets/img/vip/vip-room-aurora-hotel-1.jpg,/assets/img/vip/vip-room-aurora-hotel-3.jpg,/assets/img/vip/vip-room-aurora-hotel-4.jpg', '/assets/img/vip/vip-room-aurora-hotel-1.jpg', 4500000.00, 5500000.00, 6500000.00, 'active', 4, '2025-11-18 17:31:03', '2025-11-25 04:24:18'),
(5, 'Studio Apartment', 'studio-apartment', 'apartment', 'Căn hộ Studio 45m² được thiết kế thông minh với không gian mở, kết hợp phòng ngủ và phòng khách. Bếp nhỏ đầy đủ tiện nghi, giường Queen size thoải mái, khu vực làm việc riêng biệt. Lý tưởng cho khách lưu trú dài ngày hoặc cặp đôi muốn có không gian riêng tư.', 'Không gian thông minh cho lưu trú dài ngày', 2, 45.00, '1 Giường Queen', 'WiFi miễn phí,TV thông minh,Bếp nhỏ đầy đủ,Tủ lạnh,Lò vi sóng,Ấm đun nước,Bàn ăn,Minibar,Két an toàn,Điều hòa,Máy giặt,Phòng tắm riêng,Vòi sen,Máy sấy tóc,Bàn làm việc,Sofa,Ban công', '/assets/img/studio-apartment/can-ho-studio-aurora-hotel-1.jpg,/assets/img/studio-apartment/can-ho-studio-aurora-hotel-2.jpg', '/assets/img/studio-apartment/can-ho-studio-aurora-hotel-1.jpg', 2800000.00, 3300000.00, 3800000.00, 'active', 11, '2025-11-18 17:31:03', '2025-11-25 04:24:18'),
(6, 'Modern Studio', 'modern-studio', 'apartment', 'Modern Studio 48m² với thiết kế hiện đại tối giản, nội thất cao cấp và hệ thống smart home. Không gian mở thoáng đãng, bếp hiện đại đầy đủ thiết bị, giường King size sang trọng. Tầm nhìn đẹp và ánh sáng tự nhiên tràn ngập.', 'Thiết kế hiện đại với hệ thống smart home', 2, 48.00, '1 Giường King', 'WiFi miễn phí,TV thông minh,Smart home system,Bếp hiện đại,Tủ lạnh,Lò vi sóng,Máy rửa chén,Ấm đun nước,Bàn ăn,Minibar,Két an toàn,Điều hòa thông minh,Máy giặt,Phòng tắm cao cấp,Vòi sen massage,Máy sấy tóc,Bàn làm việc,Sofa cao cấp,Ban công rộng', '/assets/img/modern-studio-apartment/modern-studio-apartment-1.jpg,/assets/img/modern-studio-apartment/modern-studio-apartment-2.jpg', '/assets/img/modern-studio-apartment/modern-studio-apartment-1.jpg', 3200000.00, 3800000.00, 4300000.00, 'active', 5, '2025-11-18 17:31:03', '2025-11-25 04:24:18'),
(7, 'Indochine Studio', 'indochine-studio', 'apartment', 'Indochine Studio 46m² mang phong cách Đông Dương độc đáo với nội thất gỗ tự nhiên, họa tiết truyền thống kết hợp hiện đại. Không gian ấm cúng, bếp đầy đủ tiện nghi, giường Queen size thoải mái. Lựa chọn hoàn hảo cho những ai yêu thích văn hóa Việt.', 'Phong cách Đông Dương độc đáo và ấm cúng', 2, 46.00, '1 Giường Queen', 'WiFi miễn phí,TV màn hình phẳng,Bếp đầy đủ,Tủ lạnh,Lò vi sóng,Ấm đun nước,Bàn ăn,Minibar,Két an toàn,Điều hòa,Máy giặt,Phòng tắm,Vòi sen,Máy sấy tóc,Bàn làm việc,Sofa,Ban công,Trang trí Đông Dương', '/assets/img/indochine-studio-apartment/indochine-studio-apartment-1.jpg,/assets/img/indochine-studio-apartment/indochine-studio-apartment-2.jpg', '/assets/img/indochine-studio-apartment/indochine-studio-apartment-1.jpg', 3000000.00, 3500000.00, 4000000.00, 'active', 6, '2025-11-18 17:31:03', '2025-11-25 04:24:18'),
(8, 'Premium Apartment', 'premium-apartment', 'apartment', 'Premium Apartment 65m² với phòng ngủ riêng biệt, phòng khách rộng rãi và bếp đầy đủ tiện nghi cao cấp. Giường King size sang trọng, khu vực làm việc executive, phòng tắm với bồn tắm. Tầm nhìn đẹp ra thành phố, lý tưởng cho gia đình hoặc lưu trú dài hạn.', 'Không gian cao cấp với phòng ngủ riêng biệt', 3, 65.00, '1 Giường King + Sofa bed', 'WiFi miễn phí,TV thông minh,Bếp cao cấp đầy đủ,Tủ lạnh lớn,Lò vi sóng,Máy rửa chén,Ấm đun nước,Bàn ăn 4 chỗ,Minibar,Két an toàn,Điều hòa,Máy giặt sấy,Phòng tắm với bồn tắm,Vòi sen massage,Máy sấy tóc,Bàn làm việc,Phòng khách riêng,Sofa cao cấp,Ban công lớn', '/assets/img/premium-apartment/can-ho-premium-aurora-hotel-1.jpg,/assets/img/premium-apartment/can-ho-premium-aurora-hotel-2.jpg,/assets/img/premium-apartment/can-ho-premium-aurora-hotel-3.jpg', '/assets/img/premium-apartment/can-ho-premium-aurora-hotel-1.jpg', 4200000.00, 5000000.00, 5800000.00, 'active', 12, '2025-11-18 17:31:03', '2025-11-25 04:24:18'),
(9, 'Modern Premium', 'modern-premium', 'apartment', 'Modern Premium 68m² với thiết kế hiện đại tối giản, nội thất cao cấp và công nghệ smart home tiên tiến. Phòng ngủ riêng với giường King size, phòng khách sang trọng, bếp hiện đại đầy đủ. Tầm nhìn panorama tuyệt đẹp.', 'Hiện đại cao cấp với công nghệ smart home', 3, 68.00, '1 Giường King + Sofa bed', 'WiFi miễn phí,TV thông minh 55 inch,Smart home system,Bếp hiện đại cao cấp,Tủ lạnh lớn,Lò vi sóng,Máy rửa chén,Máy pha cà phê,Bàn ăn 4 chỗ,Minibar cao cấp,Két an toàn điện tử,Điều hòa thông minh,Máy giặt sấy,Phòng tắm cao cấp với bồn tắm,Vòi sen massage,Máy sấy tóc Dyson,Bàn làm việc executive,Phòng khách cao cấp,Sofa da,Ban công panorama', '/assets/img/modern-premium-apartment/modern-premium-apartment-1.jpg,/assets/img/modern-premium-apartment/modern-premium-apartment-2.jpg', '/assets/img/modern-premium-apartment/modern-premium-apartment-1.jpg', 4800000.00, 5700000.00, 6500000.00, 'active', 7, '2025-11-18 17:31:03', '2025-11-25 04:24:18'),
(10, 'Classical Premium', 'classical-premium', 'apartment', 'Classical Premium 66m² mang phong cách cổ điển sang trọng với nội thất gỗ tự nhiên cao cấp, họa tiết tinh tế. Phòng ngủ riêng, phòng khách ấm cúng, bếp đầy đủ tiện nghi. Không gian thanh lịch, quý phái cho những ai yêu thích sự cổ điển.', 'Phong cách cổ điển sang trọng và thanh lịch', 3, 66.00, '1 Giường King + Sofa bed', 'WiFi miễn phí,TV màn hình phẳng,Bếp cao cấp,Tủ lạnh,Lò vi sóng,Ấm đun nước,Bàn ăn 4 chỗ,Minibar,Két an toàn,Điều hòa,Máy giặt,Phòng tắm với bồn tắm,Vòi sen,Máy sấy tóc,Bàn làm việc,Phòng khách,Sofa cổ điển,Ban công,Nội thất gỗ cao cấp', '/assets/img/classical-premium-apartment/classical-premium-apartment-1.jpg,/assets/img/classical-premium-apartment/classical-premium-apartment-2.jpg', '/assets/img/classical-premium-apartment/classical-premium-apartment-1.jpg', 4500000.00, 5300000.00, 6000000.00, 'active', 8, '2025-11-18 17:31:03', '2025-11-25 04:24:18'),
(11, 'Family Apartment', 'family-apartment', 'apartment', 'Family Apartment 75m² được thiết kế đặc biệt cho gia đình với 2 phòng ngủ riêng biệt, phòng khách rộng rãi và bếp đầy đủ. Phòng ngủ chính có giường King, phòng ngủ phụ có 2 giường đơn. Không gian thoải mái, an toàn cho trẻ em với đầy đủ tiện nghi hiện đại.', 'Lý tưởng cho gia đình với 2 phòng ngủ riêng', 5, 75.00, '1 King + 2 Đơn', 'WiFi miễn phí,TV thông minh,Bếp đầy đủ,Tủ lạnh lớn,Lò vi sóng,Máy rửa chén,Ấm đun nước,Bàn ăn 6 chỗ,Minibar,Két an toàn,Điều hòa,Máy giặt sấy,2 Phòng tắm,Vòi sen,Máy sấy tóc,Bàn làm việc,Phòng khách rộng,Sofa,Ban công,Khu vực vui chơi trẻ em', '/assets/img/family-apartment/can-ho-family-aurora-hotel-3.jpg,/assets/img/family-apartment/can-ho-family-aurora-hotel-5.jpg,/assets/img/family-apartment/can-ho-family-aurora-hotel-6.jpg', '/assets/img/family-apartment/can-ho-family-aurora-hotel-3.jpg', 5500000.00, 6500000.00, 7500000.00, 'active', 13, '2025-11-18 17:31:03', '2025-11-25 04:24:18'),
(12, 'Indochine Family', 'indochine-family', 'apartment', 'Indochine Family 72m² kết hợp phong cách Đông Dương truyền thống với tiện nghi hiện đại. 2 phòng ngủ riêng biệt, phòng khách ấm cúng với nội thất gỗ tự nhiên, bếp đầy đủ. Không gian văn hóa đậm chất Việt, lý tưởng cho gia đình yêu thích truyền thống.', 'Phong cách Đông Dương cho gia đình', 5, 72.00, '1 King + 2 Đơn', 'WiFi miễn phí,TV màn hình phẳng,Bếp đầy đủ,Tủ lạnh,Lò vi sóng,Ấm đun nước,Bàn ăn 6 chỗ,Minibar,Két an toàn,Điều hòa,Máy giặt,2 Phòng tắm,Vòi sen,Máy sấy tóc,Bàn làm việc,Phòng khách,Sofa,Ban công,Nội thất Đông Dương,Trang trí truyền thống', '/assets/img/indochine-family-apartment/indochine-family-apartment-1.jpg,/assets/img/indochine-family-apartment/indochine-family-apartment-2.jpg', '/assets/img/indochine-family-apartment/indochine-family-apartment-1.jpg', 5200000.00, 6200000.00, 7200000.00, 'active', 9, '2025-11-18 17:31:04', '2025-11-25 04:24:18'),
(13, 'Classical Family', 'classical-family', 'apartment', 'Classical Family 78m² mang phong cách cổ điển sang trọng với 2 phòng ngủ rộng rãi, phòng khách thanh lịch và bếp cao cấp. Nội thất gỗ tự nhiên cao cấp, họa tiết tinh tế, không gian ấm cúng. Lựa chọn hoàn hảo cho gia đình yêu thích sự quý phái cổ điển.', 'Sang trọng cổ điển cho gia đình', 5, 78.00, '1 King + 2 Đơn', 'WiFi miễn phí,TV màn hình phẳng,Bếp cao cấp đầy đủ,Tủ lạnh lớn,Lò vi sóng,Máy rửa chén,Ấm đun nước,Bàn ăn 6 chỗ,Minibar,Két an toàn,Điều hòa,Máy giặt sấy,2 Phòng tắm cao cấp,Vòi sen,Máy sấy tóc,Bàn làm việc,Phòng khách rộng,Sofa cổ điển,Ban công lớn,Nội thất gỗ cao cấp,Trang trí cổ điển', '/assets/img/classical-family-apartment/classical-family-apartment1.jpg,/assets/img/classical-family-apartment/classical-family-apartment2.jpg', '/assets/img/classical-family-apartment/classical-family-apartment1.jpg', 5800000.00, 6800000.00, 7800000.00, 'active', 10, '2025-11-18 17:31:04', '2025-11-25 04:24:18');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `services`
--

CREATE TABLE `services` (
  `service_id` int(11) NOT NULL,
  `service_name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `category` enum('room_service','spa','restaurant','event','transport','laundry','other') NOT NULL,
  `description` text DEFAULT NULL,
  `short_description` varchar(255) DEFAULT NULL,
  `icon` varchar(100) DEFAULT 'room_service',
  `price` decimal(10,2) NOT NULL,
  `price_unit` varchar(50) DEFAULT NULL,
  `thumbnail` varchar(255) DEFAULT NULL,
  `images` text DEFAULT NULL,
  `is_available` tinyint(1) DEFAULT 1,
  `is_featured` tinyint(1) DEFAULT 0,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `services`
--

INSERT INTO `services` (`service_id`, `service_name`, `slug`, `category`, `description`, `short_description`, `icon`, `price`, `price_unit`, `thumbnail`, `images`, `is_available`, `is_featured`, `sort_order`, `created_at`, `updated_at`) VALUES
(1, 'Tổ chức tiệc cưới', 'wedding-service', 'event', 'Aurora Hotel Plaza tự hào là địa điểm tổ chức tiệc cưới hàng đầu tại Đồng Nai với sảnh tiệc sang trọng 500m², sức chứa lên đến 800 khách. Đội ngũ chuyên nghiệp với hơn 500 tiệc cưới thành công.', 'Sảnh tiệc 500m², sức chứa 800 khách, dịch vụ trọn gói', 'celebration', 0.00, 'Liên hệ', 'assets/img/post/wedding/Tiec-cuoi-tai-aurora-1.jpg', NULL, 1, 1, 1, '2025-11-18 18:34:50', '2025-11-18 18:34:50'),
(2, 'Tổ chức hội nghị', 'conference-service', 'event', 'Phòng hội nghị hiện đại với 5 phòng họp đa năng, sức chứa từ 20-300 người. Trang bị đầy đủ thiết bị hiện đại, WiFi tốc độ cao, dịch vụ coffee break và buffet.', 'Phòng họp hiện đại, thiết bị đầy đủ, WiFi tốc độ cao', 'meeting_room', 5000000.00, 'VNĐ', 'assets/img/post/conference/conference-1.jpg', NULL, 1, 1, 2, '2025-11-18 18:34:50', '2025-11-19 00:16:14'),
(3, 'Nhà hàng Aurora', 'aurora-restaurant', 'restaurant', 'Nhà hàng sang trọng với sức chứa 200 khách, view thành phố tuyệt đẹp. Buffet đa dạng hơn 100 món Á-Âu: hải sản tươi sống, sushi Nhật Bản, BBQ Hàn Quốc, món Việt truyền thống.', 'Buffet 100+ món Á-Âu, view đẹp, đầu bếp 5 sao', 'restaurant', 350000.00, 'VNĐ/người', 'assets/img/post/restaurant/restaurant-1.jpg', NULL, 1, 1, 3, '2025-11-18 18:34:50', '2025-11-18 18:34:50'),
(4, 'Văn phòng cho thuê', 'office-rental', 'other', 'Văn phòng hiện đại với 3 loại: Studio 20m², Standard 40m², Premium 80m². Đầy đủ nội thất, WiFi 1Gbps, phòng họp chung, bảo vệ 24/7.', 'Văn phòng đầy đủ nội thất, WiFi 1Gbps, tiện ích đầy đủ', 'business_center', 8000000.00, 'VNĐ/tháng', 'assets/img/post/office/office-1.jpg', NULL, 1, 1, 4, '2025-11-18 18:34:50', '2025-11-18 18:34:50'),
(5, 'Rooftop Bar', 'rooftop-bar', 'restaurant', 'Quầy bar tầng thượng với view 360 độ thành phố Biên Hòa. Không gian sang trọng, âm nhạc sống mỗi tối thứ 6-7. Menu cocktail đa dạng.', 'Bar tầng thượng, view 360°, cocktail đa dạng, nhạc sống', 'local_bar', 150000.00, 'VNĐ/ly', 'assets/img/post/restaurant/rooftop-bar-1.jpg', NULL, 1, 1, 5, '2025-11-18 18:34:50', '2025-11-18 18:34:50'),
(6, 'Dịch vụ phòng 24/7', 'room-service-24-7', 'room_service', 'Dịch vụ phòng hoạt động 24/7 với thực đơn đa dạng hơn 50 món.', 'Gọi món ăn, đồ uống phục vụ tận phòng 24/7', 'room_service', 0.00, 'Miễn phí', NULL, NULL, 1, 0, 6, '2025-11-18 18:34:50', '2025-11-18 18:34:50'),
(7, 'Dọn phòng hàng ngày', 'daily-housekeeping', 'room_service', 'Dịch vụ dọn phòng hàng ngày với đội ngũ chuyên nghiệp.', 'Dọn dẹp và thay đổi khăn giường mỗi ngày', 'cleaning_services', 0.00, 'Miễn phí', NULL, NULL, 1, 0, 7, '2025-11-18 18:34:50', '2025-11-18 18:34:50'),
(8, 'Giặt ủi cao cấp', 'laundry-service', 'laundry', 'Dịch vụ giặt ủi chuyên nghiệp với máy móc hiện đại.', 'Giặt ủi quần áo chuyên nghiệp, giao nhận tận phòng', 'local_laundry_service', 50000.00, 'VNĐ/kg', NULL, NULL, 1, 0, 8, '2025-11-18 18:34:50', '2025-11-18 18:34:50'),
(9, 'Massage trị liệu', 'therapeutic-massage', 'spa', 'Massage trị liệu chuyên sâu giúp giảm căng thẳng, đau nhức cơ bắp.', 'Massage thư giãn toàn thân với tinh dầu thiên nhiên', 'spa', 500000.00, 'VNĐ/60 phút', NULL, NULL, 1, 1, 9, '2025-11-18 18:34:50', '2025-11-18 18:34:50'),
(10, 'Sauna & Jacuzzi', 'sauna-jacuzzi', 'spa', 'Phòng xông hơi khô và bồn tắm massage cao cấp.', 'Xông hơi khô và bồn tắm nước nóng massage', 'hot_tub', 300000.00, 'VNĐ/45 phút', NULL, NULL, 1, 0, 10, '2025-11-18 18:34:50', '2025-11-18 18:34:50'),
(11, 'Hồ bơi & Gym', 'pool-gym', 'other', 'Hồ bơi ngoài trời 25m x 12m và phòng gym 200m² với trang thiết bị Technogym.', 'Hồ bơi ngoài trời và phòng gym hiện đại miễn phí', 'pool', 0.00, 'Miễn phí', NULL, NULL, 1, 1, 11, '2025-11-18 18:34:50', '2025-11-18 18:34:50'),
(12, 'Đưa đón sân bay', 'airport-transfer', 'transport', 'Dịch vụ đưa đón sân bay Tân Sơn Nhất bằng xe sang.', 'Xe đưa đón sân bay Tân Sơn Nhất tiện lợi, an toàn', 'local_taxi', 500000.00, 'VNĐ/chuyến', NULL, NULL, 1, 0, 12, '2025-11-18 18:34:50', '2025-11-18 18:34:50'),
(13, 'Thuê xe tự lái', 'car-rental', 'transport', 'Cho thuê xe tự lái đa dạng dòng xe 4-7 chỗ.', 'Thuê xe tự lái 4-7 chỗ, xe mới, bảo hiểm đầy đủ', 'directions_car', 800000.00, 'VNĐ/ngày', NULL, NULL, 1, 0, 13, '2025-11-18 18:34:50', '2025-11-18 18:34:50'),
(14, 'Trông trẻ', 'babysitting', 'other', 'Dịch vụ trông trẻ chuyên nghiệp, an toàn cho trẻ từ 6 tháng đến 12 tuổi.', 'Dịch vụ trông trẻ an toàn, chuyên nghiệp, giám sát camera', 'child_care', 100000.00, 'VNĐ/giờ', NULL, NULL, 1, 0, 14, '2025-11-18 18:34:50', '2025-11-18 18:34:50');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `service_bookings`
--

CREATE TABLE `service_bookings` (
  `service_booking_id` int(11) NOT NULL,
  `booking_id` int(11) DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `service_id` int(11) NOT NULL,
  `quantity` int(11) DEFAULT 1,
  `unit_price` decimal(10,2) NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `service_date` date DEFAULT NULL,
  `service_time` time DEFAULT NULL,
  `special_requests` text DEFAULT NULL,
  `status` enum('pending','confirmed','completed','cancelled') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `service_packages`
--

CREATE TABLE `service_packages` (
  `package_id` int(11) NOT NULL,
  `service_id` int(11) NOT NULL,
  `package_name` varchar(200) NOT NULL,
  `slug` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `features` text DEFAULT NULL COMMENT 'Danh sách tính năng, phân cách bởi dấu phẩy',
  `price` decimal(10,2) NOT NULL,
  `price_unit` varchar(50) DEFAULT 'VNĐ',
  `is_featured` tinyint(1) DEFAULT 0,
  `is_available` tinyint(1) DEFAULT 1,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `service_packages`
--

INSERT INTO `service_packages` (`package_id`, `service_id`, `package_name`, `slug`, `description`, `features`, `price`, `price_unit`, `is_featured`, `is_available`, `sort_order`, `created_at`, `updated_at`) VALUES
(1, 1, 'Gói Cơ bản', 'wedding-basic', 'Gói tiệc cưới cơ bản cho 200-300 khách.', 'Sảnh tiệc 200-300 khách,Trang trí cơ bản,Menu 8 món,Âm thanh ánh sáng,Bàn ghế tiêu chuẩn,Backdrop sân khấu', 15000000.00, 'VNĐ', 0, 1, 1, '2025-11-18 18:34:50', '2025-11-18 18:34:50'),
(2, 1, 'Gói Tiêu chuẩn', 'wedding-standard', 'Gói tiệc cưới tiêu chuẩn cho 400-500 khách.', 'Sảnh tiệc 400-500 khách,Trang trí theo chủ đề,Menu 10 món + tráng miệng,Âm thanh ánh sáng cao cấp,Bàn ghế VIP,Backdrop + Cổng hoa,MC chuyên nghiệp,Nhiếp ảnh cơ bản', 25000000.00, 'VNĐ', 1, 1, 2, '2025-11-18 18:34:50', '2025-11-18 18:34:50'),
(3, 1, 'Gói VIP', 'wedding-vip', 'Gói tiệc cưới VIP cho 600-800 khách.', 'Sảnh tiệc 600-800 khách,Trang trí sang trọng,Menu 12 món + buffet tráng miệng,Âm thanh ánh sáng 3D,Bàn ghế VIP cao cấp,Backdrop + Cổng hoa + Sân khấu,MC + Ban nhạc,Nhiếp ảnh + Quay phim,Trang điểm cô dâu,Xe hoa', 40000000.00, 'VNĐ', 1, 1, 3, '2025-11-18 18:34:50', '2025-11-18 18:34:50'),
(4, 3, 'Buffet Sáng', 'restaurant-breakfast', 'Buffet sáng với hơn 50 món Á - Âu.', 'Hơn 50 món ăn,Phở - Bún - Cháo,Bánh mì - Xúc xích,Trứng các loại,Hoa quả tươi,Nước ép - Cà phê,Thời gian: 6h-10h', 200000.00, 'VNĐ/người', 0, 1, 1, '2025-11-18 18:34:50', '2025-11-18 18:34:50'),
(5, 3, 'Buffet Trưa/Tối', 'restaurant-lunch-dinner', 'Buffet trưa hoặc tối với hơn 100 món.', 'Hơn 100 món ăn,Hải sản tươi sống,Sushi Nhật Bản,BBQ Hàn Quốc,Món Việt truyền thống,Món Âu cao cấp,Tráng miệng đa dạng,Nước uống không giới hạn', 350000.00, 'VNĐ/người', 1, 1, 2, '2025-11-18 18:34:50', '2025-11-18 18:34:50'),
(6, 3, 'Set Menu VIP', 'restaurant-set-menu', 'Set menu cao cấp phục vụ riêng.', 'Khai vị cao cấp,Súp đặc biệt,Món chính (Bò/Hải sản),Món phụ,Tráng miệng,Rượu vang,Phục vụ riêng tư,Không gian VIP', 800000.00, 'VNĐ/người', 1, 1, 3, '2025-11-18 18:34:50', '2025-11-18 18:34:50'),
(7, 2, 'Gói Nửa ngày', 'conference-half-day', 'Gói hội nghị nửa ngày (4 giờ) phù hợp cho 50-100 người.', 'Phòng họp 50-100 người,Projector + Màn hình,Micro không dây,WiFi miễn phí,Coffee break 1 lần,Bảng Flipchart', 6000000.00, 'VNĐ/4 giờ', 0, 1, 1, '2025-11-18 18:34:50', '2025-11-19 00:16:32'),
(8, 2, 'Gói Cả ngày', 'conference-full-day', 'Gói hội nghị cả ngày (8 giờ) cho 100-200 người.', 'Phòng họp 100-200 người,Màn hình LED lớn,Hệ thống âm thanh cao cấp,WiFi tốc độ cao,Coffee break 2 lần,Buffet trưa,Bảng Flipchart + Bút,Hỗ trợ kỹ thuật', 9000000.00, 'VNĐ/8 giờ', 1, 1, 2, '2025-11-18 18:34:50', '2025-11-18 18:34:50'),
(9, 2, 'Gói VIP', 'conference-vip', 'Gói hội nghị VIP cho 200-300 người.', 'Phòng hội nghị 200-300 người,Màn hình LED 3D,Âm thanh chuyên nghiệp,WiFi doanh nghiệp,Coffee break 3 lần,Buffet trưa + tối,Phòng VIP cho ban tổ chức,Ghi hình chuyên nghiệp,Hỗ trợ kỹ thuật 24/7', 15000000.00, 'VNĐ/ngày', 1, 1, 3, '2025-11-18 18:34:50', '2025-11-18 18:34:50'),
(10, 4, 'Studio 20m²', 'office-studio', 'Văn phòng Studio cho 2-3 người.', 'Diện tích 20m²,2-3 người,Bàn làm việc,Ghế ergonomic,Tủ tài liệu,Điều hòa,WiFi 1Gbps', 3000000.00, 'VNĐ/tháng', 0, 1, 1, '2025-11-18 18:34:50', '2025-11-18 18:34:50'),
(11, 4, 'Standard 40m²', 'office-standard', 'Văn phòng Standard cho 5-8 người.', 'Diện tích 40m²,5-8 người,Bàn làm việc,Ghế ergonomic,Tủ tài liệu,Điều hòa,WiFi 1Gbps,Phòng họp chung', 5500000.00, 'VNĐ/tháng', 1, 1, 2, '2025-11-18 18:34:50', '2025-11-18 18:34:50'),
(12, 4, 'Premium 80m²', 'office-premium', 'Văn phòng Premium cho 10-15 người.', 'Diện tích 80m²,10-15 người,Bàn làm việc cao cấp,Ghế ergonomic,Tủ tài liệu,Điều hòa trung tâm,WiFi 1Gbps,Phòng họp riêng,Khu pantry riêng', 8000000.00, 'VNĐ/tháng', 1, 1, 3, '2025-11-18 18:34:50', '2025-11-18 18:34:50');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `system_settings`
--

CREATE TABLE `system_settings` (
  `setting_id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `setting_type` enum('string','number','boolean','json') DEFAULT 'string',
  `description` varchar(255) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `system_settings`
--

INSERT INTO `system_settings` (`setting_id`, `setting_key`, `setting_value`, `setting_type`, `description`, `updated_by`, `updated_at`) VALUES
(10, 'site_name', 'Aurora Hotel Plaza', 'string', 'Tên website', 22, '2025-11-18 16:47:27'),
(11, 'site_email', 'info@aurorahotel.com', 'string', 'Email liên hệ', 22, '2025-11-18 16:47:27'),
(12, 'site_phone', '+84 251 3511 888', 'string', 'Số điện thoại', 22, '2025-11-18 16:47:27'),
(13, 'booking_advance_days', '365', 'number', 'Số ngày tối đa có thể đặt trước', 22, '2025-11-18 16:47:27'),
(14, 'cancellation_hours', '24', 'number', 'Số giờ trước khi check-in có thể hủy miễn phí', 22, '2025-11-18 16:47:27'),
(15, 'points_per_vnd', '10000', 'number', 'Số điểm tích lũy trên 1 VND (1 điểm/10,000 VND)', 22, '2025-11-18 16:47:27'),
(16, 'min_booking_amount', '500000', 'number', 'Số tiền đặt phòng tối thiểu', NULL, '2025-11-17 10:18:49'),
(17, 'tax_percentage', '10', 'number', 'Thuế VAT (%)', NULL, '2025-11-17 10:18:49'),
(18, 'service_charge_percentage', '5', 'number', 'Phí dịch vụ (%)', NULL, '2025-11-17 10:18:49'),
(22, 'site_address', 'Hà Nội, Việt Nam', 'string', NULL, 22, '2025-11-18 16:47:27'),
(24, 'booking_min_nights', '1', 'string', NULL, 22, '2025-11-18 16:47:27'),
(25, 'booking_max_nights', '30', 'string', NULL, 22, '2025-11-18 16:47:27'),
(27, 'late_checkout_fee', '50000', 'string', NULL, 22, '2025-11-18 16:47:27'),
(28, 'early_checkin_fee', '50000', 'string', NULL, 22, '2025-11-18 16:47:27'),
(29, 'allow_guest_booking', '1', 'string', NULL, 22, '2025-11-18 16:47:27'),
(30, 'tax_rate', '8', 'string', NULL, 22, '2025-11-18 16:47:27'),
(31, 'service_charge_rate', '5', 'string', NULL, 22, '2025-11-18 16:47:27'),
(33, 'points_expiry_days', '365', 'string', NULL, 22, '2025-11-18 16:47:27'),
(34, 'email_notifications', '1', 'string', NULL, 22, '2025-11-18 16:47:27');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `gender` enum('male','female','other') DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `user_role` enum('customer','receptionist','sale','admin') DEFAULT 'customer',
  `status` enum('active','inactive','banned') DEFAULT 'active',
  `email_verified` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `last_login` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `users`
--

INSERT INTO `users` (`user_id`, `email`, `password_hash`, `full_name`, `phone`, `address`, `date_of_birth`, `gender`, `avatar`, `user_role`, `status`, `email_verified`, `created_at`, `updated_at`, `last_login`) VALUES
(0, 'admin@aurorahotelplaza.com', '$2y$10$CKftn0hq/CpY0h9GmO3siu4T2bydgNesYNZfPzgt/LEBX8HzGvfmK', 'Administrator', '0123456789', NULL, NULL, NULL, NULL, 'admin', 'active', 1, '2025-12-14 03:53:00', '2025-12-14 04:06:41', '2025-12-14 04:06:41'),
(0, 'longdev.08@gmail.com', '$2y$10$L9GHSn/95z71PQdcIKh0VeTXpZT/yP9Y/Y.4X1wn2/xn524dcWbIG', 'Long Quang', NULL, NULL, NULL, NULL, 'https://lh3.googleusercontent.com/a/ACg8ocJQsNS68y6f20vdimP4mRmGe3U-tMausEPGmo6q43KETsFjB9ju=s96-c', 'customer', 'active', 1, '2025-12-14 03:53:59', '2025-12-14 04:06:41', '2025-12-14 04:06:41'),
(0, 'thuylinh.80902@gmail.com', '$2y$10$7WT.yGtFvcgfcTQS/pvfe.0WweDA.0iLPeeW0aPUXZ211DhnLXx/G', 'Linh', NULL, NULL, NULL, NULL, 'https://lh3.googleusercontent.com/a/ACg8ocJWKgoXi3sCsEKJ4XKQcYaKv251QhkqcsNz2k6znBdFUE6dSQ=s96-c', 'customer', 'active', 1, '2025-12-14 04:08:07', '2025-12-14 04:08:07', NULL),
(0, '23810067@student.hcmute.edu.vn', '$2y$10$cK1mhFEgS4.c7ZfzgMxLZ.t/gVn7DCUoMVeT22JrPshCxYyO3gL.u', 'Le Quang Long', NULL, NULL, NULL, NULL, 'https://lh3.googleusercontent.com/a/ACg8ocL88K2CMnTw8MkI77vN2M4o5Dtkar8-VUTA4Hm0HBx29wURzA=s96-c', 'customer', 'active', 1, '2025-12-14 04:13:48', '2025-12-14 04:13:48', NULL);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `user_loyalty`
--

CREATE TABLE `user_loyalty` (
  `loyalty_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `current_points` int(11) DEFAULT 0,
  `lifetime_points` int(11) DEFAULT 0,
  `tier_id` int(11) DEFAULT NULL,
  `tier_updated_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `user_loyalty`
--

INSERT INTO `user_loyalty` (`loyalty_id`, `user_id`, `current_points`, `lifetime_points`, `tier_id`, `tier_updated_at`, `created_at`, `updated_at`) VALUES
(0, 0, 0, 0, NULL, NULL, '2025-12-14 03:53:00', '2025-12-14 03:53:00'),
(0, 0, 0, 0, NULL, NULL, '2025-12-14 03:53:59', '2025-12-14 03:53:59'),
(0, 0, 0, 0, NULL, NULL, '2025-12-14 04:08:07', '2025-12-14 04:08:07'),
(0, 0, 0, 0, NULL, NULL, '2025-12-14 04:13:48', '2025-12-14 04:13:48');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `user_sessions`
--

CREATE TABLE `user_sessions` (
  `session_id` varchar(255) NOT NULL,
  `user_id` int(11) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `last_activity` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `v_booking_stats`
--

CREATE TABLE `v_booking_stats` (
  `booking_date` date DEFAULT NULL,
  `total_bookings` bigint(21) NOT NULL DEFAULT 0,
  `confirmed_bookings` decimal(22,0) DEFAULT NULL,
  `cancelled_bookings` decimal(22,0) DEFAULT NULL,
  `total_revenue` decimal(32,2) DEFAULT NULL,
  `avg_booking_value` decimal(14,6) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `v_revenue_by_room_type`
--

CREATE TABLE `v_revenue_by_room_type` (
  `room_type_id` int(11) NOT NULL DEFAULT 0,
  `type_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `category` enum('room','apartment') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'room',
  `total_bookings` bigint(21) NOT NULL DEFAULT 0,
  `total_revenue` decimal(32,2) DEFAULT NULL,
  `avg_revenue` decimal(14,6) DEFAULT NULL,
  `total_nights` decimal(32,0) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `v_room_occupancy`
--

CREATE TABLE `v_room_occupancy` (
  `room_id` int(11) NOT NULL DEFAULT 0,
  `room_number` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `type_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('available','occupied','maintenance','cleaning') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'available',
  `total_bookings` bigint(21) NOT NULL DEFAULT 0,
  `total_nights_booked` decimal(32,0) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `v_user_loyalty_summary`
--

CREATE TABLE `v_user_loyalty_summary` (
  `user_id` int(11) NOT NULL DEFAULT 0,
  `full_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `current_points` int(11) DEFAULT 0,
  `lifetime_points` int(11) DEFAULT 0,
  `tier_name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `discount_percentage` decimal(5,2) DEFAULT 0.00,
  `total_bookings` bigint(21) NOT NULL DEFAULT 0,
  `total_spent` decimal(32,2) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Chỉ mục cho các bảng đã đổ
--

--
-- Chỉ mục cho bảng `contact_submissions`
--
ALTER TABLE `contact_submissions`
  ADD KEY `idx_contact_user_id` (`user_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
