<?php
require_once __DIR__ . '/src/bootstrap.php';
require_once __DIR__ . '/src/config/db.php';
use Src\Config\DB;

$db = DB::conn();
$stmt = $db->query("SELECT id, name, email, role FROM users WHERE role = 'DOCTOR'");
echo "Doctors:\n";
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $u) {
    echo "{$u['id']} - {$u['name']} ({$u['email']})\n";
}
