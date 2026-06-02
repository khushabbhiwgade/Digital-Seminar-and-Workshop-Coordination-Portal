-- SQL Migration Script: Upgrade Seminar Portal Database to InnoDB / UTF8MB4 Relational Schema
-- Target Database: seminar_portal

SET FOREIGN_KEY_CHECKS = 0;

USE `seminar_portal`;

-- ------------------------------------------------------------
-- STEP 1: Backup legacy tables by renaming them if they exist
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `registrations_old`;
RENAME TABLE `registrations` TO `registrations_old`;

DROP TABLE IF EXISTS `events_old`;
-- If events table exists, back it up, otherwise create a dummy events_old to keep migration script safe
CREATE TABLE IF NOT EXISTS `events` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(255) NOT NULL UNIQUE
);
RENAME TABLE `events` TO `events_old`;

SET FOREIGN_KEY_CHECKS = 1;

-- ------------------------------------------------------------
-- STEP 2: Create new relational tables using InnoDB and UTF8MB4
-- ------------------------------------------------------------

-- 1. Users table (Students, Coordinators, Admins)
CREATE TABLE `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `email` VARCHAR(100) NOT NULL UNIQUE,
    `password` VARCHAR(255) DEFAULT NULL,
    `full_name` VARCHAR(100) NOT NULL,
    `mobile` VARCHAR(20) NOT NULL,
    `role` ENUM('admin', 'coordinator', 'student') NOT NULL DEFAULT 'student',
    `college` VARCHAR(150) DEFAULT NULL,
    `department` VARCHAR(100) DEFAULT NULL,
    `year_of_study` VARCHAR(50) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 2. Events table (re-structured with scheduling, coordinator, and location)
CREATE TABLE `events` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(255) NOT NULL UNIQUE,
    `description` TEXT NOT NULL,
    `host` VARCHAR(255) NOT NULL,
    `event_date` DATE NOT NULL,
    `start_time` TIME NOT NULL,
    `end_time` TIME NOT NULL,
    `location` VARCHAR(150) NOT NULL,
    `total_seats` INT NOT NULL,
    `seats_remaining` INT NOT NULL,
    `coordinator_id` INT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`coordinator_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 3. Registrations table (structured join table with foreign keys)
CREATE TABLE `registrations` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `event_id` INT NOT NULL,
    `token` VARCHAR(50) DEFAULT NULL UNIQUE,
    `attended` TINYINT(1) DEFAULT 0,
    `registration_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`event_id`) REFERENCES `events`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_user_event` (`user_id`, `event_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 4. Attendance log table (detailed check-in and check-out logs)
CREATE TABLE `attendance` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `registration_id` INT NOT NULL,
    `checked_in_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `checked_out_at` TIMESTAMP NULL DEFAULT NULL,
    `marked_by` INT DEFAULT NULL,
    FOREIGN KEY (`registration_id`) REFERENCES `registrations`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`marked_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 5. Certificates table
CREATE TABLE `certificates` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `registration_id` INT NOT NULL UNIQUE,
    `certificate_code` VARCHAR(100) NOT NULL UNIQUE,
    `issued_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `pdf_path` VARCHAR(255) DEFAULT NULL,
    FOREIGN KEY (`registration_id`) REFERENCES `registrations`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 6. Notifications table
CREATE TABLE `notifications` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `message` TEXT NOT NULL,
    `is_read` TINYINT(1) DEFAULT 0,
    `sent_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ------------------------------------------------------------
-- STEP 3: Seed Event data from old events (supplemented with details)
-- ------------------------------------------------------------
INSERT INTO `events` (`title`, `description`, `host`, `event_date`, `start_time`, `end_time`, `location`, `total_seats`, `seats_remaining`)
VALUES
('National Seminar on AI & ML', 'Join us for a detailed panel discussion led by research scientists exploring neural network foundations, transformers, deep learning applications, and future trends of artificial intelligence in software engineering.', 'Dr. A. K. Sen (IIT)', '2026-05-28', '10:00:00', '13:00:00', 'Seminar Hall A', 100, 95),
('Web Development with React & Node', 'A hands-on coding workshop covering modern full-stack web architectures. Students will build a functional web application with React components, routing, Express API endpoints, and database connection. Highly practical.', 'Prof. S. Sharma (Tech Lead)', '2026-06-02', '09:30:00', '16:30:00', 'CSE Lab 3', 50, 48),
('Cyber Security & Ethical Hacking', 'Understand security vulnerabilities in standard network protocols, scanning techniques, firewalls, and data protection regulations. Features a live demonstration of hacking mitigation steps by network security professionals.', 'Mr. Rajiv Malhotra (EC-Council)', '2026-06-10', '11:00:00', '14:00:00', 'Main Auditorium', 120, 115),
('Cloud Computing & AWS Services', 'A deep dive workshop focused on hosting and deploying services on Amazon Web Services. Hands-on configuration of AWS EC2 instances, S3 buckets, AWS Lambdas, and serverless compute pipelines. Excellent for projects.', 'Mrs. Priya Verma (AWS Solutions Architect)', '2026-06-15', '09:30:00', '16:30:00', 'CSE Lab 5', 80, 80);

-- Migrate any custom events that exist in events_old but aren't in the default set
INSERT INTO `events` (`title`, `description`, `host`, `event_date`, `start_time`, `end_time`, `location`, `total_seats`, `seats_remaining`)
SELECT `title`, 'No description provided.', `host`, '2026-06-30', '10:00:00', '12:00:00', 'Main Seminar Hall', `total_seats`, `seats_remaining`
FROM `events_old`
WHERE `title` NOT IN ('National Seminar on AI & ML', 'Web Development with React & Node', 'Cyber Security & Ethical Hacking', 'Cloud Computing & AWS Services');

-- ------------------------------------------------------------
-- STEP 4: Migrate unique users from registrations_old
-- ------------------------------------------------------------
INSERT INTO `users` (`email`, `full_name`, `mobile`, `college`, `department`, `year_of_study`, `role`)
SELECT `email`, MAX(`full_name`), MAX(`mobile`), MAX(`college`), MAX(`department`), MAX(`year_of_study`), 'student'
FROM `registrations_old`
GROUP BY `email`;

-- Insert a default admin and coordinator for testing and portal administration
-- (passwords are set to NULL by default, they can be configured with password_hash later)
INSERT INTO `users` (`email`, `full_name`, `mobile`, `role`)
VALUES 
('admin@college.edu', 'Portal Administrator', '9999999991', 'admin'),
('coordinator@college.edu', 'Academic Coordinator', '9999999992', 'coordinator')
ON DUPLICATE KEY UPDATE `role`=`role`;

-- ------------------------------------------------------------
-- STEP 5: Migrate and map registrations from registrations_old
-- ------------------------------------------------------------
INSERT INTO `registrations` (`user_id`, `event_id`, `token`, `attended`, `registration_date`)
SELECT u.`id`, e.`id`, ro.`token`, ro.`attended`, ro.`registration_date`
FROM `registrations_old` ro
JOIN `users` u ON ro.`email` = u.`email`
JOIN `events` e ON ro.`event_name` = e.`title`
ON DUPLICATE KEY UPDATE `attended` = VALUES(`attended`);

-- ------------------------------------------------------------
-- STEP 6: Cleanup old temporary backup tables
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `registrations_old`;
DROP TABLE IF EXISTS `events_old`;
