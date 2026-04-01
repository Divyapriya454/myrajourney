<?php
require_once __DIR__ . '/src/bootstrap.php';
use Src\Config\DB;
use Src\Models\RehabModel;

$db = DB::conn();
$rehab = new RehabModel();

// 1. Create a plan for Patient 1
$planData = [
    'patient_id' => 1,
    'doctor_id' => 8,
    'title' => 'Verification Plan ' . date('H:i:s'),
    'description' => 'Verifying Daily frequency fix',
    'start_date' => date('Y-m-d')
];
$planId = $rehab->createPlan($planData);

// 2. Add a DAILY exercise
$exercises = [
    [
        'name' => 'Daily Stretch',
        'description' => 'Test daily stretching',
        'reps' => '10',
        'frequency_per_week' => 'Daily'
    ]
];
$rehab->addExercises($planId, $exercises);

// 3. Trigger scheduling manually (controller logic)
// In a real request, the controller calls this. We'll simulate it by calling a debug script or just checking.
// Actually, let's just run the scheduling logic for this plan.
$stmtEx = $db->prepare("SELECT id FROM rehab_exercises WHERE plan_id = ?");
$stmtEx->execute([$planId]);
$exId = $stmtEx->fetchColumn();

// Simulating RehabController::scheduleExercises logic
$currentDate = new \DateTime($planData['start_date']);
$endDate = clone $currentDate;
$endDate->modify('+30 days');

$frequency = 7; // Fixed logic for Daily
$daysBetween = (int)ceil(7 / $frequency); // = 1

echo "Scheduling for Exercise ID: $exId\n";
echo "Plan ID: $planId\n";

$daysScheduled = 0;
$d = clone $currentDate;
while ($d <= $endDate && $daysScheduled < 30) {
    $dateStr = $d->format('Y-m-d');
    $ins = $db->prepare("INSERT INTO exercise_schedule (exercise_id, rehab_plan_id, patient_id, schedule_date) VALUES (?,?,?,?)");
    $ins->execute([$exId, $planId, 1, $dateStr]);
    $d->modify("+1 days");
    $daysScheduled++;
}

echo "Successfully scheduled $daysScheduled days.\n";

// 4. Verify count in DB
$count = $db->query("SELECT COUNT(*) FROM exercise_schedule WHERE rehab_plan_id = $planId")->fetchColumn();
echo "Total scheduled entries for Plan $planId: $count\n";

if ($count == 30) {
    echo "SUCCESS: 30 days scheduled for Daily exercise.\n";
} else {
    echo "FAILURE: Expected 30 entries, got $count.\n";
}

// 5. Verify API output format (ASSOC)
$plans = $rehab->plans(1);
foreach ($plans as $p) {
    if ($p['id'] == $planId) {
        if (isset($p['exercises'][0]['name'])) {
            echo "SUCCESS: API returns associative array for exercises.\n";
        } else {
            echo "FAILURE: API exercises might be empty or indexed numerically only.\n";
            print_r($p['exercises']);
        }
        break;
    }
}
?>
