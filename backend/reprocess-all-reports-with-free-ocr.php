<?php
/**
 * Reprocess All Reports with Free OCR
 * This will reprocess all existing reports using the new free OCR service
 */

require __DIR__ . '/src/bootstrap.php';

use Src\Services\AI\FreeOCRService;
use Src\Services\AI\MedicalTermParser;

echo "=== REPROCESSING ALL REPORTS WITH FREE OCR ===\n\n";

try {
    $db = Src\Config\DB::conn();
    
    // Get all reports
    $stmt = $db->query("
        SELECT id, patient_id, file_path, file_url, title
        FROM reports
        WHERE file_path IS NOT NULL OR file_url IS NOT NULL
        ORDER BY created_at DESC
    ");
    
    $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($reports)) {
        echo "No reports found\n";
        exit(0);
    }
    
    echo "Found " . count($reports) . " reports to process\n\n";
    
    $ocrService = new FreeOCRService();
    $parser = new MedicalTermParser();
    
    $successCount = 0;
    $failCount = 0;
    
    foreach ($reports as $report) {
        echo "Processing Report ID {$report['id']} - {$report['title']}\n";
        
        // Get file path
        $relativePath = !empty($report['file_path']) ? $report['file_path'] : $report['file_url'];
        $relativePath = ltrim($relativePath, '/');
        $filePath = __DIR__ . '/public/' . $relativePath;
        
        if (!file_exists($filePath)) {
            echo "  ✗ File not found: $relativePath\n\n";
            $failCount++;
            continue;
        }
        
        // Extract text with OCR
        $ocrResult = $ocrService->extractText($filePath);
        
        if (!$ocrResult['success']) {
            echo "  ✗ OCR failed: {$ocrResult['error']}\n\n";
            $failCount++;
            continue;
        }
        
        echo "  ✓ OCR successful ({$ocrResult['processing_time_ms']}ms)\n";
        
        // Parse medical terms
        $values = $parser->parseText($ocrResult['text']);
        
        if (empty($values)) {
            echo "  ⚠ No lab values extracted\n\n";
            $failCount++;
            continue;
        }
        
        echo "  ✓ Extracted " . count($values) . " lab values\n";
        
        // Delete old lab values for this report
        $stmt = $db->prepare("DELETE FROM lab_values WHERE report_id = ?");
        $stmt->execute([$report['id']]);
        
        // Insert new lab values
        foreach ($values as $value) {
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
            
            echo "    - {$value['test_name']}: {$value['value']} {$value['unit']}";
            if ($value['is_abnormal']) {
                echo " [ABNORMAL]";
            }
            echo "\n";
        }
        
        // Update processing log
        $stmt = $db->prepare("DELETE FROM ocr_processing_logs WHERE report_id = ?");
        $stmt->execute([$report['id']]);
        
        $stmt = $db->prepare("
            INSERT INTO ocr_processing_logs (
                report_id, processing_status, ocr_text, processing_time_ms
            ) VALUES (?, 'completed', ?, ?)
        ");
        $stmt->execute([
            $report['id'],
            $ocrResult['text'],
            $ocrResult['processing_time_ms']
        ]);
        
        // Update report
        $stmt = $db->prepare("
            UPDATE reports
            SET ocr_processed = 1,
                auto_extracted = 1,
                extraction_confidence = ?
            WHERE id = ?
        ");
        $stmt->execute([
            $ocrResult['confidence'],
            $report['id']
        ]);
        
        echo "  ✓ Report processed successfully\n\n";
        $successCount++;
        
        // Add delay to avoid rate limiting (OCR.space free tier)
        sleep(2);
    }
    
    echo "\n=== PROCESSING COMPLETE ===\n";
    echo "Success: $successCount\n";
    echo "Failed: $failCount\n";
    echo "Total: " . count($reports) . "\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
