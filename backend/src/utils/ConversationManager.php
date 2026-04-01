<?php
declare(strict_types=1);

namespace Src\Utils;

class ConversationManager
{
    private array $sessions = [];
    private ContextualResponseGenerator $responseGenerator;
    private IntentAnalysisEngine $intentEngine;

    public function __construct()
    {
        $this->responseGenerator = new ContextualResponseGenerator();
        $this->intentEngine = new IntentAnalysisEngine();
    }

    public function processUserMessage(int|string $userId, string $message, ?string $sessionId = null): array
    {
        try {
            // Create or get session
            if (!$sessionId) {
                $sessionId = $this->createSession($userId);
            }

            // Analyze the message
            $analysis = $this->intentEngine->analyzeIntent($message);
            
            // Generate response with context
            $context = $this->getSessionContext($sessionId);
            $response = $this->responseGenerator->generateResponse($message, $context);

            // Store the interaction
            $this->storeInteraction($sessionId, $message, $response, $analysis);

            return [
                'success' => true,
                'data' => [
                    'session_id' => $sessionId,
                    'message' => $response,
                    'intent' => $analysis['primary_intent']['intent'],
                    'confidence' => $analysis['primary_intent']['confidence'],
                    'entities' => $analysis['entities'],
                    'timestamp' => date('Y-m-d H:i:s'),
                    'source' => 'contextual'
                ]
            ];
        } catch (\Throwable $e) {
            error_log("ConversationManager error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    private function createSession(int|string $userId): string
    {
        $sessionId = uniqid('chat_' . $userId . '_', true);
        
        $this->sessions[$sessionId] = [
            'userId' => $userId,
            'startTime' => time(),
            'lastActivity' => time(),
            'messages' => [],
            'context' => [
                'reportedSymptoms' => [],
                'discussedTopics' => [],
                'userPreferences' => []
            ]
        ];

        return $sessionId;
    }

    private function getSessionContext(string $sessionId): ?array
    {
        if (!isset($this->sessions[$sessionId])) {
            return null;
        }

        return $this->sessions[$sessionId]['context'];
    }

    private function storeInteraction(string $sessionId, string $userMessage, string $botResponse, array $analysis): void
    {
        if (!isset($this->sessions[$sessionId])) {
            return;
        }

        $interaction = [
            'timestamp' => time(),
            'userMessage' => $userMessage,
            'botResponse' => $botResponse,
            'intent' => $analysis['primary_intent']['intent'],
            'entities' => $analysis['entities']
        ];

        $this->sessions[$sessionId]['messages'][] = $interaction;
        $this->sessions[$sessionId]['lastActivity'] = time();

        // Update context based on the interaction
        $this->updateSessionContext($sessionId, $analysis);
    }

    private function updateSessionContext(string $sessionId, array $analysis): void
    {
        $context = &$this->sessions[$sessionId]['context'];
        $entities = $analysis['entities'];

        // Track reported symptoms
        if (!empty($entities['symptoms'])) {
            foreach ($entities['symptoms'] as $symptom) {
                if (!in_array($symptom, $context['reportedSymptoms'])) {
                    $context['reportedSymptoms'][] = $symptom;
                }
            }
        }

        // Track discussed topics
        $intent = $analysis['primary_intent']['intent'];
        if (!in_array($intent, $context['discussedTopics'])) {
            $context['discussedTopics'][] = $intent;
        }

        // Keep only recent symptoms (last 10)
        if (count($context['reportedSymptoms']) > 10) {
            $context['reportedSymptoms'] = array_slice($context['reportedSymptoms'], -10);
        }

        // Keep only recent topics (last 5)
        if (count($context['discussedTopics']) > 5) {
            $context['discussedTopics'] = array_slice($context['discussedTopics'], -5);
        }
    }

    public function getSessionHistory(string $sessionId, int $limit = 50): array
    {
        if (!isset($this->sessions[$sessionId])) {
            return [];
        }

        $messages = $this->sessions[$sessionId]['messages'];
        
        // Return the most recent messages up to the limit
        if (count($messages) > $limit) {
            $messages = array_slice($messages, -$limit);
        }

        return $messages;
    }

    public function getConversationContext(string $sessionId): ?array
    {
        if (!isset($this->sessions[$sessionId])) {
            return null;
        }

        return [
            'session_id' => $sessionId,
            'user_id' => $this->sessions[$sessionId]['userId'],
            'start_time' => $this->sessions[$sessionId]['startTime'],
            'last_activity' => $this->sessions[$sessionId]['lastActivity'],
            'context' => $this->sessions[$sessionId]['context'],
            'message_count' => count($this->sessions[$sessionId]['messages'])
        ];
    }

    public function endSession(string $sessionId): bool
    {
        if (isset($this->sessions[$sessionId])) {
            unset($this->sessions[$sessionId]);
            return true;
        }
        return false;
    }

    public function cleanupOldSessions(int $maxAgeHours = 24): int
    {
        $cutoffTime = time() - ($maxAgeHours * 3600);
        $cleaned = 0;

        foreach ($this->sessions as $sessionId => $session) {
            if ($session['lastActivity'] < $cutoffTime) {
                unset($this->sessions[$sessionId]);
                $cleaned++;
            }
        }

        return $cleaned;
    }
}
