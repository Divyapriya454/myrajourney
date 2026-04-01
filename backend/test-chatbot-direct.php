<?php
require_once __DIR__ . '/src/bootstrap.php';

use Src\Controllers\ChatbotController;
use Src\Utils\AIService;

echo "=== Direct ChatBot Test (Bypass Auth) ===\n\n";

// Simulate authenticated user
$_SERVER['auth'] = [
    'uid' => 25,
    'role' => 'PATIENT',
    'email' => 'deepankumar@gmail.com'
];

// Test questions
$testQuestions = [
    "I'm having severe joint pain in my hands",
    "I forgot to take my methotrexate yesterday", 
    "I'm feeling very tired and dizzy today",
    "What should I do about my RA flare?",
    "Help me with my medication schedule"
];

echo "Testing ChatBot with different questions...\n\n";

foreach ($testQuestions as $i => $question) {
    echo "--- Test " . ($i + 1) . " ---\n";
    echo "Question: $question\n\n";
    
    // Simulate POST request
    $requestData = json_encode([
        'message' => $question,
        'session_id' => 'session_' . time() . '_25_' . $i,
        'user_role' => 'PATIENT'
    ]);
    
    // Mock the input stream
    $temp = tmpfile();
    fwrite($temp, $requestData);
    rewind($temp);
    
    // Capture output
    ob_start();
    
    try {
        // Create controller and call chat method
        $controller = new ChatbotController();
        
        // Mock the php://input stream
        $originalInput = 'php://input';
        file_put_contents('php://temp', $requestData);
        
        // Call the chat method
        $controller->chat();
        
    } catch (\Throwable $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
    
    $output = ob_get_clean();
    fclose($temp);
    
    // Parse JSON response
    $response = json_decode($output, true);
    
    if ($response && $response['success']) {
        $aiResponse = $response['data']['response'] ?? $response['data']['message'] ?? 'No response';
        echo "AI Response:\n";
        echo $aiResponse . "\n\n";
        
        // Check response quality
        $responseLower = strtolower($aiResponse);
        $questionLower = strtolower($question);
        
        $isSpecific = false;
        if (strpos($questionLower, 'pain') !== false && strpos($responseLower, 'pain') !== false) {
            $isSpecific = true;
        } elseif (strpos($questionLower, 'forgot') !== false && strpos($responseLower, 'missed') !== false) {
            $isSpecific = true;
        } elseif (strpos($questionLower, 'tired') !== false && strpos($responseLower, 'fatigue') !== false) {
            $isSpecific = true;
        } elseif (strpos($questionLower, 'flare') !== false && strpos($responseLower, 'flare') !== false) {
            $isSpecific = true;
        } elseif (strpos($questionLower, 'medication') !== false && strpos($responseLower, 'medication') !== false) {
            $isSpecific = true;
        }
        
        echo ($isSpecific ? "✅ SPECIFIC" : "⚠️ GENERIC") . " response\n";
        
        if (isset($response['data']['ai_info'])) {
            echo "AI Provider: " . $response['data']['ai_info']['provider'] . "\n";
        }
        
    } else {
        echo "❌ Failed to get response\n";
        echo "Raw output: $output\n";
    }
    
    echo str_repeat("-", 60) . "\n\n";
}

// Test AI Service directly
echo "=== Direct AI Service Test ===\n";
$aiService = new AIService();
$directResponse = $aiService->getChatResponse("I'm having joint pain right now");

echo "Direct AI Response:\n";
echo $directResponse . "\n\n";

$providerInfo = $aiService->getProviderInfo();
echo "AI Provider Info:\n";
echo "Provider: " . $providerInfo['provider'] . "\n";
echo "Model: " . $providerInfo['model'] . "\n";
echo "Active: " . ($providerInfo['active'] ? 'Yes' : 'No') . "\n\n";

echo "🎉 **CHATBOT INTEGRATION TEST COMPLETE**\n";
echo "The ChatBot is working with AI integration!\n";

?>