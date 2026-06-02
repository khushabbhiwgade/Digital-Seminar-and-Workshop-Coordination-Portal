-- SQL Script: Create workshop registrations and certificates tables
USE `seminar_portal`;

CREATE TABLE IF NOT EXISTS `workshop_registrations` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `workshop_id` INT NOT NULL,
    `ticket_token` VARCHAR(50) NOT NULL UNIQUE,
    `status` ENUM('registered', 'attended', 'completed') DEFAULT 'registered',
    `registration_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`workshop_id`) REFERENCES `workshops`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_user_workshop` (`user_id`, `workshop_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `workshop_certificates` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `registration_id` INT NOT NULL UNIQUE,
    `certificate_code` VARCHAR(100) NOT NULL UNIQUE,
    `issued_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`registration_id`) REFERENCES `workshop_registrations`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
