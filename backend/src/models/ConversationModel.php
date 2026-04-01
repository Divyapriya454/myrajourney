<?php
declare(strict_types=1);

namespace Src\Models;

use Src\Config\DB;
use PDO;

class ConversationModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = DB::conn();
    }

    /**
     * Create a new conversation session
     */
    public function createSession(string $sessionId, int $userId, array $contextData = []): bool
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO conversation_sessions (session_id, user_id, context_data, status) 
                VALUES (:session_id, :user_id, :context_data, 'active')
            ");
            
            return $stmt->execute([
                ':session_id' => $sessionId,
                ':user_id' => $userId,
                ':context_data' => json_encode($contextData)
            ]);
        } catch (\Throwable $e) {
            error_log("Failed to create conversation session: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get conversation session by session ID
     */
    public function getSession(string $sessionId): ?array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM conversation_sessions 
                WHERE session_id = :session_id
            ");
            $stmt->execute([':session_id' => $sessionId]);
            
            $session = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($session && $session['context_data']) {
                $session['context_data'] = json_decode($session['context_data'], true);
            }
            
            return $session ?: null;
        } catch (\Throwable $e) {
            error_log("Failed to get conversation session: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get active session for user (creates new if none exists)
     */
    public function getOrCreateActiveSession(int $userId): array
    {
        try {
            // First, try to get an active session
            $stmt = $this->db->prepare("
                SELECT * FROM conversation_sessions 
                WHERE user_id = :user_id AND status = 'active' 
                ORDER BY last_activity DESC 
                LIMIT 1
            ");
            $stmt->execute([':user_id' => $userId]);
            
            $session = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($session) {
                // Update last activity
                $this->updateLastActivity($session['session_id']);
                
                if ($session['context_data']) {
                    $session['context_data'] = json_decode($session['context_data'], true);
                }
                return $session;
            }
            
            // Create new session if none exists
            $sessionId = $this->generateSessionId();
            $contextData = $this->buildUserContext($userId);
            
            if ($this->createSession($sessionId, $userId, $contextData)) {
                return $this->getSession($sessionId);
            }
            
            throw new \Exception("Failed to create new session");
            
        } catch (\Throwable $e) {
            error_log("Failed to get or create active session: " . $e->getMessage());
            // Return minimal session data as fallback
            return [
                'session_id' => $this->generateSessionId(),
                'user_id' => $userId,
                'status' => 'active',
                'context_data' => []
            ];
        }
    }

    /**
     * Update session context data
     */
    public function updateSessionContext(string $sessionId, array $contextData): bool
    {
        try {
            $stmt = $this->db->prepare("
                UPDATE conversation_sessions 
                SET context_data = :context_data, updated_at = CURRENT_TIMESTAMP 
                WHERE session_id = :session_id
            ");
            
            return $stmt->execute([
                ':session_id' => $sessionId,
                ':context_data' => json_encode($contextData)
            ]);
        } catch (\Throwable $e) {
            error_log("Failed to update session context: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update last activity timestamp
     */
    public function updateLastActivity(string $sessionId): bool
    {
        try {
            $stmt = $this->db->prepare("
                UPDATE conversation_sessions 
                SET last_activity = CURRENT_TIMESTAMP 
                WHERE session_id = :session_id
            ");
            
            return $stmt->execute([':session_id' => $sessionId]);
        } catch (\Throwable $e) {
            error_log("Failed to update last activity: " . $e->getMessage());
            return false;
        }
    }

    /**
     * End conversation session
     */
    public function endSession(string $sessionId): bool
    {
        try {
            $stmt = $this->db->prepare("
                UPDATE conversation_sessions 
                SET status = 'ended', updated_at = CURRENT_TIMESTAMP 
                WHERE session_id = :session_id
            ");
            
            return $stmt->execute([':session_id' => $sessionId]);
        } catch (\Throwable $e) {
            error_log("Failed to end session: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Store conversation message
     */
    public function storeMessage(
        string $messageId,
        string $sessionId,
        string $sender,
        string $content,
        ?string $intent = null,
        ?array $entities = null,
        ?float $confidence = null,
        ?array $responseMetadata = null
    ): bool {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO conversation_messages 
                (message_id, session_id, sender, content, intent, entities, confidence, response_metadata) 
                VALUES (:message_id, :session_id, :sender, :content, :intent, :entities, :confidence, :response_metadata)
            ");
            
            return $stmt->execute([
                ':message_id' => $messageId,
                ':session_id' => $sessionId,
                ':sender' => $sender,
                ':content' => $content,
                ':intent' => $intent,
                ':entities' => $entities ? json_encode($entities) : null,
                ':confidence' => $confidence,
                ':response_metadata' => $responseMetadata ? json_encode($responseMetadata) : null
            ]);
        } catch (\Throwable $e) {
            error_log("Failed to store message: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get conversation history for session
     */
    public function getConversationHistory(string $sessionId, int $limit = 50): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM conversation_messages 
                WHERE session_id = :session_id 
                ORDER BY timestamp ASC 
                LIMIT :limit
            ");
            $stmt->bindValue(':session_id', $sessionId, PDO::PARAM_STR);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Decode JSON fields
            foreach ($messages as &$message) {
                if ($message['entities']) {
                    $message['entities'] = json_decode($message['entities'], true);
                }
                if ($message['response_metadata']) {
                    $message['response_metadata'] = json_decode($message['response_metadata'], true);
                }
            }
            
            return $messages;
        } catch (\Throwable $e) {
            error_log("Failed to get conversation history: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get recent messages for context (last N messages)
     */
    public function getRecentMessages(string $sessionId, int $count = 5): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM conversation_messages 
                WHERE session_id = :session_id 
                ORDER BY timestamp DESC 
                LIMIT :count
            ");
            $stmt->bindValue(':session_id', $sessionId, PDO::PARAM_STR);
            $stmt->bindValue(':count', $count, PDO::PARAM_INT);
            $stmt->execute();
            
            $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Decode JSON fields and reverse to chronological order
            $messages = array_reverse($messages);
            foreach ($messages as &$message) {
                if ($message['entities']) {
                    $message['entities'] = json_decode($message['entities'], true);
                }
                if ($message['response_metadata']) {
                    $message['response_metadata'] = json_decode($message['response_metadata'], true);
                }
            }
            
            return $messages;
        } catch (\Throwable $e) {
            error_log("Failed to get recent messages: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Clean up old inactive sessions
     */
    public function cleanupOldSessions(int $daysOld = 7): int
    {
        try {
            $stmt = $this->db->prepare("
                UPDATE conversation_sessions 
                SET status = 'ended' 
                WHERE status = 'active' 
                AND last_activity < DATE_SUB(NOW(), INTERVAL :days DAY)
            ");
            $stmt->execute([':days' => $daysOld]);
            
            return $stmt->rowCount();
        } catch (\Throwable $e) {
            error_log("Failed to cleanup old sessions: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Generate unique session ID
     */
    private function generateSessionId(): string
    {
        return 'sess_' . uniqid() . '_' . bin2hex(random_bytes(8));
    }

    /**
     * Build user context from patient data
     */
    private function buildUserContext(int $userId): array
    {
        $context = [
            'user_id' => $userId,
            'preferences' => [],
            'medical_history' => [],
            'current_concerns' => []
        ];

        try {
            // Get recent symptoms
            $stmt = $this->db->prepare("
                SELECT pain_level, stiffness_level, fatigue_level, notes, created_at
                FROM symptom_logs 
                WHERE patient_id = :user_id 
                ORDER BY created_at DESC 
                LIMIT 3
            ");
            $stmt->execute([':user_id' => $userId]);
            $context['recent_symptoms'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Get current medications
            $stmt = $this->db->prepare("
                SELECT m.name, pm.dosage, pm.frequency, pm.start_date
                FROM patient_medications pm
                JOIN medications m ON pm.medication_id = m.id
                WHERE pm.patient_id = :user_id AND pm.status = 'active'
            ");
            $stmt->execute([':user_id' => $userId]);
            $context['current_medications'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Get recent appointments
            $stmt = $this->db->prepare("
                SELECT appointment_date, appointment_time, status, notes
                FROM appointments 
                WHERE patient_id = :user_id 
                ORDER BY appointment_date DESC 
                LIMIT 2
            ");
            $stmt->execute([':user_id' => $userId]);
            $context['recent_appointments'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (\Throwable $e) {
            error_log("Failed to build user context: " . $e->getMessage());
        }

        return $context;
    }
}
