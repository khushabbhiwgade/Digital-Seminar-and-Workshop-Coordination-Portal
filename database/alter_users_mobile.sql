-- SQL Script: Make users.mobile column nullable
USE `seminar_portal`;
ALTER TABLE users MODIFY mobile VARCHAR(20) NULL DEFAULT NULL;
