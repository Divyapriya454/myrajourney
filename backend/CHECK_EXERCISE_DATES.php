<?php
/**
 * CHECK EXERCISE DATES
 * Verify exercises have proper scheduling
 */

require_once __DIR__ . '/src/bootstrap.php';

$db = Src\Config\DB::conn();

echo "=================================================================\n";
echo "CHECKING EXERCISE DATES AND SCHEDULING\n";
echo "=================================================================\n\n";

// Get patient
$patient = $db->query("SELECT id, name FROM users WHERE role = 'PATIENT' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
echo "Patient: {$patient['name']} (ID: {$patient['id']})\n\n";

// Get rehab plans
$stmt = $db->prepare("SELECT * FROM rehab_plans WHERE patient_id = ? ORDER BY id DESC LIMIT 5");
$stmt->execute([$patient['id']]);
$plans = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Total Plans: " . count($plans) . "\n\n";

foreach ($plans as $plan) {
    echo "Plan ID: {$plan['id']}\n";
    echo "Title: {$plan['title']}\n";
    echo "Start Date: {$plan['start_date']}\n";
    echo "Status: {$plan['status']}\n";
    
    // Get exercises
    $stmtEx = $db->prepare("SELECT * FROM rehab_exercises WHERE plan_id = ? OR rehab_plan_id = ?");
    $stmtEx->execute([$plan['id'], $plan['id']]);
    $exercises = $stmtEx->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Exercises: " . count($exercises) . "\n";
    
    if (count($exercises) > 0) {
        foreach ($exercises as $ex) {
            echo "  - {$ex['exercise_name']}\n";
            echo "    Frequency: {$ex['frequency_per_week']} times/week\n";
            
            // Check if there's a schedule
            $stmtSched = $db->prepare("SELECT * FROM exercise_schedule WHERE exercise_id = ? AND schedule_date = CURDATE()");
            $stmtSched->execute([$ex['id']]);
            $schedule = $stmtSched->fetch(PDO::FETCH_ASSOC);
            
            if ($schedule) {
                echo "    ✅ Scheduled for today\n";
            } else {
                echo "    ❌ NOT scheduled for today\n";
            }
        }
    }
    
    echo "\n";
}

// Check if exercise_schedule table exists
echo "=================================================================\n";
echo "CHECKING EXERCISE_SCHEDULE TABLE\n";
echo "=================================================================\n\n";

try {
    $stmt = $db->query("SHOW TABLES LIKE 'exercise_schedule'");
    if ($stmt->rowCount() > 0) {
        echo "✅ exercise_schedule table exists\n";
        
        $stmt = $db->query("SELECT COUNT(*) FROM exercise_schedule WHERE schedule_date = CURDATE()");
        $todayCount = $stmt->fetchColumn();
        echo "Exercises scheduled for today: $todayCount\n";
        
        if ($todayCount == 0) {
            echo "\n⚠️  NO EXERCISES SCHEDULED FOR TODAY\n";
            echo "This is why the app shows 'No exercises scheduled for today'\n";
            echo "\nThe app needs exercises to be scheduled, not just assigned.\n";
        }
    } else {
        echo "❌ exercise_schedule table does NOT exist\n";
        echo "\nThe app expects a schedule table but it doesn't exist.\n";
        echo "Exercises are assigned but not scheduled.\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n=================================================================\n";
echo "SOLUTION\n";
echo "=================================================================\n\n";

echo "The app is looking for exercises scheduled for TODAY.\n";
echo "Rehab plans are assigned, but exercises need to be scheduled.\n\n";

echo "Options:\n";
echo "1. Create exercise_schedule table and populate it\n";
echo "2. Modify app to show all assigned exercises, not just today's\n";
echo "3. Add scheduling logic when rehab plan is created\n";

echo "\n=================================================================\n";
