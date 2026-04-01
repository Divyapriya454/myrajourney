<?php
require __DIR__ . '/src/bootstrap.php';

use Src\Utils\Chatbot;

$chatbot = new Chatbot();

echo "=== Testing Chatbot ===" . PHP_EOL . PHP_EOL;

$testQueries = [
    "Hello!",
    "I have severe pain in my joints",
    "What should I eat?",
    "I'm feeling very tired",
    "How do I manage morning stiffness?",
    "Tell me about my medications",
    "What exercises can I do?",
    "I'm having a flare-up",
    "Thank you for your help"
];

foreach ($testQueries as $query) {
    echo "User: $query" . PHP_EOL;
    echo "Bot: " . $chatbot->getResponse($query) . PHP_EOL;
    echo str_repeat("-", 80) . PHP_EOL . PHP_EOL;
}

echo "=== Test Complete ===" . PHP_EOL;
