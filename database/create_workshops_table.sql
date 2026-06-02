-- SQL Script: Create workshops table
USE `seminar_portal`;

CREATE TABLE IF NOT EXISTS `workshops` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(255) NOT NULL,
    `domain` VARCHAR(100) NOT NULL,
    `host` VARCHAR(255) NOT NULL,
    `speaker` VARCHAR(255) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `prerequisites` TEXT DEFAULT NULL,
    `capacity` INT NOT NULL,
    `seats_remaining` INT NOT NULL,
    `start_date` DATE NOT NULL,
    `end_date` DATE NOT NULL,
    `venue` VARCHAR(255) NOT NULL,
    `poster_image` VARCHAR(255) DEFAULT NULL,
    `status` ENUM('active', 'archived', 'upcoming') DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
