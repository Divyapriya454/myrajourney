<?php
require __DIR__ . '/../src/bootstrap.php';
use Src\Config\DB;

echo "Fixing Patients Table...\n";

try {
    $db = DB::conn();

    // 1. Add assigned_doctor_id
    try {
        $db->exec("ALTER TABLE patients ADD COLUMN assigned_doctor_id INT UNSIGNED NULL AFTER id");
        echo "✅ Added 'assigned_doctor_id' column.\n";
    } catch (PDOException $e) {
        echo "ℹ️ 'assigned_doctor_id' already exists or error: " . $e->getMessage() . "\n";
    }

    // 2. Add address (just in case it's missing too)
    try {
        $db->exec("ALTER TABLE patients ADD COLUMN address TEXT NULL AFTER assigned_doctor_id");
        echo "✅ Added 'address' column.\n";
    } catch (PDOException $e) {
        echo "ℹ️ 'address' already exists or error: " . $e->getMessage() . "\n";
    }

    echo "\nDatabase Fixed! Try creating a patient again.\n";

} catch (Exception $e) {
    echo "❌ Critical Error: " . $e->getMessage() . "\n";
}
