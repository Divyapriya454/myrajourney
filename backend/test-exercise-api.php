<?php

require_once 'src/bootstrap.php';

echo "=== Exercise API Test ===" . PHP_EOL . PHP_EOL;

// Test 1: Get all exercises
echo "1. Testing GET /api/v1/exercises" . PHP_EOL;
try {
    $exerciseModel = new Src\Models\ExerciseModel();
    $exercises = $exerciseModel->getAllExercises();
    
    echo "   ✓ Retrieved " . count($exercises) . " exercises" . PHP_EOL;
    
    if (!empty($exercises)) {
        $firstExercise = $exercises[0];
        echo "   ✓ First exercise: " . $firstExercise['name'] . PHP_EOL;
        echo "   ✓ Category: " . $firstExercise['category'] . PHP_EOL;
        echo "   ✓ Difficulty: " . $firstExercise['difficulty_level'] . PHP_EOL;
    }
    
} catch (Exception $e) {
    echo "   ✗ Error: " . $e->getMessage() . PHP_EOL;
}

echo PHP_EOL;

// Test 2: Get exercises by category
echo "2. Testing exercises by category (WRIST)" . PHP_EOL;
try {
    $exerciseModel = new Src\Models\ExerciseModel();
    $wristExercises = $exerciseModel->getExercisesByCategory('WRIST');
    
    echo "   ✓ Retrieved " . count($wristExercises) . " wrist exercises" . PHP_EOL;
    
    foreach ($wristExercises as $exercise) {
        echo "   - " . $exercise['name'] . PHP_EOL;
    }
    
} catch (Exception $e) {
    echo "   ✗ Error: " . $e->getMessage() . PHP_EOL;
}

echo PHP_EOL;

// Test 3: Get specific exercise
echo "3. Testing get exercise by ID (ex_001)" . PHP_EOL;
try {
    $exerciseModel = new Src\Models\ExerciseModel();
    $exercise = $exerciseModel->getExerciseById('ex_001');
    
    if ($exercise) {
        echo "   ✓ Exercise found: " . $exercise['name'] . PHP_EOL;
        echo "   ✓ Instructions: " . count($exercise['instructions']) . " steps" . PHP_EOL;
        echo "   ✓ RA Benefits: " . count($exercise['ra_benefits']) . " benefits" . PHP_EOL;
    } else {
        echo "   ✗ Exercise not found" . PHP_EOL;
    }
    
} catch (Exception $e) {
    echo "   ✗ Error: " . $e->getMessage() . PHP_EOL;
}

echo PHP_EOL;

// Test 4: Test assignment creation (mock)
echo "4. Testing assignment creation" . PHP_EOL;
try {
    $exerciseModel = new Src\Models\ExerciseModel();
    
    // Get a test patient and doctor (assuming they exist)
    $db = Src\Config\DB::conn();
    $stmt = $db->query("SELECT id FROM users WHERE role = 'PATIENT' LIMIT 1");
    $patient = $stmt->fetch();
    
    $stmt = $db->query("SELECT id FROM users WHERE role = 'DOCTOR' LIMIT 1");
    $doctor = $stmt->fetch();
    
    if ($patient && $doctor) {
        $assignmentData = [
            'id' => 'test_assign_' . time(),
            'doctor_id' => $doctor['id'],
            'patient_id' => $patient['id'],
            'exercise_ids' => json_encode(['ex_001', 'ex_002', 'ex_003']),
            'notes' => 'Test assignment for wrist and thumb exercises',
            'assigned_date' => date('Y-m-d H:i:s')
        ];
        
        $result = $exerciseModel->createAssignment($assignmentData);
        
        if ($result) {
            echo "   ✓ Assignment created successfully" . PHP_EOL;
            echo "   ✓ Doctor ID: " . $doctor['id'] . PHP_EOL;
            echo "   ✓ Patient ID: " . $patient['id'] . PHP_EOL;
            echo "   ✓ Exercises assigned: 3" . PHP_EOL;
        } else {
            echo "   ✗ Failed to create assignment" . PHP_EOL;
        }
    } else {
        echo "   ⚠ No test users found (need at least 1 doctor and 1 patient)" . PHP_EOL;
    }
    
} catch (Exception $e) {
    echo "   ✗ Error: " . $e->getMessage() . PHP_EOL;
}

echo PHP_EOL;

// Test 5: Show all categories
echo "5. Exercise categories summary" . PHP_EOL;
try {
    $db = Src\Config\DB::conn();
    $stmt = $db->query("
        SELECT category, 
               COUNT(*) as count,
               AVG(difficulty_level) as avg_difficulty
        FROM ra_exercises 
        GROUP BY category 
        ORDER BY category
    ");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($categories as $cat) {
        echo "   {$cat['category']}: {$cat['count']} exercises (avg difficulty: " . 
             round($cat['avg_difficulty'], 1) . ")" . PHP_EOL;
    }
    
} catch (Exception $e) {
    echo "   ✗ Error: " . $e->getMessage() . PHP_EOL;
}

echo PHP_EOL . "=== Test Complete ===" . PHP_EOL;
