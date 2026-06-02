-- SQL Script: Rebuild Tickets, Attendance, and Certificates schema for Phase 9
USE `seminar_portal`;

-- Drop existing tables to ensure clean relations
DROP TABLE IF EXISTS `workshop_certificates`;
DROP TABLE IF EXISTS `workshop_attendance`;
DROP TABLE IF EXISTS `attendance`;
DROP TABLE IF EXISTS `tickets`;

-- 1. Create rebuilt tickets table
CREATE TABLE `tickets` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `event_id` INT NOT NULL, -- references workshops.id
    `ticket_number` VARCHAR(50) NOT NULL UNIQUE,
    `qr_code_path` VARCHAR(255) DEFAULT NULL,
    `status` ENUM('Pending', 'Verified', 'Used', 'Cancelled') DEFAULT 'Pending',
    `verified_by` INT DEFAULT NULL,
    `verified_at` DATETIME DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`event_id`) REFERENCES `workshops`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`verified_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    UNIQUE KEY `unique_user_event_ticket` (`user_id`, `event_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 2. Create rebuilt attendance table
CREATE TABLE `attendance` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `ticket_id` INT NOT NULL UNIQUE,
    `user_id` INT NOT NULL,
    `event_id` INT NOT NULL, -- references workshops.id
    `attendance_status` ENUM('Present', 'Absent') DEFAULT 'Present',
    `marked_by` INT DEFAULT NULL,
    `marked_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`ticket_id`) REFERENCES `tickets`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`event_id`) REFERENCES `workshops`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`marked_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 3. Create rebuilt workshop_certificates table
CREATE TABLE `workshop_certificates` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `ticket_id` INT NOT NULL UNIQUE,
    `certificate_code` VARCHAR(100) NOT NULL UNIQUE,
    `issued_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`ticket_id`) REFERENCES `tickets`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
