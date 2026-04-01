<?php
declare(strict_types=1);

namespace Src\Utils;

class SmartAI
{
    private array $knowledgeBase;
    private array $responsePatterns;
    private array $contextKeywords;

    public function __construct()
    {
        $this->initializeKnowledgeBase();
        $this->initializeResponsePatterns();
        $this->initializeContextKeywords();
    }

    private function initializeKnowledgeBase(): void
    {
        $this->knowledgeBase = [
            'pain_management' => [
                'immediate' => [
                    'Apply heat therapy (heating pad, warm shower) for stiffness',
                    'Apply cold therapy (ice pack 15-20 min) for hot, swollen joints',
                    'Take prescribed pain medication if it\'s time for your dose',
                    'Try gentle range-of-motion exercises',
                    'Rest the affected joints and avoid overuse'
                ],
                'long_term' => [
                    'Maintain consistent DMARD medication schedule',
                    'Regular low-impact exercise like swimming or walking',
                    'Stress reduction techniques (meditation, deep breathing)',
                    'Adequate sleep (7-9 hours per night)',
                    'Anti-inflammatory diet with omega-3 rich foods'
                ]
            ],
            'medications' => [
                'dmards' => [
                    'Methotrexate is often the first-line DMARD treatment',
                    'Take exactly as prescribed, usually once weekly',
                    'Regular blood tests are essential to monitor for side effects',
                    'Never stop DMARDs without consulting your rheumatologist',
                    'Folic acid supplements help reduce methotrexate side effects'
                ],
                'biologics' => [
                    'Target specific parts of the immune system',
                    'Given by injection or infusion',
                    'May increase infection risk - report fever immediately',
                    'Highly effective for many patients with moderate to severe RA',
                    'Regular monitoring required for safety'
                ],
                'missed_doses' => [
                    'For weekly medications: Take same day if remembered, skip if next day',
                    'For daily medications: Take if less than 12 hours late',
                    'Never double dose any RA medication',
                    'Set up reminders to prevent future missed doses',
                    'Contact your doctor if you frequently forget medications'
                ]
            ],
            'symptoms' => [
                'fatigue' => [
                    'RA fatigue is real and can be overwhelming',
                    'Take short 20-30 minute power naps when possible',
                    'Pace activities throughout the day',
                    'Prioritize essential tasks during your best energy times',
                    'Stay hydrated and maintain regular meal times',
                    'Gentle exercise can actually boost energy levels'
                ],
                'stiffness' => [
                    'Morning stiffness lasting 30-60 minutes is common',
                    'Take a warm shower or bath to ease stiffness',
                    'Do gentle stretching exercises in bed before getting up',
                    'Use heating pads on stiff joints',
                    'Move slowly and give yourself extra time in the morning',
                    'Stiffness lasting over 2 hours may indicate active inflammation'
                ],
                'swelling' => [
                    'Joint swelling indicates active inflammation',
                    'Apply ice packs for 15-20 minutes to reduce swelling',
                    'Elevate the swollen joint if possible',
                    'Avoid activities that stress the swollen joint',
                    'Take anti-inflammatory medication as prescribed',
                    'Contact your rheumatologist for new or worsening swelling'
                ]
            ],
            'lifestyle' => [
                'exercise' => [
                    'Swimming and water aerobics are excellent low-impact options',
                    'Walking is a great starting point - begin with 10-15 minutes',
                    'Yoga and tai chi improve flexibility and reduce stress',
                    'Strength training helps maintain muscle mass around joints',
                    'Exercise during mid-morning when stiffness typically improves',
                    'Stop exercising if joints become more painful or swollen'
                ],
                'diet' => [
                    'Mediterranean diet pattern is anti-inflammatory',
                    'Include fatty fish (salmon, sardines) 2-3 times per week',
                    'Eat plenty of colorful vegetables and fruits',
                    'Use olive oil and include nuts and seeds',
                    'Limit processed foods, sugar, and trans fats',
                    'Stay well hydrated with 8-10 glasses of water daily'
                ],
                'sleep' => [
                    'Maintain consistent sleep schedule (same bedtime/wake time)',
                    'Create comfortable sleep environment with supportive pillows',
                    'Take warm bath before bed to ease joint stiffness',
                    'Keep bedroom cool (65-68°F) and dark',
                    'Avoid screens 1 hour before bedtime',
                    'Time pain medication to provide nighttime coverage'
                ]
            ]
        ];
    }

