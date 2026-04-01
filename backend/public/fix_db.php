<?php
// Save this as backend/public/fix_db.php
require __DIR__ . '/../src/bootstrap.php';

use Src\Config\DB;

echo "<h1>Database Repair Tool</h1>";

try {
    $db = DB::conn();

    // 1. Fix Patients Table
    echo "<p>Checking 'patients' table...</p>";

    // Check if column exists
    $stmt = $db->prepare("SHOW COLUMNS FROM patients LIKE 'assigned_doctor_id'");
    $stmt->execute();
    if (!$stmt->fetch()) {
        echo "Adding missing column 'assigned_doctor_id' to patients table... ";
        $db->exec("ALTER TABLE patients ADD COLUMN assigned_doctor_id INT NULL AFTER id");
        echo "<b>DONE</b><br>";
    } else {
        echo "Column 'assigned_doctor_id' already exists.<br>";
    }

    // Check for address column too, just in case
    $stmt = $db->prepare("SHOW COLUMNS FROM patients LIKE 'address'");
    $stmt->execute();
    if (!$stmt->fetch()) {
        echo "Adding missing column 'address' to patients table... ";
        $db->exec("ALTER TABLE patients ADD COLUMN address TEXT NULL AFTER assigned_doctor_id");
        echo "<b>DONE</b><br>";
    } else {
        echo "Column 'address' already exists.<br>";
    }

    // 2. Fix Doctors Table (Prevent future errors)
    echo "<p>Checking 'doctors' table...</p>";
    $stmt = $db->prepare("SHOW COLUMNS FROM doctors LIKE 'specialization'");
    $stmt->execute();
    if (!$stmt->fetch()) {
        echo "Adding missing column 'specialization' to doctors table... ";
        $db->exec("ALTER TABLE doctors ADD COLUMN specialization VARCHAR(100) NULL AFTER id");
        echo "<b>DONE</b><br>";
    } else {
        echo "Column 'specialization' already exists.<br>";
    }

    echo "<h3>✅ Database Fixed Successfully!</h3>";
    echo "<p>You can now create patients in the app.</p>";

} catch (PDOException $e) {
    echo "<h3 style='color:red'>Error: " . $e->getMessage() . "</h3>";
    if (strpos($e->getMessage(), "doesn't exist") !== false) {
        echo "<p>It seems the table 'patients' or 'doctors' is missing completely. Please run the full migration scripts.</p>";
    }
}
?>
