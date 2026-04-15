-- Digital Tax Accounting Client Portal
-- Database Schema
-- =============================================

CREATE DATABASE IF NOT EXISTS `digitaltax_portal` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `digitaltax_portal`;

-- =============================================
-- CLIENTS TABLE
-- =============================================
CREATE TABLE IF NOT EXISTS `clients` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `first_name` VARCHAR(100) NOT NULL,
  `last_name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(255) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `phone` VARCHAR(30) DEFAULT NULL,
  `business_name` VARCHAR(200) DEFAULT NULL,
  `business_type` VARCHAR(100) DEFAULT NULL,
  `address` TEXT DEFAULT NULL,
  `city` VARCHAR(100) DEFAULT NULL,
  `state` VARCHAR(100) DEFAULT NULL,
  `zip_code` VARCHAR(20) DEFAULT NULL,
  `plan` ENUM('free','basic','professional','premium') DEFAULT 'free',
  `plan_expires_at` DATE DEFAULT NULL,
  `avatar` VARCHAR(255) DEFAULT NULL,
  `is_active` TINYINT(1) DEFAULT 1,
  `email_verified` TINYINT(1) DEFAULT 0,
  `verification_token` VARCHAR(255) DEFAULT NULL,
  `reset_token` VARCHAR(255) DEFAULT NULL,
  `reset_expires_at` DATETIME DEFAULT NULL,
  `last_login` DATETIME DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =============================================
-- ADMINS TABLE
-- =============================================
CREATE TABLE IF NOT EXISTS `admins` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(200) NOT NULL,
  `email` VARCHAR(255) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('super_admin','admin','staff') DEFAULT 'admin',
  `is_active` TINYINT(1) DEFAULT 1,
  `last_login` DATETIME DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =============================================
-- INCOME TABLE
-- =============================================
CREATE TABLE IF NOT EXISTS `income` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `client_id` INT UNSIGNED NOT NULL,
  `description` VARCHAR(500) NOT NULL,
  `category` VARCHAR(100) DEFAULT 'General',
  `amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `income_date` DATE NOT NULL,
  `payment_method` VARCHAR(50) DEFAULT NULL,
  `reference` VARCHAR(200) DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  `receipt_file` VARCHAR(255) DEFAULT NULL,
  `document_type` VARCHAR(200) DEFAULT NULL,
  `tax_year` YEAR DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`client_id`) REFERENCES `clients`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =============================================