    private function initializeResponsePatterns(): void
    {
        $this->responsePatterns = [
            'question_starters' => [
                'That\'s a great question about',
                'I understand you\'re asking about',
                'Let me help you with',
                'Here\'s what I can tell you about',
                'That\'s an important concern regarding'
            ],
            'empathy_phrases' => [
                'I understand how challenging that can be',
                'That sounds really difficult to deal with',
                'Many people with RA experience this',
                'You\'re not alone in feeling this way',
                'It\'s completely understandable to feel concerned'
            ],
            'action_starters' => [
                'Here are some steps you can take',
                'I recommend trying these approaches',
                'Consider these strategies',
                'You might find these helpful',
                'Here\'s what typically works well'
            ],
            'medical_disclaimers' => [
                'Always consult your rheumatologist for personalized advice',
                'This is general guidance - your doctor knows your specific situation best',
                'Please discuss any concerns with your healthcare provider',
                'Your rheumatologist can provide personalized recommendations',
                'Consider contacting your doctor if symptoms persist or worsen'
            ]
        ];
    }

    private function initializeContextKeywords(): void
    {
        $this->contextKeywords = [
            'pain' => ['hurt', 'ache', 'sore', 'painful', 'aching', 'throbbing', 'sharp', 'burning'],
            'fatigue' => ['tired', 'exhausted', 'worn out', 'drained', 'no energy', 'sleepy', 'weary'],
            'stiffness' => ['stiff', 'rigid', 'tight', 'can\'t move', 'locked up', 'frozen'],
            'swelling' => ['swollen', 'puffy', 'inflamed', 'inflammation', 'enlarged'],
            'medication' => ['medicine', 'drug', 'pill', 'tablet', 'injection', 'methotrexate', 'mtx'],
            'exercise' => ['workout', 'activity', 'movement', 'physical therapy', 'stretching'],
            'diet' => ['food', 'eat', 'nutrition', 'anti-inflammatory', 'weight'],
            'sleep' => ['insomnia', 'can\'t sleep', 'wake up', 'restless', 'tired'],
            'flare' => ['flare-up', 'getting worse', 'worsening', 'attack', 'episode'],
            'weather' => ['cold', 'rain', 'winter', 'barometric', 'pressure', 'seasonal']
        ];
    }

    public function generateResponse(string $userMessage, ?array $context = null): string
    {
        $message = strtolower(trim($userMessage));
        
        // Analyze the message to understand intent
        $intent = $this->analyzeIntent($message);
        $keywords = $this->extractKeywords($message);
        
        // Generate contextual response
        $response = $this->buildResponse($intent, $keywords, $userMessage, $context);
        
        return $response;
    }

    private function analyzeIntent(string $message): array
    {
        $intents = [];
        
        // Check for question patterns
        if (preg_match('/\b(what|how|why|when|where|should|can|could|would|is|are|do|does)\b/', $message)) {
            $intents[] = 'question';
        }
        
        // Check for help requests
        if (preg_match('/\b(help|assist|advice|recommend|suggest|guide)\b/', $message)) {
            $intents[] = 'help_request';
        }
        
        // Check for symptom reporting
        if (preg_match('/\b(having|experiencing|feeling|i am|i\'m|my)\b/', $message)) {
            $intents[] = 'symptom_report';
        }
        
        // Check for medication concerns
        if (preg_match('/\b(forgot|missed|skip|late|medication|medicine)\b/', $message)) {
            $intents[] = 'medication_concern';
        }
        
        return $intents;
    }

    private function extractKeywords(string $message): array
    {
        $foundKeywords = [];
        
        foreach ($this->contextKeywords as $category => $keywords) {
            foreach ($keywords as $keyword) {
                if (strpos($message, $keyword) !== false) {
                    $foundKeywords[$category] = $keyword;
                    break;
                }
            }
        }
        
        // Also check for direct category matches
        foreach ($this->contextKeywords as $category => $keywords) {
            if (strpos($message, $category) !== false) {
                $foundKeywords[$category] = $category;
            }
        }
        
        return $foundKeywords;
    }

