-- SQL Script: Upgrade Tickets and Create Activity Logs schema for Phase 10
USE `seminar_portal`;

-- 1. Alter tickets registration_status column to support the full 7-state lifecycle
ALTER TABLE `tickets` 
    MODIFY COLUMN `registration_status` ENUM('Pending', 'Verified', 'Attended', 'Completed', 'Absent', 'Cancelled', 'Rejected') DEFAULT 'Pending';

-- 2. Create activity_logs table to track audit trails
CREATE TABLE IF NOT EXISTS `activity_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT DEFAULT NULL,
    `action` VARCHAR(255) NOT NULL,
    `details` TEXT DEFAULT NULL,
    `timestamp` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
