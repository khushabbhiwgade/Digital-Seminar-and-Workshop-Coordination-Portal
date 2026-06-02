-- database/add_is_active.sql
-- Add is_active column to users table for admin account management
-- Safe to run multiple times (uses IF NOT EXISTS logic via ALTER IGNORE)

ALTER TABLE `users`
    ADD COLUMN IF NOT EXISTS `is_active` TINYINT(1) NOT NULL DEFAULT 1
    COMMENT '1 = Active account, 0 = Deactivated by admin';

-- Set all existing users as active by default
UPDATE `users` SET `is_active` = 1 WHERE `is_active` IS NULL;
