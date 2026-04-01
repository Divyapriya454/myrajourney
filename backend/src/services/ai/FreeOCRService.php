<?php

namespace Src\Services\AI;

use Exception;

/**
 * Free OCR Service using OCR.space Free API
 * No installation required, works out of the box
 * Free tier: 25,000 requests/month
 */
class FreeOCRService
{
    private $apiKey;
    private $apiUrl = 'https://api.ocr.space/parse/image';
    private $tempDir;
    
    public function __construct()
    {
        // OCR.space free API key (you can get your own at https://ocr.space/ocrapi)
        // This is a demo key with limited requests - replace with your own
        $this->apiKey = getenv('OCR_SPACE_API_KEY') ?: 'K87899142388957';
        
        $this->tempDir = __DIR__ . '/../../../../storage/temp/ocr/';
        
        if (!is_dir($this->tempDir)) {
            mkdir($this->tempDir, 0755, true);
        }
    }
    
    /**
     * Extract text from a file using OCR.space API
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
            $supportedFormats = ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'bmp', 'tiff'];
            if (!in_array($extension, $supportedFormats)) {
                throw new Exception("Unsupported file format: $extension");
            }
            
            // OCR.space accepts up to 5MB when we can use a public URL.
            $fileSize = filesize($filePath);
            $fileSizeMB = round($fileSize / 1024 / 1024, 2);
            error_log("File size: $fileSizeMB MB");

            if ($fileSize > 5 * 1024 * 1024) {
                throw new Exception("File size ($fileSizeMB MB) exceeds maximum limit of 5MB. Please upload a smaller file.");
            }
            
            // Call OCR.space API
            $result = $this->callOCRSpaceAPI($filePath);
            
            $processingTime = (microtime(true) - $startTime) * 1000;
            
            if ($result['success']) {
                return [
                    'success' => true,
                    'text' => $result['text'],
                    'confidence' => $result['confidence'],
                    'processing_time_ms' => round($processingTime),
                    'pages' => 1,
                    'method' => 'ocr_space_api'
                ];
            } else {
                throw new Exception($result['error']);
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
     * Call OCR.space API.
     * Uses a public URL when available, otherwise falls back to base64 for files under 1MB.
     */
    private function callOCRSpaceAPI($filePath)
    {
        try {
            $fileSize = filesize($filePath);
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'] ?? '';
            $publicDir = realpath(__DIR__ . '/../../../../public');
            $realFile = realpath($filePath);

            if ($host && $publicDir && $realFile && strpos($realFile, $publicDir) === 0) {
                $relativePath = str_replace('\\', '/', substr($realFile, strlen($publicDir)));
                $fileUrl = "$scheme://$host$relativePath";
                error_log("Trying OCR via URL: $fileUrl");
                $result = $this->sendToOCRSpace(http_build_query([
                    'apikey' => $this->apiKey,
                    'url' => $fileUrl,
                    'language' => 'eng',
                    'isOverlayRequired' => 'false',
                    'detectOrientation' => 'true',
                    'scale' => 'true',
                    'OCREngine' => '2',
                    'isTable' => 'true',
                ]));

                if ($result['success']) {
                    return $result;
                }

                error_log("URL OCR fallback failed: " . $result['error']);
            }

            if ($fileSize > 1024 * 1024) {
                return [
                    'success' => false,
                    'text' => '',
                    'confidence' => 0,
                    'error' => 'Image file is too large for OCR on this local server. Please upload an image under 1MB or let the app compress it before upload.'
                ];
            }

            $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
            $mimeType = match ($extension) {
                'pdf' => 'application/pdf',
                'png' => 'image/png',
                'gif' => 'image/gif',
                'bmp' => 'image/bmp',
                'tiff', 'tif' => 'image/tiff',
                default => 'image/jpeg',
            };

            $base64String = 'data:' . $mimeType . ';base64,' . base64_encode(file_get_contents($filePath));

            return $this->sendToOCRSpace(http_build_query([
                'apikey' => $this->apiKey,
                'base64Image' => $base64String,
                'language' => 'eng',
                'isOverlayRequired' => 'false',
                'detectOrientation' => 'true',
                'scale' => 'true',
                'OCREngine' => '2',
                'isTable' => 'true',
            ]));

        } catch (Exception $e) {
            return [
                'success' => false,
                'text' => '',
                'confidence' => 0,
                'error' => $e->getMessage()
            ];
        }
    }

