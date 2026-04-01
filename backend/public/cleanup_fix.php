<?php
// Save as: backend/public/cleanup_fix.php
require __DIR__ . '/../src/bootstrap.php';
use Src\Config\DB;

header('Content-Type: text/html');
echo "<h1>🧹 Zombie User Cleanup Tool</h1>";

try {
    $db = DB::conn();

    // 1. Find Zombies (Users with role PATIENT but NO ID in patients table)
    $sql = "SELECT id, email FROM users
            WHERE role = 'PATIENT'
            AND id NOT IN (SELECT id FROM patients)";

    $stmt = $db->query($sql);
    $zombies = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($zombies) > 0) {
        echo "<h3>⚠️ Found " . count($zombies) . " broken user(s):</h3><ul>";
        foreach ($zombies as $z) {
            echo "<li>Deleting ID: " . $z['id'] . " - Email: " . $z['email'] . "</li>";

            // Delete the zombie
            $del = $db->prepare("DELETE FROM users WHERE id = :id");
            $del->execute([':id' => $z['id']]);
        }
        echo "</ul>";
        echo "<h3 style='color:green'>✅ Cleanup Complete! You can now register these emails again.</h3>";
    } else {
        echo "<h3 style='color:green'>✅ No broken users found. System is clean.</h3>";
    }

} catch (Exception $e) {
    echo "<h3 style='color:red'>Error: " . $e->getMessage() . "</h3>";
}
?>
