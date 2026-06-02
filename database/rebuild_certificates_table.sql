-- SQL Script: Rebuild Certificates Schema for Phase 11
USE `seminar_portal`;

-- Drop existing tables to ensure clean relations
DROP TABLE IF EXISTS `workshop_certificates`;
DROP TABLE IF EXISTS `certificates`;

-- Create rebuild certificates table
CREATE TABLE `certificates` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `event_id` INT NOT NULL, -- references workshops.id
    `registration_id` INT NOT NULL UNIQUE, -- references tickets.id, acts as the unique registration relation
    `certificate_no` VARCHAR(100) NOT NULL UNIQUE,
    `verification_code` VARCHAR(100) NOT NULL UNIQUE,
    `certificate_path` VARCHAR(255) NOT NULL,
    `generated_by` INT DEFAULT NULL,
    `generated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `status` ENUM('Generated', 'Revoked') DEFAULT 'Generated',
    
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`event_id`) REFERENCES `workshops`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`registration_id`) REFERENCES `tickets`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`generated_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
