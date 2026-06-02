-- SQL Script: Seed workshop registrations and certificates dynamically
USE `seminar_portal`;

-- Fetch the first student user ID in the database
SET @student_id = (SELECT id FROM users WHERE role = 'student' LIMIT 1);

-- If a student exists, insert registrations and certificates
INSERT INTO `workshop_registrations` (`user_id`, `workshop_id`, `ticket_token`, `status`)
SELECT @student_id, id, CONCAT('WORKSHOP-REG', id), 'registered'
FROM `workshops`
WHERE id IN (1, 2)
ON DUPLICATE KEY UPDATE `status` = 'registered';

INSERT INTO `workshop_registrations` (`user_id`, `workshop_id`, `ticket_token`, `status`)
SELECT @student_id, id, CONCAT('WORKSHOP-COM', id), 'completed'
FROM `workshops`
WHERE id = 3
ON DUPLICATE KEY UPDATE `status` = 'completed';

-- Deduct seats from workshops 1, 2, and 3
UPDATE workshops SET seats_remaining = seats_remaining - 1 WHERE id IN (1, 2, 3);

-- Seed certificate
INSERT INTO `workshop_certificates` (`registration_id`, `certificate_code`)
SELECT id, CONCAT('CERT-WS-', ticket_token)
FROM `workshop_registrations`
WHERE user_id = @student_id AND status = 'completed'
ON DUPLICATE KEY UPDATE `certificate_code` = VALUES(`certificate_code`);
