-- Practical 9: Secure User Registration
-- This file contains only the tables required for Practical 9.
-- The 'studenthub' database is assumed to already exist.

USE `studenthub`;

-- =====================================================================
-- 1. Users Table
-- Stores login accounts separate from academic student profiles.
-- =====================================================================
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(150) NOT NULL UNIQUE, -- Database-level duplicate check
    `password` VARCHAR(255) NOT NULL,     -- For bcrypt hashes (60+ chars)
    `role` VARCHAR(20) DEFAULT 'student',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- 2. Audit Logs Table (Advanced Extension)
-- Tracks important actions like registration.
-- =====================================================================
CREATE TABLE IF NOT EXISTS `audit_logs` (
    `log_id` INT NOT NULL AUTO_INCREMENT,
    `user_id` INT,
    `action` VARCHAR(100),
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`log_id`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
