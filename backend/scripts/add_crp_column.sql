-- Add CRP column to report_notes table
-- Migration script for CRP Progress Tracking feature

-- Add CRP value column
ALTER TABLE report_notes 
ADD COLUMN IF NOT EXISTS crp_value DECIMAL(5,2) NULL 
COMMENT 'CRP value in mg/L (0-500 range)';

-- Add index for faster queries
CREATE INDEX IF NOT EXISTS idx_crp_value ON report_notes(crp_value);

-- Verify the column was added
SELECT 
    COLUMN_NAME, 
    DATA_TYPE, 
    IS_NULLABLE, 
    COLUMN_COMMENT 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_NAME = 'report_notes' 
  AND COLUMN_NAME = 'crp_value';
