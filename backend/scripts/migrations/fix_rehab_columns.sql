-- Fix rehab_exercises table columns to allow string values for reps and frequency
ALTER TABLE rehab_exercises MODIFY reps VARCHAR(50) NULL;
ALTER TABLE rehab_exercises MODIFY frequency_per_week VARCHAR(50) NULL;
