<?php

require __DIR__ . '/src/bootstrap.php';

echo "=== AI Chatbot Test ===" . PHP_EOL . PHP_EOL;

// Test the AI service directly
$aiService = new Src\Utils\AIService();

echo "AI Provider Info:" . PHP_EOL;
$info = $aiService->getProviderInfo();
echo "Provider: " . $info['provider'] . PHP_EOL;
echo "Active: " . ($info['active'] ? 'Yes' : 'No') . PHP_EOL;
echo "Model: " . $info['model'] . PHP_EOL . PHP_EOL;

// Test various queries
$testQueries = [
    "Hello, I'm having joint pain today",
    "What medications are good for RA?",
    "I'm feeling very tired and fatigued",
    "Can you help me with exercise recommendations?",
    "I'm having a flare-up, what should I do?"
];

foreach ($testQueries as $query) {
    echo "User: " . $query . PHP_EOL;
    
    // Simulate context data
    $context = [
        'pain_level' => rand(3, 8),
        'stiffness_level' => rand(2, 7),
        'fatigue_level' => rand(4, 9),
        'notes' => 'Test patient context'
    ];
    
    $response = $aiService->getChatResponse($query, $context);
    echo "AI Assistant: " . $response . PHP_EOL;
    echo str_repeat("-", 80) . PHP_EOL . PHP_EOL;
}

echo "✅ AI Chatbot test completed!" . PHP_EOL;
?>
