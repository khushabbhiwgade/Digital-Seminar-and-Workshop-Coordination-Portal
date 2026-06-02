-- database/upgrade_to_participant.sql
-- Migration Script: Expand users and add feedback relation

USE `seminar_portal`;

-- 1. Add participant_type and organization columns to users
ALTER TABLE `users` 
    ADD COLUMN IF NOT EXISTS `participant_type` VARCHAR(50) DEFAULT NULL AFTER `full_name`,
    ADD COLUMN IF NOT EXISTS `organization` VARCHAR(150) DEFAULT NULL AFTER `participant_type`;

-- 2. Modify role ENUM to support 'participant'
ALTER TABLE `users` 
    MODIFY COLUMN `role` ENUM('admin', 'coordinator', 'student', 'participant') NOT NULL DEFAULT 'participant';

-- 3. Migrate legacy student role to participant role
UPDATE `users` 
SET `role` = 'participant' 
WHERE `role` = 'student';

-- 4. Map legacy student details (college) to organization and mark participant_type as Student
UPDATE `users` 
SET `participant_type` = 'Student', 
    `organization` = IF(TRIM(`college`) = '' OR `college` IS NULL, 'Not Specified', `college`)
WHERE `role` = 'participant' AND (`participant_type` IS NULL OR `participant_type` = '');

-- 5. Create relational feedback table
CREATE TABLE IF NOT EXISTS `feedback` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `event_id` INT NOT NULL,
    `rating` INT NOT NULL CHECK (`rating` >= 1 AND `rating` <= 5),
    `comments` TEXT DEFAULT NULL,
    `submitted_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`event_id`) REFERENCES `events`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_user_event_feedback` (`user_id`, `event_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
