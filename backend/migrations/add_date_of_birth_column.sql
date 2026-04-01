-- Add date_of_birth column to patients table
-- Date: March 3, 2026
-- Purpose: Store patient date of birth for age calculation and records

ALTER TABLE patients 
ADD COLUMN date_of_birth VARCHAR(20) AFTER gender;

-- Verify the column was added
DESCRIBE patients;

-- Optional: Update existing records with calculated date of birth based on age
-- UPDATE patients 
-- SET date_of_birth = DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL age YEAR), '%d/%m/%Y')
-- WHERE age IS NOT NULL AND date_of_birth IS NULL;
