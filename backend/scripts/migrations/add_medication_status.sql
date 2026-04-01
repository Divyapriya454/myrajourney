-- Add status column to patient_medications table
ALTER TABLE patient_medications 
ADD COLUMN status ENUM('active', 'stopped', 'removed') DEFAULT 'active' AFTER active;

-- Add removal tracking columns
ALTER TABLE patient_medications 
ADD COLUMN removed_at DATETIME NULL AFTER status;

ALTER TABLE patient_medications 
ADD COLUMN removed_by VARCHAR(50) NULL AFTER removed_at;

-- Update existing records to have 'active' status where active=1
UPDATE patient_medications 
SET status = 'active' 
WHERE active = 1 AND status IS NULL;

-- Update existing records to have 'stopped' status where active=0
UPDATE patient_medications 
SET status = 'stopped' 
WHERE active = 0 AND status IS NULL;

-- Add index for faster status queries
CREATE INDEX idx_medication_status ON patient_medications(status, patient_id);
