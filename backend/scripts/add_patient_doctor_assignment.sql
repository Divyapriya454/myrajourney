-- Add patient-doctor assignment to database
-- This allows admins to assign patients to specific doctors

USE myrajourney;

-- Add assigned_doctor_id column to patients table
ALTER TABLE patients 
ADD COLUMN assigned_doctor_id INT UNSIGNED NULL AFTER id,
ADD CONSTRAINT fk_patients_assigned_doctor 
FOREIGN KEY (assigned_doctor_id) REFERENCES users(id) ON DELETE SET NULL;

-- Add index for faster queries
CREATE INDEX idx_patients_assigned_doctor ON patients(assigned_doctor_id);

-- Verify the change
DESCRIBE patients;

SELECT 'Patient-doctor assignment column added successfully!' as status;
