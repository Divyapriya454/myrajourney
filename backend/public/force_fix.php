<?php
// backend/public/force_fix.php
require __DIR__ . '/../src/bootstrap.php';
use Src\Config\DB;

echo "\n--- STARTING DATABASE REPAIR ---\n";

try {
    $db = DB::conn();

    // 1. FIX PATIENTS TABLE
    echo "Checking 'patients' table... ";
    $stmt = $db->query("SHOW COLUMNS FROM patients LIKE 'assigned_doctor_id'");
    if ($stmt->rowCount() == 0) {
        $db->exec("ALTER TABLE patients ADD COLUMN assigned_doctor_id INT NULL AFTER id");
        echo "[FIXED] Added 'assigned_doctor_id'\n";
    } else {
        echo "[OK] Column exists\n";
    }

    echo "Checking 'patients' address... ";
    $stmt = $db->query("SHOW COLUMNS FROM patients LIKE 'address'");
    if ($stmt->rowCount() == 0) {
        $db->exec("ALTER TABLE patients ADD COLUMN address TEXT NULL AFTER assigned_doctor_id");
        echo "[FIXED] Added 'address'\n";
    } else {
        echo "[OK] Column exists\n";
    }

    // 2. FIX DOCTORS TABLE
    echo "Checking 'doctors' table... ";
    $stmt = $db->query("SHOW COLUMNS FROM doctors LIKE 'specialization'");
    if ($stmt->rowCount() == 0) {
        $db->exec("ALTER TABLE doctors ADD COLUMN specialization VARCHAR(100) NULL AFTER id");
        echo "[FIXED] Added 'specialization'\n";
    } else {
        echo "[OK] Column exists\n";
    }

    echo "\n--- REPAIR COMPLETE ---\n";

} catch (Exception $e) {
    echo "\n[CRITICAL ERROR] " . $e->getMessage() . "\n";
    echo "Check your .env file database credentials.\n";
}
?>
