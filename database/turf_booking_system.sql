-- TurfBooking database restore script
-- Generated to match current backend code (register/login, owner add turf with image, browse, booking, review)

CREATE DATABASE IF NOT EXISTS `turf_booking_system`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_general_ci;

USE `turf_booking_system`;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `reviews`;
DROP TABLE IF EXISTS `bookings`;
DROP TABLE IF EXISTS `slots`;
DROP TABLE IF EXISTS `turf_images`;
DROP TABLE IF EXISTS `turfs`;
DROP TABLE IF EXISTS `turf_owners`;
DROP TABLE IF EXISTS `user_point_logs`;
DROP TABLE IF EXISTS `user_points`;
DROP TABLE IF EXISTS `users`;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(120) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `language_pref` varchar(10) DEFAULT 'en',
  `status` enum('active','banned') DEFAULT 'active',
  `role` enum('user','owner','admin') DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `uq_users_email` (`email`),
  UNIQUE KEY `uq_users_phone` (`phone`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `user_points` (
  `user_id` int(11) NOT NULL,
  `total_points` int(11) NOT NULL DEFAULT 0,
  `booking_points` int(11) NOT NULL DEFAULT 0,
  `review_points` int(11) NOT NULL DEFAULT 0,
  `resell_points` int(11) NOT NULL DEFAULT 0,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`user_id`),
  CONSTRAINT `fk_user_points_user`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `user_point_logs` (
  `log_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `source` enum('booking','review','resell','admin_adjustment') NOT NULL,
  `points` int(11) NOT NULL,
  `note` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`log_id`),
  KEY `idx_user_point_logs_user` (`user_id`),
  CONSTRAINT `fk_user_point_logs_user`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `turf_owners` (
  `owner_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `owner_name` varchar(100) NOT NULL,
  `email` varchar(120) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `status` enum('pending','verified','suspended') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`owner_id`),
  UNIQUE KEY `uq_turf_owners_email` (`email`),
  UNIQUE KEY `uq_turf_owners_phone` (`phone`),
  UNIQUE KEY `uq_turf_owners_user_id` (`user_id`),
  CONSTRAINT `fk_turf_owners_user`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `turfs` (
  `turf_id` int(11) NOT NULL AUTO_INCREMENT,
  `owner_id` int(11) NOT NULL,
  `turf_name` varchar(150) NOT NULL,
  `sport_type` enum('football','cricket','badminton','basketball','tennis','other') DEFAULT 'football',
  `address` varchar(255) NOT NULL,
  `area` varchar(120) DEFAULT NULL,
  `city` varchar(120) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  `price_per_hour` decimal(10,2) NOT NULL,
  `description` text DEFAULT NULL,
  `rating_avg` decimal(3,2) DEFAULT 0.00,
  `is_featured` tinyint(1) DEFAULT 0,
  `status` enum('pending','active','inactive','rejected') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `cancel_before_hours` int(11) DEFAULT 24,
  `refund_percent` int(11) DEFAULT 80,
  PRIMARY KEY (`turf_id`),
  KEY `idx_turfs_owner` (`owner_id`),
  KEY `idx_turfs_status` (`status`),
  CONSTRAINT `fk_turfs_owner`
    FOREIGN KEY (`owner_id`) REFERENCES `turf_owners` (`owner_id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `turf_images` (
  `image_id` int(11) NOT NULL AUTO_INCREMENT,
  `turf_id` int(11) NOT NULL,
  `image_url` varchar(255) NOT NULL,
  `is_primary` tinyint(1) DEFAULT 0,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`image_id`),
  KEY `idx_turf_images_turf` (`turf_id`),
  CONSTRAINT `fk_turf_images_turf`
    FOREIGN KEY (`turf_id`) REFERENCES `turfs` (`turf_id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `slots` (
  `slot_id` int(11) NOT NULL AUTO_INCREMENT,
  `turf_id` int(11) NOT NULL,
  `slot_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `base_price` decimal(10,2) NOT NULL,
  `is_enabled` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`slot_id`),
  UNIQUE KEY `uq_slots_unique_window` (`turf_id`,`slot_date`,`start_time`,`end_time`),
  KEY `idx_slots_turf_date` (`turf_id`,`slot_date`),
  CONSTRAINT `fk_slots_turf`
    FOREIGN KEY (`turf_id`) REFERENCES `turfs` (`turf_id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `bookings` (
  `booking_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `slot_id` int(11) NOT NULL,
  `booking_status` varchar(30) DEFAULT 'confirmed',
  `booked_price` decimal(10,2) NOT NULL,
  `promo_code` varchar(50) DEFAULT NULL,
  `refund_amount` decimal(10,2) DEFAULT 0.00,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`booking_id`),
  KEY `idx_bookings_user` (`user_id`),
  KEY `idx_bookings_slot` (`slot_id`),
  KEY `idx_bookings_status` (`booking_status`),
  CONSTRAINT `fk_bookings_user`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_bookings_slot`
    FOREIGN KEY (`slot_id`) REFERENCES `slots` (`slot_id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `reviews` (
  `review_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `turf_id` int(11) NOT NULL,
  `rating` tinyint(4) NOT NULL,
  `comment` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`review_id`),
  UNIQUE KEY `uq_reviews_booking` (`booking_id`),
  KEY `idx_reviews_user` (`user_id`),
  KEY `idx_reviews_turf` (`turf_id`),
  CONSTRAINT `fk_reviews_user`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_reviews_booking`
    FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`booking_id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_reviews_turf`
    FOREIGN KEY (`turf_id`) REFERENCES `turfs` (`turf_id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `chk_reviews_rating` CHECK (`rating` BETWEEN 1 AND 5)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Optional seed: first registered user can be promoted to owner manually by setting role='owner'.
-- UPDATE users SET role='owner' WHERE email='your_email@example.com';
