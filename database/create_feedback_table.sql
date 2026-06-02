-- SQL Script: Create Feedback Table for Phase 12
USE `seminar_portal`;

DROP TABLE IF EXISTS `feedback`;

CREATE TABLE `feedback` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `event_id` INT NOT NULL, -- references workshops.id
    `registration_id` INT NOT NULL UNIQUE, -- references tickets.id, limits to 1 feedback per registration/ticket
    
    `overall_rating` TINYINT NOT NULL,
    `speaker_rating` TINYINT NOT NULL,
    `content_rating` TINYINT NOT NULL,
    `organization_rating` TINYINT NOT NULL,
    `venue_rating` TINYINT NOT NULL,
    
    `comments` TEXT NOT NULL,
    `submitted_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`event_id`) REFERENCES `workshops`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`registration_id`) REFERENCES `tickets`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
