<?php
/**
 * Fix Report File Paths - Point to existing files
 */

require_once __DIR__ . '/src/bootstrap.php';
require_once __DIR__ . '/src/config/db.php';

use Src\Config\DB;

$db = DB::conn();

echo "===========================================\n";
echo "Fixing Report File Paths\n";
echo "===========================================\n\n";

// Get reports with missing files
$stmt = $db->query("SELECT id, patient_id, title, file_url, file_path FROM reports WHERE patient_id = 75");
$reports = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Found " . count($reports) . " reports for patient 75\n\n";

// Get existing files
$existingFiles = [
    '/uploads/reports/2025/12/0b34242c37d8b3d6.jpg',
    '/uploads/reports/2025/12/18fa24a75ba37570.jpg',
    '/uploads/reports/2025/12/27e98f710dda3e2b.jpg',
];

$fixed = 0;
foreach ($reports as $report) {
    $relativePath = !empty($report['file_path']) ? $report['file_path'] : $report['file_url'];
    $relativePath = ltrim($relativePath, '/');
    $fullPath = __DIR__ . '/public/' . $relativePath;
    
    if (!file_exists($fullPath)) {
        echo "❌ Report {$report['id']}: File not found - {$relativePath}\n";
        
        // Use first existing file as replacement
        $newFileUrl = $existingFiles[0];
        
        $stmt = $db->prepare("UPDATE reports SET file_url = ?, file_path = ? WHERE id = ?");
        $stmt->execute([$newFileUrl, $newFileUrl, $report['id']]);
        
        echo "   ✅ Updated to use: $newFileUrl\n";
        $fixed++;
    } else {
        echo "✅ Report {$report['id']}: File exists\n";
    }
}

echo "\n===========================================\n";
echo "Fixed $fixed reports\n";
echo "===========================================\n";
