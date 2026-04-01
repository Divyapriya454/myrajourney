<?php

require __DIR__ . '/src/bootstrap.php';

echo "=== Testing Enhanced Chatbot System ===" . PHP_EOL . PHP_EOL;

// Test conversation session creation
try {
    $db = Src\Config\DB::conn();
    
    // Test 1: Create a conversation session
    echo "Test 1: Creating conversation session..." . PHP_EOL;
    
    $sessionId = 'test_session_' . time();
    $userId = 1; // Test user ID
    
    $stmt = $db->prepare("
        INSERT INTO conversation_sessions (session_id, user_id, status, context_data) 
        VALUES (?, ?, 'active', ?)
    ");
    
    $contextData = json_encode([
        'preferences' => ['communication_style' => 'simple'],
        'medical_history' => [],
        'current_medications' => []
    ]);
    
    $stmt->execute([$sessionId, $userId, $contextData]);
    echo "✓ Session created: $sessionId" . PHP_EOL . PHP_EOL;
    
    // Test 2: Add conversation messages
    echo "Test 2: Adding conversation messages..." . PHP_EOL;
    
    $messages = [
        ['user', 'Hello, I missed my medication today'],
        ['bot', 'I understand you missed a medication dose. You can report it to your doctor through the medications section. Would you like me to take you there?']
    ];
    
    foreach ($messages as $index => $msgData) {
        $messageId = 'msg_' . time() . '_' . $index;
        $sender = $msgData[0];
        $content = $msgData[1];
        
        $stmt = $db->prepare("
            INSERT INTO conversation_messages (message_id, session_id, sender, content, intent, confidence, timestamp) 
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $intent = $sender === 'user' ? 'medication_inquiry' : null;
        $confidence = $sender === 'user' ? 0.85 : null;
        
        $stmt->execute([$messageId, $sessionId, $sender, $content, $intent, $confidence]);
        echo "✓ Message added: $sender - " . substr($content, 0, 50) . "..." . PHP_EOL;
    }
    
    echo PHP_EOL;
    
    // Test 3: Retrieve conversation
    echo "Test 3: Retrieving conversation..." . PHP_EOL;
    
    $stmt = $db->prepare("
        SELECT cs.session_id, cs.status, cs.start_time, 
               COUNT(cm.id) as message_count,
               MAX(cm.timestamp) as last_message
        FROM conversation_sessions cs
        LEFT JOIN conversation_messages cm ON cs.session_id = cm.session_id
        WHERE cs.session_id = ?
        GROUP BY cs.session_id, cs.status, cs.start_time
    ");
    
    $stmt->execute([$sessionId]);
    $session = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($session) {
        echo "✓ Session retrieved:" . PHP_EOL;
        echo "  - Session ID: " . $session['session_id'] . PHP_EOL;
        echo "  - Status: " . $session['status'] . PHP_EOL;
        echo "  - Message Count: " . $session['message_count'] . PHP_EOL;
        echo "  - Last Message: " . $session['last_message'] . PHP_EOL;
    }
    
    echo PHP_EOL;
    
    // Test 4: Test escalation event
    echo "Test 4: Testing escalation event..." . PHP_EOL;
    
    $escalationId = 'esc_' . time();
    $stmt = $db->prepare("
        INSERT INTO escalation_events (session_id, message_id, escalation_reason, escalation_type, user_message) 
        VALUES (?, ?, 'Emergency symptoms detected', 'emergency', 'I have severe chest pain')
    ");
    
    $stmt->execute([$sessionId, 'msg_emergency_' . time()]);
    echo "✓ Escalation event logged" . PHP_EOL . PHP_EOL;
    
    // Test 5: Check database views
    echo "Test 5: Testing database views..." . PHP_EOL;
    
    $stmt = $db->prepare("SELECT * FROM active_conversations LIMIT 1");
    $stmt->execute();
    $activeConv = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($activeConv) {
        echo "✓ Active conversations view working" . PHP_EOL;
    }
    
    $stmt = $db->prepare("SELECT * FROM conversation_summary WHERE session_id = ?");
    $stmt->execute([$sessionId]);
    $summary = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($summary) {
        echo "✓ Conversation summary view working" . PHP_EOL;
        echo "  - Total messages: " . $summary['total_messages'] . PHP_EOL;
        echo "  - User messages: " . $summary['user_messages'] . PHP_EOL;
        echo "  - Bot messages: " . $summary['bot_messages'] . PHP_EOL;
    }
    
    echo PHP_EOL . "✅ All enhanced chatbot tests passed!" . PHP_EOL;
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . PHP_EOL;
    exit(1);
}
