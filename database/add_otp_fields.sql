-- SQL Script: Add OTP verification fields to users table
USE `seminar_portal`;

-- 1. Add verification columns if they do not already exist
ALTER TABLE `users` 
ADD COLUMN `is_verified` TINYINT(1) DEFAULT 0,
ADD COLUMN `otp_code` VARCHAR(6) DEFAULT NULL,
ADD COLUMN `otp_expiry` DATETIME DEFAULT NULL;

-- 2. Verify all existing admin and coordinator accounts so they can log in without verification
UPDATE `users` 
SET `is_verified` = 1 
WHERE `role` IN ('admin', 'coordinator');
