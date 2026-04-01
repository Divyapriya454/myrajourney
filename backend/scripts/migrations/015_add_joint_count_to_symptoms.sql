-- Add joint_count column to symptom_logs table
ALTER TABLE symptom_logs 
ADD COLUMN joint_count TINYINT UNSIGNED NULL 
AFTER fatigue_level;

-- Add constraint to ensure joint_count is between 0 and 10
ALTER TABLE symptom_logs 
ADD CONSTRAINT chk_joint_count_range 
CHECK (joint_count IS NULL OR (joint_count >= 0 AND joint_count <= 10));

-- Update existing records to extract joint count from notes if possible
-- This is optional and can be run manually if needed
-- UPDATE symptom_logs 
-- SET joint_count = CASE 
--     WHEN notes REGEXP 'Joints affected: ([0-9]+)' THEN 
--         CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(notes, 'Joints affected: ', -1), '\n', 1) AS UNSIGNED)
--     ELSE NULL 
-- END 
-- WHERE joint_count IS NULL AND notes IS NOT NULL;