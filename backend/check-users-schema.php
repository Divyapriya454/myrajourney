<?php
require_once __DIR__ . '/src/bootstrap.php';
use Src\Config\DB;

$db = DB::conn();
print_r($db->query("DESCRIBE users")->fetchAll(PDO::FETCH_ASSOC));
