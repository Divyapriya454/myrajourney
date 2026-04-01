<?php
/**
 * Test Free OCR Service
 * Tests OCR.space API with actual report images
 */

require __DIR__ . '/src/bootstrap.php';

use Src\Services\AI\FreeOCRService;
use Src\Services\AI\MedicalTermParser;

echo "=== TESTING FREE OCR SERVICE ===\n\n";

try {
    $db = Src\Config\DB::conn();
    
    // Get a report to test
    $stmt = $db->query("
        SELECT id, patient_id, file_path, file_url, title
        FROM reports
        WHERE file_path IS NOT NULL OR file_url IS NOT NULL
        ORDER BY created_at DESC
        LIMIT 1
    ");
    
    $report = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$report) {
        echo "No reports found in database\n";
        exit(1);
    }
    
    echo "Testing with Report ID: {$report['id']}\n";
    echo "Title: {$report['title']}\n\n";
    
    // Get file path
    $relativePath = !empty($report['file_path']) ? $report['file_path'] : $report['file_url'];
    $relativePath = ltrim($relativePath, '/');
    $filePath = __DIR__ . '/public/' . $relativePath;
    
    echo "File path: $filePath\n";
    
    if (!file_exists($filePath)) {
        echo "✗ File not found: $filePath\n";
        exit(1);
    }
    
    echo "✓ File exists\n";
    echo "File size: " . round(filesize($filePath) / 1024, 2) . " KB\n\n";
    
    // Test OCR service
    echo "--- TESTING OCR EXTRACTION ---\n\n";
    
    $ocrService = new FreeOCRService();
    $result = $ocrService->extractText($filePath);
    
    if ($result['success']) {
        echo "✓ OCR extraction successful\n";
        echo "Method: {$result['method']}\n";
        echo "Confidence: " . ($result['confidence'] * 100) . "%\n";
        echo "Processing time: {$result['processing_time_ms']}ms\n";
        echo "Pages: {$result['pages']}\n\n";
        
        echo "--- EXTRACTED TEXT ---\n";
        echo substr($result['text'], 0, 500);
        if (strlen($result['text']) > 500) {
            echo "...\n(truncated, total length: " . strlen($result['text']) . " characters)\n";
        }
        echo "\n\n";
        
        // Test parser
        echo "--- TESTING MEDICAL TERM PARSER ---\n\n";
        
        $parser = new MedicalTermParser();
        $values = $parser->parseText($result['text']);
        
        if (empty($values)) {
            echo "✗ No lab values extracted\n";
            echo "This might mean:\n";
            echo "  1. The image doesn't contain lab values\n";
            echo "  2. OCR couldn't read the text clearly\n";
            echo "  3. The format is not recognized\n\n";
            
            echo "Raw text for debugging:\n";
            echo $result['text'] . "\n";
        } else {
            echo "✓ Extracted " . count($values) . " lab values:\n\n";
            
            foreach ($values as $value) {
                echo "  {$value['test_name']}: {$value['value']} {$value['unit']}";
                if ($value['is_abnormal']) {
                    echo " [ABNORMAL]";
                }
                echo "\n";
                echo "    Normal Range: {$value['normal_range_min']} - {$value['normal_range_max']} {$value['unit']}\n";
                echo "    Confidence: " . ($value['confidence'] * 100) . "%\n\n";
            }
        }
        
    } else {
        echo "✗ OCR extraction failed\n";
        echo "Error: {$result['error']}\n\n";
        
        echo "Possible solutions:\n";
        echo "  1. Check internet connection (OCR.space is an online API)\n";
        echo "  2. Verify API key in .env file\n";
        echo "  3. Check if file format is supported\n";
        echo "  4. Ensure file size is under 1MB\n";
    }
    
    echo "\n=== TEST COMPLETE ===\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
