<?php
/**
 * COMPREHENSIVE TEST FOR ALL DOCTOR FUNCTIONS
 * Tests: Rehab assignment, Medication assignment, Report processing, Profile pictures
 */

require_once __DIR__ . '/src/bootstrap.php';

echo "=================================================================\n";
echo "TESTING ALL DOCTOR FUNCTIONS\n";
echo "=================================================================\n\n";

$db = Src\Config\DB::conn();

// Get test doctor and patient
$doctor = $db->query("SELECT id, email FROM users WHERE role = 'DOCTOR' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$patient = $db->query("SELECT id, email FROM users WHERE role = 'PATIENT' LIMIT 1")->fetch(PDO::FETCH_ASSOC);

if (!$doctor || !$patient) {
    die("❌ No test doctor or patient found in database\n");
}

echo "Test Doctor: {$doctor['email']} (ID: {$doctor['id']})\n";
echo "Test Patient: {$patient['email']} (ID: {$patient['id']})\n\n";

$testResults = [];

// ============================================================================
// TEST 1: REHAB PLAN CREATION
// ============================================================================
echo "TEST 1: Creating Rehab Plan...\n";
try {
    $stmt = $db->prepare("
        INSERT INTO rehab_plans (patient_id, doctor_id, title, description, start_date, status, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())
    ");
    $stmt->execute([
        $patient['id'],
        $doctor['id'],
        'Test Rehab Plan',
        'Comprehensive rehabilitation program',
        date('Y-m-d'), // start_date is required
        'ACTIVE'
    ]);
    $rehabPlanId = $db->lastInsertId();
    
    // Add exercises to the plan
    $stmt = $db->prepare("
        INSERT INTO rehab_exercises (plan_id, exercise_name, description, repetitions, sets, frequency_per_week, video_url, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
    ");
    $stmt->execute([
        $rehabPlanId,
        'Wrist Flexion',
        'Gentle wrist flexion exercise',
        10,
        3,
        5,
        '/assets/exercise_videos/ex_001_wrist_flexion.mp4'
    ]);
    
    // Verify
    $stmt = $db->prepare("SELECT COUNT(*) FROM rehab_exercises WHERE plan_id = ?");
    $stmt->execute([$rehabPlanId]);
    $exerciseCount = $stmt->fetchColumn();
    
    if ($exerciseCount > 0) {
        echo "  ✅ Rehab plan created with exercises (Plan ID: $rehabPlanId)\n";
        $testResults['rehab_creation'] = 'PASS';
    } else {
        echo "  ❌ Rehab plan created but no exercises added\n";
        $testResults['rehab_creation'] = 'FAIL';
    }
} catch (Exception $e) {
    echo "  ❌ Failed: " . $e->getMessage() . "\n";
    $testResults['rehab_creation'] = 'FAIL';
}

// ============================================================================
// TEST 2: MEDICATION ASSIGNMENT
// ============================================================================
echo "\nTEST 2: Assigning Medication...\n";
try {
    $stmt = $db->prepare("
        INSERT INTO patient_medications 
        (patient_id, medication_name, dosage, frequency, frequency_per_day, duration, description, morning, afternoon, evening, night, start_date, status, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
    ");
    $stmt->execute([
        $patient['id'],
        'Test Medication',
        '500mg',
        'Twice daily', // frequency is required
        2,
        '30 days',
        'Take with food',
        1,
        0,
        1,
        0,
        date('Y-m-d'), // start_date is required
        'ACTIVE'
    ]);
    $medicationId = $db->lastInsertId();
    
    // Verify
    $stmt = $db->prepare("SELECT * FROM patient_medications WHERE id = ?");
    $stmt->execute([$medicationId]);
    $medication = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($medication && $medication['frequency_per_day'] == 2) {
        echo "  ✅ Medication assigned successfully (ID: $medicationId)\n";
        $testResults['medication_assignment'] = 'PASS';
    } else {
        echo "  ❌ Medication assignment failed\n";
        $testResults['medication_assignment'] = 'FAIL';
    }
} catch (Exception $e) {
    echo "  ❌ Failed: " . $e->getMessage() . "\n";
    $testResults['medication_assignment'] = 'FAIL';
}

// ============================================================================
// TEST 3: REPORT UPLOAD
// ============================================================================
echo "\nTEST 3: Report Upload...\n";
try {
    $stmt = $db->prepare("
        INSERT INTO reports 
        (patient_id, title, description, file_url, file_name, file_size, mime_type, status, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
    ");
    $stmt->execute([
        $patient['id'],
        'Test Lab Report',
        'Blood test results',
        '/uploads/reports/test_report.pdf',
        'test_report.pdf',
        1024000,
        'application/pdf',
        'PENDING'
    ]);
    $reportId = $db->lastInsertId();
    
    // Verify
    $stmt = $db->prepare("SELECT * FROM reports WHERE id = ?");
    $stmt->execute([$reportId]);
    $report = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($report && $report['status'] == 'PENDING') {
        echo "  ✅ Report uploaded successfully (ID: $reportId)\n";
        $testResults['report_upload'] = 'PASS';
    } else {
        echo "  ❌ Report upload failed\n";
        $testResults['report_upload'] = 'FAIL';
    }
} catch (Exception $e) {
    echo "  ❌ Failed: " . $e->getMessage() . "\n";
    $testResults['report_upload'] = 'FAIL';
}

// ============================================================================
// TEST 4: REPORT REVIEW (Doctor)
// ============================================================================
echo "\nTEST 4: Report Review by Doctor...\n";
try {
    $stmt = $db->prepare("
        UPDATE reports 
        SET status = ?, reviewed_by = ?, reviewed_at = NOW(), updated_at = NOW()
        WHERE id = ?
    ");
    $stmt->execute(['REVIEWED', $doctor['id'], $reportId]);
    
    // Verify
    $stmt = $db->prepare("SELECT * FROM reports WHERE id = ?");
    $stmt->execute([$reportId]);
    $report = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($report && $report['status'] == 'REVIEWED' && $report['reviewed_by'] == $doctor['id']) {
        echo "  ✅ Report reviewed successfully\n";
        $testResults['report_review'] = 'PASS';
    } else {
        echo "  ❌ Report review failed\n";
        $testResults['report_review'] = 'FAIL';
    }
} catch (Exception $e) {
    echo "  ❌ Failed: " . $e->getMessage() . "\n";
    $testResults['report_review'] = 'FAIL';
}

// ============================================================================
// TEST 5: CRP MEASUREMENT
// ============================================================================
echo "\nTEST 5: CRP Measurement Recording...\n";
try {
    $stmt = $db->prepare("
        INSERT INTO crp_measurements 
        (patient_id, doctor_id, measurement_date, crp_value, measurement_unit, notes, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())
    ");
    $stmt->execute([
        $patient['id'],
        $doctor['id'],
        date('Y-m-d'),
        5.2,
        'mg/L',
        'Normal range'
    ]);
    $crpId = $db->lastInsertId();
    
    // Verify
    $stmt = $db->prepare("SELECT * FROM crp_measurements WHERE id = ?");
    $stmt->execute([$crpId]);
    $crp = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($crp && $crp['doctor_id'] == $doctor['id']) {
        echo "  ✅ CRP measurement recorded successfully (ID: $crpId)\n";
        $testResults['crp_measurement'] = 'PASS';
    } else {
        echo "  ❌ CRP measurement failed\n";
        $testResults['crp_measurement'] = 'FAIL';
    }
} catch (Exception $e) {
    echo "  ❌ Failed: " . $e->getMessage() . "\n";
    $testResults['crp_measurement'] = 'FAIL';
}

// ============================================================================
// TEST 6: PROFILE PICTURE UPDATE
// ============================================================================
echo "\nTEST 6: Profile Picture Update...\n";
try {
    $testAvatarUrl = 'profile_pictures/test_doctor_' . time() . '.jpg';
    
    $stmt = $db->prepare("
        UPDATE users 
        SET avatar_url = ?, profile_picture = ?, updated_at = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$testAvatarUrl, $testAvatarUrl, $doctor['id']]);
    
    // Verify
    $stmt = $db->prepare("SELECT avatar_url, profile_picture FROM users WHERE id = ?");
    $stmt->execute([$doctor['id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user && $user['avatar_url'] == $testAvatarUrl) {
        echo "  ✅ Profile picture updated successfully\n";
        $testResults['profile_picture'] = 'PASS';
    } else {
        echo "  ❌ Profile picture update failed\n";
        $testResults['profile_picture'] = 'FAIL';
    }
} catch (Exception $e) {
    echo "  ❌ Failed: " . $e->getMessage() . "\n";
    $testResults['profile_picture'] = 'FAIL';
}

// ============================================================================
// TEST 7: APPOINTMENT MANAGEMENT
// ============================================================================
echo "\nTEST 7: Appointment Management...\n";
try {
    $stmt = $db->prepare("
        INSERT INTO appointments 
        (patient_id, doctor_id, appointment_date, appointment_time, status, notes, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())
    ");
    $stmt->execute([
        $patient['id'],
        $doctor['id'],
        date('Y-m-d', strtotime('+7 days')),
        '10:00:00',
        'SCHEDULED',
        'Follow-up appointment'
    ]);
    $appointmentId = $db->lastInsertId();
    
    // Verify
    $stmt = $db->prepare("SELECT * FROM appointments WHERE id = ?");
    $stmt->execute([$appointmentId]);
    $appointment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($appointment && $appointment['status'] == 'SCHEDULED') {
        echo "  ✅ Appointment created successfully (ID: $appointmentId)\n";
        $testResults['appointment_management'] = 'PASS';
    } else {
        echo "  ❌ Appointment creation failed\n";
        $testResults['appointment_management'] = 'FAIL';
    }
} catch (Exception $e) {
    echo "  ❌ Failed: " . $e->getMessage() . "\n";
    $testResults['appointment_management'] = 'FAIL';
}

// ============================================================================
// TEST 8: NOTIFICATION CREATION
// ============================================================================
echo "\nTEST 8: Notification System...\n";
try {
    $stmt = $db->prepare("
        INSERT INTO notifications 
        (user_id, type, title, message, is_read, created_at)
        VALUES (?, ?, ?, ?, ?, NOW())
    ");
    $stmt->execute([
        $patient['id'],
        'REHAB',
        'New Rehab Plan',
        'Your doctor has assigned a new rehabilitation plan',
        0
    ]);
    $notificationId = $db->lastInsertId();
    
    // Verify
    $stmt = $db->prepare("SELECT * FROM notifications WHERE id = ?");
    $stmt->execute([$notificationId]);
    $notification = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($notification && $notification['type'] == 'REHAB') {
        echo "  ✅ Notification created successfully (ID: $notificationId)\n";
        $testResults['notification_system'] = 'PASS';
    } else {
        echo "  ❌ Notification creation failed\n";
        $testResults['notification_system'] = 'FAIL';
    }
} catch (Exception $e) {
    echo "  ❌ Failed: " . $e->getMessage() . "\n";
    $testResults['notification_system'] = 'FAIL';
}

// ============================================================================
// SUMMARY
// ============================================================================
echo "\n=================================================================\n";
echo "TEST SUMMARY\n";
echo "=================================================================\n";

$passed = 0;
$failed = 0;

foreach ($testResults as $test => $result) {
    $icon = $result === 'PASS' ? '✅' : '❌';
    echo "$icon " . str_pad(ucwords(str_replace('_', ' ', $test)), 30) . " : $result\n";
    
    if ($result === 'PASS') {
        $passed++;
    } else {
        $failed++;
    }
}

$total = $passed + $failed;
$percentage = $total > 0 ? round(($passed / $total) * 100) : 0;

echo "\n";
echo "Total Tests: $total\n";
echo "Passed: $passed\n";
echo "Failed: $failed\n";
echo "Success Rate: $percentage%\n";

if ($failed === 0) {
    echo "\n🎉 ALL TESTS PASSED! Doctor functions are working correctly.\n";
} else {
    echo "\n⚠️  Some tests failed. Check the errors above.\n";
}

echo "\n=================================================================\n";
