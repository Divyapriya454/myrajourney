<?php
declare(strict_types=1);

namespace Src\Utils;

class HuggingFaceAI
{
    private string $baseUrl = 'https://api-inference.huggingface.co/models/';
    private string $model;
    private array $medicalKnowledge;

    public function __construct()
    {
        $this->model = $_ENV['HUGGINGFACE_MODEL'] ?? 'microsoft/DialoGPT-medium';
        $this->initializeMedicalKnowledge();
    }

    private function initializeMedicalKnowledge(): void
    {
        $this->medicalKnowledge = [
            'ra_info' => [
                'definition' => 'Rheumatoid arthritis (RA) is a chronic autoimmune disease that primarily affects joints, causing inflammation, pain, and potential joint damage.',
                'symptoms' => ['joint pain', 'swelling', 'stiffness', 'fatigue', 'morning stiffness'],
                'management' => ['medication adherence', 'regular exercise', 'stress management', 'healthy diet', 'adequate sleep']
            ],
            'medications' => [
                'dmards' => 'Disease-modifying antirheumatic drugs (DMARDs) like methotrexate help slow RA progression',
                'biologics' => 'Biologic medications target specific parts of the immune system',
                'nsaids' => 'NSAIDs help reduce inflammation and pain'
            ],
            'lifestyle' => [
                'exercise' => 'Low-impact exercises like swimming, walking, and yoga are beneficial for RA',
                'diet' => 'Anti-inflammatory diet with omega-3 fatty acids, fruits, and vegetables',
                'stress' => 'Stress management through meditation, relaxation techniques, and support groups'
            ]
        ];
    }

    public function getChatResponse(string $userMessage, ?array $context = null): string
    {
        // First, try to provide intelligent response using medical knowledge
        $intelligentResponse = $this->getIntelligentResponse($userMessage, $context);
        if ($intelligentResponse) {
            return $intelligentResponse;
        }

        // Fallback to Hugging Face API for general conversation
        try {
            return $this->getHuggingFaceResponse($userMessage);
        } catch (\Exception $e) {
            // Final fallback to local knowledge
            return $this->getLocalResponse($userMessage);
        }
    }

