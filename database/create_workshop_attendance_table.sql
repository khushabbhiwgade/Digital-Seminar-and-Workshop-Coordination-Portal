-- SQL Script: Create workshop_attendance table
USE `seminar_portal`;

CREATE TABLE IF NOT EXISTS `workshop_attendance` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `ticket_id` INT NOT NULL UNIQUE,
    `status` ENUM('present', 'absent') DEFAULT 'present',
    `marked_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `marked_by` INT DEFAULT NULL,
    FOREIGN KEY (`ticket_id`) REFERENCES `tickets`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`marked_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
