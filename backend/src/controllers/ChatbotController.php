<?php
declare(strict_types=1);

namespace Src\Controllers;

use Src\Utils\Chatbot;
use Src\Utils\ConversationManager;
use Src\Utils\Response;
use Src\Config\DB;

class ChatbotController
{
    private Chatbot $chatbot;
    private ConversationManager $conversationManager;

    public function __construct()
    {
        $this->chatbot = new Chatbot();
        $this->conversationManager = new ConversationManager();
    }

    /**
     * Get chatbot response with AI integration
     */
    public function chat(): void
    {
        $auth = $_SERVER['auth'] ?? [];
        $uid = (int)($auth['uid'] ?? 0);

        if ($uid <= 0) {
            Response::json([
                'success' => false,
                'error' => ['code' => 'UNAUTHORIZED', 'message' => 'Authentication required']
            ], 401);
            return;
        }

        $body = json_decode(file_get_contents('php://input'), true) ?? [];
        $message = trim($body['message'] ?? '');
        $sessionId = $body['session_id'] ?? null;
        $userRole = $body['user_role'] ?? 'PATIENT';

        if (empty($message)) {
            Response::json([
                'success' => false,
                'error' => ['code' => 'VALIDATION', 'message' => 'Message is required']
            ], 422);
            return;
        }

        try {
            // Get user context for better AI responses
            $userContext = $this->getUserContext($uid, $userRole);
            
            // Use AI Service for ChatGPT-like responses
            $aiService = new \Src\Utils\AIService();
            $aiResponse = $aiService->getChatResponse($message, $userContext);
            
            // Log the conversation
            $this->logConversation($uid, $message, $aiResponse, $sessionId);
            
            // Get AI provider info
            $aiInfo = $aiService->getProviderInfo();
            
            Response::json([
                'success' => true,
                'data' => [
                    'response' => $aiResponse,
                    'message' => $aiResponse, // For backward compatibility
                    'timestamp' => date('Y-m-d H:i:s'),
                    'session_id' => $sessionId,
                    'ai_info' => $aiInfo,
                    'source' => 'ai_service'
                ]
            ]);
            
        } catch (\Throwable $e) {
            error_log("ChatBot AI Error: " . $e->getMessage());
            
            // Fallback to basic chatbot if AI fails
            $this->fallbackChat($uid, $message, $sessionId);
        }
    }