    private function getIntelligentResponse(string $userMessage, ?array $context = null): ?string
    {
        $message = strtolower(trim($userMessage));
        
        // Greeting responses
        if (preg_match('/\b(hello|hi|hey|good morning|good afternoon|good evening)\b/', $message)) {
            $greeting = "Hello! I'm your AI-powered RA health assistant. ";
            if ($context) {
                $greeting .= $this->addContextualGreeting($context);
            }
            $greeting .= "\n\nI can help you with:\n🔹 RA symptoms and management\n🔹 Medication guidance\n🔹 Exercise recommendations\n🔹 Diet and lifestyle tips\n\nWhat would you like to know about today?";
            return $greeting;
        }

        // Pain management
        if (preg_match('/\b(pain|hurt|ache|painful|sore|discomfort)\b/', $message)) {
            $response = "I understand you're dealing with pain. Here's what can help with RA pain:\n\n";
            $response .= "🔹 **Immediate Relief:**\n";
            $response .= "• Apply heat for stiffness or cold for inflammation\n";
            $response .= "• Take prescribed pain medications as directed\n";
            $response .= "• Gentle movement and stretching\n\n";
            $response .= "🔹 **Long-term Management:**\n";
            $response .= "• Stay consistent with DMARD medications\n";
            $response .= "• Regular low-impact exercise\n";
            $response .= "• Stress reduction techniques\n\n";
            
            if ($context && isset($context['pain_level']) && $context['pain_level'] >= 7) {
                $response .= "⚠️ I see your recent pain level was {$context['pain_level']}/10. If pain is severe or worsening, please contact your rheumatologist immediately.\n\n";
            }
            
            $response .= "💡 Remember: This is general guidance. Always consult your healthcare provider for personalized medical advice.";
            return $response;
        }

        // Medication queries
        if (preg_match('/\b(medication|medicine|drug|pill|methotrexate|dmard|biologic)\b/', $message)) {
            $response = "Here's important information about RA medications:\n\n";
            $response .= "🔹 **DMARDs (Disease-Modifying Drugs):**\n";
            $response .= "• Methotrexate is often the first-line treatment\n";
            $response .= "• Take exactly as prescribed, usually weekly\n";
            $response .= "• Regular blood tests are essential\n\n";
            $response .= "🔹 **Biologics:**\n";
            $response .= "• Target specific immune system components\n";
            $response .= "• May increase infection risk\n";
            $response .= "• Highly effective for many patients\n\n";
            $response .= "🔹 **Key Reminders:**\n";
            $response .= "• Never stop medications without consulting your doctor\n";
            $response .= "• Report side effects immediately\n";
            $response .= "• Keep regular follow-up appointments\n\n";
            $response .= "💡 Always discuss medication concerns with your rheumatologist.";
            return $response;
        }

        // Exercise queries
        if (preg_match('/\b(exercise|workout|physical activity|movement|gym|walk|swim)\b/', $message)) {
            $response = "Exercise is crucial for RA management! Here are AI-recommended activities:\n\n";
            $response .= "🔹 **Best Exercises for RA:**\n";
            $response .= "• Swimming - excellent for joints\n";
            $response .= "• Walking - start with 10-15 minutes\n";
            $response .= "• Yoga - improves flexibility and strength\n";
            $response .= "• Tai Chi - gentle, flowing movements\n";
            $response .= "• Cycling - low impact cardio\n\n";
            $response .= "🔹 **Exercise Tips:**\n";
            $response .= "• Start slowly and build gradually\n";
            $response .= "• Listen to your body\n";
            $response .= "• Avoid high-impact activities during flares\n";
            $response .= "• Warm up before and cool down after\n\n";
            
            if ($context && isset($context['stiffness_level']) && $context['stiffness_level'] >= 6) {
                $response .= "💡 Given your recent stiffness levels, focus on gentle range-of-motion exercises and stretching.\n\n";
            }
            
            $response .= "⚠️ Consult your healthcare team before starting new exercise programs.";
            return $response;
        }

        // Diet and nutrition
        if (preg_match('/\b(diet|food|eat|nutrition|meal|anti-inflammatory)\b/', $message)) {
            $response = "Nutrition plays a key role in managing RA inflammation:\n\n";
            $response .= "🔹 **Anti-Inflammatory Foods:**\n";
            $response .= "• Fatty fish (salmon, sardines, mackerel)\n";
            $response .= "• Colorful fruits and vegetables\n";
            $response .= "• Nuts and seeds\n";
            $response .= "• Olive oil and avocados\n";
            $response .= "• Whole grains and legumes\n\n";
            $response .= "🔹 **Foods to Limit:**\n";
            $response .= "• Processed and fried foods\n";
            $response .= "• Sugary drinks and snacks\n";
            $response .= "• Excessive red meat\n";
            $response .= "• Refined carbohydrates\n\n";
            $response .= "🔹 **Hydration:**\n";
            $response .= "• Drink 8-10 glasses of water daily\n";
            $response .= "• Green tea has anti-inflammatory properties\n\n";
            $response .= "💡 Consider consulting a registered dietitian for personalized nutrition advice.";
            return $response;
        }

        // Flare management
        if (preg_match('/\b(flare|flare-up|worse|worsening|attack|inflammation)\b/', $message)) {
            $response = "Managing RA flares requires immediate attention:\n\n";
            $response .= "🔹 **Immediate Actions:**\n";
            $response .= "• Rest the affected joints\n";
            $response .= "• Apply ice to reduce swelling\n";
            $response .= "• Take prescribed medications as directed\n";
            $response .= "• Contact your rheumatologist\n\n";
            $response .= "🔹 **Flare Prevention:**\n";
            $response .= "• Maintain consistent medication schedule\n";
            $response .= "• Manage stress levels\n";
            $response .= "• Get adequate sleep (7-9 hours)\n";
            $response .= "• Avoid known triggers\n\n";
            $response .= "🔹 **When to Seek Help:**\n";
            $response .= "• Severe joint pain or swelling\n";
            $response .= "• Fever or signs of infection\n";
            $response .= "• Unable to move affected joints\n";
            $response .= "• Symptoms don't improve in 2-3 days\n\n";
            $response .= "⚠️ Don't wait - early intervention during flares is crucial!";
            return $response;
        }

        // Sleep and fatigue
        if (preg_match('/\b(sleep|tired|fatigue|exhausted|rest|insomnia)\b/', $message)) {
            $response = "Sleep and fatigue management are essential for RA:\n\n";
            $response .= "🔹 **Better Sleep Habits:**\n";
            $response .= "• Maintain consistent sleep schedule\n";
            $response .= "• Create comfortable sleep environment\n";
            $response .= "• Use supportive pillows for joints\n";
            $response .= "• Avoid screens 1 hour before bed\n";
            $response .= "• Keep bedroom cool and dark\n\n";
            $response .= "🔹 **Managing Fatigue:**\n";
            $response .= "• Pace activities throughout the day\n";
            $response .= "• Take short rest breaks\n";
            $response .= "• Prioritize important tasks\n";
            $response .= "• Ask for help when needed\n\n";
            
            if ($context && isset($context['fatigue_level']) && $context['fatigue_level'] >= 7) {
                $response .= "💡 Your recent fatigue level was {$context['fatigue_level']}/10. This may indicate active inflammation - discuss with your doctor.\n\n";
            }
            
            $response .= "⚠️ Persistent fatigue may indicate disease activity. Consult your healthcare provider.";
            return $response;
        }

        // Stress management
        if (preg_match('/\b(stress|anxiety|worried|overwhelmed|mental health|depression)\b/', $message)) {
            $response = "Stress management is crucial for RA - stress can trigger flares:\n\n";
            $response .= "🔹 **Stress Reduction Techniques:**\n";
            $response .= "• Deep breathing exercises (4-7-8 technique)\n";
            $response .= "• Meditation and mindfulness apps\n";
            $response .= "• Progressive muscle relaxation\n";
            $response .= "• Gentle yoga or tai chi\n\n";
            $response .= "🔹 **Social Support:**\n";
            $response .= "• Connect with RA support groups\n";
            $response .= "• Talk to friends and family\n";
            $response .= "• Consider counseling or therapy\n";
            $response .= "• Join online RA communities\n\n";
            $response .= "🔹 **Lifestyle Adjustments:**\n";
            $response .= "• Set realistic daily goals\n";
            $response .= "• Practice saying 'no' to overcommitment\n";
            $response .= "• Engage in enjoyable hobbies\n";
            $response .= "• Limit news and social media if overwhelming\n\n";
            $response .= "💡 Remember: It's okay to have difficult days. Seek professional help if stress becomes overwhelming.";
            return $response;
        }

        return null; // No specific response found
    }

