-- Specific Users Migration
-- Creates the exact users as specified:
-- 2 Doctors, 1 Admin, Multiple Patients (to be created by admin/doctors)

-- IMPORTANT: Generate password hash first using:
-- php -r "echo password_hash('Divya@ida7', PASSWORD_BCRYPT);"
-- Then replace PASSWORD_HASH_PLACEHOLDER with the generated hash

SET @password_hash = 'PASSWORD_HASH_PLACEHOLDER'; -- Replace with generated hash

-- Doctor 1: divyapriyaa0454.sse@saveetha.com / Divya@ida7
INSERT IGNORE INTO users (email, password_hash, role, name, status, created_at, updated_at) VALUES
('divyapriyaa0454.sse@saveetha.com', @password_hash, 'DOCTOR', 'Divya', 'ACTIVE', NOW(), NOW());

-- Doctor 2: divyapriyaa87@gmail.com / Divya@ida7
INSERT IGNORE INTO users (email, password_hash, role, name, status, created_at, updated_at) VALUES
('divyapriyaa87@gmail.com', @password_hash, 'DOCTOR', 'Divya', 'ACTIVE', NOW(), NOW());

-- Admin: divyapriyaa0454.sse@saveetha.com / Divya@ida7
-- Note: Since email must be unique, we'll use a variation for admin
-- OR you can modify the database to allow same email with different roles
INSERT IGNORE INTO users (email, password_hash, role, name, status, created_at, updated_at) VALUES
('divyapriyaa0454.sse@saveetha.com+admin', @password_hash, 'ADMIN', 'Divya', 'ACTIVE', NOW(), NOW());

-- Patients will be created through admin/doctor panel and will be stored in database
-- All patients created will have role='PATIENT' and status='ACTIVE'

