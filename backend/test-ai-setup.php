<?php
/**
 * Test script to verify AI setup
 * Run: php backend/test-ai-setup.php
 */

echo "=== MYRA Journey AI Setup Test ===\n\n";

// Test 1: Check PHP version
echo "1. PHP Version: " . PHP_VERSION;
if (version_compare(PHP_VERSION, '7.4.0') >= 0) {
    echo " ✅\n";
} else {
    echo " ❌ (Need PHP 7.4+)\n";
}

// Test 2: Check required extensions
echo "\n2. PHP Extensions:\n";
$required = ['pdo', 'pdo_mysql', 'json', 'mbstring'];
$optional = ['imagick', 'gd'];

foreach ($required as $ext) {
    echo "   - $ext: " . (extension_loaded($ext) ? "✅" : "❌ REQUIRED");
    echo "\n";
}

foreach ($optional as $ext) {
    echo "   - $ext: " . (extension_loaded($ext) ? "✅" : "⚠️  Optional (for PDF/image processing)");
    echo "\n";
}

// Test 3: Check Tesseract
echo "\n3. Tesseract OCR:\n";
exec('tesseract --version 2>&1', $output, $returnCode);
if ($returnCode === 0) {
    echo "   ✅ Installed: " . $output[0] . "\n";
} else {
    echo "   ❌ Not found. Install with: sudo apt-get install tesseract-ocr\n";
}

// Test 4: Check directories
echo "\n4. Storage Directories:\n";
$dirs = [
    __DIR__ . '/storage/temp/ocr',
    __DIR__ . '/storage/logs',
    __DIR__ . '/storage/reports'
];

foreach ($dirs as $dir) {
    if (is_dir($dir)) {
        echo "   ✅ " . basename(dirname($dir)) . "/" . basename($dir) . " exists\n";
        if (is_writable($dir)) {
            echo "      ✅ Writable\n";
        } else {
            echo "      ❌ Not writable. Run: chmod 755 $dir\n";
        }
    } else {
        echo "   ⚠️  " . basename(dirname($dir)) . "/" . basename($dir) . " missing. Creating...\n";
        if (mkdir($dir, 0755, true)) {
            echo "      ✅ Created\n";
        } else {
            echo "      ❌ Failed to create\n";
        }
    }
}

// Test 5: Check AI service files
echo "\n5. AI Service Files:\n";
$files = [
    __DIR__ . '/src/services/ai/OCRService.php',
    __DIR__ . '/src/services/ai/MedicalTermParser.php',
    __DIR__ . '/src/services/ai/UnitConverter.php',
    __DIR__ . '/src/services/ai/dictionaries/medical_terms.json',
    __DIR__ . '/src/controllers/AIController.php'
];

foreach ($files as $file) {
    $name = str_replace(__DIR__ . '/', '', $file);
    echo "   " . (file_exists($file) ? "✅" : "❌") . " $name\n";
}

// Test 6: Check database connection
echo "\n6. Database Connection:\n";
try {
    require_once __DIR__ . '/src/bootstrap.php';
    $db = Src\Core\Database::getInstance()->getConnection();
    echo "   ✅ Connected to database\n";
    
    // Check if AI tables exist
    $tables = ['lab_values', 'ocr_processing_logs', 'flareup_predictions'];
    foreach ($tables as $table) {
        $stmt = $db->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "   ✅ Table '$table' exists\n";
        } else {
            echo "   ❌ Table '$table' missing. Run migration: mysql < database/migrations/create_ai_tables.sql\n";
        }
    }
} catch (Exception $e) {
    echo "   ❌ Database error: " . $e->getMessage() . "\n";
}

// Test 7: Test OCR Service
echo "\n7. OCR Service Test:\n";
try {
    require_once __DIR__ . '/src/services/ai/OCRService.php';
    $ocr = new Src\Services\AI\OCRService();
    echo "   ✅ OCRService class loaded\n";
} catch (Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
}

// Test 8: Test Medical Parser
echo "\n8. Medical Parser Test:\n";
try {
    require_once __DIR__ . '/src/services/ai/MedicalTermParser.php';
    $parser = new Src\Services\AI\MedicalTermParser();
    echo "   ✅ MedicalTermParser class loaded\n";
    
    // Test parsing
    $testText = "CRP: 5.2 mg/L\nESR: 25 mm/hr";
    $values = $parser->parseText($testText);
    echo "   ✅ Parsed " . count($values) . " values from test text\n";
    
    if (count($values) > 0) {
        echo "   ✅ Sample: " . $values[0]['test_name'] . " = " . $values[0]['value'] . " " . $values[0]['unit'] . "\n";
    }
} catch (Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
}

// Summary
echo "\n=== SUMMARY ===\n";
echo "✅ = Ready\n";
echo "⚠️  = Optional/Warning\n";
echo "❌ = Action Required\n\n";

echo "Next Steps:\n";
echo "1. If any ❌ above, fix those issues first\n";
echo "2. Run database migration if tables are missing\n";
echo "3. Test API endpoint: curl -X POST http://localhost:8000/api/v1/ai/status\n";
echo "4. Start implementing Android UI\n\n";

echo "=== TEST COMPLETE ===\n";
