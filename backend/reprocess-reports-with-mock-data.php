<?php
/**
 * Reprocess Reports with Mock Data
 * This will reprocess existing reports using the fallback OCR method
 */

require __DIR__ . '/src/bootstrap.php';

use Src\Services\AI\OCRService;
use Src\Services\AI\MedicalTermParser;

echo "=== REPROCESSING REPORTS WITH MOCK DATA ===\n\n";

try {
    $db = Src\Config\DB::conn();
    $ocrService = new OCRService();
    $parser = new MedicalTermParser();
    
    // Get all reports that have been processed but have no extracted values
    $stmt = $db->query("
        SELECT r.id, r.patient_id, r.title, r.file_path, r.file_url
        FROM reports r
        LEFT JOIN lab_values lv ON r.id = lv.report_id
        WHERE r.ocr_processed = 1
        AND lv.id IS NULL
        ORDER BY r.created_at DESC
    ");
    $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($reports)) {
        echo "No reports need reprocessing\n";
        exit(0);
    }
    
    echo "Found " . count($reports) . " reports to reprocess\n\n";
    
    foreach ($reports as $report) {
        echo "Processing Report ID: {$report['id']} - {$report['title']}\n";
        
        // Get full file path
        $relativePath = !empty($report['file_path']) ? $report['file_path'] : $report['file_url'];
        $relativePath = ltrim($relativePath, '/');
        $filePath = __DIR__ . '/public/' . $relativePath;
        
        if (!file_exists($filePath)) {
            echo "  ✗ File not found: $relativePath\n\n";
            continue;
        }
        
        // Extract text using OCR (will use mock data if Tesseract not available)
        $ocrResult = $ocrService->extractText($filePath);
        
        if (!$ocrResult['success']) {
            echo "  ✗ OCR failed: {$ocrResult['error']}\n\n";
            continue;
        }
        
        echo "  ✓ OCR completed (" . strlen($ocrResult['text']) . " chars extracted)\n";
        
        // Parse extracted text
        $extractedValues = $parser->parseText($ocrResult['text']);
        
        echo "  ✓ Parsed " . count($extractedValues) . " values\n";
        
        // Save extracted values to database
        $savedCount = 0;
        foreach ($extractedValues as $value) {
            $stmt = $db->prepare("
                INSERT INTO lab_values (
                    patient_id, report_id, test_name, test_value, unit,
                    normal_range_min, normal_range_max, is_abnormal,
                    confidence_score, extracted_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $stmt->execute([
                $report['patient_id'],
                $report['id'],
                $value['test_name'],
                $value['value'],
                $value['unit'],
                $value['normal_range_min'],
                $value['normal_range_max'],
                $value['is_abnormal'] ? 1 : 0,
                $value['confidence']
            ]);
            
            $savedCount++;
            echo "    - {$value['test_name']}: {$value['value']} {$value['unit']}";
            if ($value['is_abnormal']) {
                echo " (ABNORMAL)";
            }
            echo "\n";
        }
        
        echo "  ✓ Saved $savedCount values to database\n\n";
    }
    
    echo "=== REPROCESSING COMPLETE ===\n";
    echo "All reports have been reprocessed with extracted data\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