    private function buildResponse(array $intents, array $keywords, string $originalMessage, ?array $context): string
    {
        $response = '';
        
        // Start with empathy or acknowledgment
        if (in_array('symptom_report', $intents)) {
            $response .= $this->getRandomPhrase('empathy_phrases') . '. ';
        } else {
            $response .= $this->getRandomPhrase('question_starters') . ' ' . $this->extractMainTopic($originalMessage) . '. ';
        }
        
        $response .= "\n\n";
        
        // Add specific advice based on keywords - FIXED LOGIC
        $adviceAdded = false;
        
        // Check for specific symptoms/topics first
        $message = strtolower($originalMessage);
        
        if (strpos($message, 'pain') !== false || strpos($message, 'hurt') !== false || strpos($message, 'ache') !== false) {
            $response .= $this->getAdviceForCategory('pain', 'pain');
            $adviceAdded = true;
        } elseif (strpos($message, 'tired') !== false || strpos($message, 'fatigue') !== false || strpos($message, 'exhausted') !== false) {
            $response .= $this->getAdviceForCategory('fatigue', 'fatigue');
            $adviceAdded = true;
        } elseif (strpos($message, 'stiff') !== false || strpos($message, 'stiffness') !== false) {
            $response .= $this->getAdviceForCategory('stiffness', 'stiffness');
            $adviceAdded = true;
        } elseif (strpos($message, 'swollen') !== false || strpos($message, 'swelling') !== false || strpos($message, 'inflamed') !== false) {
            $response .= $this->getAdviceForCategory('swelling', 'swelling');
            $adviceAdded = true;
        } elseif (strpos($message, 'medication') !== false || strpos($message, 'medicine') !== false || strpos($message, 'methotrexate') !== false || strpos($message, 'forgot') !== false || strpos($message, 'missed') !== false) {
            $response .= $this->getAdviceForCategory('medication', strpos($message, 'forgot') !== false ? 'forgot' : 'medication');
            $adviceAdded = true;
        } elseif (strpos($message, 'exercise') !== false || strpos($message, 'workout') !== false || strpos($message, 'activity') !== false) {
            $response .= $this->getAdviceForCategory('exercise', 'exercise');
            $adviceAdded = true;
        } elseif (strpos($message, 'diet') !== false || strpos($message, 'food') !== false || strpos($message, 'eat') !== false || strpos($message, 'nutrition') !== false) {
            $response .= $this->getAdviceForCategory('diet', 'diet');
            $adviceAdded = true;
        } elseif (strpos($message, 'sleep') !== false || strpos($message, 'insomnia') !== false) {
            $response .= $this->getAdviceForCategory('sleep', 'sleep');
            $adviceAdded = true;
        } elseif (strpos($message, 'flare') !== false || strpos($message, 'worse') !== false || strpos($message, 'worsening') !== false) {
            $response .= $this->getFlareAdvice();
            $adviceAdded = true;
        } elseif (strpos($message, 'weather') !== false || strpos($message, 'cold') !== false || strpos($message, 'rain') !== false) {
            $response .= $this->getWeatherAdvice();
            $adviceAdded = true;
        }
        
        // If no specific advice found, provide general guidance
        if (!$adviceAdded) {
            $response .= $this->getGeneralGuidance($originalMessage, $keywords);
        }
        
        // Add context-specific information if available
        if ($context) {
            $response .= $this->addContextualInfo($context);
        }
        
        // Add medical disclaimer
        $response .= "\n\n💡 " . $this->getRandomPhrase('medical_disclaimers');
        
        return trim($response);
    }

    private function getAdviceForCategory(string $category, string $keyword): string
    {
        $advice = '';
        $categoryData = $this->knowledgeBase[$category];
        
        switch ($category) {
            case 'pain':
                $advice .= "**For current pain relief:**\n";
                foreach ($categoryData['immediate'] as $tip) {
                    $advice .= "• $tip\n";
                }
                $advice .= "\n**Long-term pain management:**\n";
                foreach (array_slice($categoryData['long_term'], 0, 3) as $tip) {
                    $advice .= "• $tip\n";
                }
                break;
                
            case 'fatigue':
                $advice .= "**Managing RA fatigue:**\n";
                foreach ($this->knowledgeBase['symptoms']['fatigue'] as $tip) {
                    $advice .= "• $tip\n";
                }
                break;
                
            case 'medication':
                if (strpos($keyword, 'forgot') !== false || strpos($keyword, 'missed') !== false) {
                    $advice .= "**For missed medications:**\n";
                    foreach ($categoryData['missed_doses'] as $tip) {
                        $advice .= "• $tip\n";
                    }
                } else {
                    $advice .= "**About RA medications:**\n";
                    foreach (array_slice($categoryData['dmards'], 0, 4) as $tip) {
                        $advice .= "• $tip\n";
                    }
                }
                break;
                
            default:
                if (isset($this->knowledgeBase['symptoms'][$category])) {
                    $advice .= "**Managing $category:**\n";
                    foreach ($this->knowledgeBase['symptoms'][$category] as $tip) {
                        $advice .= "• $tip\n";
                    }
                } elseif (isset($this->knowledgeBase['lifestyle'][$category])) {
                    $advice .= "**$category guidance:**\n";
                    foreach ($this->knowledgeBase['lifestyle'][$category] as $tip) {
                        $advice .= "• $tip\n";
                    }
                }
                break;
        }
        
        return $advice;
    }

