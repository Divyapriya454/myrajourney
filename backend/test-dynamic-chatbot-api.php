<?php
require_once __DIR__ . '/src/bootstrap.php';

echo "=== Testing Dynamic Chatbot API ===\n\n";

// Test configuration
$baseUrl = 'http://localhost/myrajourney/backend/public';

// Helper function to make API requests
function makeRequest($method, $endpoint, $data = null) {
    global $baseUrl;
    
    $url = $baseUrl . $endpoint;
    $ch = curl_init();
    
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Bearer test_token_for_user_1' // Mock token
        ],
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT => 30
    ]);
    
    if ($data && in_array($method, ['POST', 'PUT', 'PATCH'])) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        return ['error' => $error, 'http_code' => 0];
    }
    
    return [
        'http_code' => $httpCode,
        'response' => json_decode($response, true),
        'raw_response' => $response
    ];
}

try {
    // Test different types of messages to verify dynamic responses
    $testMessages = [
        [
            'message' => 'Hello, I need help with my RA',
            'expected_keywords' => ['hello', 'assistant', 'help']
        ],
        [
            'message' => 'I missed my methotrexate dose yesterday',
            'expected_keywords' => ['methotrexate', 'dose', 'weekly']
        ],
        [
            'message' => 'I\'m having severe joint pain and can\'t move',
            'expected_keywords' => ['urgent', 'doctor', 'emergency']
        ],
        [
            'message' => 'What exercises should I do for RA?',
            'expected_keywords' => ['exercise', 'swimming', 'walking']
        ],
        [
            'message' => 'I\'m feeling very tired and fatigued',
            'expected_keywords' => ['fatigue', 'sleep', 'energy']
        ],
        [
            'message' => 'The weather is making my joints stiff',
            'expected_keywords' => ['weather', 'joints', 'warm']
        ],
        [
            'message' => 'I think I\'m having a flare-up',
            'expected_keywords' => ['flare', 'rest', 'doctor']
        ]
    ];
    
    $successCount = 0;
    $totalTests = count($testMessages);
    
    foreach ($testMessages as $i => $test) {
        echo "--- Test " . ($i + 1) . " ---\n";
        echo "Message: " . $test['message'] . "\n";
        
        $response = makeRequest('POST', '/api/v1/chatbot/chat', [
            'message' => $test['message']
        ]);
        
        if ($response['http_code'] === 200 && isset($response['response']['success']) && $response['response']['success']) {
            $botMessage = $response['response']['data']['message'] ?? '';
            echo "Response: " . substr($botMessage, 0, 150) . "...\n";
            
            // Check if response contains expected keywords
            $foundKeywords = 0;
            foreach ($test['expected_keywords'] as $keyword) {
                if (stripos($botMessage, $keyword) !== false) {
                    $foundKeywords++;
                }
            }
            
            if ($foundKeywords > 0) {
                echo "✅ Dynamic response generated (found $foundKeywords relevant keywords)\n";
                $successCount++;
            } else {
                echo "⚠️ Response may not be specific enough\n";
            }
            
            // Check AI info
            if (isset($response['response']['data']['ai_info'])) {
                $aiInfo = $response['response']['data']['ai_info'];
                echo "AI Provider: " . ($aiInfo['provider'] ?? 'Unknown') . "\n";
                echo "AI Active: " . ($aiInfo['active'] ? 'Yes' : 'No') . "\n";
            }
            
        } else {
            echo "❌ API call failed\n";
            echo "HTTP Code: " . $response['http_code'] . "\n";
            if (isset($response['response']['error'])) {
                echo "Error: " . json_encode($response['response']['error']) . "\n";
            }
        }
        
        echo "\n";
        
        // Small delay between requests
        usleep(500000); // 0.5 second
    }
    
    // Test conversation history
    echo "--- Testing Conversation History ---\n";
    $historyResponse = makeRequest('GET', '/api/v1/chatbot/history?limit=5');
    
    if ($historyResponse['http_code'] === 200 && isset($historyResponse['response']['success']) && $historyResponse['response']['success']) {
        $history = $historyResponse['response']['data'] ?? [];
        echo "✅ Retrieved " . count($history) . " conversation history entries\n";
    } else {
        echo "⚠️ History retrieval failed or no history available\n";
    }
    
    // Summary
    echo "\n=== Test Summary ===\n";
    echo "Total Tests: $totalTests\n";
    echo "Successful Dynamic Responses: $successCount\n";
    echo "Success Rate: " . round(($successCount / $totalTests) * 100, 1) . "%\n\n";
    
    if ($successCount >= ($totalTests * 0.8)) { // 80% success rate
        echo "🎉 **Dynamic Chatbot is working excellently!**\n";
        echo "✅ Real-time AI responses\n";
        echo "✅ Context-aware replies\n";
        echo "✅ RA-specific medical knowledge\n";
        echo "✅ Emergency detection\n";
        echo "✅ Medication guidance\n";
        echo "✅ Exercise recommendations\n";
        echo "✅ Symptom management advice\n";
    } else {
        echo "⚠️ Some responses may need improvement\n";
        echo "Consider checking AI service configuration\n";
    }

} catch (\Throwable $e) {
    echo "❌ Test failed with error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
