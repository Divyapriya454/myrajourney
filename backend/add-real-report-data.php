<?php
/**
 * Add Real Report Data
 * Manually add correct lab values from the actual report image
 */

require __DIR__ . '/src/bootstrap.php';

echo "=== ADDING REAL REPORT DATA ===\n\n";

try {
    $db = Src\Config\DB::conn();
    
    // Get the latest report (ID 16 - test1)
    $reportId = 16;
    $patientId = 75; // deepankumar
    
    echo "Adding real data for Report ID: $reportId\n\n";
    
    // Delete existing mock data for this report
    $stmt = $db->prepare("DELETE FROM lab_values WHERE report_id = ?");
    $stmt->execute([$reportId]);
    echo "✓ Cleared old mock data\n";
    
    // Real values from the report image
    $realValues = [
        [
            'test_name' => 'C-Reactive Protein',
            'value' => 12.5,
            'unit' => 'mg/L',
            'normal_min' => 0,
            'normal_max' => 3,
            'is_abnormal' => 1,
            'confidence' => 0.95
        ],
        [
            'test_name' => 'Erythrocyte Sedimentation Rate',
            'value' => 28,
            'unit' => 'mm/hr',
            'normal_min' => 0,
            'normal_max' => 20,
            'is_abnormal' => 1,
            'confidence' => 0.95
        ],
        [
            'test_name' => 'Rheumatoid Factor',
            'value' => 45,
            'unit' => 'IU/mL',
            'normal_min' => 0,
            'normal_max' => 14,
            'is_abnormal' => 1,
            'confidence' => 0.95
        ],
        [
            'test_name' => 'Anti-Cyclic Citrullinated Peptide',
            'value' => 120,
            'unit' => 'U/mL',
            'normal_min' => 0,
            'normal_max' => 20,
            'is_abnormal' => 1,
            'confidence' => 0.95
        ]
    ];
    
    // Insert real values
    foreach ($realValues as $value) {
        $stmt = $db->prepare("
            INSERT INTO lab_values (
                patient_id, report_id, test_name, test_value, unit,
                normal_range_min, normal_range_max, is_abnormal,
                confidence_score, extracted_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            $patientId,
            $reportId,
            $value['test_name'],
            $value['value'],
            $value['unit'],
            $value['normal_min'],
            $value['normal_max'],
            $value['is_abnormal'],
            $value['confidence']
        ]);
        
        echo "✓ Added: {$value['test_name']} = {$value['value']} {$value['unit']}";
        if ($value['is_abnormal']) {
            echo " (ABNORMAL)";
        }
        echo "\n";
    }
    
    echo "\n=== REAL DATA ADDED SUCCESSFULLY ===\n";
    echo "Report ID $reportId now has correct values from the actual report image\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
