<?php
/**
 * Quick Database Check and Auto-Setup
 */

echo "=================================================================\n";
echo "DATABASE CHECK AND AUTO-SETUP\n";
echo "=================================================================\n\n";

// Load .env
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    $lines = explode("\n", file_get_contents($envFile));
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line) || strpos($line, '#') === 0) continue;
        $parts = explode('=', $line, 2);
        if (count($parts) === 2) {
            $_ENV[trim($parts[0])] = trim($parts[1]);
        }
    }
}

$host = $_ENV['DB_HOST'] ?? '127.0.0.1';
$dbname = $_ENV['DB_NAME'] ?? 'myrajourney';
$user = $_ENV['DB_USER'] ?? 'root';
$pass = $_ENV['DB_PASS'] ?? '';

try {
    // Connect to MySQL server
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    echo "✓ Connected to MySQL server\n\n";
    
    // Check if database exists
    $stmt = $pdo->query("SHOW DATABASES LIKE '$dbname'");
    $dbExists = $stmt->fetch();
    
    if (!$dbExists) {
        echo "Database '$dbname' does not exist.\n";
        echo "Creating database...\n";
        
        $pdo->exec("CREATE DATABASE `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        echo "✓ Database created successfully\n\n";
    } else {
        echo "✓ Database '$dbname' exists\n\n";
    }
    
    // Connect to the database
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    // Check tables
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "Tables in database: " . count($tables) . "\n";
    
    $requiredTables = [
        'users' => 'User accounts (admin, doctor, patient)',
        'patients' => 'Patient profiles',
        'doctors' => 'Doctor profiles',
        'appointments' => 'Appointment scheduling',
        'patient_medications' => 'Medication tracking',
        'rehab_plans' => 'Rehabilitation plans',
        'rehab_exercises' => 'Rehabilitation exercises',
        'reports' => 'Medical reports',
        'symptoms' => 'Symptom tracking',
        'notifications' => 'User notifications'
    ];
    
    // Optional tables (different names possible)
    $optionalTables = [
        'education_articles' => 'Educational articles',
        'education_content' => 'Educational articles (alt)',
        'conversation_sessions' => 'Chat conversations',
        'conversation_messages' => 'Chat messages',
        'chatbot_conversations' => 'Chatbot conversations'
    ];
    
    $missingTables = array_diff(array_keys($requiredTables), $tables);
    
    if (empty($missingTables)) {
        echo "✓ All required tables present\n\n";
        
        // Check sample data
        echo "Checking data...\n";
        
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
        $userCount = $stmt->fetch()['count'];
        echo "  Users: $userCount\n";
        
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM patients");
        $patientCount = $stmt->fetch()['count'];
        echo "  Patients: $patientCount\n";
        
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM doctors");
        $doctorCount = $stmt->fetch()['count'];
        echo "  Doctors: $doctorCount\n";
        
        if ($userCount === 0) {
            echo "\n⚠ No users found. Database needs to be populated.\n";
            echo "→ Run: php setup-fresh-database.php\n";
        } else {
            echo "\n✓ Database has data\n";
        }
        
    } else {
        echo "✗ Missing tables:\n";
        foreach ($missingTables as $table) {
            echo "  - $table: " . $requiredTables[$table] . "\n";
        }
        echo "\n→ Run: php setup-fresh-database.php\n";
    }
    
    echo "\n=================================================================\n";
    echo "DATABASE STATUS: ";
    
    if (empty($missingTables) && $userCount > 0) {
        echo "READY ✓\n";
    } else {
        echo "NEEDS SETUP ⚠\n";
    }
    
    echo "=================================================================\n";
    
} catch (PDOException $e) {
    echo "✗ Database Error: " . $e->getMessage() . "\n";
    echo "\nTroubleshooting:\n";
    echo "1. Make sure MySQL is running in XAMPP Control Panel\n";
    echo "2. Check .env file has correct credentials\n";
    echo "3. Default credentials: root / (no password)\n";
    exit(1);
}
