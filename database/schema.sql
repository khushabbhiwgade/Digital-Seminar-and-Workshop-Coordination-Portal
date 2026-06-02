-- Database Schema for Seminar and Workshop Coordination Portal
CREATE DATABASE IF NOT EXISTS `seminar_portal`;
USE `seminar_portal`;

-- 1. Create registrations table if not exists, and make sure all columns exist
CREATE TABLE IF NOT EXISTS `registrations` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `full_name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(100) NOT NULL,
    `mobile` VARCHAR(20) NOT NULL,
    `college` VARCHAR(150),
    `department` VARCHAR(100),
    `year_of_study` VARCHAR(50),
    `event_name` VARCHAR(150) NOT NULL,
    `token` VARCHAR(50) UNIQUE DEFAULT NULL,
    `attended` TINYINT(1) DEFAULT 0,
    `registration_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Ensure token and attended columns exist in registrations in case the table already existed without them
ALTER TABLE `registrations` ADD COLUMN IF NOT EXISTS `token` VARCHAR(50) UNIQUE DEFAULT NULL;
ALTER TABLE `registrations` ADD COLUMN IF NOT EXISTS `attended` TINYINT(1) DEFAULT 0;

-- 2. Create events table
CREATE TABLE IF NOT EXISTS `events` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(255) NOT NULL UNIQUE,
    `host` VARCHAR(255) NOT NULL,
    `total_seats` INT NOT NULL,
    `seats_remaining` INT NOT NULL
);

-- Insert sample events
INSERT INTO `events` (`title`, `host`, `total_seats`, `seats_remaining`) VALUES
('National Seminar on AI & ML', 'Dr. A. K. Sen (IIT)', 100, 95),
('Web Development with React & Node', 'Prof. S. Sharma (Tech Lead)', 50, 48),
('Cyber Security & Ethical Hacking', 'Mr. Rajiv Malhotra (EC-Council)', 120, 115),
('Cloud Computing & AWS Services', 'Mrs. Priya Verma (AWS Solutions Architect)', 80, 80)
ON DUPLICATE KEY UPDATE 
    `host` = VALUES(`host`), 
    `total_seats` = VALUES(`total_seats`), 
    `seats_remaining` = VALUES(`seats_remaining`);
