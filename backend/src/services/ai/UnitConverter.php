<?php

namespace Src\Services\AI;

/**
 * Unit Converter for medical lab values
 */
class UnitConverter
{
    private $conversionRules = [
        // CRP conversions
        'mg/L' => [
            'mg/dL' => 0.1,
            'mg/dl' => 0.1
        ],
        'mg/dL' => [
            'mg/L' => 10,
            'mg/dl' => 1
        ],
        'mg/dl' => [
            'mg/L' => 10,
            'mg/dL' => 1
        ],
        
        // ESR (no conversion needed, always mm/hr)
        'mm/hr' => [
            'mm/hour' => 1
        ],
        'mm/hour' => [
            'mm/hr' => 1
        ],
        
        // RF and Anti-CCP (IU/mL and U/mL are equivalent)
        'IU/mL' => [
            'IU/ml' => 1,
            'U/mL' => 1,
            'U/ml' => 1
        ],
        'IU/ml' => [
            'IU/mL' => 1,
            'U/mL' => 1,
            'U/ml' => 1
        ],
        'U/mL' => [
            'IU/mL' => 1,
            'IU/ml' => 1,
            'U/ml' => 1
        ],
        'U/ml' => [
            'IU/mL' => 1,
            'IU/ml' => 1,
            'U/mL' => 1
        ],
        
        // WBC and Platelet counts
        '10^3/μL' => [
            'K/μL' => 1,
            'cells/μL' => 1000
        ],
        'K/μL' => [
            '10^3/μL' => 1,
            'cells/μL' => 1000
        ],
        'cells/μL' => [
            '10^3/μL' => 0.001,
            'K/μL' => 0.001
        ],
        
        // Hemoglobin
        'g/dL' => [
            'g/dl' => 1
        ],
        'g/dl' => [
            'g/dL' => 1
        ]
    ];
    
    /**
     * Convert value from one unit to another
     * 
     * @param float $value Value to convert
     * @param string $fromUnit Source unit
     * @param string $toUnit Target unit
     * @return float Converted value
     */
    public function convert($value, $fromUnit, $toUnit)
    {
        // If units are the same, no conversion needed
        if ($fromUnit === $toUnit) {
            return $value;
        }
        
        // Check if conversion rule exists
        if (isset($this->conversionRules[$fromUnit][$toUnit])) {
            $factor = $this->conversionRules[$fromUnit][$toUnit];
            return $value * $factor;
        }
        
        // If no conversion rule found, return original value
        // (This might indicate a unit mismatch that needs attention)
        return $value;
    }
    
    /**
     * Check if conversion is possible
     * 
     * @param string $fromUnit Source unit
     * @param string $toUnit Target unit
     * @return bool True if conversion is possible
     */
    public function canConvert($fromUnit, $toUnit)
    {
        if ($fromUnit === $toUnit) {
            return true;
        }
        
        return isset($this->conversionRules[$fromUnit][$toUnit]);
    }
    
    /**
     * Get conversion factor
     * 
     * @param string $fromUnit Source unit
     * @param string $toUnit Target unit
     * @return float|null Conversion factor or null if not possible
     */
    public function getConversionFactor($fromUnit, $toUnit)
    {
        if ($fromUnit === $toUnit) {
            return 1.0;
        }
        
        return $this->conversionRules[$fromUnit][$toUnit] ?? null;
    }
    
    /**
     * Normalize unit string (handle case variations)
     * 
     * @param string $unit Unit string
     * @return string Normalized unit
     */
    public function normalizeUnit($unit)
    {
        $unit = trim($unit);
        
        // Common normalizations
        $normalizations = [
            'mg/dl' => 'mg/dL',
            'iu/ml' => 'IU/mL',
            'u/ml' => 'U/mL',
            'g/dl' => 'g/dL',
            'mm/hour' => 'mm/hr'
        ];
        
        $lowerUnit = strtolower($unit);
        
        return $normalizations[$lowerUnit] ?? $unit;
    }
}
