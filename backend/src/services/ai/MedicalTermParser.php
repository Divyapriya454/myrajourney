<?php

namespace Src\Services\AI;

use Exception;

/**
 * Medical Term Parser for extracting lab values from OCR text
 */
class MedicalTermParser
{
    private $dictionary;
    private $unitConverter;
    
    public function __construct()
    {
        $this->loadDictionary();
        $this->unitConverter = new UnitConverter();
    }
    
    /**
     * Load medical terms dictionary
     */
    private function loadDictionary()
    {
        $dictionaryPath = __DIR__ . '/dictionaries/medical_terms.json';
        
        if (!file_exists($dictionaryPath)) {
            throw new Exception("Medical terms dictionary not found");
        }
        
        $json = file_get_contents($dictionaryPath);
        $this->dictionary = json_decode($json, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Failed to parse medical terms dictionary");
        }
    }
    
    /**
     * Parse OCR text and extract lab values
     * 
     * @param string $text OCR extracted text
     * @return array Array of extracted lab values
     */
    public function parseText($text)
    {
        $extractedValues = [];
        $normalizedText = $this->normalizeOcrText($text);
        
        // Split text into lines
        $lines = preg_split('/\r\n|\r|\n/', $normalizedText);
        $lineCount = count($lines);
        
        // Process each line
        foreach ($lines as $lineNumber => $line) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }
            
            // Try to extract values from this line
            $values = $this->extractValuesFromLine($line, $lineNumber);
            $extractedValues = array_merge($extractedValues, $values);

