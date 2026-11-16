-- CampusFind - Lost & Found Management System Database Schema
-- Database Name: campusfind_db

CREATE DATABASE IF NOT EXISTS `campusfind_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `campusfind_db`;

-- --------------------------------------------------------
-- Table structure for `users`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
  `user_id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `phone` VARCHAR(20) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('admin', 'user') NOT NULL DEFAULT 'user',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `categories`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `categories` (
  `category_id` INT AUTO_INCREMENT PRIMARY KEY,
  `category_name` VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `items`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `items` (
  `item_id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `category_id` INT NOT NULL,
  `item_name` VARCHAR(150) NOT NULL,
  `description` TEXT NOT NULL,
  `item_type` ENUM('lost', 'found') NOT NULL,
  `location` VARCHAR(150) NOT NULL,
  `item_date` DATE NOT NULL,
  `image` VARCHAR(255) DEFAULT NULL,
  `approval_status` ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
  `item_status` ENUM('open', 'claimed', 'returned') NOT NULL DEFAULT 'open',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_user_id` (`user_id`),
  INDEX `idx_category_id` (`category_id`),
  INDEX `idx_approval_status` (`approval_status`),
  INDEX `idx_item_type` (`item_type`),
  CONSTRAINT `fk_items_users` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_items_categories` FOREIGN KEY (`category_id`) REFERENCES `categories` (`category_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `claims`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `claims` (
  `claim_id` INT AUTO_INCREMENT PRIMARY KEY,
  `item_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `claim_message` TEXT NOT NULL,
  `claim_status` ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_claim_item` (`item_id`),
  INDEX `idx_claim_user` (`user_id`),
  CONSTRAINT `fk_claims_items` FOREIGN KEY (`item_id`) REFERENCES `items` (`item_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_claims_users` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Seed Data: Categories
-- --------------------------------------------------------
INSERT INTO `categories` (`category_id`, `category_name`) VALUES
(1, 'Electronics'),
(2, 'Documents'),
(3, 'Books'),
(4, 'Bags'),
(5, 'Clothing'),
(6, 'Accessories'),
(7, 'Others')
ON DUPLICATE KEY UPDATE `category_name` = VALUES(`category_name`);

-- --------------------------------------------------------
-- Seed Data: Default Administrator
-- Email: admin@campusfind.edu
-- Password: admin123
-- --------------------------------------------------------
INSERT INTO `users` (`user_id`, `name`, `email`, `phone`, `password`, `role`) VALUES
(1, 'Campus Admin', 'admin@campusfind.edu', '9876543210', '$2y$10$A.l928BnVaWKaQwghLwkced7.gciW6/GQkJWkANPGklc4xGm5MFmW', 'admin')
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);
