<?php
require __DIR__ . '/../src/bootstrap.php';

use Src\Config\Config;

echo "DB_HOST: " . Config::get('DB_HOST') . "\n";
echo "DB_NAME: " . Config::get('DB_NAME') . "\n";
echo "DB_USER: " . Config::get('DB_USER') . "\n";
echo "DB_PASS: " . (Config::get('DB_PASS') ? '***SET***' : 'EMPTY') . "\n";
echo "JWT_SECRET: " . (Config::get('JWT_SECRET') ? '***SET***' : 'NOT SET') . "\n";