    private function addContextualGreeting(?array $context): string
    {
        if (!$context) return "";
        
        $greeting = "";
        if (isset($context['pain_level']) && $context['pain_level'] >= 6) {
            $greeting .= "I see you've been experiencing some pain recently. ";
        }
        if (isset($context['fatigue_level']) && $context['fatigue_level'] >= 6) {
            $greeting .= "I notice you've been feeling fatigued. ";
        }
        if (isset($context['stiffness_level']) && $context['stiffness_level'] >= 6) {
            $greeting .= "Your recent logs show some stiffness. ";
        }
        
        if (!empty($greeting)) {
            $greeting .= "I'm here to help you manage these symptoms. ";
        }
        
        return $greeting;
    }

    private function getHuggingFaceResponse(string $userMessage): string
    {
        // For now, we'll use our intelligent local responses as they're more reliable
        // Hugging Face free tier can be inconsistent
        return $this->getLocalResponse($userMessage);
    }

    private function getLocalResponse(string $userMessage): string
    {
        $responses = [
            "I'm here to help you manage your rheumatoid arthritis. Could you tell me more about what you'd like to know?",
            "As your AI RA assistant, I can provide information about symptoms, medications, exercise, and lifestyle management. What specific area interests you?",
            "I understand living with RA can be challenging. I'm here to provide evidence-based information to help you manage your condition better. What would you like to discuss?",
            "Let me help you with your RA management questions. I can assist with pain management, medication information, exercise tips, and more. What's on your mind today?"
        ];
        
        return $responses[array_rand($responses)];
    }

    public function isConfigured(): bool
    {
        return true; // Always available since we use local intelligence
    }
}
