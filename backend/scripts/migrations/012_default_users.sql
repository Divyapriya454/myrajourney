-- Default Users Migration
-- Creates default patient, doctor, and admin user: Divya
-- Base Username: divyapriyaa0454.sse@saveetha.com
-- Password: Divya@ida7

-- IMPORTANT: Generate password hash first using:
-- php -r "echo password_hash('Divya@ida7', PASSWORD_BCRYPT);"
-- Then replace the password_hash value below with the generated hash

-- Since email must be unique in the database, we create three users with role-specific email identifiers
-- All users have the same name "Divya" and password "Divya@ida7"

-- Replace PASSWORD_HASH_PLACEHOLDER with the actual hash generated above
SET @password_hash = 'PASSWORD_HASH_PLACEHOLDER'; -- Replace this with generated hash

INSERT IGNORE INTO users (email, password_hash, role, name, status, created_at, updated_at) VALUES
('divyapriyaa0454.sse@saveetha.com', @password_hash, 'PATIENT', 'Divya', 'ACTIVE', NOW(), NOW()),
('divyapriyaa0454.sse@saveetha.com+doctor', @password_hash, 'DOCTOR', 'Divya', 'ACTIVE', NOW(), NOW()),
('divyapriyaa0454.sse@saveetha.com+admin', @password_hash, 'ADMIN', 'Divya', 'ACTIVE', NOW(), NOW());

-- Note: If you prefer to use the same email for all roles, you would need to:
-- 1. Modify the users table to remove the UNIQUE constraint on email, OR
-- 2. Create a composite unique key on (email, role)
-- For now, using email variations with +doctor and +admin suffixes

