-- Fix missing doctor profiles
-- Insert missing doctor profile for existing doctor users

USE myrajourney;

-- Insert doctor profile for any doctor user that doesn't have one
INSERT INTO doctors (id, specialization, created_at, updated_at)
SELECT u.id, 'General Practice', NOW(), NOW()
FROM users u
LEFT JOIN doctors d ON u.id = d.id
WHERE u.role = 'DOCTOR' AND d.id IS NULL;

-- Verify
SELECT u.id, u.name, u.email, u.role, d.specialization
FROM users u
LEFT JOIN doctors d ON u.id = d.id
WHERE u.role = 'DOCTOR';
