-- Fix collation mismatch: Convert all tables to utf8mb4_general_ci to match server default
-- Then re-insert registration records from known user/event data

USE `seminar_portal`;

SET FOREIGN_KEY_CHECKS = 0;

-- Convert all tables to utf8mb4_general_ci
ALTER TABLE `users` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE `events` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE `registrations` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE `attendance` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE `certificates` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE `notifications` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- Re-insert registration records that were lost during the failed migration step.
-- We know from the old data which users registered for which events.
-- Original registrations_old data (from earlier MySQL query):
--   Khushab (id=1): AI&ML x5, Cloud x1, Web Dev x3, Cyber x1
--   Yash (id=3):    AI&ML x1
--   Test (id=2):    Web Dev x1

-- Insert unique user-event pairs (the UNIQUE KEY prevents duplicates)
INSERT INTO `registrations` (`user_id`, `event_id`, `registration_date`) VALUES
-- Khushab Bhiwgade (user_id=1) registered for:
(1, (SELECT id FROM events WHERE title = 'National Seminar on AI & ML'), '2026-05-24 14:11:53'),
(1, (SELECT id FROM events WHERE title = 'Cloud Computing & AWS Services'), '2026-05-24 14:47:09'),
(1, (SELECT id FROM events WHERE title = 'Web Development with React & Node'), '2026-05-25 10:03:54'),
(1, (SELECT id FROM events WHERE title = 'Cyber Security & Ethical Hacking'), '2026-06-01 13:36:40'),
-- Yash Sonare (user_id=3) registered for:
(3, (SELECT id FROM events WHERE title = 'National Seminar on AI & ML'), '2026-05-26 13:16:25'),
-- Test Student (user_id=2) registered for:
(2, (SELECT id FROM events WHERE title = 'Web Development with React & Node'), '2026-06-02 13:44:11');
