<?php
/**
 * Run CRP column migration
 * Adds crp_value column to report_notes table
 */

require_once __DIR__ . '/src/bootstrap.php';

use Src\Config\DB;

try {
    $db = DB::conn();
    
    echo "Starting CRP column migration...\n\n";
    
    // Check if column already exists
    $stmt = $db->prepare("
        SELECT COUNT(*) 
        FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'report_notes' 
          AND COLUMN_NAME = 'crp_value'
    ");
    $stmt->execute();
    $exists = (int)$stmt->fetchColumn();
    
    if ($exists > 0) {
        echo "✓ Column 'crp_value' already exists in report_notes table\n";
    } else {
        echo "Adding 'crp_value' column to report_notes table...\n";
        
        $db->exec("
            ALTER TABLE report_notes 
            ADD COLUMN crp_value DECIMAL(5,2) NULL 
            COMMENT 'CRP value in mg/L (0-500 range)'
        ");
        
        echo "✓ Column 'crp_value' added successfully\n";
    }
    
    // Check if index exists
    $stmt = $db->prepare("
        SELECT COUNT(*) 
        FROM INFORMATION_SCHEMA.STATISTICS 
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'report_notes' 
          AND INDEX_NAME = 'idx_crp_value'
    ");
    $stmt->execute();
    $indexExists = (int)$stmt->fetchColumn();
    
    if ($indexExists > 0) {
        echo "✓ Index 'idx_crp_value' already exists\n";
    } else {
        echo "Creating index on crp_value column...\n";
        
        $db->exec("CREATE INDEX idx_crp_value ON report_notes(crp_value)");
        
        echo "✓ Index 'idx_crp_value' created successfully\n";
    }
    
    // Verify the migration
    echo "\nVerifying migration...\n";
    $stmt = $db->prepare("
        SELECT 
            COLUMN_NAME, 
            DATA_TYPE, 
            IS_NULLABLE, 
            COLUMN_COMMENT 
        FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'report_notes' 
          AND COLUMN_NAME = 'crp_value'
    ");
    $stmt->execute();
    $column = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($column) {
        echo "\n✓ Migration successful!\n";
        echo "Column details:\n";
        echo "  - Name: {$column['COLUMN_NAME']}\n";
        echo "  - Type: {$column['DATA_TYPE']}\n";
        echo "  - Nullable: {$column['IS_NULLABLE']}\n";
        echo "  - Comment: {$column['COLUMN_COMMENT']}\n";
    } else {
        echo "\n✗ Migration verification failed\n";
        exit(1);
    }
    
    echo "\n✓ CRP migration completed successfully!\n";
    
} catch (Exception $e) {
    echo "\n✗ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
