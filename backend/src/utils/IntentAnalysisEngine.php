<?php
declare(strict_types=1);

namespace Src\Utils;

class IntentAnalysisEngine
{
    private array $intentPatterns = [];
    private array $symptomEntities = [];
    private array $medicationEntities = [];
    private array $appointmentEntities = [];

    public function __construct()
    {
        $this->initializeIntentPatterns();
        $this->initializeEntityRecognition();
    }

    private function initializeIntentPatterns(): void
    {
        // Symptom reporting intents
        $this->intentPatterns['symptom_report'] = [
            'patterns' => [
                '/i\s+(am|feel|feeling|have|experiencing|got)\s+(.*?)(?:\s+today|\s+now|\s+lately|$)/i',
                '/my\s+(.*?)\s+(is|are|feels?|hurts?)/i',
                '/having\s+(.*?)(?:\s+today|\s+now|\s+lately|$)/i'
            ],
            'confidence_boost' => ['feeling', 'experiencing', 'having', 'symptoms']
        ];

        // Pain-related intents
        $this->intentPatterns['pain_inquiry'] = [
            'patterns' => [
                '/pain\s+(management|relief|help|treatment)/i',
                '/how\s+to\s+(manage|treat|deal\s+with|reduce)\s+pain/i',
                '/what\s+(can|should)\s+i\s+do\s+(for|about)\s+pain/i'
            ],
            'confidence_boost' => ['pain', 'hurt', 'ache', 'sore']
        ];

        // Medication intents
        $this->intentPatterns['medication_inquiry'] = [
            'patterns' => [
                '/medication|medicine|drug|pill|prescription/i',
                '/when\s+to\s+take/i',
                '/side\s+effects?/i',
                '/dosage|dose/i'
            ],
            'confidence_boost' => ['medication', 'medicine', 'pills', 'prescription']
        ];

        // Appointment intents
        $this->intentPatterns['appointment_inquiry'] = [
            'patterns' => [
                '/appointment|visit|checkup|consultation/i',
                '/see\s+(doctor|physician|rheumatologist)/i',
                '/schedule|book|make\s+appointment/i'
            ],
            'confidence_boost' => ['appointment', 'doctor', 'visit']
        ];

        // Emergency intents
        $this->intentPatterns['emergency'] = [
            'patterns' => [
                '/emergency|urgent|severe|can\'?t\s+breathe|chest\s+pain/i',
                '/call\s+(ambulance|911|doctor)/i',
                '/hospital|er|emergency\s+room/i'
            ],
            'confidence_boost' => ['emergency', 'severe', 'urgent', 'help']
        ];

        // Exercise intents
        $this->intentPatterns['exercise_inquiry'] = [
            'patterns' => [
                '/exercise|workout|physical\s+activity|movement/i',
                '/can\s+i\s+(exercise|work\s+out|move)/i',
                '/safe\s+(exercises?|activities?)/i'
            ],
            'confidence_boost' => ['exercise', 'workout', 'activity', 'movement']
        ];

        // Diet intents
        $this->intentPatterns['diet_inquiry'] = [
            'patterns' => [
                '/diet|food|eat|nutrition|meal/i',
                '/what\s+(should|can)\s+i\s+eat/i',
                '/anti[-\s]?inflammatory\s+diet/i'
            ],
            'confidence_boost' => ['diet', 'food', 'nutrition', 'eating']
        ];
    }

    private function initializeEntityRecognition(): void
    {
        // Specific symptoms with their variations
        $this->symptomEntities = [
            'dizziness' => ['dizzy', 'dizziness', 'lightheaded', 'vertigo', 'spinning'],
            'fatigue' => ['tired', 'fatigue', 'exhausted', 'weak', 'weakness', 'energy', 'sleepy'],
            'pain' => ['pain', 'hurt', 'hurting', 'ache', 'aching', 'painful', 'sore', 'discomfort'],
            'stiffness' => ['stiff', 'stiffness', 'rigid', 'tight', 'morning stiffness'],
            'swelling' => ['swollen', 'swelling', 'puffy', 'inflammation', 'inflamed'],
            'fever' => ['fever', 'hot', 'temperature', 'feverish', 'burning up'],
            'nausea' => ['nausea', 'nauseous', 'sick', 'queasy', 'vomiting', 'throw up'],
            'headache' => ['headache', 'head pain', 'migraine', 'head hurts'],
            'joint_pain' => ['joint pain', 'joints hurt', 'knee pain', 'ankle pain', 'wrist pain'],
            'muscle_pain' => ['muscle pain', 'muscles hurt', 'muscle ache', 'muscle soreness'],
            'sleep_issues' => ['insomnia', 'can\'t sleep', 'trouble sleeping', 'sleep problems'],
            'anxiety' => ['anxious', 'anxiety', 'worried', 'stress', 'stressed', 'overwhelmed'],
            'depression' => ['depressed', 'depression', 'sad', 'down', 'hopeless']
        ];

        // Common medications
        $this->medicationEntities = [
            'methotrexate', 'humira', 'enbrel', 'remicade', 'prednisone', 
            'ibuprofen', 'naproxen', 'celebrex', 'plaquenil', 'sulfasalazine'
        ];

        // Appointment-related entities
        $this->appointmentEntities = [
            'rheumatologist', 'doctor', 'physician', 'specialist', 'nurse',
            'appointment', 'visit', 'checkup', 'consultation', 'follow-up'
        ];
    }