-- EXPENSES TABLE
-- =============================================
CREATE TABLE IF NOT EXISTS `expenses` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `client_id` INT UNSIGNED NOT NULL,
  `description` VARCHAR(500) NOT NULL,
  `category` VARCHAR(100) DEFAULT 'General',
  `amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `expense_date` DATE NOT NULL,
  `payment_method` VARCHAR(50) DEFAULT NULL,
  `vendor` VARCHAR(200) DEFAULT NULL,
  `reference` VARCHAR(200) DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  `receipt_file` VARCHAR(255) DEFAULT NULL,
  `is_deductible` TINYINT(1) DEFAULT 1,
  `tax_year` YEAR DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`client_id`) REFERENCES `clients`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =============================================
-- MILEAGE TABLE
-- =============================================
CREATE TABLE IF NOT EXISTS `mileage` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `client_id` INT UNSIGNED NOT NULL,
  `trip_date` DATE NOT NULL,
  `purpose` VARCHAR(300) NOT NULL,
  `from_location` VARCHAR(300) DEFAULT NULL,
  `to_location` VARCHAR(300) DEFAULT NULL,
  `miles` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `rate_per_mile` DECIMAL(6,4) DEFAULT 0.6700,
  `deduction_amount` DECIMAL(12,2) GENERATED ALWAYS AS (`miles` * `rate_per_mile`) STORED,
  `vehicle` VARCHAR(200) DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  `tax_year` YEAR DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`client_id`) REFERENCES `clients`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =============================================
-- NOTES TABLE
-- =============================================
CREATE TABLE IF NOT EXISTS `notes` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `client_id` INT UNSIGNED NOT NULL,
  `title` VARCHAR(300) NOT NULL,
  `content` TEXT DEFAULT NULL,
  `category` VARCHAR(100) DEFAULT 'General',
  `color` VARCHAR(20) DEFAULT '#FF7421',
  `is_pinned` TINYINT(1) DEFAULT 0,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`client_id`) REFERENCES `clients`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =============================================
-- TAXES TABLE
-- =============================================
CREATE TABLE IF NOT EXISTS `taxes` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `client_id` INT UNSIGNED NOT NULL,
  `tax_year` YEAR NOT NULL,
  `filing_status` ENUM('single','married_jointly','married_separately','head_of_household','qualifying_widow') DEFAULT 'single',
  `gross_income` DECIMAL(14,2) DEFAULT 0.00,
  `total_deductions` DECIMAL(14,2) DEFAULT 0.00,
  `taxable_income` DECIMAL(14,2) DEFAULT 0.00,
  `estimated_tax` DECIMAL(14,2) DEFAULT 0.00,
  `tax_paid` DECIMAL(14,2) DEFAULT 0.00,
  `refund_owed` DECIMAL(14,2) DEFAULT 0.00,
  `status` ENUM('not_started','in_progress','filed','accepted','rejected') DEFAULT 'not_started',
  `filed_date` DATE DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  `admin_notes` TEXT DEFAULT NULL,
  `documents` JSON DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`client_id`) REFERENCES `clients`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `unique_client_year` (`client_id`, `tax_year`)
) ENGINE=InnoDB;

-- =============================================
-- PLANS TABLE
-- =============================================
CREATE TABLE IF NOT EXISTS `plans` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `slug` VARCHAR(100) NOT NULL UNIQUE,
  `description` TEXT DEFAULT NULL,
  `price_monthly` DECIMAL(10,2) DEFAULT 0.00,
  `price_yearly` DECIMAL(10,2) DEFAULT 0.00,
  `features` JSON DEFAULT NULL,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =============================================
-- SESSIONS TABLE (for remember me)
-- =============================================
CREATE TABLE IF NOT EXISTS `client_sessions` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `client_id` INT UNSIGNED NOT NULL,
  `token` VARCHAR(255) NOT NULL UNIQUE,
  `ip_address` VARCHAR(50) DEFAULT NULL,
  `user_agent` TEXT DEFAULT NULL,
  `expires_at` DATETIME NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`client_id`) REFERENCES `clients`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =============================================
-- ADMIN NOTES ON CLIENTS
-- =============================================
CREATE TABLE IF NOT EXISTS `admin_client_notes` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `client_id` INT UNSIGNED NOT NULL,
  `admin_id` INT UNSIGNED NOT NULL,
  `note` TEXT NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`client_id`) REFERENCES `clients`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`admin_id`) REFERENCES `admins`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =============================================
-- SEED DEFAULT ADMIN
-- Password: Admin@123456 (bcrypt)
-- =============================================
INSERT INTO `admins` (`name`, `email`, `password`, `role`) VALUES
('Super Admin', 'admin@digitaltaxaccounting.com', '$2y$12$iurbw8ooOX5WGNxRQPmHaOr5291zJXJbGWWoyUcRun92/q458xv6O', 'super_admin');

-- =============================================
-- SEED PLANS
-- =============================================
INSERT INTO `plans` (`name`, `slug`, `description`, `price_monthly`, `price_yearly`, `features`) VALUES
('Free', 'free', 'Get started with basic tax tracking', 0.00, 0.00, '["Track up to 50 transactions","Basic income & expense tracking","Mileage log","Notes","Email support"]'),
('Basic', 'basic', 'Perfect for freelancers and self-employed', 19.99, 199.99, '["Unlimited transactions","Income & expense tracking","Mileage tracking","Notes & reminders","Tax summaries","Priority email support"]'),
('Professional', 'professional', 'Advanced features for growing businesses', 49.99, 499.99, '["Everything in Basic","Multi-year tax history","Advanced reports","Receipt scanning","Dedicated account manager","Phone support"]'),
('Premium', 'premium', 'Full-service for established businesses', 99.99, 999.99, '["Everything in Professional","CPA consultation included","Real-time tax estimates","Business analytics","White-glove onboarding","24/7 support"]');
