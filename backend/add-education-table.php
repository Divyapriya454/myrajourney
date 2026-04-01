<?php
require_once __DIR__ . '/src/bootstrap.php';

try {
    $db = Src\Config\DB::conn();
    
    $sql = file_get_contents(__DIR__ . '/create_education_table.sql');
    $db->exec($sql);
    
    echo "✓ Education table created successfully\n";
    
    // Verify
    $stmt = $db->query("SELECT COUNT(*) as count FROM education_articles");
    $count = $stmt->fetch()['count'];
    echo "✓ Sample articles added: $count\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
