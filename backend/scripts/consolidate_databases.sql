-- ============================================
-- Database Consolidation Script
-- This script helps you consolidate duplicate myrajourney databases
-- ============================================

-- STEP 1: Check which databases exist
-- Run this first to see all databases:
SHOW DATABASES LIKE 'myrajourney%';

-- STEP 2: Check which database has more data
-- Replace 'myrajourney' and 'myrajourney_old' with your actual database names

-- Check table count in first database
SELECT 'myrajourney' as db_name, COUNT(*) as table_count
FROM information_schema.tables 
WHERE table_schema = 'myrajourney';

-- Check table count in second database (if it exists)
-- SELECT 'myrajourney_old' as db_name, COUNT(*) as table_count
-- FROM information_schema.tables 
-- WHERE table_schema = 'myrajourney_old';

-- Check user count in first database
SELECT 'myrajourney' as db_name, COUNT(*) as user_count
FROM myrajourney.users;

-- Check user count in second database (if it exists)
-- SELECT 'myrajourney_old' as db_name, COUNT(*) as user_count
-- FROM myrajourney_old.users;

-- STEP 3: If you need to merge data from both databases:
-- 1. Export data from the database you want to keep
-- 2. Export data from the database you want to delete
-- 3. Import into the main database
-- 4. Delete the duplicate database

-- STEP 4: Delete the duplicate database (UNCOMMENT AND RUN AFTER VERIFICATION)
-- WARNING: This will permanently delete the database and all its data!
-- Make sure you've backed up any important data first!

-- DROP DATABASE IF EXISTS myrajourney_old;
-- DROP DATABASE IF EXISTS myrajourney_backup;
-- DROP DATABASE IF EXISTS myrajourney_copy;

-- STEP 5: After deleting duplicate, run setup_database.sql to ensure all tables exist

