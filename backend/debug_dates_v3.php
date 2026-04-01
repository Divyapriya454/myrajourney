<?php
$db = new PDO('mysql:host=localhost;dbname=myrajourney', 'root', '');
$stmt = $db->query('SELECT id, start_date, created_at FROM rehab_plans ORDER BY id DESC LIMIT 10');
$res = $stmt->fetchAll(PDO::FETCH_ASSOC);
file_put_content('debug_dates_v2_final.json', json_encode($res, JSON_PRETTY_PRINT));
echo "SUCCESS: Saved to debug_dates_v2_final.json\n";
?>
