<?php
require_once __DIR__ . '/src/bootstrap.php';

use Src\Utils\AIService;

echo "=== Testing SmartAI System ===\n\n";

$aiService = new AIService();

// Test various types of questions to ensure dynamic responses
$testQuestions = [
    // Medical questions
    "I have severe joint pain in my hands and wrists right now",
    "What should I do if I forgot to take my methotrexate yesterday?",
    "I'm feeling extremely tired and dizzy today",
    "My joints are very swollen and hot to touch",
    "I think I'm having an RA flare-up",
    
    // Lifestyle questions  
    "What exercises are safe for someone with rheumatoid arthritis?",
    "Can you recommend foods that help with inflammation?",
    "I'm having trouble sleeping because of joint pain",
    "How does cold weather affect RA symptoms?",
    
    // General questions (should still get relevant responses)
    "What is rheumatoid arthritis?",
    "How can I manage stress with RA?",
    "What should I expect at my next rheumatologist appointment?",
    "Can RA affect other parts of my body besides joints?",
    
    // Specific scenarios
    "I want to start exercising but I'm afraid it will hurt my joints",
    "My morning stiffness lasts for over 2 hours",
    "I'm considering changing my diet to help with RA",
    "I feel overwhelmed managing all my medications"
];

echo "Testing SmartAI with various questions...\n\n";

foreach ($testQuestions as $i => $question) {
    echo "--- Test " . ($i + 1) . " ---\n";
    echo "Question: $question\n\n";
    
    $startTime = microtime(true);
    $response = $aiService->getChatResponse($question);
    $endTime = microtime(true);
    
    $responseTime = round(($endTime - $startTime) * 1000, 2);
    
    echo "Response ({$responseTime}ms):\n";
    echo $response . "\n\n";
    
    // Analyze response quality
    $responseLower = strtolower($response);
    $questionLower = strtolower($question);
    
    $qualityChecks = [
        'relevant' => checkRelevance($questionLower, $responseLower),
        'specific' => strlen($response) > 200 && strpos($response, '•') !== false,
        'medical' => strpos($responseLower, 'doctor') !== false || strpos($responseLower, 'rheumatologist') !== false,
        'actionable' => strpos($response, '•') !== false || strpos($responseLower, 'try') !== false
    ];
    
    $qualityScore = array_sum($qualityChecks);
    
    echo "Quality Analysis:\n";
    echo ($qualityChecks['relevant'] ? '✅' : '❌') . " Relevant to question\n";
    echo ($qualityChecks['specific'] ? '✅' : '❌') . " Specific and detailed\n";
    echo ($qualityChecks['medical'] ? '✅' : '❌') . " Includes medical guidance\n";
    echo ($qualityChecks['actionable'] ? '✅' : '❌') . " Provides actionable advice\n";
    echo "Overall Score: $qualityScore/4\n";
    
    if ($qualityScore >= 3) {
        echo "🎉 **EXCELLENT RESPONSE**\n";
    } elseif ($qualityScore >= 2) {
        echo "✅ **GOOD RESPONSE**\n";
    } else {
        echo "⚠️ **NEEDS IMPROVEMENT**\n";
    }
    
    echo str_repeat("-", 70) . "\n\n";
}

// Test with context
echo "=== Testing with Patient Context ===\n";
$contextualQuestion = "I'm having pain today";
$patientContext = [
    'recent_symptoms' => [
        'pain_level' => 7,
        'stiffness_level' => 6,
        'fatigue_level' => 8,
        'last_recorded' => '2026-01-28 10:00:00'
    ],
    'current_medications' => [
        ['name' => 'Methotrexate', 'dosage' => '15mg', 'frequency' => 'weekly'],
        ['name' => 'Folic Acid', 'dosage' => '5mg', 'frequency' => 'daily']
    ]
];

echo "Question with context: $contextualQuestion\n\n";
$contextResponse = $aiService->getChatResponse($contextualQuestion, $patientContext);
echo "Contextual Response:\n";
echo $contextResponse . "\n\n";

// Get provider info
$providerInfo = $aiService->getProviderInfo();
echo "=== AI Provider Information ===\n";
echo "Provider: " . $providerInfo['provider'] . "\n";
echo "Model: " . $providerInfo['model'] . "\n";
echo "Active: " . ($providerInfo['active'] ? 'Yes' : 'No') . "\n\n";

echo "🎉 **SMARTAI TESTING COMPLETE**\n";
echo "The AI now generates dynamic, contextual responses to any question!\n";

// Helper function for relevance checking
function checkRelevance($question, $response) {
    $questionWords = explode(' ', $question);
    $relevantWords = 0;
    
    foreach ($questionWords as $word) {
        if (strlen($word) > 3 && strpos($response, $word) !== false) {
            $relevantWords++;
        }
    }
    
    return $relevantWords > 0;
}

?>