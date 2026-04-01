<?php
require_once __DIR__ . '/src/bootstrap.php';

use Src\Utils\ConversationManager;
use Src\Models\ConversationModel;

echo "=== Testing Conversation Management System ===\n\n";

try {
    // Test 1: Create ConversationManager
    echo "1. Creating ConversationManager...\n";
    $conversationManager = new ConversationManager();
    echo "✓ ConversationManager created successfully\n\n";

    // Test 2: Test ConversationModel
    echo "2. Testing ConversationModel...\n";
    $conversationModel = new ConversationModel();
    echo "✓ ConversationModel created successfully\n\n";

    // Test 3: Process a user message (simulating user ID 1)
    echo "3. Processing user message...\n";
    $testUserId = 1;
    $testMessage = "Hello, I'm having some joint pain today. Can you help me?";
    
    $result = $conversationManager->processUserMessage($testUserId, $testMessage);
    
    if ($result['success']) {
        echo "✓ Message processed successfully\n";
        echo "Session ID: " . $result['data']['session_id'] . "\n";
        echo "Response: " . substr($result['data']['message'], 0, 100) . "...\n";
        
        $sessionId = $result['data']['session_id'];
        
        // Test 4: Send follow-up message
        echo "\n4. Sending follow-up message...\n";
        $followUpMessage = "What exercises can I do to help with the pain?";
        
        $followUpResult = $conversationManager->processUserMessage($testUserId, $followUpMessage, $sessionId);
        
        if ($followUpResult['success']) {
            echo "✓ Follow-up message processed successfully\n";
            echo "Response: " . substr($followUpResult['data']['message'], 0, 100) . "...\n";
            
            // Test 5: Get conversation history
            echo "\n5. Getting conversation history...\n";
            $history = $conversationManager->getSessionHistory($sessionId);
            echo "✓ Retrieved " . count($history) . " messages from conversation history\n";
            
            foreach ($history as $msg) {
                echo "- " . $msg['sender'] . ": " . substr($msg['content'], 0, 50) . "...\n";
            }
            
            // Test 6: Get conversation context
            echo "\n6. Getting conversation context...\n";
            $context = $conversationManager->getConversationContext($sessionId);
            echo "✓ Retrieved conversation context\n";
            echo "- Messages in context: " . count($context['messages']) . "\n";
            echo "- User context available: " . (empty($context['user_context']) ? 'No' : 'Yes') . "\n";
            
            // Test 7: End session
            echo "\n7. Ending conversation session...\n";
            $endResult = $conversationManager->endSession($sessionId);
            echo ($endResult ? "✓" : "✗") . " Session ended\n";
            
        } else {
            echo "✗ Follow-up message failed: " . ($followUpResult['error']['message'] ?? 'Unknown error') . "\n";
        }
        
    } else {
        echo "✗ Message processing failed: " . ($result['error']['message'] ?? 'Unknown error') . "\n";
    }

    // Test 8: Database table check
    echo "\n8. Checking database tables...\n";
    $db = Src\Config\DB::conn();
    
    $tables = [
        'conversation_sessions',
        'conversation_messages',
        'user_context_cache',
        'intent_classification_logs',
        'escalation_events'
    ];
    
    foreach ($tables as $table) {
        try {
            $stmt = $db->prepare("SELECT COUNT(*) FROM $table");
            $stmt->execute();
            $count = $stmt->fetchColumn();
            echo "✓ Table '$table' exists with $count records\n";
        } catch (\Throwable $e) {
            echo "✗ Table '$table' issue: " . $e->getMessage() . "\n";
        }
    }

    echo "\n=== Conversation Management Test Complete ===\n";

} catch (\Throwable $e) {
    echo "✗ Test failed with error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
