<?php
/**
 * CREATE EXERCISE SCHEDULE TABLE AND POPULATE IT
 */

require_once __DIR__ . '/src/bootstrap.php';

$db = Src\Config\DB::conn();

echo "=================================================================\n";
echo "CREATING EXERCISE SCHEDULE SYSTEM\n";
echo "=================================================================\n\n";

// Create exercise_schedule table
echo "Creating exercise_schedule table...\n";
try {
    $db->exec("
        CREATE TABLE IF NOT EXISTS exercise_schedule (
            id INT AUTO_INCREMENT PRIMARY KEY,
            exercise_id INT NOT NULL,
            rehab_plan_id INT NOT NULL,
            patient_id INT NOT NULL,
            schedule_date DATE NOT NULL,
            is_completed TINYINT(1) DEFAULT 0,
            completed_at DATETIME NULL,
            notes TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_patient_date (patient_id, schedule_date),
            INDEX idx_exercise (exercise_id),
            INDEX idx_plan (rehab_plan_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    echo "  ✅ Table created\n\n";
} catch (Exception $e) {
    echo "  Error: " . $e->getMessage() . "\n\n";
}

// Get all active rehab plans
echo "Populating schedule for active rehab plans...\n";
$stmt = $db->query("
    SELECT rp.id as plan_id, rp.patient_id, rp.start_date, rp.end_date,
           re.id as exercise_id, re.exercise_name, re.frequency_per_week
    FROM rehab_plans rp
    JOIN rehab_exercises re ON (re.plan_id = rp.id OR re.rehab_plan_id = rp.id)
    WHERE rp.status = 'ACTIVE'
");

$plans = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "Found " . count($plans) . " active exercises\n\n";

$scheduledCount = 0;

foreach ($plans as $plan) {
    $startDate = $plan['start_date'] ?: date('Y-m-d');
    $endDate = $plan['end_date'] ?: date('Y-m-d', strtotime('+30 days'));
    
    // Parse frequency
    $frequency = (int)$plan['frequency_per_week'];
    if ($frequency == 0) $frequency = 3; // Default to 3 times per week
    
    // Schedule exercises for the next 30 days
    $currentDate = new DateTime($startDate);
    $endDateTime = new DateTime($endDate);
    
    $daysScheduled = 0;
    $daysBetween = (int)ceil(7 / $frequency); // Days between each session
    
    while ($currentDate <= $endDateTime && $daysScheduled < 30) {
        $dateStr = $currentDate->format('Y-m-d');
        
        // Check if already scheduled
        $checkStmt = $db->prepare("
            SELECT id FROM exercise_schedule 
            WHERE exercise_id = ? AND schedule_date = ?
        ");
        $checkStmt->execute([$plan['exercise_id'], $dateStr]);
        
        if (!$checkStmt->fetch()) {
            // Insert schedule
            $insertStmt = $db->prepare("
                INSERT INTO exercise_schedule 
                (exercise_id, rehab_plan_id, patient_id, schedule_date)
                VALUES (?, ?, ?, ?)
            ");
            $insertStmt->execute([
                $plan['exercise_id'],
                $plan['plan_id'],
                $plan['patient_id'],
                $dateStr
            ]);
            $scheduledCount++;
        }
        
        // Move to next scheduled day
        $currentDate->modify("+{$daysBetween} days");
        $daysScheduled++;
    }
}

echo "✅ Scheduled $scheduledCount exercise sessions\n\n";

// Show today's schedule
echo "=================================================================\n";
echo "TODAY'S SCHEDULE\n";
echo "=================================================================\n\n";

$stmt = $db->query("
    SELECT es.*, re.exercise_name, u.name as patient_name
    FROM exercise_schedule es
    JOIN rehab_exercises re ON es.exercise_id = re.id
    JOIN users u ON es.patient_id = u.id
    WHERE es.schedule_date = CURDATE()
    ORDER BY u.name, re.exercise_name
");

$todayExercises = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($todayExercises) > 0) {
    echo "Exercises scheduled for today: " . count($todayExercises) . "\n\n";
    
    $currentPatient = null;
    foreach ($todayExercises as $ex) {
        if ($currentPatient != $ex['patient_name']) {
            $currentPatient = $ex['patient_name'];
            echo "\n{$currentPatient}:\n";
        }
        echo "  - {$ex['exercise_name']}\n";
    }
} else {
    echo "No exercises scheduled for today\n";
}

echo "\n=================================================================\n";
echo "COMPLETE\n";
echo "=================================================================\n";
echo "✅ Exercise schedule system created\n";
echo "✅ Exercises scheduled for next 30 days\n";
echo "✅ App will now show today's exercises\n";
echo "\n=================================================================\n";
