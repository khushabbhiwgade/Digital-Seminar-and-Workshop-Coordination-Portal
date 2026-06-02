-- SQL Script: Add otp_attempts column to users table
USE `seminar_portal`;

ALTER TABLE `users` 
ADD COLUMN `otp_attempts` INT DEFAULT 0;
