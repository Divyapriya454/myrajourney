<?php
require_once __DIR__ . '/src/bootstrap.php';

echo "=== Testing Conversation Management API ===\n\n";

// Test configuration
$baseUrl = 'http://localhost/myrajourney/backend/public';
$testUserId = 1; // Assuming user ID 1 exists

// Helper function to make API requests
function makeRequest($method, $endpoint, $data = null, $headers = []) {
    global $baseUrl;
    
    $url = $baseUrl . $endpoint;
    $ch = curl_init();
    
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => array_merge([
            'Content-Type: application/json',
            'Accept: application/json'
        ], $headers),
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

// Get JWT token for authentication (assuming we have a test user)
function getAuthToken() {
    global $testUserId;
    
    // Create a simple JWT token for testing
    // In real implementation, this would come from login
    $secret = 'test_secret_key_for_conversation_testing';
    $payload = [
        'uid' => $testUserId, 
        'role' => 'patient',
        'exp' => time() + 3600 // 1 hour expiry
    ];
    return Src\Utils\Jwt::encode($payload, $secret);
}

try {
    $authToken = getAuthToken();
    $authHeaders = ['Authorization: Bearer ' . $authToken];
    
    echo "1. Testing basic chat endpoint...\n";
    $chatResponse = makeRequest('POST', '/api/v1/chatbot/chat', [
        'message' => 'Hello, I have some joint pain today. Can you help me?'
    ], $authHeaders);
    
    if ($chatResponse['http_code'] === 200 && $chatResponse['response']['success']) {
        echo "✓ Chat endpoint working\n";
        echo "Response: " . substr($chatResponse['response']['data']['message'], 0, 100) . "...\n";
        
        $sessionId = $chatResponse['response']['data']['session_id'] ?? null;
        
        if ($sessionId) {
            echo "Session ID: $sessionId\n";
            
            // Test 2: Follow-up message
            echo "\n2. Testing follow-up message...\n";
            $followUpResponse = makeRequest('POST', '/api/v1/chatbot/chat', [
                'message' => 'What exercises can I do for the pain?',
                'session_id' => $sessionId
            ], $authHeaders);
            
            if ($followUpResponse['http_code'] === 200 && $followUpResponse['response']['success']) {
                echo "✓ Follow-up message working\n";
                echo "Response: " . substr($followUpResponse['response']['data']['message'], 0, 100) . "...\n";
                
                // Test 3: Get session history
                echo "\n3. Testing session history endpoint...\n";
                $historyResponse = makeRequest('GET', '/api/v1/chatbot/session/history?session_id=' . $sessionId, null, $authHeaders);
                
                if ($historyResponse['http_code'] === 200 && $historyResponse['response']['success']) {
                    echo "✓ Session history endpoint working\n";
                    $messageCount = count($historyResponse['response']['data']['messages']);
                    echo "Retrieved $messageCount messages\n";
                    
                    // Test 4: Get conversation context
                    echo "\n4. Testing conversation context endpoint...\n";
                    $contextResponse = makeRequest('GET', '/api/v1/chatbot/session/context?session_id=' . $sessionId, null, $authHeaders);
                    
                    if ($contextResponse['http_code'] === 200 && $contextResponse['response']['success']) {
                        echo "✓ Conversation context endpoint working\n";
                        $contextMessages = count($contextResponse['response']['data']['messages']);
                        echo "Context contains $contextMessages messages\n";
                        
                        // Test 5: End session
                        echo "\n5. Testing end session endpoint...\n";
                        $endResponse = makeRequest('POST', '/api/v1/chatbot/session/end', [
                            'session_id' => $sessionId
                        ], $authHeaders);
                        
                        if ($endResponse['http_code'] === 200 && $endResponse['response']['success']) {
                            echo "✓ End session endpoint working\n";
                            echo "Session ended successfully\n";
                        } else {
                            echo "✗ End session failed: " . ($endResponse['response']['error']['message'] ?? 'Unknown error') . "\n";
                        }
                        
                    } else {
                        echo "✗ Context endpoint failed: " . ($contextResponse['response']['error']['message'] ?? 'Unknown error') . "\n";
                    }
                    
                } else {
                    echo "✗ History endpoint failed: " . ($historyResponse['response']['error']['message'] ?? 'Unknown error') . "\n";
                }
                
            } else {
                echo "✗ Follow-up message failed: " . ($followUpResponse['response']['error']['message'] ?? 'Unknown error') . "\n";
            }
            
        } else {
            echo "✗ No session ID returned from chat\n";
        }
        
    } else {
        echo "✗ Chat endpoint failed\n";
        echo "HTTP Code: " . $chatResponse['http_code'] . "\n";
        echo "Response: " . ($chatResponse['raw_response'] ?? 'No response') . "\n";
    }
    
    // Test 6: Legacy history endpoint
    echo "\n6. Testing legacy history endpoint...\n";
    $legacyHistoryResponse = makeRequest('GET', '/api/v1/chatbot/history', null, $authHeaders);
    
    if ($legacyHistoryResponse['http_code'] === 200 && $legacyHistoryResponse['response']['success']) {
        echo "✓ Legacy history endpoint working\n";
        $historyCount = count($legacyHistoryResponse['response']['data']);
        echo "Retrieved $historyCount legacy history entries\n";
    } else {
        echo "✗ Legacy history failed: " . ($legacyHistoryResponse['response']['error']['message'] ?? 'Unknown error') . "\n";
    }
    
    echo "\n=== API Test Complete ===\n";

} catch (\Throwable $e) {
    echo "✗ Test failed with error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
