-- SQL Script: Create tickets table and update certificates table
USE `seminar_portal`;

-- Drop legacy tables to ensure clean relational constraints
DROP TABLE IF EXISTS `workshop_certificates`;
DROP TABLE IF EXISTS `workshop_registrations`;

-- 1. Create tickets table
CREATE TABLE `tickets` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `workshop_id` INT NOT NULL,
    `ticket_number` VARCHAR(50) NOT NULL UNIQUE,
    `qr_code_path` VARCHAR(255) DEFAULT NULL,
    `status` ENUM('active', 'cancelled', 'attended', 'completed') DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`workshop_id`) REFERENCES `workshops`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_user_workshop_ticket` (`user_id`, `workshop_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 2. Create workshop_certificates table referencing tickets
CREATE TABLE `workshop_certificates` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `ticket_id` INT NOT NULL UNIQUE,
    `certificate_code` VARCHAR(100) NOT NULL UNIQUE,
    `issued_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`ticket_id`) REFERENCES `tickets`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
