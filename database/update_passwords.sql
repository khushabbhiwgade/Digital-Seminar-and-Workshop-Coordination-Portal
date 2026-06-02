-- SQL Script: Seed hashed passwords for existing users in seminar_portal
USE `seminar_portal`;

-- Update Admin account password to 'admin123'
UPDATE users 
SET password = '$2y$10$OMyMS7Ov3zif/pw1bxqATe8hi9rEwZwvAiKcV972HKsNjPNgvfKHu' 
WHERE email = 'admin@college.edu';

-- Update Coordinator account password to 'coord123'
UPDATE users 
SET password = '$2y$10$6fd23oIBVK1qcCkSgV7kf.bb1Itp.dzK.19eiYiod6R0pXMJdcevu' 
WHERE email = 'coordinator@college.edu';

-- Update Student account passwords to 'student123'
UPDATE users 
SET password = '$2y$10$30ZL1PYXuS0vLCNYbeSFF.oHfy3rSq7mATfk91RcllWMDvUBhK6U.' 
WHERE role = 'student' OR email = 'student@test.com';
