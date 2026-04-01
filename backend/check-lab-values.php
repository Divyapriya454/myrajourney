<?php
/**
 * Check Lab Values in Database
 */

require __DIR__ . '/src/bootstrap.php';

echo "=== CHECKING LAB VALUES ===\n\n";

try {
    $db = Src\Config\DB::conn();
    
    // Get all lab values
    $stmt = $db->query("
        SELECT 
            lv.id, lv.report_id, lv.patient_id,
            lv.test_name, lv.test_value, lv.unit,
            lv.normal_range_min, lv.normal_range_max,
            lv.is_abnormal, lv.confidence_score,
            lv.extracted_at,
            r.title as report_title,
            u.name as patient_name
        FROM lab_values lv
        LEFT JOIN reports r ON lv.report_id = r.id
        LEFT JOIN users u ON lv.patient_id = u.id
        ORDER BY lv.report_id, lv.id
    ");
    
    $values = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($values)) {
        echo "No lab values found in database\n";
    } else {
        echo "Found " . count($values) . " lab values:\n\n";
        
        $currentReportId = null;
        foreach ($values as $val) {
            if ($val['report_id'] != $currentReportId) {
                $currentReportId = $val['report_id'];
                echo "\n--- Report ID: {$val['report_id']} ({$val['report_title']}) ---\n";
                echo "Patient: {$val['patient_name']} (ID: {$val['patient_id']})\n\n";
            }
            
            echo "  {$val['test_name']}: {$val['test_value']} {$val['unit']}";
            if ($val['is_abnormal']) {
                echo " [ABNORMAL]";
            }
            echo "\n";
            echo "    Normal Range: {$val['normal_range_min']} - {$val['normal_range_max']} {$val['unit']}\n";
            echo "    Confidence: " . ($val['confidence_score'] * 100) . "%\n";
            echo "    Extracted: {$val['extracted_at']}\n\n";
        }
    }
    
    // Check processing logs
    echo "\n=== PROCESSING LOGS ===\n\n";
    $stmt = $db->query("
        SELECT 
            id, report_id, processing_status,
            processing_time_ms, error_message,
            created_at
        FROM ocr_processing_logs
        ORDER BY created_at DESC
        LIMIT 10
    ");
    
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($logs)) {
        echo "No processing logs found\n";
    } else {
        foreach ($logs as $log) {
            echo "Report ID {$log['report_id']}: {$log['processing_status']}";
            if ($log['processing_time_ms']) {
                echo " ({$log['processing_time_ms']}ms)";
            }
            if ($log['error_message']) {
                echo " - Error: {$log['error_message']}";
            }
            echo "\n  Created: {$log['created_at']}\n\n";
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