            // OCR often splits test names and values across adjacent lines.
            if ($lineNumber + 1 < $lineCount) {
                $nextLine = trim($lines[$lineNumber + 1]);
                if ($nextLine !== '') {
                    $combined = $line . ' ' . $nextLine;
                    $values = $this->extractValuesFromLine($combined, $lineNumber);
                    $extractedValues = array_merge($extractedValues, $values);
                }
            }
        }
        
        // Post-process and validate
        $extractedValues = $this->validateAndEnrichValues($extractedValues);
        
        return $extractedValues;
    }
    
    /**
     * Extract lab values from a single line
     * 
     * @param string $line Text line
     * @param int $lineNumber Line number for reference
     * @return array Array of extracted values
     */
    private function extractValuesFromLine($line, $lineNumber)
    {
        $values = [];
        
        // Try each test in dictionary
        foreach ($this->dictionary as $testKey => $testInfo) {
            // Check if any alias matches
            foreach ($testInfo['aliases'] as $alias) {
                if (stripos($line, $alias) !== false) {
                    // Found a potential match, try to extract value
                    $extracted = $this->extractValueAndUnit($line, $testInfo, $testKey, $alias);
                    
                    if ($extracted) {
                        $extracted['line_number'] = $lineNumber;
                        $extracted['raw_text'] = $line;
                        $values[] = $extracted;
                        break; // Move to next test
                    }
                }
            }
        }
        
        return $values;
    }

    
    /**
     * Extract value and unit from line
     * 
     * @param string $line Text line
     * @param array $testInfo Test information from dictionary
     * @param string $testKey Test key
     * @return array|null Extracted value or null
     */
    private function extractValueAndUnit($line, $testInfo, $testKey, $matchedAlias)
    {
        $aliasPosition = stripos($line, $matchedAlias);
        $lineSegment = $aliasPosition !== false ? substr($line, $aliasPosition) : $line;
        $unitPattern = $this->buildUnitPattern($testInfo['units']);

        // Common patterns for lab values
        $patterns = [
            // Pattern 1: "CRP: 5.2 mg/L"
            '/:\s*([<>]?\s*\d+(?:[.,]\d+)?)\s*(' . $unitPattern . ')\b/i',
            // Pattern 2: "CRP 5.2 mg/L"
            '/\s+([<>]?\s*\d+(?:[.,]\d+)?)\s*(' . $unitPattern . ')\b/i',
            // Pattern 3: "5.2 mg/L" (value at start)
            '/^([<>]?\s*\d+(?:[.,]\d+)?)\s*(' . $unitPattern . ')\b/i',
            // Pattern 4: "Result: 5.2 mg/L"
            '/result[:\s]+([<>]?\s*\d+(?:[.,]\d+)?)\s*(' . $unitPattern . ')\b/i',
            // Pattern 5: OCR tables like "CRP 12.5 H mg/L"
            '/(?:^|\s)([<>]?\s*\d+(?:[.,]\d+)?)\s*(?:H|L|High|Low)?\s*(' . $unitPattern . ')\b/i',
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $lineSegment, $matches, PREG_OFFSET_CAPTURE)) {
                $value = $this->parseNumericValue($matches[1][0]);
                $unit = trim($matches[2][0]);
                
                if ($value === null) {
                    continue;
                }

                $prefix = substr($lineSegment, 0, $matches[1][1]);
                if ($this->containsDifferentTestAlias($prefix, $testKey)) {
                    continue;
                }
                
                // Normalize unit
                $normalizedUnit = $this->normalizeUnit($unit, $testInfo['units']);
                
                // Convert to standard unit if needed
                $standardValue = $this->unitConverter->convert(
                    $value,
                    $normalizedUnit,
                    $testInfo['normal_range']['unit']
                );
                
                // Check if abnormal
                $isAbnormal = $this->isAbnormal(
                    $standardValue,
                    $testInfo['normal_range']['min'],
                    $testInfo['normal_range']['max']
                );
                
                // Calculate confidence
                $confidence = $this->calculateConfidence($lineSegment, $testInfo);

                return [
                    'test_key' => $testKey,
                    'test_name' => $testInfo['full_name'],
                    'value' => $standardValue,
                    'original_value' => $value,
                    'unit' => $testInfo['normal_range']['unit'],
                    'original_unit' => $unit,
                    'is_abnormal' => $isAbnormal,
                    'normal_range_min' => $testInfo['normal_range']['min'],
                    'normal_range_max' => $testInfo['normal_range']['max'],
                    'confidence' => $confidence,
                    'category' => $testInfo['category'],
                    'importance' => $testInfo['importance']
                ];
            }
        }

        // OCR can miss the unit even when the numeric result is still readable.
        $valueWithoutUnit = $this->extractValueWithoutUnit($line, $testInfo);
        if ($valueWithoutUnit !== null) {
            $standardUnit = $testInfo['normal_range']['unit'];

            return [
                'test_key' => $testKey,
                'test_name' => $testInfo['full_name'],
                'value' => $valueWithoutUnit,
                'original_value' => $valueWithoutUnit,
                'unit' => $standardUnit,
                'original_unit' => $standardUnit,
                'is_abnormal' => $this->isAbnormal(
                    $valueWithoutUnit,
                    $testInfo['normal_range']['min'],
                    $testInfo['normal_range']['max']
                ),
                'normal_range_min' => $testInfo['normal_range']['min'],
                'normal_range_max' => $testInfo['normal_range']['max'],
                'confidence' => max(0.55, $this->calculateConfidence($line, $testInfo) - 0.15),
                'category' => $testInfo['category'],
                'importance' => $testInfo['importance']
            ];
        }
        
        return null;
    }
    
    /**
     * Build regex pattern for units
     */
    private function buildUnitPattern($units)
    {
        $escapedUnits = array_map(function($unit) {
            return preg_quote($unit, '/');
        }, $units);
        
        return implode('|', $escapedUnits);
    }
    
    /**
     * Normalize unit to match dictionary
     */
    private function normalizeUnit($unit, $validUnits)
    {
        $unit = trim(str_replace([' ', '\\'], ['', '/'], $unit));
        $unit = str_ireplace(['mg/1', 'mg/l', 'mg/d1', 'iu/ml', 'u/ml', 'mm/hour'], ['mg/L', 'mg/L', 'mg/dL', 'IU/mL', 'U/mL', 'mm/hr'], $unit);
        
        foreach ($validUnits as $validUnit) {
            if (strcasecmp($unit, $validUnit) === 0) {
                return $validUnit;
            }
        }
        
        return $unit;
    }
    
    /**
     * Check if value is abnormal
     */
    private function isAbnormal($value, $min, $max)
    {
        return $value < $min || $value > $max;
    }
    
    /**
     * Calculate confidence score for extraction
     */
    private function calculateConfidence($line, $testInfo)
    {
        $confidence = 0.7; // Base confidence
        
        // Increase confidence if line contains "result" or "value"
        if (preg_match('/result|value/i', $line)) {
            $confidence += 0.1;
        }
        
        // Increase confidence if line contains date
        if (preg_match('/\d{1,2}[\/\-]\d{1,2}[\/\-]\d{2,4}/', $line)) {
            $confidence += 0.05;
        }
        
        // Increase confidence for high importance tests
        if ($testInfo['importance'] === 'high') {
            $confidence += 0.05;
        }
        
        // Decrease confidence if line is very long (might be misidentified)
        if (strlen($line) > 200) {
            $confidence -= 0.1;
        }
        
        return max(0.0, min(1.0, $confidence));
    }
    
    /**
     * Validate and enrich extracted values
     */
    private function validateAndEnrichValues($values)
    {
        $validated = [];
        $seen = [];
        
        foreach ($values as $value) {
            // Remove duplicates (keep highest confidence)
            $key = $value['test_key'];
            
            if (isset($seen[$key])) {
                if ($value['confidence'] > $seen[$key]['confidence']) {
                    // Replace with higher confidence value
                    $validated = array_filter($validated, function($v) use ($key) {
                        return $v['test_key'] !== $key;
                    });
                    $validated[] = $value;
                    $seen[$key] = $value;
                }
            } else {
                $validated[] = $value;
                $seen[$key] = $value;
            }
        }
        
        return array_values($validated);
    }

    private function containsDifferentTestAlias($text, $currentTestKey)
    {
        $text = trim($text);
        if ($text === '') {
            return false;
        }

        foreach ($this->dictionary as $testKey => $testInfo) {
            if ($testKey === $currentTestKey) {
                continue;
            }

            foreach ($testInfo['aliases'] as $alias) {
                if (stripos($text, $alias) !== false) {
                    return true;
                }
            }
        }

        return false;
    }

    private function normalizeOcrText($text)
    {
        $text = str_replace(["\t", "\xC2\xA0"], [' ', ' '], $text);
        $text = str_replace(['–', '—', '−'], '-', $text);
        $text = preg_replace('/[ ]{2,}/', ' ', $text);

        return trim($text);
    }

    private function parseNumericValue($rawValue)
    {
        $clean = trim($rawValue);
        $clean = str_replace(['<', '>', ','], ['', '', '.'], $clean);
        $clean = preg_replace('/[^0-9.]/', '', $clean);

        if ($clean === '' || !is_numeric($clean)) {
            return null;
        }

        return (float)$clean;
    }

    private function extractValueWithoutUnit($line, $testInfo)
    {
        $line = preg_replace('/\b(reference|normal)\s+range\b.*/i', '', $line);

        if (!preg_match_all('/([<>]?\s*\d+(?:[.,]\d+)?)/', $line, $matches)) {
            return null;
        }

        $candidates = [];
        foreach ($matches[1] as $rawMatch) {
            $value = $this->parseNumericValue($rawMatch);
            if ($value === null) {
                continue;
            }

            if ($value < 0) {
                continue;
            }

            $candidates[] = $value;
        }

        if (count($candidates) === 0) {
            return null;
        }

        $preferred = null;
        foreach ($candidates as $candidate) {
            if ($this->isPlausibleValue($candidate, $testInfo)) {
                $preferred = $candidate;
                break;
            }
        }

        return $preferred;
    }

    private function isPlausibleValue($value, $testInfo)
    {
        $max = $testInfo['critical_high'] ?? ($testInfo['normal_range']['max'] * 10);
        $min = min(0, $testInfo['normal_range']['min']);

        return $value >= $min && $value <= ($max * 2);
    }
    
    /**
     * Get test information by key
     */
    public function getTestInfo($testKey)
    {
        return $this->dictionary[$testKey] ?? null;
    }
    
    /**
     * Get all test keys
     */
    public function getAllTestKeys()
    {
        return array_keys($this->dictionary);
    }
}