    private function sendToOCRSpace(string $postData): array
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->apiUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            throw new Exception("cURL error: $curlError");
        }

        if ($httpCode !== 200) {
            throw new Exception("HTTP error: $httpCode");
        }

        $data = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("JSON parse error: " . json_last_error_msg());
        }

        error_log("OCR.space API Response: " . print_r($data, true));

        if (!isset($data['ParsedResults']) || empty($data['ParsedResults'])) {
            $errorMsg = 'Unknown error';
            if (isset($data['ErrorMessage'])) {
                $errorMsg = is_array($data['ErrorMessage']) ? implode(', ', $data['ErrorMessage']) : $data['ErrorMessage'];
            } elseif (isset($data['OCRExitCode']) && (int)$data['OCRExitCode'] !== 1) {
                $errorMsg = "OCR Exit Code: {$data['OCRExitCode']}";
            }

            return [
                'success' => false,
                'text' => '',
                'confidence' => 0,
                'error' => "OCR API error: $errorMsg"
            ];
        }

        $allText = '';
        $totalConfidence = 0;
        $pageCount = 0;

        foreach ($data['ParsedResults'] as $page) {
            if (isset($page['ParsedText'])) {
                $allText .= $page['ParsedText'] . "\n";
                $totalConfidence += $this->estimateConfidence($page['ParsedText']);
                $pageCount++;
            }
        }

        return [
            'success' => true,
            'text' => trim($allText),
            'confidence' => round($pageCount > 0 ? $totalConfidence / $pageCount : 0.5, 2),
            'pages' => $pageCount
        ];
    }
    
    /**
     * Compress image if too large
     */
    private function compressImage($filePath)
    {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        
        // Only compress images, not PDFs
        if ($extension === 'pdf') {
            error_log("Skipping compression for PDF file");
            return $filePath;
        }
        
        // Check if GD extension is available
        if (!extension_loaded('gd')) {
            error_log("GD extension not available - skipping image compression");
            return $filePath;
        }
        
        try {
            // Load image
            $image = null;
            switch ($extension) {
                case 'jpg':
                case 'jpeg':
                    if (function_exists('imagecreatefromjpeg')) {
                        $image = @imagecreatefromjpeg($filePath);
                    }
                    break;
                case 'png':
                    if (function_exists('imagecreatefrompng')) {
                        $image = @imagecreatefrompng($filePath);
                    }
                    break;
                case 'gif':
                    if (function_exists('imagecreatefromgif')) {
                        $image = @imagecreatefromgif($filePath);
                    }
                    break;
                default:
                    error_log("Unsupported image format for compression: $extension");
                    return $filePath;
            }
            
            if (!$image) {
                error_log("Failed to load image for compression");
                return $filePath;
            }
            
            // Get dimensions
            $width = imagesx($image);
            $height = imagesy($image);
            
            error_log("Original image dimensions: {$width}x{$height}");
            
            // Aggressive compression for files > 800KB
            // Reduce to 1000px width max for better OCR while staying under 1MB
            $maxWidth = 1000;
            if ($width > $maxWidth) {
                $ratio = $maxWidth / $width;
                $newWidth = $maxWidth;
                $newHeight = (int)($height * $ratio);
            } else {
                // Even if width is OK, still compress quality
                $newWidth = $width;
                $newHeight = $height;
            }
            
            error_log("Compressing to: {$newWidth}x{$newHeight}");
            
            // Create new image
            $newImage = imagecreatetruecolor($newWidth, $newHeight);
            
            // Preserve transparency for PNG
            if ($extension === 'png') {
                imagealphablending($newImage, false);
                imagesavealpha($newImage, true);
            }
            
            imagecopyresampled($newImage, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
            
            // Save compressed image with quality 80 for good OCR while reducing size
            $compressedPath = $this->tempDir . uniqid('compressed_') . '.jpg';
            imagejpeg($newImage, $compressedPath, 80);
            
            imagedestroy($image);
            imagedestroy($newImage);
            
            $compressedSize = filesize($compressedPath);
            $compressedSizeKB = round($compressedSize / 1024, 2);
            error_log("Compressed image saved: $compressedPath ($compressedSizeKB KB)");
            
            return $compressedPath;
            
        } catch (Exception $e) {
            error_log("Image compression failed: " . $e->getMessage());
            return $filePath;
        }
    }
    
    /**
     * Estimate confidence score based on text quality
     */
    private function estimateConfidence($text)
    {
        if (empty($text)) {
            return 0.0;
        }
        
        $score = 0.6; // Base score for OCR.space
        
        // Check for medical terms
        $medicalTerms = [
            'patient', 'test', 'result', 'value', 'normal', 'range', 
            'date', 'lab', 'report', 'crp', 'esr', 'rheumatoid', 
            'hemoglobin', 'platelet', 'wbc', 'blood'
        ];
        
        $foundTerms = 0;
        foreach ($medicalTerms as $term) {
            if (stripos($text, $term) !== false) {
                $foundTerms++;
            }
        }
        
        $score += min(0.2, $foundTerms * 0.02);
        
        // Check for numbers (lab reports have many numbers)
        $numberCount = preg_match_all('/\d+\.?\d*/', $text);
        if ($numberCount > 5) {
            $score += 0.1;
        }
        
        // Check for units
        if (preg_match('/mg\/[Ll]|mm\/hr|IU\/mL|g\/dL|U\/mL/', $text)) {
            $score += 0.1;
        }
        
        // Check text length (too short might be error)
        if (strlen($text) < 50) {
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
            if (is_file($file) && (time() - filemtime($file)) > 3600) {
                @unlink($file);
            }
        }
    }
}