    /**
     * Get user context for AI responses
     */
    private function getUserContext(int $uid, string $userRole): ?array
    {
        if ($userRole !== 'PATIENT') {
            return null;
        }

        try {
            $db = DB::conn();
            $context = [];

            // Get recent symptoms from correct table
            $stmt = $db->prepare("SELECT pain_level, stiffness_level, fatigue_level, notes, created_at
                                 FROM symptoms
                                 WHERE patient_id = :pid
                                 ORDER BY created_at DESC
                                 LIMIT 3");
            $stmt->execute([':pid' => $uid]);
            $symptoms = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            if ($symptoms) {
                $context['recent_symptoms'] = [
                    'pain_level'      => $symptoms[0]['pain_level'] ?? 0,
                    'stiffness_level' => $symptoms[0]['stiffness_level'] ?? 0,
                    'fatigue_level'   => $symptoms[0]['fatigue_level'] ?? 0,
                    'last_recorded'   => $symptoms[0]['created_at'] ?? null
                ];
            }

            // Get current medications
            $stmt = $db->prepare("SELECT COALESCE(pm.name_override, pm.medication_name, 'Medication') as name,
                                        pm.dosage, pm.frequency
                                 FROM patient_medications pm
                                 WHERE pm.patient_id = :pid AND pm.active = 1
                                 ORDER BY pm.created_at DESC LIMIT 5");
            $stmt->execute([':pid' => $uid]);
            $medications = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            if ($medications) {
                $context['current_medications'] = $medications;
            }

            return empty($context) ? null : $context;

        } catch (\Throwable $e) {
            error_log("Error getting user context: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Log conversation for history and analytics
     */
    private function logConversation(int $uid, string $message, string $response, ?string $sessionId): void
    {
        try {
            $db = DB::conn();
            $stmt = $db->prepare("INSERT INTO chatbot_conversations (user_id, message, response, created_at)
                                 VALUES (:uid, :msg, :resp, NOW())");
            $stmt->execute([
                ':uid'  => $uid,
                ':msg'  => $message,
                ':resp' => $response,
            ]);
        } catch (\Throwable $e) {
            error_log("Error logging conversation: " . $e->getMessage());
        }
    }

    /**
     * Fallback to basic chatbot functionality
     */
    private function fallbackChat(int $uid, string $message, ?string $sessionId = null): void
    {
        $recentSymptoms = null;
        if ($uid > 0) {
            try {
                $db = DB::conn();
                $stmt = $db->prepare("SELECT pain_level, stiffness_level, fatigue_level, notes
                                     FROM symptoms
                                     WHERE patient_id = :pid
                                     ORDER BY created_at DESC
                                     LIMIT 1");
                $stmt->execute([':pid' => $uid]);
                $recentSymptoms = $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
            } catch (\Throwable $e) {
                // Continue without context
            }
        }

        // Get chatbot response
        $response = $this->chatbot->getContextualResponse($message, $recentSymptoms);

        // Log the conversation
        $this->logConversation($uid, $message, $response, $sessionId);

        // Get AI service info
        $aiService = new \Src\Utils\AIService();
        $aiInfo = $aiService->getProviderInfo();

        Response::json([
            'success' => true,
            'data' => [
                'response' => $response,
                'message' => $response, // For backward compatibility
                'timestamp' => date('Y-m-d H:i:s'),
                'session_id' => $sessionId,
                'ai_info' => $aiInfo,
                'source' => 'fallback'
            ]
        ]);
    }

    /**
     * Get chat history (legacy endpoint - maintains backward compatibility)
     */
    public function history(): void
    {
        $auth = $_SERVER['auth'] ?? [];
        $uid = (int)($auth['uid'] ?? 0);

        if ($uid <= 0) {
            Response::json([
                'success' => false,
                'error' => ['code' => 'UNAUTHORIZED', 'message' => 'Authentication required']
            ], 401);
            return;
        }

        $limit = min(50, max(1, (int)($_GET['limit'] ?? 20)));

        try {
            $db = DB::conn();
            $stmt = $db->prepare("SELECT message as user_message, response as bot_response, created_at
                                 FROM chatbot_conversations
                                 WHERE user_id = :uid
                                 ORDER BY created_at DESC
                                 LIMIT :limit");
            $stmt->bindValue(':uid', $uid, \PDO::PARAM_INT);
            $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
            $stmt->execute();

            $history = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            Response::json([
                'success' => true,
                'data' => array_reverse($history)
            ]);
        } catch (\Throwable $e) {
            Response::json([
                'success' => false,
                'error' => ['code' => 'SERVER_ERROR', 'message' => 'Failed to fetch history']
            ], 500);
        }
    }

    /**
     * Get conversation session history
     */
    public function sessionHistory(): void
    {
        $auth = $_SERVER['auth'] ?? [];
        $uid = (int)($auth['uid'] ?? 0);

        if ($uid <= 0) {
            Response::json([
                'success' => false,
                'error' => ['code' => 'UNAUTHORIZED', 'message' => 'Authentication required']
            ], 401);
            return;
        }

        $sessionId = $_GET['session_id'] ?? '';
        $limit = min(100, max(1, (int)($_GET['limit'] ?? 50)));

        if (empty($sessionId)) {
            Response::json([
                'success' => false,
                'error' => ['code' => 'VALIDATION', 'message' => 'Session ID is required']
            ], 422);
            return;
        }

        try {
            $history = $this->conversationManager->getSessionHistory($sessionId, $limit);

            Response::json([
                'success' => true,
                'data' => [
                    'session_id' => $sessionId,
                    'messages' => $history,
                    'message_count' => count($history)
                ]
            ]);
        } catch (\Throwable $e) {
            Response::json([
                'success' => false,
                'error' => ['code' => 'SERVER_ERROR', 'message' => 'Failed to fetch session history']
            ], 500);
        }
    }

    /**
     * End conversation session
     */
    public function endSession(): void
    {
        $auth = $_SERVER['auth'] ?? [];
        $uid = (int)($auth['uid'] ?? 0);

        if ($uid <= 0) {
            Response::json([
                'success' => false,
                'error' => ['code' => 'UNAUTHORIZED', 'message' => 'Authentication required']
            ], 401);
            return;
        }

        $body = json_decode(file_get_contents('php://input'), true) ?? [];
        $sessionId = $body['session_id'] ?? '';

        if (empty($sessionId)) {
            Response::json([
                'success' => false,
                'error' => ['code' => 'VALIDATION', 'message' => 'Session ID is required']
            ], 422);
            return;
        }

        try {
            $result = $this->conversationManager->endSession($sessionId);

            if ($result) {
                Response::json([
                    'success' => true,
                    'data' => [
                        'session_id' => $sessionId,
                        'status' => 'ended'
                    ]
                ]);
            } else {
                Response::json([
                    'success' => false,
                    'error' => ['code' => 'SERVER_ERROR', 'message' => 'Failed to end session']
                ], 500);
            }
        } catch (\Throwable $e) {
            Response::json([
                'success' => false,
                'error' => ['code' => 'SERVER_ERROR', 'message' => 'Failed to end session']
            ], 500);
        }
    }

    /**
     * Get conversation context for debugging/admin purposes
     */
    public function getContext(): void
    {
        $auth = $_SERVER['auth'] ?? [];
        $uid = (int)($auth['uid'] ?? 0);

        if ($uid <= 0) {
            Response::json([
                'success' => false,
                'error' => ['code' => 'UNAUTHORIZED', 'message' => 'Authentication required']
            ], 401);
            return;
        }

        $sessionId = $_GET['session_id'] ?? '';

        if (empty($sessionId)) {
            Response::json([
                'success' => false,
                'error' => ['code' => 'VALIDATION', 'message' => 'Session ID is required']
            ], 422);
            return;
        }

        try {
            $context = $this->conversationManager->getConversationContext($sessionId);

            Response::json([
                'success' => true,
                'data' => $context
            ]);
        } catch (\Throwable $e) {
            Response::json([
                'success' => false,
                'error' => ['code' => 'SERVER_ERROR', 'message' => 'Failed to get context']
            ], 500);
        }
    }
}
