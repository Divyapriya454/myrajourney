<?php
$db = new PDO('mysql:host=localhost;dbname=myrajourney', 'root', '');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "Testing rehab plan creation...\n";
echo str_repeat('=', 60) . "\n";

try {
    // Insert a test rehab plan
    $stmt = $db->prepare("
        INSERT INTO rehab_plans (patient_id, title, description, created_at, updated_at)
        VALUES (1, 'Test Plan', 'Test Description', NOW(), NOW())
    ");
    $stmt->execute();
    $planId = $db->lastInsertId();
    echo "✓ Rehab plan created with ID: $planId\n";
    
    // Fetch it back
    $stmt = $db->prepare("SELECT * FROM rehab_plans WHERE id = :id");
    $stmt->execute([':id' => $planId]);
    $plan = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "✓ Plan fetched:\n";
    print_r($plan);
    
    // Clean up
    $db->exec("DELETE FROM rehab_plans WHERE id = $planId");
    echo "\n✓ Cleaned up test data\n";
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}
