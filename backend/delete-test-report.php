<?php
/**
 * Delete Test Report (ID 15)
 */

require __DIR__ . '/src/bootstrap.php';

echo "=== DELETING TEST REPORT ===\n\n";

try {
    $db = Src\Config\DB::conn();
    
    // Get report details first
    $stmt = $db->prepare("SELECT * FROM reports WHERE id = 15");
    $stmt->execute();
    $report = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($report) {
        echo "Found test report:\n";
        echo "  ID: {$report['id']}\n";
        echo "  Title: {$report['title']}\n";
        echo "  File: {$report['file_url']}\n\n";
        
        // Delete the file if it exists
        if (!empty($report['file_url'])) {
            $filePath = __DIR__ . '/public' . $report['file_url'];
            if (file_exists($filePath)) {
                unlink($filePath);
                echo "✓ Deleted file: $filePath\n";
            }
        }
        
        // Delete from database
        $stmt = $db->prepare("DELETE FROM reports WHERE id = 15");
        $stmt->execute();
        
        echo "✓ Deleted report from database\n\n";
        echo "Test report removed successfully!\n";
    } else {
        echo "Test report (ID 15) not found.\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
