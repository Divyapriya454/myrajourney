<?php
// Save this as: backend/public/add_age_column.php
require __DIR__ . '/../src/bootstrap.php';
use Src\Config\DB;

try {
    $db = DB::conn();
    echo "Connected to database...<br>";

    // 1. Add 'age' column if missing
    try {
        $db->exec("ALTER TABLE patients ADD COLUMN age VARCHAR(10) NULL");
        echo "✅ Added 'age' column.<br>";
    } catch (PDOException $e) {
        echo "ℹ️ 'age' column likely exists or error: " . $e->getMessage() . "<br>";
    }

    // 2. Add 'gender' column if missing
    try {
        $db->exec("ALTER TABLE patients ADD COLUMN gender VARCHAR(20) NULL");
        echo "✅ Added 'gender' column.<br>";
    } catch (PDOException $e) {
        echo "ℹ️ 'gender' column likely exists or error: " . $e->getMessage() . "<br>";
    }

    echo "<h3>Database Patch Complete! You can now run the app.</h3>";

} catch (Exception $e) {
    echo "<h1>Error:</h1> " . $e->getMessage();
}
