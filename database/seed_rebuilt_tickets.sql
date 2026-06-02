-- SQL Script: Seed rebuilt tickets, attendance, and certificates
USE `seminar_portal`;

SET @student_id = (SELECT id FROM users WHERE role = 'student' LIMIT 1);
SET @admin_id = (SELECT id FROM users WHERE role = 'admin' LIMIT 1);

-- 1. Seed initial tickets with diverse statuses
-- Ticket 1: Pending Verification
INSERT INTO `tickets` (`user_id`, `event_id`, `ticket_number`, `qr_code_path`, `status`)
VALUES (@student_id, 1, 'TKT-2026-00001', 'uploads/qrcodes/TKT-2026-00001.png', 'Pending')
ON DUPLICATE KEY UPDATE `status` = 'Pending';

-- Ticket 2: Verified
INSERT INTO `tickets` (`user_id`, `event_id`, `ticket_number`, `qr_code_path`, `status`, `verified_by`, `verified_at`)
VALUES (@student_id, 2, 'TKT-2026-00002', 'uploads/qrcodes/TKT-2026-00002.png', 'Verified', @admin_id, NOW())
ON DUPLICATE KEY UPDATE `status` = 'Verified';

-- Ticket 3: Used (Present Attendance / Certificate Issued)
INSERT INTO `tickets` (`user_id`, `event_id`, `ticket_number`, `qr_code_path`, `status`, `verified_by`, `verified_at`)
VALUES (@student_id, 3, 'TKT-2026-00003', 'uploads/qrcodes/TKT-2026-00003.png', 'Used', @admin_id, NOW())
ON DUPLICATE KEY UPDATE `status` = 'Used';

-- Ticket 4: Cancelled (Absent Attendance / No Certificate)
INSERT INTO `tickets` (`user_id`, `event_id`, `ticket_number`, `qr_code_path`, `status`, `verified_by`, `verified_at`)
VALUES (@student_id, 4, 'TKT-2026-00004', 'uploads/qrcodes/TKT-2026-00004.png', 'Cancelled', @admin_id, NOW())
ON DUPLICATE KEY UPDATE `status` = 'Cancelled';

-- 2. Seed attendance logs
-- Attendance 3: Present
INSERT INTO `attendance` (`ticket_id`, `user_id`, `event_id`, `attendance_status`, `marked_by`)
SELECT id, user_id, event_id, 'Present', @admin_id
FROM `tickets`
WHERE user_id = @student_id AND event_id = 3
ON DUPLICATE KEY UPDATE `attendance_status` = 'Present';

-- Attendance 4: Absent
INSERT INTO `attendance` (`ticket_id`, `user_id`, `event_id`, `attendance_status`, `marked_by`)
SELECT id, user_id, event_id, 'Absent', @admin_id
FROM `tickets`
WHERE user_id = @student_id AND event_id = 4
ON DUPLICATE KEY UPDATE `attendance_status` = 'Absent';

-- 3. Seed workshop certificate for present attendee only
INSERT INTO `workshop_certificates` (`ticket_id`, `certificate_code`)
SELECT id, CONCAT('CERT-WS-', ticket_number)
FROM `tickets`
WHERE user_id = @student_id AND event_id = 3
ON DUPLICATE KEY UPDATE `certificate_code` = VALUES(`certificate_code`);

-- 4. Adjust seats remaining in workshops
UPDATE workshops SET seats_remaining = seats_remaining - 1 WHERE id IN (1, 2, 3, 4);
