-- ============================================================================
-- AURORA HOTEL BOOKING SYSTEM - COMPLETE DATABASE SCHEMA
-- Version: 2.0
-- Date: 2025-11-17
-- Description: Full-featured hotel booking system with role-based access
-- ============================================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- ============================================================================
-- 1. USERS & AUTHENTICATION
-- ============================================================================

-- Users table with role-based access control
CREATE TABLE `users` (
  `user_id` INT(11) NOT NULL AUTO_INCREMENT,
  `email` VARCHAR(255) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `full_name` VARCHAR(255) NOT NULL,
  `phone` VARCHAR(20) DEFAULT NULL,
  `address` TEXT DEFAULT NULL,
  `date_of_birth` DATE DEFAULT NULL,
  `gender` ENUM('male', 'female', 'other') DEFAULT NULL,
  `avatar` VARCHAR(255) DEFAULT NULL,
  `user_role` ENUM('customer', 'receptionist', 'sale', 'admin') DEFAULT 'customer',
  `status` ENUM('active', 'inactive', 'banned') DEFAULT 'active',
  `email_verified` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_login` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_user_role` (`user_role`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Password reset tokens
CREATE TABLE `password_resets` (
  `reset_id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL,
  `token` VARCHAR(255) NOT NULL,
  `expires_at` TIMESTAMP NOT NULL,
  `used` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`reset_id`),
  KEY `idx_token` (`token`),
  KEY `fk_password_reset_user` (`user_id`),
  CONSTRAINT `fk_password_reset_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Session management
CREATE TABLE `user_sessions` (
  `session_id` VARCHAR(255) NOT NULL,
  `user_id` INT(11) NOT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `user_agent` TEXT DEFAULT NULL,
  `last_activity` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`session_id`),
  KEY `fk_session_user` (`user_id`),
  CONSTRAINT `fk_session_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- CSRF tokens
CREATE TABLE `csrf_tokens` (
  `token_id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) DEFAULT NULL,
  `token` VARCHAR(255) NOT NULL,
  `expires_at` TIMESTAMP NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`token_id`),
  KEY `idx_token` (`token`),
  KEY `fk_csrf_user` (`user_id`),
  CONSTRAINT `fk_csrf_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Rate limiting
CREATE TABLE `rate_limits` (
  `limit_id` INT(11) NOT NULL AUTO_INCREMENT,
  `identifier` VARCHAR(255) NOT NULL,
  `action` VARCHAR(50) NOT NULL,
  `attempts` INT(11) DEFAULT 1,
  `last_attempt` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `blocked_until` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`limit_id`),
  UNIQUE KEY `idx_identifier_action` (`identifier`, `action`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 2. LOYALTY & MEMBERSHIP SYSTEM
-- ============================================================================

-- Membership tiers
CREATE TABLE `membership_tiers` (
  `tier_id` INT(11) NOT NULL AUTO_INCREMENT,
  `tier_name` VARCHAR(50) NOT NULL,
  `tier_level` INT(11) NOT NULL,
  `min_points` INT(11) NOT NULL DEFAULT 0,
  `discount_percentage` DECIMAL(5,2) DEFAULT 0.00,
  `benefits` TEXT DEFAULT NULL,
  `color_code` VARCHAR(7) DEFAULT '#000000',
  `icon` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`tier_id`),
  UNIQUE KEY `tier_name` (`tier_name`),
  UNIQUE KEY `tier_level` (`tier_level`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User lty points
CREATE TABLE `user_loyalty` (
  `loyalty_id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL,
  `current_points` INT(11) DEFAULT 0,
  `lifetime_points` INT(11) DEFAULT 0,
  `tier_id` INT(11) DEFAULT NULL,
  `tier_updated_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`loyalty_id`),
  UNIQUE KEY `user_id` (`user_id`),
  KEY `fk_loyalty_tier` (`tier_id`),
  CONSTRAINT `fk_loyalty_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_loyalty_tier` FOREIGN KEY (`tier_id`) REFERENCES `membership_tiers` (`tier_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Points transaction history
CREATE TABLE `points_transactions` (
  `transaction_id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL,
  `points` INT(11) NOT NULL,
  `transaction_type` ENUM('earn', 'redeem', 'expire', 'adjust') NOT NULL,
  `reference_type` VARCHAR(50) DEFAULT NULL,
  `reference_id` INT(11) DEFAULT NULL,
  `description` TEXT DEFAULT NULL,
  `created_by` INT(11) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`transaction_id`),
  KEY `fk_points_user` (`user_id`),
  KEY `idx_reference` (`reference_type`, `reference_id`),
  CONSTRAINT `fk_points_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 3. ROOM MANAGEMENT
-- ============================================================================

-- Room types/categories
CREATE TABLE `room_types` (
  `room_type_id` INT(11) NOT NULL AUTO_INCREMENT,
  `type_name` VARCHAR(100) NOT NULL,
  `slug` VARCHAR(100) NOT NULL,
  `category` ENUM('room', 'apartment') NOT NULL DEFAULT 'room',
  `description` TEXT DEFAULT NULL,
  `short_description` VARCHAR(255) DEFAULT NULL,
  `max_occupancy` INT(11) NOT NULL DEFAULT 2,
  `size_sqm` DECIMAL(10,2) DEFAULT NULL,
  `bed_type` VARCHAR(100) DEFAULT NULL,
  `amenities` TEXT DEFAULT NULL,
  `images` TEXT DEFAULT NULL,
  `thumbnail` VARCHAR(255) DEFAULT NULL,
  `base_price` DECIMAL(10,2) NOT NULL,
  `weekend_price` DECIMAL(10,2) DEFAULT NULL,
  `holiday_price` DECIMAL(10,2) DEFAULT NULL,
  `status` ENUM('active', 'inactive') DEFAULT 'active',
  `sort_order` INT(11) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`room_type_id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `idx_category` (`category`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Individual rooms
CREATE TABLE `rooms` (
  `room_id` INT(11) NOT NULL AUTO_INCREMENT,
  `room_type_id` INT(11) NOT NULL,
  `room_number` VARCHAR(20) NOT NULL,
  `floor` INT(11) DEFAULT NULL,
  `building` VARCHAR(50) DEFAULT NULL,
  `status` ENUM('available', 'occupied', 'maintenance', 'cleaning') DEFAULT 'available',
  `notes` TEXT DEFAULT NULL,
  `last_cleaned` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`room_id`),
  UNIQUE KEY `room_number` (`room_number`),
  KEY `fk_room_type` (`room_type_id`),
  KEY `idx_status` (`status`),
  CONSTRAINT `fk_room_type` FOREIGN KEY (`room_type_id`) REFERENCES `room_types` (`room_type_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Room pricing rules
CREATE TABLE `room_pricing` (
  `pricing_id` INT(11) NOT NULL AUTO_INCREMENT,
  `room_type_id` INT(11) NOT NULL,
  `start_date` DATE NOT NULL,
  `end_date` DATE NOT NULL,
  `price` DECIMAL(10,2) NOT NULL,
  `pricing_type` ENUM('special', 'seasonal', 'promotion') DEFAULT 'special',
  `description` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`pricing_id`),
  KEY `fk_pricing_room_type` (`room_type_id`),
  KEY `idx_dates` (`start_date`, `end_date`),
  CONSTRAINT `fk_pricing_room_type` FOREIGN KEY (`room_type_id`) REFERENCES `room_types` (`room_type_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 4. BOOKING SYSTEM
-- ============================================================================

-- Bookings
CREATE TABLE `bookings` (
  `booking_id` INT(11) NOT NULL AUTO_INCREMENT,
  `booking_code` VARCHAR(20) NOT NULL,
  `user_id` INT(11) NOT NULL,
  `room_type_id` INT(11) NOT NULL,
  `room_id` INT(11) DEFAULT NULL,
  `check_in_date` DATE NOT NULL,
  `check_out_date` DATE NOT NULL,
  `num_adults` INT(11) NOT NULL DEFAULT 1,
  `num_children` INT(11) DEFAULT 0,
  `num_rooms` INT(11) NOT NULL DEFAULT 1,
  `total_nights` INT(11) NOT NULL,
  `room_price` DECIMAL(10,2) NOT NULL,
  `service_charges` DECIMAL(10,2) DEFAULT 0.00,
  `discount_amount` DECIMAL(10,2) DEFAULT 0.00,
  `points_used` INT(11) DEFAULT 0,
  `total_amount` DECIMAL(10,2) NOT NULL,
  `special_requests` TEXT DEFAULT NULL,
  `guest_name` VARCHAR(255) NOT NULL,
  `guest_email` VARCHAR(255) NOT NULL,
  `guest_phone` VARCHAR(20) NOT NULL,
  `guest_id_number` VARCHAR(50) DEFAULT NULL,
  `status` ENUM('pending', 'confirmed', 'checked_in', 'checked_out', 'cancelled', 'no_show') DEFAULT 'pending',
  `payment_status` ENUM('unpaid', 'partial', 'paid', 'refunded') DEFAULT 'unpaid',
  `qr_code` VARCHAR(255) DEFAULT NULL,
  `confirmation_sent` TINYINT(1) DEFAULT 0,
  `checked_in_at` TIMESTAMP NULL DEFAULT NULL,
  `checked_out_at` TIMESTAMP NULL DEFAULT NULL,
  `checked_in_by` INT(11) DEFAULT NULL,
  `cancelled_at` TIMESTAMP NULL DEFAULT NULL,
  `cancelled_by` INT(11) DEFAULT NULL,
  `cancellation_reason` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`booking_id`),
  UNIQUE KEY `booking_code` (`booking_code`),
  KEY `fk_booking_user` (`user_id`),
  KEY `fk_booking_room_type` (`room_type_id`),
  KEY `fk_booking_room` (`room_id`),
  KEY `idx_dates` (`check_in_date`, `check_out_date`),
  KEY `idx_status` (`status`),
  CONSTRAINT `fk_booking_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_booking_room_type` FOREIGN KEY (`room_type_id`) REFERENCES `room_types` (`room_type_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_booking_room` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`room_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Booking status history
CREATE TABLE `booking_history` (
  `history_id` INT(11) NOT NULL AUTO_INCREMENT,
  `booking_id` INT(11) NOT NULL,
  `old_status` VARCHAR(50) DEFAULT NULL,
  `new_status` VARCHAR(50) NOT NULL,
  `changed_by` INT(11) DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`history_id`),
  KEY `fk_history_booking` (`booking_id`),
  CONSTRAINT `fk_history_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`booking_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 5. PAYMENT SYSTEM
-- ============================================================================

-- Payments
CREATE TABLE `payments` (
  `payment_id` INT(11) NOT NULL AUTO_INCREMENT,
  `booking_id` INT(11) NOT NULL,
  `payment_method` ENUM('vnpay', 'cash', 'bank_transfer', 'credit_card') NOT NULL,
  `amount` DECIMAL(10,2) NOT NULL,
  `currency` VARCHAR(3) DEFAULT 'VND',
  `transaction_id` VARCHAR(255) DEFAULT NULL,
  `vnpay_response` TEXT DEFAULT NULL,
  `status` ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending',
  `paid_at` TIMESTAMP NULL DEFAULT NULL,
  `refunded_at` TIMESTAMP NULL DEFAULT NULL,
  `refund_amount` DECIMAL(10,2) DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`payment_id`),
  KEY `fk_payment_booking` (`booking_id`),
  KEY `idx_transaction` (`transaction_id`),
  KEY `idx_status` (`status`),
  CONSTRAINT `fk_payment_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`booking_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 6. SERVICES MANAGEMENT
-- ============================================================================

-- Services (spa, restaurant, laundry, etc.)
CREATE TABLE `services` (
  `service_id` INT(11) NOT NULL AUTO_INCREMENT,
  `service_name` VARCHAR(255) NOT NULL,
  `slug` VARCHAR(255) NOT NULL,
  `category` ENUM('spa', 'restaurant', 'laundry', 'transport', 'other') NOT NULL,
  `description` TEXT DEFAULT NULL,
  `short_description` VARCHAR(255) DEFAULT NULL,
  `price` DECIMAL(10,2) NOT NULL,
  `unit` VARCHAR(50) DEFAULT NULL,
  `image` VARCHAR(255) DEFAULT NULL,
  `available` TINYINT(1) DEFAULT 1,
  `sort_order` INT(11) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`service_id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `idx_category` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Service bookings
CREATE TABLE `service_bookings` (
  `service_booking_id` INT(11) NOT NULL AUTO_INCREMENT,
  `booking_id` INT(11) DEFAULT NULL,
  `user_id` INT(11) NOT NULL,
  `service_id` INT(11) NOT NULL,
  `quantity` INT(11) DEFAULT 1,
  `unit_price` DECIMAL(10,2) NOT NULL,
  `total_price` DECIMAL(10,2) NOT NULL,
  `service_date` DATE DEFAULT NULL,
  `service_time` TIME DEFAULT NULL,
  `special_requests` TEXT DEFAULT NULL,
  `status` ENUM('pending', 'confirmed', 'completed', 'cancelled') DEFAULT 'pending',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`service_booking_id`),
  KEY `fk_service_booking` (`booking_id`),
  KEY `fk_service_user` (`user_id`),
  KEY `fk_service` (`service_id`),
  CONSTRAINT `fk_service_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`booking_id`) ON DELETE SET NULL,
  CONSTRAINT `fk_service_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_service` FOREIGN KEY (`service_id`) REFERENCES `services` (`service_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 7. BLOG & CONTENT MANAGEMENT
-- ============================================================================

-- Blog categories
CREATE TABLE `blog_categories` (
  `category_id` INT(11) NOT NULL AUTO_INCREMENT,
  `category_name` VARCHAR(100) NOT NULL,
  `slug` VARCHAR(100) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `parent_id` INT(11) DEFAULT NULL,
  `sort_order` INT(11) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`category_id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `fk_parent_category` (`parent_id`),
  CONSTRAINT `fk_parent_category` FOREIGN KEY (`parent_id`) REFERENCES `blog_categories` (`category_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Blog posts
CREATE TABLE `blog_posts` (
  `post_id` INT(11) NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(255) NOT NULL,
  `slug` VARCHAR(255) NOT NULL,
  `excerpt` TEXT DEFAULT NULL,
  `content` LONGTEXT NOT NULL,
  `featured_image` VARCHAR(255) DEFAULT NULL,
  `category_id` INT(11) DEFAULT NULL,
  `author_id` INT(11) NOT NULL,
  `status` ENUM('draft', 'published', 'archived') DEFAULT 'draft',
  `views` INT(11) DEFAULT 0,
  `published_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`post_id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `fk_post_category` (`category_id`),
  KEY `fk_post_author` (`author_id`),
  KEY `idx_status` (`status`),
  CONSTRAINT `fk_post_category` FOREIGN KEY (`category_id`) REFERENCES `blog_categories` (`category_id`) ON DELETE SET NULL,
  CONSTRAINT `fk_post_author` FOREIGN KEY (`author_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Blog comments
CREATE TABLE `blog_comments` (
  `comment_id` INT(11) NOT NULL AUTO_INCREMENT,
  `post_id` INT(11) NOT NULL,
  `user_id` INT(11) DEFAULT NULL,
  `parent_id` INT(11) DEFAULT NULL,
  `author_name` VARCHAR(255) NOT NULL,
  `author_email` VARCHAR(255) NOT NULL,
  `content` TEXT NOT NULL,
  `status` ENUM('pending', 'approved', 'spam', 'trash') DEFAULT 'pending',
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`comment_id`),
  KEY `fk_comment_post` (`post_id`),
  KEY `fk_comment_user` (`user_id`),
  KEY `fk_comment_parent` (`parent_id`),
  KEY `idx_status` (`status`),
  CONSTRAINT `fk_comment_post` FOREIGN KEY (`post_id`) REFERENCES `blog_posts` (`post_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_comment_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  CONSTRAINT `fk_comment_parent` FOREIGN KEY (`parent_id`) REFERENCES `blog_comments` (`comment_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 8. PROMOTIONS & DISCOUNTS
-- ============================================================================

-- Promotions
CREATE TABLE `promotions` (
  `promotion_id` INT(11) NOT NULL AUTO_INCREMENT,
  `promotion_code` VARCHAR(50) NOT NULL,
  `promotion_name` VARCHAR(255) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `discount_type` ENUM('percentage', 'fixed_amount') NOT NULL,
  `discount_value` DECIMAL(10,2) NOT NULL,
  `min_booking_amount` DECIMAL(10,2) DEFAULT NULL,
  `max_discount` DECIMAL(10,2) DEFAULT NULL,
  `usage_limit` INT(11) DEFAULT NULL,
  `usage_per_user` INT(11) DEFAULT 1,
  `used_count` INT(11) DEFAULT 0,
  `applicable_to` ENUM('all', 'rooms', 'apartments', 'services') DEFAULT 'all',
  `start_date` TIMESTAMP NOT NULL,
  `end_date` TIMESTAMP NOT NULL,
  `status` ENUM('active', 'inactive', 'expired') DEFAULT 'active',
  `created_by` INT(11) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`promotion_id`),
  UNIQUE KEY `promotion_code` (`promotion_code`),
  KEY `idx_dates` (`start_date`, `end_date`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Promotion usage tracking
CREATE TABLE `promotion_usage` (
  `usage_id` INT(11) NOT NULL AUTO_INCREMENT,
  `promotion_id` INT(11) NOT NULL,
  `user_id` INT(11) NOT NULL,
  `booking_id` INT(11) DEFAULT NULL,
  `discount_amount` DECIMAL(10,2) NOT NULL,
  `used_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`usage_id`),
  KEY `fk_usage_promotion` (`promotion_id`),
  KEY `fk_usage_user` (`user_id`),
  KEY `fk_usage_booking` (`booking_id`),
  CONSTRAINT `fk_usage_promotion` FOREIGN KEY (`promotion_id`) REFERENCES `promotions` (`promotion_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_usage_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_usage_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`booking_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 9. BANNERS & SLIDERS
-- ============================================================================

-- Banners
CREATE TABLE `banners` (
  `banner_id` INT(11) NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(255) NOT NULL,
  `subtitle` VARCHAR(255) DEFAULT NULL,
  `image_desktop` VARCHAR(255) NOT NULL,
  `image_mobile` VARCHAR(255) DEFAULT NULL,
  `link_url` VARCHAR(255) DEFAULT NULL,
  `link_text` VARCHAR(100) DEFAULT NULL,
  `position` ENUM('hero', 'sidebar', 'footer', 'popup') DEFAULT 'hero',
  `sort_order` INT(11) DEFAULT 0,
  `start_date` TIMESTAMP NULL DEFAULT NULL,
  `end_date` TIMESTAMP NULL DEFAULT NULL,
  `status` ENUM('active', 'inactive') DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`banner_id`),
  KEY `idx_position` (`position`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 10. CHAT SYSTEM
-- ============================================================================

-- Chat conversations
CREATE TABLE `chat_conversations` (
  `conversation_id` INT(11) NOT NULL AUTO_INCREMENT,
  `customer_id` INT(11) NOT NULL,
  `staff_id` INT(11) DEFAULT NULL,
  `status` ENUM('open', 'assigned', 'closed') DEFAULT 'open',
  `locked_by` INT(11) DEFAULT NULL,
  `locked_at` TIMESTAMP NULL DEFAULT NULL,
  `last_message_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`conversation_id`),
  KEY `fk_conv_customer` (`customer_id`),
  KEY `fk_conv_staff` (`staff_id`),
  KEY `idx_status` (`status`),
  CONSTRAINT `fk_conv_customer` FOREIGN KEY (`customer_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_conv_staff` FOREIGN KEY (`staff_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Chat messages
CREATE TABLE `chat_messages` (
  `message_id` INT(11) NOT NULL AUTO_INCREMENT,
  `conversation_id` INT(11) NOT NULL,
  `sender_id` INT(11) NOT NULL,
  `message` TEXT NOT NULL,
  `attachment` VARCHAR(255) DEFAULT NULL,
  `is_read` TINYINT(1) DEFAULT 0,
  `read_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`message_id`),
  KEY `fk_msg_conversation` (`conversation_id`),
  KEY `fk_msg_sender` (`sender_id`),
  KEY `idx_is_read` (`is_read`),
  CONSTRAINT `fk_msg_conversation` FOREIGN KEY (`conversation_id`) REFERENCES `chat_conversations` (`conversation_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_msg_sender` FOREIGN KEY (`sender_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 11. NOTIFICATIONS SYSTEM
-- ============================================================================

-- Notifications
CREATE TABLE `notifications` (
  `notification_id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL,
  `type` VARCHAR(50) NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `message` TEXT NOT NULL,
  `link` VARCHAR(255) DEFAULT NULL,
  `icon` VARCHAR(50) DEFAULT NULL,
  `is_read` TINYINT(1) DEFAULT 0,
  `read_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`notification_id`),
  KEY `fk_notif_user` (`user_id`),
  KEY `idx_is_read` (`is_read`),
  CONSTRAINT `fk_notif_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Push notification subscriptions
CREATE TABLE `push_subscriptions` (
  `subscription_id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL,
  `endpoint` TEXT NOT NULL,
  `auth_key` VARCHAR(255) DEFAULT NULL,
  `p256dh_key` VARCHAR(255) DEFAULT NULL,
  `user_agent` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`subscription_id`),
  KEY `fk_push_user` (`user_id`),
  CONSTRAINT `fk_push_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 12. REVIEWS & RATINGS
-- ============================================================================

-- Reviews
CREATE TABLE `reviews` (
  `review_id` INT(11) NOT NULL AUTO_INCREMENT,
  `booking_id` INT(11) NOT NULL,
  `user_id` INT(11) NOT NULL,
  `room_type_id` INT(11) DEFAULT NULL,
  `rating` DECIMAL(2,1) NOT NULL,
  `cleanliness_rating` INT(11) DEFAULT NULL,
  `service_rating` INT(11) DEFAULT NULL,
  `location_rating` INT(11) DEFAULT NULL,
  `value_rating` INT(11) DEFAULT NULL,
  `title` VARCHAR(255) DEFAULT NULL,
  `comment` TEXT DEFAULT NULL,
  `pros` TEXT DEFAULT NULL,
  `cons` TEXT DEFAULT NULL,
  `images` TEXT DEFAULT NULL,
  `status` ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
  `helpful_count` INT(11) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`review_id`),
  KEY `fk_review_booking` (`booking_id`),
  KEY `fk_review_user` (`user_id`),
  KEY `fk_review_room_type` (`room_type_id`),
  KEY `idx_status` (`status`),
  CONSTRAINT `fk_review_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`booking_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_review_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_review_room_type` FOREIGN KEY (`room_type_id`) REFERENCES `room_types` (`room_type_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Review responses (from hotel staff)
CREATE TABLE `review_responses` (
  `response_id` INT(11) NOT NULL AUTO_INCREMENT,
  `review_id` INT(11) NOT NULL,
  `staff_id` INT(11) NOT NULL,
  `response` TEXT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`response_id`),
  KEY `fk_response_review` (`review_id`),
  KEY `fk_response_staff` (`staff_id`),
  CONSTRAINT `fk_response_review` FOREIGN KEY (`review_id`) REFERENCES `reviews` (`review_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_response_staff` FOREIGN KEY (`staff_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 13. DYNAMIC CONTENT MANAGEMENT
-- ============================================================================

-- Page content (for dynamic UI content)
CREATE TABLE `page_content` (
  `content_id` INT(11) NOT NULL AUTO_INCREMENT,
  `page_name` VARCHAR(100) NOT NULL,
  `section_name` VARCHAR(100) NOT NULL,
  `content_key` VARCHAR(100) NOT NULL,
  `content_value` TEXT NOT NULL,
  `content_type` ENUM('text', 'html', 'image', 'json') DEFAULT 'text',
  `language` VARCHAR(5) DEFAULT 'vi',
  `updated_by` INT(11) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`content_id`),
  UNIQUE KEY `unique_content` (`page_name`, `section_name`, `content_key`, `language`),
  KEY `idx_page` (`page_name`),
  KEY `idx_language` (`language`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 14. SYSTEM SETTINGS & LOGS
-- ============================================================================

-- System settings
CREATE TABLE `system_settings` (
  `setting_id` INT(11) NOT NULL AUTO_INCREMENT,
  `setting_key` VARCHAR(100) NOT NULL,
  `setting_value` TEXT DEFAULT NULL,
  `setting_type` ENUM('string', 'number', 'boolean', 'json') DEFAULT 'string',
  `description` VARCHAR(255) DEFAULT NULL,
  `updated_by` INT(11) DEFAULT NULL,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`setting_id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Activity logs
CREATE TABLE `activity_logs` (
  `log_id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) DEFAULT NULL,
  `action` VARCHAR(100) NOT NULL,
  `entity_type` VARCHAR(50) DEFAULT NULL,
  `entity_id` INT(11) DEFAULT NULL,
  `description` TEXT DEFAULT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `user_agent` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`log_id`),
  KEY `fk_log_user` (`user_id`),
  KEY `idx_entity` (`entity_type`, `entity_id`),
  KEY `idx_created` (`created_at`),
  CONSTRAINT `fk_log_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Email logs
CREATE TABLE `email_logs` (
  `email_log_id` INT(11) NOT NULL AUTO_INCREMENT,
  `recipient` VARCHAR(255) NOT NULL,
  `subject` VARCHAR(255) NOT NULL,
  `template` VARCHAR(100) DEFAULT NULL,
  `status` ENUM('sent', 'failed', 'pending') DEFAULT 'pending',
  `error_message` TEXT DEFAULT NULL,
  `sent_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`email_log_id`),
  KEY `idx_status` (`status`),
  KEY `idx_recipient` (`recipient`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 15. GALLERY
-- ============================================================================

-- Gallery images
CREATE TABLE `gallery` (
  `gallery_id` INT(11) NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(255) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `image_url` VARCHAR(255) NOT NULL,
  `thumbnail_url` VARCHAR(255) DEFAULT NULL,
  `category` VARCHAR(50) DEFAULT NULL,
  `sort_order` INT(11) DEFAULT 0,
  `status` ENUM('active', 'inactive') DEFAULT 'active',
  `uploaded_by` INT(11) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`gallery_id`),
  KEY `idx_category` (`category`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 16. FAQ
-- ============================================================================

-- FAQs
CREATE TABLE `faqs` (
  `faq_id` INT(11) NOT NULL AUTO_INCREMENT,
  `question` VARCHAR(255) NOT NULL,
  `answer` TEXT NOT NULL,
  `category` VARCHAR(50) DEFAULT NULL,
  `sort_order` INT(11) DEFAULT 0,
  `status` ENUM('active', 'inactive') DEFAULT 'active',
  `views` INT(11) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`faq_id`),
  KEY `idx_category` (`category`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 17. CONTACT & INQUIRIES
-- ============================================================================

-- Contact submissions
CREATE TABLE `contact_submissions` (
  `submission_id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `phone` VARCHAR(20) DEFAULT NULL,
  `subject` VARCHAR(255) DEFAULT NULL,
  `message` TEXT NOT NULL,
  `status` ENUM('new', 'in_progress', 'resolved', 'closed') DEFAULT 'new',
  `assigned_to` INT(11) DEFAULT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`submission_id`),
  KEY `idx_status` (`status`),
  KEY `fk_contact_assigned` (`assigned_to`),
  CONSTRAINT `fk_contact_assigned` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- INITIAL DATA INSERTION
-- ============================================================================

-- Insert default membership tiers
INSERT INTO `membership_tiers` (`tier_name`, `tier_level`, `min_points`, `discount_percentage`, `benefits`, `color_code`) VALUES
('Bronze', 1, 0, 0.00, 'Ưu tiên check-in sớm', '#CD7F32'),
('Silver', 2, 1000, 5.00, 'Giảm 5%, Late checkout miễn phí', '#C0C0C0'),
('Gold', 3, 5000, 10.00, 'Giảm 10%, Nâng cấp phòng miễn phí, Welcome drink', '#FFD700'),
('Platinum', 4, 15000, 15.00, 'Giảm 15%, Ưu tiên đặt phòng, Spa miễn phí', '#E5E4E2'),
('Diamond', 5, 50000, 20.00, 'Giảm 20%, Phòng VIP, Dịch vụ cao cấp', '#B9F2FF');

-- Insert default system settings
INSERT INTO `system_settings` (`setting_key`, `setting_value`, `setting_type`, `description`) VALUES
('site_name', 'Aurora Hotel', 'string', 'Tên website'),
('site_email', 'info@aurorahotel.com', 'string', 'Email liên hệ'),
('site_phone', '+84 123 456 789', 'string', 'Số điện thoại'),
('booking_advance_days', '365', 'number', 'Số ngày tối đa có thể đặt trước'),
('cancellation_hours', '24', 'number', 'Số giờ trước khi check-in có thể hủy miễn phí'),
('points_per_vnd', '0.01', 'number', 'Số điểm tích lũy trên 1 VND'),
('min_booking_amount', '500000', 'number', 'Số tiền đặt phòng tối thiểu'),
('tax_percentage', '10', 'number', 'Thuế VAT (%)'),
('service_charge_percentage', '5', 'number', 'Phí dịch vụ (%)');

-- Insert default admin user (password: admin123)
INSERT INTO `users` (`email`, `password_hash`, `full_name`, `phone`, `user_role`, `status`, `email_verified`) VALUES
('admin@aurorahotel.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', '+84 123 456 789', 'admin', 'active', 1);

-- Insert sample blog categories
INSERT INTO `blog_categories` (`category_name`, `slug`, `description`) VALUES
('Tin tức', 'tin-tuc', 'Tin tức và sự kiện của khách sạn'),
('Khuyến mãi', 'khuyen-mai', 'Các chương trình khuyến mãi'),
('Du lịch', 'du-lich', 'Thông tin du lịch và điểm đến'),
('Ẩm thực', 'am-thuc', 'Món ăn và nhà hàng'),
('Hướng dẫn', 'huong-dan', 'Hướng dẫn sử dụng dịch vụ');

-- Insert sample services
INSERT INTO `services` (`service_name`, `slug`, `category`, `description`, `price`, `unit`, `available`) VALUES
('Massage toàn thân', 'massage-toan-than', 'spa', 'Massage thư giãn toàn thân 60 phút', 500000, '60 phút', 1),
('Giặt ủi', 'giat-ui', 'laundry', 'Dịch vụ giặt ủi quần áo', 50000, 'kg', 1),
('Đưa đón sân bay', 'dua-don-san-bay', 'transport', 'Dịch vụ đưa đón sân bay', 300000, 'chuyến', 1),
('Ăn sáng buffet', 'an-sang-buffet', 'restaurant', 'Buffet sáng tại nhà hàng', 200000, 'người', 1),
('Thuê xe máy', 'thue-xe-may', 'transport', 'Thuê xe máy theo ngày', 150000, 'ngày', 1);

-- ============================================================================
-- VIEWS FOR REPORTING
-- ============================================================================

-- View: Booking statistics
CREATE OR REPLACE VIEW `v_booking_stats` AS
SELECT 
    DATE(created_at) as booking_date,
    COUNT(*) as total_bookings,
    SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed_bookings,
    SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_bookings,
    SUM(total_amount) as total_revenue,
    AVG(total_amount) as avg_booking_value
FROM bookings
GROUP BY DATE(created_at);

-- View: Room occupancy
CREATE OR REPLACE VIEW `v_room_occupancy` AS
SELECT 
    r.room_id,
    r.room_number,
    rt.type_name,
    r.status,
    COUNT(b.booking_id) as total_bookings,
    SUM(b.total_nights) as total_nights_booked
FROM rooms r
LEFT JOIN room_types rt ON r.room_type_id = rt.room_type_id
LEFT JOIN bookings b ON r.room_id = b.room_id 
    AND b.status IN ('confirmed', 'checked_in')
    AND b.check_out_date >= CURDATE()
GROUP BY r.room_id;

-- View: User loyalty summary
CREATE OR REPLACE VIEW `v_user_loyalty_summary` AS
SELECT 
    u.user_id,
    u.full_name,
    u.email,
    ul.current_points,
    ul.lifetime_points,
    mt.tier_name,
    mt.discount_percentage,
    COUNT(b.booking_id) as total_bookings,
    SUM(b.total_amount) as total_spent
FROM users u
LEFT JOIN user_loyalty ul ON u.user_id = ul.user_id
LEFT JOIN membership_tiers mt ON ul.tier_id = mt.tier_id
LEFT JOIN bookings b ON u.user_id = b.user_id
WHERE u.user_role = 'customer'
GROUP BY u.user_id;

-- View: Revenue by room type
CREATE OR REPLACE VIEW `v_revenue_by_room_type` AS
SELECT 
    rt.room_type_id,
    rt.type_name,
    rt.category,
    COUNT(b.booking_id) as total_bookings,
    SUM(b.total_amount) as total_revenue,
    AVG(b.total_amount) as avg_revenue,
    SUM(b.total_nights) as total_nights
FROM room_types rt
LEFT JOIN bookings b ON rt.room_type_id = b.room_type_id
    AND b.status IN ('confirmed', 'checked_in', 'checked_out')
GROUP BY rt.room_type_id;

-- ============================================================================
-- STORED PROCEDURES
-- ============================================================================

-- Procedure: Check room availability
DELIMITER $$
CREATE PROCEDURE `sp_check_room_availability`(
    IN p_room_type_id INT,
    IN p_check_in DATE,
    IN p_check_out DATE,
    IN p_num_rooms INT
)
BEGIN
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
DELIMITER ;

-- Procedure: Calculate loyalty points
DELIMITER $$
CREATE PROCEDURE `sp_calculate_loyalty_points`(
    IN p_user_id INT,
    IN p_booking_amount DECIMAL(10,2)
)
BEGIN
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
DELIMITER ;

-- Procedure: Generate booking code
DELIMITER $$
CREATE PROCEDURE `sp_generate_booking_code`()
BEGIN
    DECLARE v_code VARCHAR(20);
    DECLARE v_exists INT;
    
    REPEAT
        SET v_code = CONCAT('BK', DATE_FORMAT(NOW(), '%Y%m%d'), LPAD(FLOOR(RAND() * 10000), 4, '0'));
        SELECT COUNT(*) INTO v_exists FROM bookings WHERE booking_code = v_code;
    UNTIL v_exists = 0 END REPEAT;
    
    SELECT v_code as booking_code;
END$$
DELIMITER ;

-- ============================================================================
-- TRIGGERS
-- ============================================================================

-- Trigger: Update booking total amount
DELIMITER $$
CREATE TRIGGER `tr_booking_calculate_total` BEFORE INSERT ON `bookings`
FOR EACH ROW
BEGIN
    SET NEW.total_amount = (NEW.room_price * NEW.total_nights * NEW.num_rooms) 
                          + NEW.service_charges 
                          - NEW.discount_amount;
END$$
DELIMITER ;

-- Trigger: Log booking status changes
DELIMITER $$
CREATE TRIGGER `tr_booking_status_log` AFTER UPDATE ON `bookings`
FOR EACH ROW
BEGIN
    IF OLD.status != NEW.status THEN
        INSERT INTO booking_history (booking_id, old_status, new_status, changed_by)
        VALUES (NEW.booking_id, OLD.status, NEW.status, NEW.checked_in_by);
    END IF;
END$$
DELIMITER ;

-- Trigger: Update promotion usage count
DELIMITER $$
CREATE TRIGGER `tr_promotion_usage_increment` AFTER INSERT ON `promotion_usage`
FOR EACH ROW
BEGIN
    UPDATE promotions 
    SET used_count = used_count + 1 
    WHERE promotion_id = NEW.promotion_id;
END$$
DELIMITER ;

-- Trigger: Update room status on check-in
DELIMITER $$
CREATE TRIGGER `tr_booking_checkin` AFTER UPDATE ON `bookings`
FOR EACH ROW
BEGIN
    IF NEW.status = 'checked_in' AND OLD.status != 'checked_in' AND NEW.room_id IS NOT NULL THEN
        UPDATE rooms SET status = 'occupied' WHERE room_id = NEW.room_id;
    END IF;
END$$
DELIMITER ;

-- Trigger: Update room status on check-out
DELIMITER $$
CREATE TRIGGER `tr_booking_checkout` AFTER UPDATE ON `bookings`
FOR EACH ROW
BEGIN
    IF NEW.status = 'checked_out' AND OLD.status != 'checked_out' AND NEW.room_id IS NOT NULL THEN
        UPDATE rooms SET status = 'cleaning', last_cleaned = NULL WHERE room_id = NEW.room_id;
    END IF;
END$$
DELIMITER ;

-- ============================================================================
-- INDEXES FOR PERFORMANCE OPTIMIZATION
-- ============================================================================

-- Additional composite indexes for common queries
CREATE INDEX idx_bookings_user_status ON bookings(user_id, status);
CREATE INDEX idx_bookings_dates_status ON bookings(check_in_date, check_out_date, status);
CREATE INDEX idx_payments_booking_status ON payments(booking_id, status);
CREATE INDEX idx_notifications_user_read ON notifications(user_id, is_read);
CREATE INDEX idx_chat_messages_conv_created ON chat_messages(conversation_id, created_at);
CREATE INDEX idx_reviews_room_status ON reviews(room_type_id, status);
CREATE INDEX idx_blog_posts_status_published ON blog_posts(status, published_at);

-- ============================================================================
-- EVENTS FOR AUTOMATED TASKS
-- ============================================================================

-- Enable event scheduler
SET GLOBAL event_scheduler = ON;

-- Event: Clean expired password reset tokens
DELIMITER $$
CREATE EVENT IF NOT EXISTS `evt_clean_expired_tokens`
ON SCHEDULE EVERY 1 HOUR
DO
BEGIN
    DELETE FROM password_resets WHERE expires_at < NOW();
    DELETE FROM csrf_tokens WHERE expires_at < NOW();
END$$
DELIMITER ;

-- Event: Update expired promotions
DELIMITER $$
CREATE EVENT IF NOT EXISTS `evt_update_expired_promotions`
ON SCHEDULE EVERY 1 DAY
DO
BEGIN
    UPDATE promotions 
    SET status = 'expired' 
    WHERE end_date < NOW() AND status = 'active';
END$$
DELIMITER ;

-- Event: Clean old sessions
DELIMITER $$
CREATE EVENT IF NOT EXISTS `evt_clean_old_sessions`
ON SCHEDULE EVERY 1 DAY
DO
BEGIN
    DELETE FROM user_sessions 
    WHERE last_activity < DATE_SUB(NOW(), INTERVAL 30 DAY);
END$$
DELIMITER ;

-- Event: Clean old activity logs (keep 90 days)
DELIMITER $$
CREATE EVENT IF NOT EXISTS `evt_clean_old_logs`
ON SCHEDULE EVERY 1 WEEK
DO
BEGIN
    DELETE FROM activity_logs 
    WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY);
END$$
DELIMITER ;

-- ============================================================================
-- SECURITY & PERMISSIONS
-- ============================================================================

-- Create database user roles (example - adjust as needed)
-- Note: Execute these separately with appropriate credentials

-- CREATE USER 'aurora_app'@'localhost' IDENTIFIED BY 'secure_password_here';
-- GRANT SELECT, INSERT, UPDATE, DELETE ON aurora_hotel.* TO 'aurora_app'@'localhost';

-- CREATE USER 'aurora_readonly'@'localhost' IDENTIFIED BY 'readonly_password_here';
-- GRANT SELECT ON aurora_hotel.* TO 'aurora_readonly'@'localhost';

-- FLUSH PRIVILEGES;

-- ============================================================================
-- DOCUMENTATION & NOTES
-- ============================================================================

/*
DATABASE SCHEMA OVERVIEW:

1. USERS & AUTHENTICATION (5 tables)
   - users: Main user accounts with role-based access
   - password_resets: Password recovery tokens
   - user_sessions: Active user sessions
   - csrf_tokens: CSRF protection tokens
   - rate_limits: Rate limiting for security

2. LOYALTY & MEMBERSHIP (3 tables)
   - membership_tiers: Bronze, Silver, Gold, Platinum, Diamond
   - user_loyalty: User points and tier tracking
   - points_transactions: Points history

3. ROOM MANAGEMENT (3 tables)
   - room_types: Room categories and pricing
   - rooms: Individual room inventory
   - room_pricing: Special pricing rules

4. BOOKING SYSTEM (2 tables)
   - bookings: Main booking records with QR codes
   - booking_history: Status change tracking

5. PAYMENT SYSTEM (1 table)
   - payments: Payment transactions (VNPay, cash, etc.)

6. SERVICES (2 tables)
   - services: Available services (spa, restaurant, etc.)
   - service_bookings: Service reservations

7. BLOG & CONTENT (3 tables)
   - blog_categories: Blog categories
   - blog_posts: Blog articles
   - blog_comments: User comments

8. PROMOTIONS (2 tables)
   - promotions: Discount codes and campaigns
   - promotion_usage: Usage tracking

9. BANNERS (1 table)
   - banners: Hero sliders and promotional banners

10. CHAT SYSTEM (2 tables)
    - chat_conversations: Chat sessions
    - chat_messages: Individual messages

11. NOTIFICATIONS (2 tables)
    - notifications: In-app notifications
    - push_subscriptions: Push notification endpoints

12. REVIEWS (2 tables)
    - reviews: Customer reviews and ratings
    - review_responses: Staff responses

13. DYNAMIC CONTENT (1 table)
    - page_content: Editable UI content

14. SYSTEM (3 tables)
    - system_settings: Configuration
    - activity_logs: Audit trail
    - email_logs: Email tracking

15. GALLERY (1 table)
    - gallery: Image gallery

16. FAQ (1 table)
    - faqs: Frequently asked questions

17. CONTACT (1 table)
    - contact_submissions: Contact form submissions

TOTAL: 35 tables + 4 views + 3 stored procedures + 6 triggers + 4 events

FEATURES IMPLEMENTED:
✅ Role-based access control (Customer, Receptionist, Sale, Admin)
✅ Loyalty points & membership tiers
✅ Room availability checking
✅ Booking with QR codes
✅ Payment integration (VNPay)
✅ Service bookings
✅ Blog with comments
✅ Promotions & discounts
✅ Chat system with conversation locking
✅ Push notifications
✅ Reviews & ratings
✅ Dynamic content management
✅ Activity logging
✅ Email tracking
✅ Security features (CSRF, rate limiting)
✅ Automated tasks (events)
✅ Performance optimization (indexes, views)

NEXT STEPS:
1. Import this schema into your database
2. Configure VNPay credentials
3. Setup PHPMailer for emails
4. Implement QR code generation
5. Build admin panel UI
6. Create user profile pages
7. Implement chat WebSocket/polling
8. Setup push notification service
*/

COMMIT;

-- ============================================================================
-- END OF SCHEMA
-- ============================================================================
