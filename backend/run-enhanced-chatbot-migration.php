<?php
require_once __DIR__ . '/src/bootstrap.php';

echo "Running Enhanced Chatbot Migration...\n\n";

$db = Src\Config\DB::conn();
$sql = file_get_contents(__DIR__ . '/scripts/migrations/016_enhanced_chatbot_schema.sql');

try {
    // Execute the entire SQL file at once since it contains complex statements
    echo "Executing enhanced chatbot schema migration...\n";
    $db->exec($sql);
    echo "\n✅ Enhanced chatbot schema migration completed successfully!\n";

    // Verify tables were created
    echo "\nVerifying tables...\n";
    $tables = [
        'conversation_sessions',
        'conversation_messages', 
        'user_context_cache',
        'intent_classification_logs',
        'escalation_events',
        'user_interaction_analytics',
        'response_feedback'
    ];

    foreach ($tables as $table) {
        try {
            $stmt = $db->prepare("SHOW TABLES LIKE '$table'");
            $stmt->execute();
            if ($stmt->fetch()) {
                echo "✓ $table created successfully\n";
            } else {
                echo "✗ $table not found\n";
            }
        } catch (\Throwable $e) {
            echo "✗ Error checking $table: " . $e->getMessage() . "\n";
        }
    }

    // Check views
    echo "\nVerifying views...\n";
    $views = ['active_conversations', 'conversation_summary'];
    
    foreach ($views as $view) {
        try {
            $stmt = $db->prepare("SHOW TABLES LIKE '$view'");
            $stmt->execute();
            if ($stmt->fetch()) {
                echo "✓ $view created successfully\n";
            } else {
                echo "✗ $view not found\n";
            }
        } catch (\Throwable $e) {
            echo "✗ Error checking $view: " . $e->getMessage() . "\n";
        }
    }

} catch (\Throwable $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    echo "❌ Migration failed: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
