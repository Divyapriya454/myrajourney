<?php
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'message' => 'This is a NEW file created just now',
    'timestamp' => date('Y-m-d H:i:s')
]);
