<?php
require_once __DIR__ . '/src/bootstrap.php';

echo "=== Testing Real ChatGPT APIs ===\n\n";

// Test the free ChatGPT APIs directly
$freeAPIs = [
    'gpt4free' => [
        'url' => 'https://api.g4f.icu/v1/chat/completions',
        'model' => 'gpt-3.5-turbo'
    ],
    'chatgpt_demo' => [
        'url' => 'https://chatgpt-api.shn.hk/v1/',
        'model' => 'gpt-3.5-turbo'
    ],
    'free_gpt' => [
        'url' => 'https://api.freegpt.one/v1/chat/completions',
        'model' => 'gpt-3.5-turbo'
    ],
    'openai_proxy' => [
        'url' => 'https://api.openai-proxy.org/v1/chat/completions',
        'model' => 'gpt-3.5-turbo'
    ]
];

$testMessage = "What is the capital of France? Give me a short answer.";

foreach ($freeAPIs as $apiName => $config) {
    echo "--- Testing $apiName ---\n";
    echo "URL: " . $config['url'] . "\n";
    
    $data = [
        'model' => $config['model'],
        'messages' => [
            ['role' => 'user', 'content' => $testMessage]
        ],
        'max_tokens' => 100,
        'temperature' => 0.7
    ];

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $config['url'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'User-Agent: MyRA-Journey-Test/1.0'
        ],
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => false
    ]);
    
    $startTime = microtime(true);
    $response = curl_exec($ch);
    $endTime = microtime(true);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    $responseTime = round(($endTime - $startTime) * 1000, 2);
    
    echo "HTTP Code: $httpCode\n";
    echo "Response Time: {$responseTime}ms\n";
    
    if ($error) {
        echo "❌ CURL Error: $error\n";
    } elseif ($httpCode !== 200) {
        echo "❌ HTTP Error: $httpCode\n";
        echo "Response: " . substr($response, 0, 200) . "...\n";
    } else {
        $decoded = json_decode($response, true);
        if ($decoded && isset($decoded['choices'][0]['message']['content'])) {
            $aiResponse = trim($decoded['choices'][0]['message']['content']);
            echo "✅ SUCCESS!\n";
            echo "Response: $aiResponse\n";
            
            // Check if it's a real AI response (not generic)
            if (stripos($aiResponse, 'paris') !== false) {
                echo "🎉 **REAL AI RESPONSE** - API is working!\n";
            } else {
                echo "⚠️ **UNEXPECTED RESPONSE** - May not be real AI\n";
            }
        } else {
            echo "❌ Invalid JSON or no content\n";
            echo "Response: " . substr($response, 0, 200) . "...\n";
        }
    }
    
    echo str_repeat("-", 50) . "\n\n";
}

// Test with a more complex question
echo "=== Testing Complex Question ===\n";
$complexQuestion = "I have rheumatoid arthritis and I'm experiencing joint pain. What should I do right now?";

// Try the first working API
foreach ($freeAPIs as $apiName => $config) {
    echo "Testing complex question with $apiName...\n";
    
    $data = [
        'model' => $config['model'],
        'messages' => [
            [
                'role' => 'system', 
                'content' => 'You are a helpful medical assistant specializing in rheumatoid arthritis. Provide specific, actionable advice.'
            ],
            ['role' => 'user', 'content' => $complexQuestion]
        ],
        'max_tokens' => 300,
        'temperature' => 0.7
    ];

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $config['url'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'User-Agent: MyRA-Journey-Test/1.0'
        ],
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => false
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        $decoded = json_decode($response, true);
        if ($decoded && isset($decoded['choices'][0]['message']['content'])) {
            $aiResponse = trim($decoded['choices'][0]['message']['content']);
            echo "✅ Complex Response:\n";
            echo $aiResponse . "\n\n";
            break; // Stop after first successful response
        }
    }
    
    echo "❌ Failed with $apiName\n\n";
}

echo "🔍 **API Test Complete**\n";
echo "If any API showed '✅ SUCCESS!' above, the ChatGPT integration should work.\n";

?>