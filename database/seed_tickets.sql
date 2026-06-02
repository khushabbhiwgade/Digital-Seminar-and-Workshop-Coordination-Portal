-- SQL Script: Seed tickets and certificates dynamically
USE `seminar_portal`;

-- Fetch the first student user ID in the database
SET @student_id = (SELECT id FROM users WHERE role = 'student' LIMIT 1);

-- 1. Insert seed tickets for workshops 1 and 2
INSERT INTO `tickets` (`user_id`, `workshop_id`, `ticket_number`, `qr_code_path`, `status`)
VALUES 
(@student_id, 1, 'TKT-2026-00001', 'uploads/qrcodes/TKT-2026-00001.png', 'active'),
(@student_id, 2, 'TKT-2026-00002', 'uploads/qrcodes/TKT-2026-00002.png', 'active')
ON DUPLICATE KEY UPDATE `ticket_number` = VALUES(`ticket_number`);

-- 2. Insert seed ticket for workshop 3 (completed)
INSERT INTO `tickets` (`user_id`, `workshop_id`, `ticket_number`, `qr_code_path`, `status`)
VALUES 
(@student_id, 3, 'TKT-2026-00003', 'uploads/qrcodes/TKT-2026-00003.png', 'completed')
ON DUPLICATE KEY UPDATE `status` = 'completed';

-- 3. Adjust seats left in workshops
UPDATE workshops SET seats_remaining = seats_remaining - 1 WHERE id IN (1, 2, 3);

-- 4. Seed workshop certificate referencing completed ticket
INSERT INTO `workshop_certificates` (`ticket_id`, `certificate_code`)
SELECT id, CONCAT('CERT-WS-', ticket_number)
FROM `tickets`
WHERE user_id = @student_id AND status = 'completed'
ON DUPLICATE KEY UPDATE `certificate_code` = VALUES(`certificate_code`);
