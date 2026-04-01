<?php
/**
 * TEST REHAB RESPONSE
 * Check what data is being returned to the Android app
 */

$baseUrl = 'http://localhost/myrajourney/public/index.php';

echo "=================================================================\n";
echo "TESTING REHAB RESPONSE DATA\n";
echo "=================================================================\n\n";

// Login as patient
$ch = curl_init("$baseUrl/api/v1/auth/login");
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'email' => 'deepankumar@gmail.com',
    'password' => 'Welcome@456'
]));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
$response = curl_exec($ch);
curl_close($ch);

$loginData = json_decode($response, true);
$token = $loginData['data']['token'];
$patientId = $loginData['data']['user']['id'];

echo "Patient ID: $patientId\n";
echo "Token: " . substr($token, 0, 20) . "...\n\n";

// Get rehab plans
$ch = curl_init("$baseUrl/api/v1/rehab-plans");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    "Authorization: Bearer $token"
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
echo "Response Size: " . strlen($response) . " bytes\n\n";

$data = json_decode($response, true);

if ($data['success']) {
    $plans = $data['data'];
    echo "Total Plans: " . count($plans) . "\n\n";
    
    if (count($plans) > 0) {
        echo "Latest 3 Plans:\n";
        echo "================\n";
        
        for ($i = 0; $i < min(3, count($plans)); $i++) {
            $plan = $plans[$i];
            echo "\nPlan " . ($i + 1) . ":\n";
            echo "  ID: {$plan['id']}\n";
            echo "  Title: {$plan['title']}\n";
            echo "  Doctor ID: {$plan['doctor_id']}\n";
            echo "  Created: {$plan['created_at']}\n";
            
            if (isset($plan['exercises'])) {
                echo "  Exercises: " . count($plan['exercises']) . "\n";
                if (count($plan['exercises']) > 0) {
                    echo "    - {$plan['exercises'][0]['name']}\n";
                    if (isset($plan['exercises'][0]['reps'])) {
                        echo "      Reps: {$plan['exercises'][0]['reps']}\n";
                    }
                    if (isset($plan['exercises'][0]['sets'])) {
                        echo "      Sets: {$plan['exercises'][0]['sets']}\n";
                    }
                }
            } else {
                echo "  Exercises: NOT INCLUDED\n";
            }
        }
    }
} else {
    echo "Error: " . json_encode($data['error']) . "\n";
}

echo "\n=================================================================\n";
echo "CONCLUSION\n";
echo "=================================================================\n";

if ($data['success'] && count($data['data']) > 0) {
    $hasExercises = isset($data['data'][0]['exercises']) && count($data['data'][0]['exercises']) > 0;
    
    if ($hasExercises) {
        echo "✅ Backend is returning plans with exercises correctly\n";
        echo "✅ Data structure is correct\n";
        echo "⚠️  If plans don't show in app, it's an Android UI refresh issue\n";
        echo "\nAndroid App Fix Needed:\n";
        echo "1. Check if RecyclerView adapter is being notified\n";
        echo "2. Verify data is being parsed correctly\n";
        echo "3. Check if UI is refreshing after API call\n";
        echo "4. Try pull-to-refresh or restart app\n";
    } else {
        echo "❌ Plans don't include exercises\n";
        echo "Backend issue - exercises not being attached\n";
    }
} else {
    echo "❌ No plans returned or API error\n";
}

echo "\n=================================================================\n";
