<?php
require __DIR__ . '/src/bootstrap.php';

$db = Src\Config\DB::conn();
$sql = file_get_contents(__DIR__ . '/scripts/migrations/015_chatbot_logs.sql');

try {
    $db->exec($sql);
    echo "✓ Chatbot logs table created successfully!" . PHP_EOL;
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . PHP_EOL;
}
