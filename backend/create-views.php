<?php

require __DIR__ . '/src/bootstrap.php';

try {
    $db = Src\Config\DB::conn();
    
    echo "Creating database views..." . PHP_EOL;
    
    // Create active_conversations view
    $sql1 = "CREATE OR REPLACE VIEW active_conversations AS
    SELECT 
        cs.session_id,
        cs.user_id,
        u.name as user_name,
        cs.start_time,
        cs.last_activity,
        COUNT(cm.id) as message_count,
        MAX(cm.timestamp) as last_message_time
    FROM conversation_sessions cs
    LEFT JOIN users u ON cs.user_id = u.id
    LEFT JOIN conversation_messages cm ON cs.session_id = cm.session_id
    WHERE cs.status = 'active'
    GROUP BY cs.session_id, cs.user_id, u.name, cs.start_time, cs.last_activity";
    
    $db->exec($sql1);
    echo "✓ active_conversations view created" . PHP_EOL;
    
    // Create conversation_summary view
    $sql2 = "CREATE OR REPLACE VIEW conversation_summary AS
    SELECT 
        cs.session_id,
        cs.user_id,
        cs.status,
        cs.start_time,
        cs.last_activity,
        COUNT(cm.id) as total_messages,
        COUNT(CASE WHEN cm.sender = 'user' THEN 1 END) as user_messages,
        COUNT(CASE WHEN cm.sender = 'bot' THEN 1 END) as bot_messages,
        AVG(cm.confidence) as avg_confidence,
        COUNT(CASE WHEN ee.id IS NOT NULL THEN 1 END) as escalation_count
    FROM conversation_sessions cs
    LEFT JOIN conversation_messages cm ON cs.session_id = cm.session_id
    LEFT JOIN escalation_events ee ON cs.session_id = ee.session_id
    GROUP BY cs.session_id, cs.user_id, cs.status, cs.start_time, cs.last_activity";
    
    $db->exec($sql2);
    echo "✓ conversation_summary view created" . PHP_EOL;
    
    echo "✅ All views created successfully!" . PHP_EOL;
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . PHP_EOL;
}
