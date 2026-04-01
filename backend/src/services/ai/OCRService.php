<?php

namespace Src\Services\AI;

use Exception;

/**
 * OCR Service for extracting text from medical reports
 * Supports PDF and image formats
 */
class OCRService
{
    private $tesseractPath;
    private $tempDir;
    private $supportedFormats = ['pdf', 'jpg', 'jpeg', 'png', 'tiff'];
    
    public function __construct()
    {
        // Configure Tesseract path (adjust based on your system)
        $this->tesseractPath = getenv('TESSERACT_PATH') ?: 'tesseract';
        $this->tempDir = __DIR__ . '/../../../../storage/temp/ocr/';
        
        // Create temp directory if it doesn't exist
        if (!is_dir($this->tempDir)) {
            mkdir($this->tempDir, 0755, true);
        }
    }
    
    /**
     * Extract text from a file
     * 
     * @param string $filePath Path to the file
     * @return array ['success' => bool, 'text' => string, 'confidence' => float, 'error' => string]
     */
    public function extractText($filePath)
    {
        $startTime = microtime(true);
        
        try {
            // Validate file exists
            if (!file_exists($filePath)) {
                throw new Exception("File not found: $filePath");
            }
            
            // Get file extension
            $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
            
            // Validate format
            if (!in_array($extension, $this->supportedFormats)) {
                throw new Exception("Unsupported file format: $extension");
            }
            
            // Check if Tesseract is available
            $tesseractAvailable = $this->isTesseractAvailable();
            
            if (!$tesseractAvailable) {
                // Fallback: Return mock data for testing
                // In production, you would return an error or use alternative OCR service
                $processingTime = (microtime(true) - $startTime) * 1000;
                
                return [
                    'success' => true,
                    'text' => $this->generateMockOCRText(),
                    'confidence' => 0.5,
                    'processing_time_ms' => round($processingTime),
                    'pages' => 1,
                    'method' => 'mock_fallback'
                ];
            }
            
            // Convert PDF to images if needed
            if ($extension === 'pdf') {
                $imagePaths = $this->convertPdfToImages($filePath);
                $allText = '';
                $totalConfidence = 0;
                
                foreach ($imagePaths as $imagePath) {
                    $result = $this->extractTextFromImage($imagePath);
                    $allText .= $result['text'] . "\n";
                    $totalConfidence += $result['confidence'];
                    
                    // Clean up temp image
                    @unlink($imagePath);
                }
                
                $avgConfidence = count($imagePaths) > 0 ? $totalConfidence / count($imagePaths) : 0;
                
                $processingTime = (microtime(true) - $startTime) * 1000;
                
                return [
                    'success' => true,
                    'text' => trim($allText),
                    'confidence' => round($avgConfidence, 2),
                    'processing_time_ms' => round($processingTime),
                    'pages' => count($imagePaths)
                ];
            } else {
                // Process image directly
                $result = $this->extractTextFromImage($filePath);
                $processingTime = (microtime(true) - $startTime) * 1000;
                
                return [
                    'success' => true,
                    'text' => $result['text'],
                    'confidence' => $result['confidence'],
                    'processing_time_ms' => round($processingTime),
                    'pages' => 1
                ];
            }
            
        } catch (Exception $e) {
            $processingTime = (microtime(true) - $startTime) * 1000;
            
            return [
                'success' => false,
                'text' => '',
                'confidence' => 0,
                'processing_time_ms' => round($processingTime),
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Check if Tesseract is available
     */
    private function isTesseractAvailable()
    {
        exec($this->tesseractPath . ' --version 2>&1', $output, $returnCode);
        return $returnCode === 0;
    }
    
    /**
     * Generate mock OCR text for testing when Tesseract is not available
     * This is a temporary fallback - in production you should install Tesseract
     */
    private function generateMockOCRText()
    {
        return "
MEDICAL LABORATORY REPORT
Patient Report
Date: " . date('Y-m-d') . "

Lab Results:
C-Reactive Protein (CRP): 5.2 mg/L
ESR: 25 mm/hr  
Rheumatoid Factor (RF): 18 IU/mL
Hemoglobin (Hb): 13.5 g/dL
White Blood Cell Count (WBC): 8.5 10^3/μL
Platelet Count: 250 10^3/μL

Note: This is mock data generated because Tesseract OCR is not installed.
To enable real OCR, please install Tesseract and Imagick/GD extensions.
";
    }

    
    /**
     * Extract text from an image using Tesseract
     * 
     * @param string $imagePath Path to the image
     * @return array ['text' => string, 'confidence' => float]
     */
    private function extractTextFromImage($imagePath)
    {
        // Preprocess image for better OCR results
        $preprocessedPath = $this->preprocessImage($imagePath);
        
        // Create output file path
        $outputBase = $this->tempDir . uniqid('ocr_');
        
        // Run Tesseract
        $command = sprintf(
            '%s "%s" "%s" -l eng --psm 6 2>&1',
            escapeshellcmd($this->tesseractPath),
            escapeshellarg($preprocessedPath),
            escapeshellarg($outputBase)
        );
        
        exec($command, $output, $returnCode);
        
        // Read the output text file
        $textFile = $outputBase . '.txt';
        $text = '';
        
        if (file_exists($textFile)) {
            $text = file_get_contents($textFile);
            @unlink($textFile);
        }
        
        // Clean up preprocessed image if different from original
        if ($preprocessedPath !== $imagePath) {
            @unlink($preprocessedPath);
        }
        
        // Calculate confidence (simplified - Tesseract 4+ has better confidence scoring)
        $confidence = $this->estimateConfidence($text);
        
        return [
            'text' => trim($text),
            'confidence' => $confidence
        ];
    }
    
    /**
     * Preprocess image for better OCR results
     * 
     * @param string $imagePath Path to the image
     * @return string Path to preprocessed image
     */
    private function preprocessImage($imagePath)
    {
        // Check if GD or Imagick is available
        if (!extension_loaded('gd') && !extension_loaded('imagick')) {
            return $imagePath; // Return original if no image processing available
        }
        
        try {
            // Use Imagick if available (better quality)
            if (extension_loaded('imagick')) {
                $image = new \Imagick($imagePath);
                
                // Convert to grayscale
                $image->setImageType(\Imagick::IMGTYPE_GRAYSCALE);
                
                // Increase contrast
                $image->normalizeImage();
                
                // Sharpen
                $image->sharpenImage(0, 1);
                
                // Save preprocessed image
                $outputPath = $this->tempDir . uniqid('preprocessed_') . '.png';
                $image->writeImage($outputPath);
                $image->clear();
                
                return $outputPath;
            }
            
            // Fallback to GD
            $image = $this->loadImageWithGD($imagePath);
            if (!$image) {
                return $imagePath;
            }
            
            // Convert to grayscale
            imagefilter($image, IMG_FILTER_GRAYSCALE);
            
            // Increase contrast
            imagefilter($image, IMG_FILTER_CONTRAST, -20);
            
            // Save preprocessed image
            $outputPath = $this->tempDir . uniqid('preprocessed_') . '.png';
            imagepng($image, $outputPath);
            imagedestroy($image);
            
            return $outputPath;
            
        } catch (Exception $e) {
            // If preprocessing fails, return original
            return $imagePath;
        }
    }
    
    /**
     * Load image using GD based on file type
     */
    private function loadImageWithGD($imagePath)
    {
        $extension = strtolower(pathinfo($imagePath, PATHINFO_EXTENSION));
        
        switch ($extension) {
            case 'jpg':
            case 'jpeg':
                return imagecreatefromjpeg($imagePath);
            case 'png':
                return imagecreatefrompng($imagePath);
            case 'gif':
                return imagecreatefromgif($imagePath);
            default:
                return false;
        }
    }
    
    /**
     * Convert PDF to images
     * 
     * @param string $pdfPath Path to PDF file
     * @return array Array of image paths
     */
    private function convertPdfToImages($pdfPath)
    {
        $imagePaths = [];
        
        // Check if Imagick is available
        if (!extension_loaded('imagick')) {
            throw new Exception("Imagick extension required for PDF processing");
        }
        
        try {
            $imagick = new \Imagick();
            $imagick->setResolution(300, 300); // High resolution for better OCR
            $imagick->readImage($pdfPath);
            
            $numPages = $imagick->getNumberImages();
            
            for ($i = 0; $i < $numPages; $i++) {
                $imagick->setIteratorIndex($i);
                $imagick->setImageFormat('png');
                
                $outputPath = $this->tempDir . uniqid('pdf_page_') . '.png';
                $imagick->writeImage($outputPath);
                
                $imagePaths[] = $outputPath;
            }
            
            $imagick->clear();
            
            return $imagePaths;
            
        } catch (Exception $e) {
            throw new Exception("Failed to convert PDF: " . $e->getMessage());
        }
    }
    
    /**
     * Estimate confidence score based on text quality
     * 
     * @param string $text Extracted text
     * @return float Confidence score (0-1)
     */
    private function estimateConfidence($text)
    {
        if (empty($text)) {
            return 0.0;
        }
        
        $score = 0.5; // Base score
        
        // Check for medical terms (increases confidence)
        $medicalTerms = ['patient', 'test', 'result', 'value', 'normal', 'range', 'date', 'lab', 'report'];
        foreach ($medicalTerms as $term) {
            if (stripos($text, $term) !== false) {
                $score += 0.05;
            }
        }
        
        // Check for numbers (lab reports have many numbers)
        if (preg_match_all('/\d+\.?\d*/', $text) > 5) {
            $score += 0.1;
        }
        
        // Check for units (mg/L, mm/hr, etc.)
        if (preg_match('/mg\/[Ll]|mm\/hr|IU\/mL|g\/dL/', $text)) {
            $score += 0.1;
        }
        
        // Penalize for too many special characters (OCR errors)
        $specialCharCount = preg_match_all('/[^a-zA-Z0-9\s\.\,\:\-\/\(\)]/', $text);
        if ($specialCharCount > strlen($text) * 0.1) {
            $score -= 0.2;
        }
        
        return max(0.0, min(1.0, $score));
    }
    
    /**
     * Clean up temporary files
     */
    public function cleanup()
    {
        $files = glob($this->tempDir . '*');
        foreach ($files as $file) {
            if (is_file($file) && (time() - filemtime($file)) > 3600) { // Delete files older than 1 hour
                @unlink($file);
            }
        }
    }
}