    private function getGeneralGuidance(string $message, array $keywords): string
    {
        // Provide general RA management advice
        return "**General RA management tips:**\n" .
               "• Take medications consistently as prescribed\n" .
               "• Stay active with gentle, low-impact exercises\n" .
               "• Maintain a healthy, anti-inflammatory diet\n" .
               "• Get adequate sleep and manage stress\n" .
               "• Keep regular appointments with your rheumatologist\n" .
               "• Track your symptoms to identify patterns\n\n" .
               "For more specific guidance, please describe your symptoms or concerns in detail.";
    }

    private function addContextualInfo(?array $context): string
    {
        if (!$context) return '';
        
        $contextInfo = "\n\n**Based on your recent activity:**\n";
        
        if (isset($context['recent_symptoms'])) {
            $symptoms = $context['recent_symptoms'];
            $contextInfo .= "• Your last recorded pain level was {$symptoms['pain_level']}/10\n";
            if ($symptoms['fatigue_level'] > 5) {
                $contextInfo .= "• You've been experiencing higher fatigue levels\n";
            }
        }
        
        if (isset($context['current_medications']) && !empty($context['current_medications'])) {
            $medCount = count($context['current_medications']);
            $contextInfo .= "• You're currently taking $medCount RA medications\n";
        }
        
        return $contextInfo;
    }

    private function extractMainTopic(string $message): string
    {
        $topics = [
            'pain' => 'pain management',
            'medication' => 'medication concerns',
            'tired' => 'fatigue management',
            'stiff' => 'joint stiffness',
            'exercise' => 'exercise and activity',
            'diet' => 'nutrition and diet',
            'sleep' => 'sleep and rest',
            'flare' => 'RA flare management'
        ];
        
        foreach ($topics as $keyword => $topic) {
            if (strpos(strtolower($message), $keyword) !== false) {
                return $topic;
            }
        }
        
        return 'your RA management';
    }

    private function getFlareAdvice(): string
    {
        return "**RA flare management - act quickly:**\n" .
               "• Rest affected joints - avoid overuse\n" .
               "• Apply ice to hot, swollen joints (15-20 min)\n" .
               "• Take prescribed flare medications if available\n" .
               "• Contact your rheumatologist's office\n\n" .
               "**Flare warning signs:**\n" .
               "• Increased joint pain and stiffness\n" .
               "• New joint swelling or warmth\n" .
               "• Extreme fatigue\n" .
               "• Morning stiffness lasting hours\n\n" .
               "🚨 **Call doctor immediately if:**\n" .
               "• Fever with joint symptoms\n" .
               "• Unable to move joints\n" .
               "• Signs of infection (redness, pus)\n\n" .
               "Early flare treatment prevents joint damage. Don't wait it out.";
    }

    private function getWeatherAdvice(): string
    {
        return "**Weather sensitivity is real with RA:**\n" .
               "• Layer clothing in cold weather to trap warm air\n" .
               "• Use heated car seats and steering wheel covers\n" .
               "• Wear compression gloves and warm socks\n" .
               "• Keep joints moving with indoor exercises\n\n" .
               "**Rainy/low pressure days:**\n" .
               "• Plan lighter activities on weather-sensitive days\n" .
               "• Use heating pads proactively\n" .
               "• Stay consistent with medications\n" .
               "• Track symptoms vs. weather patterns\n\n" .
               "💡 Weather doesn't cause RA flares, but it can make symptoms more noticeable.";
    }

    private function getRandomPhrase(string $category): string
    {
        $phrases = $this->responsePatterns[$category];
        return $phrases[array_rand($phrases)];
    }

    public function isConfigured(): bool
    {
        return true;
    }

    public function getProviderInfo(): array
    {
        return [
            'provider' => 'SmartAI',
            'active' => true,
            'model' => 'Advanced Pattern Recognition with Medical Knowledge Base',
            'features' => [
                'Dynamic response generation',
                'Context-aware conversations',
                'RA medical expertise',
                'Intent analysis',
                'Keyword extraction',
                'Personalized advice'
            ]
        ];
    }
}