    public function analyzeIntent(string $message): array
    {
        $message = strtolower(trim($message));
        $results = [];

        // Analyze each intent pattern
        foreach ($this->intentPatterns as $intent => $config) {
            $confidence = $this->calculateIntentConfidence($message, $config);
            if ($confidence > 0) {
                $results[] = [
                    'intent' => $intent,
                    'confidence' => $confidence
                ];
            }
        }

        // Sort by confidence
        usort($results, function($a, $b) {
            return $b['confidence'] <=> $a['confidence'];
        });

        // Extract entities
        $entities = $this->extractEntities($message);

        return [
            'primary_intent' => $results[0] ?? ['intent' => 'general_inquiry', 'confidence' => 0.3],
            'all_intents' => $results,
            'entities' => $entities,
            'original_message' => $message
        ];
    }

    private function calculateIntentConfidence(string $message, array $config): float
    {
        $confidence = 0.0;

        // Check pattern matches
        foreach ($config['patterns'] as $pattern) {
            if (preg_match($pattern, $message)) {
                $confidence += 0.6;
                break;
            }
        }

        // Check confidence boost keywords
        foreach ($config['confidence_boost'] as $keyword) {
            if (strpos($message, $keyword) !== false) {
                $confidence += 0.2;
            }
        }

        return min($confidence, 1.0);
    }

    private function extractEntities(string $message): array
    {
        $entities = [
            'symptoms' => [],
            'medications' => [],
            'appointments' => [],
            'severity' => null,
            'time_context' => null
        ];

        // Extract symptoms
        foreach ($this->symptomEntities as $symptom => $variations) {
            foreach ($variations as $variation) {
                if (strpos($message, $variation) !== false) {
                    $entities['symptoms'][] = $symptom;
                    break;
                }
            }
        }

        // Extract medications
        foreach ($this->medicationEntities as $medication) {
            if (strpos($message, $medication) !== false) {
                $entities['medications'][] = $medication;
            }
        }

        // Extract appointment entities
        foreach ($this->appointmentEntities as $appointment) {
            if (strpos($message, $appointment) !== false) {
                $entities['appointments'][] = $appointment;
            }
        }

        // Extract severity indicators
        $severityPatterns = [
            'severe' => ['severe', 'terrible', 'unbearable', 'excruciating', 'intense'],
            'moderate' => ['moderate', 'medium', 'noticeable', 'bothering'],
            'mild' => ['mild', 'slight', 'little', 'minor', 'light']
        ];

        foreach ($severityPatterns as $level => $keywords) {
            foreach ($keywords as $keyword) {
                if (strpos($message, $keyword) !== false) {
                    $entities['severity'] = $level;
                    break 2;
                }
            }
        }

        // Extract time context
        $timePatterns = [
            'now' => ['now', 'currently', 'right now', 'at the moment'],
            'today' => ['today', 'this morning', 'this afternoon', 'this evening'],
            'recently' => ['recently', 'lately', 'past few days', 'this week'],
            'always' => ['always', 'constantly', 'all the time', 'every day']
        ];

        foreach ($timePatterns as $time => $keywords) {
            foreach ($keywords as $keyword) {
                if (strpos($message, $keyword) !== false) {
                    $entities['time_context'] = $time;
                    break 2;
                }
            }
        }

        // Remove duplicates
        $entities['symptoms'] = array_unique($entities['symptoms']);
        $entities['medications'] = array_unique($entities['medications']);
        $entities['appointments'] = array_unique($entities['appointments']);

        return $entities;
    }

    public function getIntentDescription(string $intent): string
    {
        $descriptions = [
            'symptom_report' => 'User is reporting a specific symptom or health concern',
            'pain_inquiry' => 'User is asking about pain management or treatment',
            'medication_inquiry' => 'User has questions about medications',
            'appointment_inquiry' => 'User wants information about appointments or scheduling',
            'emergency' => 'User may have an emergency situation requiring immediate attention',
            'exercise_inquiry' => 'User is asking about exercise or physical activity',
            'diet_inquiry' => 'User has questions about diet or nutrition',
            'general_inquiry' => 'General health-related question'
        ];

        return $descriptions[$intent] ?? 'Unknown intent';
    }
}
