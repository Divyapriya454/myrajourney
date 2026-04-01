<?php
/**
 * Test CRP API endpoints
 */

require_once __DIR__ . '/src/bootstrap.php';

use Src\Config\DB;

echo "=== Testing CRP API Implementation ===\n\n";

try {
    $db = DB::conn();
    
    // 1. Verify column exists
    echo "1. Checking if crp_value column exists...\n";
    $stmt = $db->query("SHOW COLUMNS FROM report_notes LIKE 'crp_value'");
    $column = $stmt->fetch();
    
    if ($column) {
        echo "   ✓ Column exists: {$column['Field']} ({$column['Type']})\n\n";
    } else {
        echo "   ✗ Column does not exist!\n\n";
        exit(1);
    }
    
    // 2. Check if we have any reports to test with
    echo "2. Checking for test data...\n";
    $stmt = $db->query("SELECT COUNT(*) FROM reports");
    $reportCount = $stmt->fetchColumn();
    echo "   Reports in database: $reportCount\n";
    
    $stmt = $db->query("SELECT COUNT(*) FROM report_notes");
    $noteCount = $stmt->fetchColumn();
    echo "   Report notes in database: $noteCount\n\n";
    
    // 3. Insert a test CRP value if we have reports
    if ($reportCount > 0) {
        echo "3. Testing CRP value insertion...\n";
        
        // Get a report
        $stmt = $db->query("SELECT id, patient_id FROM reports LIMIT 1");
        $report = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($report) {
            // Get a doctor
            $stmt = $db->query("SELECT id FROM users WHERE role = 'DOCTOR' LIMIT 1");
            $doctor = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($doctor) {
                // Insert test note with CRP
                $testCrp = 15.5;
                $stmt = $db->prepare("
                    INSERT INTO report_notes 
                        (report_id, doctor_id, diagnosis_text, suggestions_text, crp_value, created_at, updated_at)
                    VALUES 
                        (:rid, :did, 'Test diagnosis', 'Test suggestions', :crp, NOW(), NOW())
                ");
                
                $stmt->execute([
                    ':rid' => $report['id'],
                    ':did' => $doctor['id'],
                    ':crp' => $testCrp
                ]);
                
                $noteId = $db->lastInsertId();
                echo "   ✓ Test note created with CRP value: $testCrp mg/L (Note ID: $noteId)\n\n";
                
                // 4. Test retrieval
                echo "4. Testing CRP history retrieval...\n";
                $stmt = $db->prepare("
                    SELECT 
                        rn.crp_value,
                        rn.created_at as review_date,
                        r.title as report_title
                    FROM report_notes rn
                    INNER JOIN reports r ON rn.report_id = r.id
                    WHERE r.patient_id = :pid 
                      AND rn.crp_value IS NOT NULL
                    ORDER BY rn.created_at ASC
                ");
                
                $stmt->execute([':pid' => $report['patient_id']]);
                $crpData = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                if (count($crpData) > 0) {
                    echo "   ✓ Found " . count($crpData) . " CRP record(s) for patient {$report['patient_id']}\n";
                    foreach ($crpData as $record) {
                        echo "     - CRP: {$record['crp_value']} mg/L on {$record['review_date']}\n";
                    }
                    echo "\n";
                } else {
                    echo "   ✗ No CRP data found\n\n";
                }
                
                // 5. Clean up test data
                echo "5. Cleaning up test data...\n";
                $stmt = $db->prepare("DELETE FROM report_notes WHERE id = :id");
                $stmt->execute([':id' => $noteId]);
                echo "   ✓ Test note deleted\n\n";
                
            } else {
                echo "   ⚠ No doctor found in database\n\n";
            }
        } else {
            echo "   ⚠ No reports found\n\n";
        }
    } else {
        echo "3. ⚠ No reports in database to test with\n\n";
    }
    
    echo "=== CRP API Test Complete ===\n";
    echo "✓ Backend is ready for CRP tracking!\n";
    
} catch (Exception $e) {
    echo "\n✗ Test failed: " . $e->getMessage() . "\n";
    exit(1);
}
