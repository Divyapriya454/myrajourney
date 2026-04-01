<?php
/**
 * Test AI Report Processing
 */

require_once __DIR__ . '/src/bootstrap.php';
require_once __DIR__ . '/src/config/db.php';

use Src\Config\DB;

$db = DB::conn();

echo "===========================================\n";
echo "Testing AI Report Processing\n";
echo "===========================================\n\n";

// Get a real report
$stmt = $db->query("SELECT id, patient_id, title, file_url, file_path FROM reports LIMIT 1");
$report = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$report) {
    echo "❌ No reports found in database\n";
    exit(1);
}

echo "Found report:\n";
echo "ID: {$report['id']}\n";
echo "Patient ID: {$report['patient_id']}\n";
echo "Title: {$report['title']}\n";
echo "File URL: {$report['file_url']}\n";
echo "File Path: {$report['file_path']}\n\n";

// Check if file exists
$relativePath = !empty($report['file_path']) ? $report['file_path'] : $report['file_url'];
$relativePath = ltrim($relativePath, '/');
$fullPath = __DIR__ . '/public/' . $relativePath;

echo "Full path: $fullPath\n";
echo "File exists: " . (file_exists($fullPath) ? "YES" : "NO") . "\n\n";

if (!file_exists($fullPath)) {
    echo "❌ File not found at expected location\n";
    echo "Checking alternative locations...\n";
    
    // Try without public/
    $altPath = __DIR__ . '/' . $relativePath;
    echo "Alt path 1: $altPath - " . (file_exists($altPath) ? "FOUND" : "NOT FOUND") . "\n";
    
    // Try with uploads/
    $altPath2 = __DIR__ . '/uploads/' . basename($relativePath);
    echo "Alt path 2: $altPath2 - " . (file_exists($altPath2) ? "FOUND" : "NOT FOUND") . "\n";
} else {
    echo "✅ File found!\n";
    echo "File size: " . filesize($fullPath) . " bytes\n";
}

echo "\n===========================================\n";
