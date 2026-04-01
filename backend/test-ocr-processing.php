<?php
/**
 * Test OCR Processing - Debug Script
 * Tests the complete OCR and data extraction pipeline
 */

require __DIR__ . '/src/bootstrap.php';

use Src\Services\AI\OCRService;
use Src\Services\AI\MedicalTermParser;

echo "=== OCR PROCESSING DEBUG TEST ===\n\n";

// Test 1: Check if Tesseract is available
echo "1. Checking Tesseract availability...\n";
exec('tesseract --version 2>&1', $output, $returnCode);
if ($returnCode === 0) {
    echo "   ✓ Tesseract found: " . $output[0] . "\n\n";
} else {
    echo "   ✗ Tesseract NOT found\n";
    echo "   This is OK - OCR will use fallback method\n\n";
}

// Test 2: Check if Imagick is available
echo "2. Checking Imagick extension...\n";
if (extension_loaded('imagick')) {
    echo "   ✓ Imagick extension loaded\n\n";
} else {
    echo "   ✗ Imagick extension NOT loaded\n";
    echo "   PDF processing will not work\n\n";
}

// Test 3: Check if GD is available
echo "3. Checking GD extension...\n";
if (extension_loaded('gd')) {
    echo "   ✓ GD extension loaded\n\n";
} else {
    echo "   ✗ GD extension NOT loaded\n\n";
}

// Test 4: Check medical terms dictionary
echo "4. Checking medical terms dictionary...\n";
try {
    $parser = new MedicalTermParser();
    $testKeys = $parser->getAllTestKeys();
    echo "   ✓ Dictionary loaded with " . count($testKeys) . " tests\n";
    echo "   Tests: " . implode(', ', $testKeys) . "\n\n";
} catch (Exception $e) {
    echo "   ✗ Dictionary error: " . $e->getMessage() . "\n\n";
}

// Test 5: Test text parsing with sample data
echo "5. Testing text parsing with sample data...\n";
$sampleText = "
Patient Report
Date: 2024-02-10

Lab Results:
C-Reactive Protein (CRP): 5.2 mg/L
ESR: 25 mm/hr
Rheumatoid Factor: 18 IU/mL
Hemoglobin: 13.5 g/dL
";

try {
    $parser = new MedicalTermParser();
    $extracted = $parser->parseText($sampleText);
    
    echo "   ✓ Extracted " . count($extracted) . " values:\n";
    foreach ($extracted as $value) {
        echo "   - {$value['test_name']}: {$value['value']} {$value['unit']}";
        if ($value['is_abnormal']) {
            echo " (ABNORMAL)";
        }
        echo " [Confidence: " . round($value['confidence'] * 100) . "%]\n";
    }
    echo "\n";
} catch (Exception $e) {
    echo "   ✗ Parsing error: " . $e->getMessage() . "\n\n";
}

// Test 6: Check recent reports
echo "6. Checking recent reports...\n";
try {
    $db = Src\Config\DB::conn();
    $stmt = $db->query("
        SELECT id, patient_id, title, file_path, file_url, ocr_processed, created_at
        FROM reports
        ORDER BY created_at DESC
        LIMIT 5
    ");
    $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($reports)) {
        echo "   No reports found\n\n";
    } else {
        echo "   Found " . count($reports) . " recent reports:\n";
        foreach ($reports as $report) {
            echo "   - ID: {$report['id']}, Title: {$report['title']}\n";
            echo "     File: " . ($report['file_path'] ?: $report['file_url']) . "\n";
            echo "     OCR Processed: " . ($report['ocr_processed'] ? 'Yes' : 'No') . "\n";
            
            // Check if file exists
            $relativePath = !empty($report['file_path']) ? $report['file_path'] : $report['file_url'];
            $relativePath = ltrim($relativePath, '/');
            $filePath = __DIR__ . '/public/' . $relativePath;
            
            if (file_exists($filePath)) {
                echo "     File exists: ✓ (" . filesize($filePath) . " bytes)\n";
            } else {
                echo "     File exists: ✗ (NOT FOUND)\n";
            }
            echo "\n";
        }
    }
} catch (Exception $e) {
    echo "   ✗ Database error: " . $e->getMessage() . "\n\n";
}

// Test 7: Check OCR processing logs
echo "7. Checking OCR processing logs...\n";
try {
    $db = Src\Config\DB::conn();
    $stmt = $db->query("
        SELECT id, report_id, processing_status, error_message, processing_time_ms, created_at
        FROM ocr_processing_logs
        ORDER BY created_at DESC
        LIMIT 5
    ");
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($logs)) {
        echo "   No processing logs found\n\n";
    } else {
        echo "   Found " . count($logs) . " recent logs:\n";
        foreach ($logs as $log) {
            echo "   - Report ID: {$log['report_id']}, Status: {$log['processing_status']}\n";
            if ($log['error_message']) {
                echo "     Error: {$log['error_message']}\n";
            }
            if ($log['processing_time_ms']) {
                echo "     Processing time: {$log['processing_time_ms']}ms\n";
            }
            echo "\n";
        }
    }
} catch (Exception $e) {
    echo "   ✗ Database error: " . $e->getMessage() . "\n\n";
}

// Test 8: Check extracted lab values
echo "8. Checking extracted lab values...\n";
try {
    $db = Src\Config\DB::conn();
    $stmt = $db->query("
        SELECT id, report_id, test_name, test_value, unit, is_abnormal, confidence_score
        FROM lab_values
        ORDER BY extracted_at DESC
        LIMIT 10
    ");
    $values = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($values)) {
        echo "   ✗ No lab values found in database\n";
        echo "   This means OCR is not extracting data properly\n\n";
    } else {
        echo "   ✓ Found " . count($values) . " extracted values:\n";
        foreach ($values as $value) {
            echo "   - Report {$value['report_id']}: {$value['test_name']} = {$value['test_value']} {$value['unit']}";
            if ($value['is_abnormal']) {
                echo " (ABNORMAL)";
            }
            echo "\n";
        }
        echo "\n";
    }
} catch (Exception $e) {
    echo "   ✗ Database error: " . $e->getMessage() . "\n\n";
}

echo "=== DIAGNOSIS ===\n\n";

// Provide diagnosis
if (!extension_loaded('imagick') && !extension_loaded('gd')) {
    echo "⚠️  CRITICAL: No image processing extensions available\n";
    echo "   Install Imagick or GD extension for OCR to work\n\n";
}

if ($returnCode !== 0) {
    echo "⚠️  WARNING: Tesseract not found\n";
    echo "   OCR will use fallback method (less accurate)\n";
    echo "   Install Tesseract for better results\n\n";
}

echo "=== RECOMMENDATIONS ===\n\n";
echo "1. If no values are being extracted:\n";
echo "   - Check if report file exists and is readable\n";
echo "   - Check if Tesseract is installed: tesseract --version\n";
echo "   - Check if Imagick/GD extensions are loaded\n";
echo "   - Try processing a simple test image with clear text\n\n";

echo "2. If values are extracted but wrong:\n";
echo "   - Check medical_terms.json dictionary\n";
echo "   - Adjust regex patterns in MedicalTermParser\n";
echo "   - Improve OCR preprocessing\n\n";

echo "3. If processing is slow:\n";
echo "   - Reduce image resolution\n";
echo "   - Optimize preprocessing\n";
echo "   - Use faster OCR engine\n\n";

echo "=== END ===\n";
