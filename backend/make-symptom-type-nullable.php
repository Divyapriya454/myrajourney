<?php
$pdo = new PDO('mysql:host=localhost;dbname=myrajourney', 'root', '');
$pdo->exec('ALTER TABLE symptoms MODIFY symptom_type VARCHAR(100) NULL');
$pdo->exec('ALTER TABLE symptoms MODIFY severity ENUM("MILD","MODERATE","SEVERE") NULL');
echo "✓ symptom_type and severity are now nullable\n";
