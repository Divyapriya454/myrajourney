<?php
require 'backend/src/bootstrap.php';
$db = \Src\Config\DB::conn();
$tables = $db->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
foreach($tables as $table) {
    echo "Table: $table\n";
    $cols = $db->query("DESCRIBE `$table`")->fetchAll(PDO::FETCH_ASSOC);
    foreach($cols as $col) {
        echo "  {$col['Field']} ({$col['Type']})\n";
    }
}
