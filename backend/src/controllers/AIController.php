<?php

namespace Src\Controllers;

use Src\Config\DB;
use Src\Services\AI\OCRService;
use Src\Services\AI\FreeOCRService;
use Src\Services\AI\MedicalTermParser;
use Src\Utils\Response;
use Exception;

/**
 * AI Controller for medical report analysis and predictions
 */
class AIController
{
    private $db;
    private $ocrService;
    private $parser;
    
    public function __construct()
    {
        $this->db = DB::conn();
        // Use Free OCR Service (no installation required)
        $this->ocrService = new FreeOCRService();
        $this->parser = new MedicalTermParser();
        $this->ensureAiTables();
    }
    
    /**
     * Process uploaded report with OCR
     * POST /api/ai/reports/process
     */
    public function processReport()
    {
        // Ensure no output before JSON
        if (ob_get_level() > 0) {
            ob_clean();
        }
        
        try {
            // Get report ID from request
            $input = file_get_contents('php://input');
            $data = json_decode($input, true);
            
            // Log for debugging
            error_log("AI Process Request - Input: " . $input);
            
            $reportId = $data['report_id'] ?? null;
            
            if (!$reportId) {
                Response::json([
                    'success' => false,
                    'error' => ['message' => 'Report ID is required']
                ], 400);
                return;
            }
            
            // Convert to integer
            $reportId = (int)$reportId;
            
            // Get report from database
            $stmt = $this->db->prepare("
                SELECT id, patient_id, file_path, file_url, created_at 
                FROM reports 
                WHERE id = ?
            ");
            $stmt->execute([$reportId]);
            $report = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            if (!$report) {
                Response::json([
                    'success' => false,
                    'error' => ['message' => 'Report not found']
                ], 404);
                return;
            }
            
            // Check if already processed with stored values.
            $stmt = $this->db->prepare("
                SELECT id, processing_status 
                FROM ocr_processing_logs 
                WHERE report_id = ? 
                ORDER BY created_at DESC 
                LIMIT 1
            ");
            $stmt->execute([$reportId]);
            $existingLog = $stmt->fetch(\PDO::FETCH_ASSOC);

            $stmt = $this->db->prepare("
                SELECT COUNT(*)
                FROM lab_values
                WHERE report_id = ?
            ");
            $stmt->execute([$reportId]);
            $existingValueCount = (int)$stmt->fetchColumn();
            
            if ($existingLog && $existingLog['processing_status'] === 'completed' && $existingValueCount > 0) {
                Response::json([
                    'success' => true,
                    'message' => 'Report already processed',
                    'processing_id' => $existingLog['id'],
                    'data' => [
                        'values_extracted' => $existingValueCount
                    ]
                ]);
                return;
            }

            if ($existingLog) {
                $stmt = $this->db->prepare("DELETE FROM ocr_processing_logs WHERE report_id = ?");
                $stmt->execute([$reportId]);
            }

            if ($existingValueCount > 0) {
                $stmt = $this->db->prepare("DELETE FROM lab_values WHERE report_id = ?");
                $stmt->execute([$reportId]);
            }
            
            // Create processing log
            $stmt = $this->db->prepare("
                INSERT INTO ocr_processing_logs (report_id, processing_status) 
                VALUES (?, 'processing')
            ");
            $stmt->execute([$reportId]);
            $processingId = $this->db->lastInsertId();
            
            // Get full file path - use file_url if file_path is empty
            $relativePath = !empty($report['file_path']) ? $report['file_path'] : $report['file_url'];
            
            // Remove leading slash if present
            $relativePath = ltrim($relativePath, '/');
            
            // Build full path - files are in public directory
            $filePath = __DIR__ . '/../../public/' . $relativePath;
            
            // Check if file exists
            if (!file_exists($filePath)) {
                Response::json([
                    'success' => false,
                    'error' => ['message' => 'Report file not found: ' . $relativePath]
                ], 404);
                return;
            }
            
            // Extract text using OCR
            $ocrResult = $this->ocrService->extractText($filePath);
            
            if (!$ocrResult['success']) {
                // Update log with error
                $stmt = $this->db->prepare("
                    UPDATE ocr_processing_logs 
                    SET processing_status = 'failed',
                        error_message = ?,
                        processing_time_ms = ?
                    WHERE id = ?
                ");
                $stmt->execute([
                    $ocrResult['error'],
                    $ocrResult['processing_time_ms'],
                    $processingId
                ]);
                
                Response::json([
                    'success' => false,
                    'error' => ['message' => 'OCR processing failed: ' . $ocrResult['error']]
                ], 500);
                return;
            }
            
            // Parse extracted text
            $extractedValues = $this->parser->parseText($ocrResult['text']);
            
            // Save extracted values to database
            $savedCount = 0;
            foreach ($extractedValues as $value) {
                $stmt = $this->db->prepare("
                    INSERT INTO lab_values (
                        patient_id, report_id, test_name, test_value, unit,
                        normal_range_min, normal_range_max, is_abnormal,
                        confidence_score, extracted_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                ");
                
                $stmt->execute([
                    $report['patient_id'],
                    $reportId,
                    $value['test_name'],
                    $value['value'],
                    $value['unit'],
                    $value['normal_range_min'],
                    $value['normal_range_max'],
                    $value['is_abnormal'] ? 1 : 0,
                    $value['confidence']
                ]);
                
                $savedCount++;
            }
            
            // Update processing log
            $stmt = $this->db->prepare("
                UPDATE ocr_processing_logs 
                SET processing_status = 'completed',
                    ocr_text = ?,
                    processing_time_ms = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $ocrResult['text'],
                $ocrResult['processing_time_ms'],
                $processingId
            ]);
            
            // Update report
            $stmt = $this->db->prepare("
                UPDATE reports 
                SET ocr_processed = 1,
                    auto_extracted = 1,
                    extraction_confidence = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $ocrResult['confidence'],
                $reportId
            ]);
            
            $message = $savedCount > 0
                ? 'Report processed successfully'
                : 'AI processing completed, but no extractable lab values were found in this report';

            Response::json([
                'success' => true,
                'message' => $message,
                'data' => [
                    'processing_id' => $processingId,
                    'values_extracted' => $savedCount,
                    'confidence' => $ocrResult['confidence'],
                    'processing_time_ms' => $ocrResult['processing_time_ms'],
                    'has_extractable_values' => $savedCount > 0
                ]
            ]);
            
        } catch (Exception $e) {
            error_log("AI Report Processing Error: " . $e->getMessage());
            
            Response::json([
                'success' => false,
                'error' => ['message' => 'Internal server error: ' . $e->getMessage()]
            ], 500);
        }
    }

    private function ensureAiTables(): void
    {
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS ocr_processing_logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                report_id INT NOT NULL,
                processing_status VARCHAR(20) NOT NULL DEFAULT 'processing',
                ocr_text LONGTEXT NULL,
                error_message TEXT NULL,
                processing_time_ms INT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_ocr_report (report_id, created_at)
            )
        ");

        $this->db->exec("
            CREATE TABLE IF NOT EXISTS lab_values (
                id INT AUTO_INCREMENT PRIMARY KEY,
                patient_id INT NULL,
                report_id INT NOT NULL,
                test_name VARCHAR(255) NOT NULL,
                test_value DECIMAL(12,4) NULL,
                unit VARCHAR(50) NULL,
                normal_range_min DECIMAL(12,4) NULL,
                normal_range_max DECIMAL(12,4) NULL,
                is_abnormal TINYINT(1) DEFAULT 0,
                confidence_score DECIMAL(5,2) NULL,
                extracted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                manually_verified TINYINT(1) DEFAULT 0,
                verified_by INT NULL,
                verified_at DATETIME NULL,
                INDEX idx_lab_report (report_id),
                INDEX idx_lab_patient (patient_id, test_name)
            )
        ");

        $this->db->exec("
            CREATE TABLE IF NOT EXISTS flareup_predictions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                patient_id INT NOT NULL,
                prediction_date DATE NOT NULL,
                flareup_probability DECIMAL(5,2) DEFAULT 0,
                predicted_severity VARCHAR(20) DEFAULT 'LOW',
                confidence_score DECIMAL(5,2) DEFAULT 0,
                risk_factors JSON NULL,
                recommendations TEXT NULL,
                model_version VARCHAR(50) DEFAULT 'local-default',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_prediction_patient (patient_id, prediction_date)
            )
        ");

        $this->db->exec("
            CREATE TABLE IF NOT EXISTS flareup_occurrences (
                id INT AUTO_INCREMENT PRIMARY KEY,
                patient_id INT NOT NULL,
                occurrence_date DATE NOT NULL,
                severity VARCHAR(20) NOT NULL,
                symptoms TEXT NULL,
                joints_affected TEXT NULL,
                pain_level INT NULL,
                reported_by VARCHAR(20) DEFAULT 'patient',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_occurrence_patient (patient_id, occurrence_date)
            )
        ");

        $this->addReportColumnIfMissing('ocr_processed', 'TINYINT(1) DEFAULT 0');
        $this->addReportColumnIfMissing('auto_extracted', 'TINYINT(1) DEFAULT 0');
        $this->addReportColumnIfMissing('extraction_confidence', 'DECIMAL(5,2) NULL');
    }

    private function addReportColumnIfMissing(string $column, string $definition): void
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*)
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'reports'
              AND COLUMN_NAME = :column
        ");
        $stmt->execute([':column' => $column]);

        if ((int)$stmt->fetchColumn() === 0) {
            $this->db->exec("ALTER TABLE reports ADD COLUMN {$column} {$definition}");
        }
    }

    
    /**
     * Get extracted data from a report
     * GET /api/ai/reports/{report_id}/extracted-data
     */
    public function getExtractedData($reportId)
    {
        try {
            // Get extracted lab values
            $stmt = $this->db->prepare("
                SELECT 
                    id, test_name, test_value as value, unit,
                    normal_range_min, normal_range_max, is_abnormal,
                    confidence_score, extracted_at, manually_verified
                FROM lab_values
                WHERE report_id = ?
                ORDER BY test_name
            ");
            $stmt->execute([$reportId]);
            $values = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            // Get processing status
            $stmt = $this->db->prepare("
                SELECT processing_status, processing_time_ms, error_message
                FROM ocr_processing_logs
                WHERE report_id = ?
                ORDER BY created_at DESC
                LIMIT 1
            ");
            $stmt->execute([$reportId]);
            $processingLog = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            Response::json([
                'success' => true,
                'data' => [
                    'values' => $values,
                    'processing_status' => $processingLog['processing_status'] ?? 'not_processed',
                    'processing_time_ms' => $processingLog['processing_time_ms'] ?? null,
                    'error_message' => $processingLog['error_message'] ?? null,
                    'summary_message' => empty($values) && (($processingLog['processing_status'] ?? '') === 'completed')
                        ? 'AI finished reading this file, but no measurable lab values such as CRP, ESR, RF, or Anti-CCP were detected. Please upload a clear medical lab report image or PDF.'
                        : null
                ]
            ]);
            
        } catch (Exception $e) {
            Response::json([
                'success' => false,
                'error' => ['message' => $e->getMessage()]
            ], 500);
        }
    }
    
    /**
     * Get trend analysis for a patient
     * GET /api/ai/patients/{patient_id}/trends
     */
    public function getTrends($patientId)
    {
        try {
            $testName = $_GET['test_name'] ?? 'C-Reactive Protein';
            $dateRange = $_GET['date_range'] ?? 90; // days
            
            $stmt = $this->db->prepare("
                SELECT 
                    lv.test_value as value,
                    lv.unit,
                    lv.is_abnormal,
                    lv.extracted_at as date,
                    r.created_at as report_date
                FROM lab_values lv
                JOIN reports r ON lv.report_id = r.id
                WHERE lv.patient_id = ?
                    AND lv.test_name = ?
                    AND lv.extracted_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                ORDER BY lv.extracted_at ASC
            ");
            $stmt->execute([$patientId, $testName, $dateRange]);
            $data = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            // Calculate trend statistics
            $values = array_column($data, 'value');
            $trend = [
                'test_name' => $testName,
                'data_points' => $data,
                'statistics' => [
                    'count' => count($values),
                    'min' => count($values) > 0 ? min($values) : null,
                    'max' => count($values) > 0 ? max($values) : null,
                    'avg' => count($values) > 0 ? array_sum($values) / count($values) : null,
                    'latest' => count($values) > 0 ? end($values) : null
                ],
                'trend_direction' => $this->calculateTrendDirection($values)
            ];
            
            Response::json([
                'success' => true,
                'data' => $trend
            ]);
            
        } catch (Exception $e) {
            Response::json([
                'success' => false,
                'error' => ['message' => $e->getMessage()]
            ], 500);
        }
    }
    
    /**
     * Verify/correct extracted value
     * POST /api/ai/lab-values/{id}/verify
     */
    public function verifyLabValue($valueId)
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            $verifiedValue = $data['verified_value'] ?? null;
            $unit = $data['unit'] ?? null;
            $doctorId = $data['doctor_id'] ?? null;
            
            if ($verifiedValue === null) {
                Response::json([
                    'success' => false,
                    'error' => ['message' => 'Verified value is required']
                ], 400);
                return;
            }
            
            $stmt = $this->db->prepare("
                UPDATE lab_values
                SET test_value = ?,
                    unit = COALESCE(?, unit),
                    manually_verified = 1,
                    verified_by = ?,
                    verified_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$verifiedValue, $unit, $doctorId, $valueId]);
            
            Response::json([
                'success' => true,
                'message' => 'Lab value verified successfully'
            ]);
            
        } catch (Exception $e) {
            Response::json([
                'success' => false,
                'error' => ['message' => $e->getMessage()]
            ], 500);
        }
    }
    
    /**
     * Get flare-up prediction for patient
     * GET /api/v1/ai/patients/{patient_id}/prediction
     */
    public function getFlareUpPrediction($patientId)
    {
        try {
            // Get latest prediction
            $stmt = $this->db->prepare("
                SELECT 
                    id, prediction_date, flareup_probability,
                    predicted_severity, confidence_score,
                    risk_factors, recommendations, model_version,
                    created_at
                FROM flareup_predictions
                WHERE patient_id = ?
                ORDER BY prediction_date DESC
                LIMIT 1
            ");
            $stmt->execute([$patientId]);
            $prediction = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            if ($prediction && isset($prediction['risk_factors'])) {
                $prediction['risk_factors'] = json_decode($prediction['risk_factors'], true);
            }
            
            Response::json([
                'success' => true,
                'data' => $prediction
            ]);
            
        } catch (Exception $e) {
            Response::json([
                'success' => false,
                'error' => ['message' => $e->getMessage()]
            ], 500);
        }
    }
    
    /**
     * Report actual flare-up occurrence
     * POST /api/v1/ai/flareup/report
     */
    public function reportFlareUp()
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            $patientId = $data['patient_id'] ?? null;
            $severity = $data['severity'] ?? null;
            $symptoms = $data['symptoms'] ?? '';
            $jointsAffected = $data['joints_affected'] ?? '';
            $painLevel = $data['pain_level'] ?? null;
            
            if (!$patientId || !$severity) {
                Response::json([
                    'success' => false,
                    'error' => ['message' => 'Patient ID and severity are required']
                ], 400);
                return;
            }
            
            $stmt = $this->db->prepare("
                INSERT INTO flareup_occurrences (
                    patient_id, occurrence_date, severity,
                    symptoms, joints_affected, pain_level,
                    reported_by
                ) VALUES (?, CURDATE(), ?, ?, ?, ?, 'patient')
            ");
            $stmt->execute([
                $patientId, $severity, $symptoms,
                $jointsAffected, $painLevel
            ]);
            
            Response::json([
                'success' => true,
                'message' => 'Flare-up reported successfully',
                'data' => ['id' => $this->db->lastInsertId()]
            ]);
            
        } catch (Exception $e) {
            Response::json([
                'success' => false,
                'error' => ['message' => $e->getMessage()]
            ], 500);
        }
    }
    
    /**
     * Get AI system status
     * GET /api/v1/ai/status
     */
    public function getSystemStatus()
    {
        try {
            // Check OCR service
            $ocrStatus = class_exists('Src\Services\AI\FreeOCRService') || class_exists('Src\Services\AI\OCRService');
            
            // Check recent processing stats
            $stmt = $this->db->query("
                SELECT 
                    COUNT(*) as total_processed,
                    SUM(CASE WHEN processing_status = 'completed' THEN 1 ELSE 0 END) as successful,
                    SUM(CASE WHEN processing_status = 'failed' THEN 1 ELSE 0 END) as failed,
                    AVG(processing_time_ms) as avg_processing_time
                FROM ocr_processing_logs
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            ");
            $stats = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            // Check prediction stats
            $stmt = $this->db->query("
                SELECT COUNT(*) as total_predictions
                FROM flareup_predictions
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            ");
            $predictionStats = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            Response::json([
                'success' => true,
                'data' => [
                    'ocr_service_available' => $ocrStatus,
                    'last_7_days' => [
                        'reports_processed' => (int)$stats['total_processed'],
                        'successful' => (int)$stats['successful'],
                        'failed' => (int)$stats['failed'],
                        'avg_processing_time_ms' => round($stats['avg_processing_time'] ?? 0),
                        'predictions_made' => (int)$predictionStats['total_predictions']
                    ],
                    'timestamp' => date('Y-m-d H:i:s')
                ]
            ]);
            
        } catch (Exception $e) {
            Response::json([
                'success' => false,
                'error' => ['message' => $e->getMessage()]
            ], 500);
        }
    }
    
    /**
     * Calculate trend direction
     */
    private function calculateTrendDirection($values)
    {
        if (count($values) < 2) {
            return 'insufficient_data';
        }
        
        // Simple linear regression
        $n = count($values);
        $sumX = 0;
        $sumY = 0;
        $sumXY = 0;
        $sumX2 = 0;
        
        for ($i = 0; $i < $n; $i++) {
            $sumX += $i;
            $sumY += $values[$i];
            $sumXY += $i * $values[$i];
            $sumX2 += $i * $i;
        }
        
        $slope = ($n * $sumXY - $sumX * $sumY) / ($n * $sumX2 - $sumX * $sumX);
        
        if ($slope > 0.1) {
            return 'increasing';
        } elseif ($slope < -0.1) {
            return 'decreasing';
        } else {
            return 'stable';
        }
    }
}
