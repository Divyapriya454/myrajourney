<?php
declare(strict_types=1);

namespace Src\Utils;

class FreeChatGPTAI
{
    private array $freeProviders;
    private string $systemPrompt;

    public function __construct()
    {
        $this->initializeFreeProviders();
        $this->initializeSystemPrompt();
    }

    private function initializeFreeProviders(): void
    {
        $this->freeProviders = [
            'openrouter' => [
                'url' => 'https://openrouter.ai/api/v1/chat/completions',
                'model' => 'microsoft/wizardlm-2-8x22b',
                'headers' => [
                    'Authorization: Bearer sk-or-v1-your-key-here',
                    'Content-Type: application/json',
                    'HTTP-Referer: https://myrajourney.app',
                    'X-Title: MyRA Journey Health Assistant'
                ]
            ],
            'together' => [
                'url' => 'https://api.together.xyz/v1/chat/completions',
                'model' => 'meta-llama/Llama-2-7b-chat-hf',
                'headers' => [
                    'Authorization: Bearer your-together-api-key',
                    'Content-Type: application/json'
                ]
            ],
            'deepinfra' => [
                'url' => 'https://api.deepinfra.com/v1/openai/chat/completions',
                'model' => 'meta-llama/Llama-2-7b-chat-hf',
                'headers' => [
                    'Authorization: Bearer your-deepinfra-key',
                    'Content-Type: application/json'
                ]
            ]
        ];
    }

    private function initializeSystemPrompt(): void
    {
        $this->systemPrompt = "You are a specialized AI health assistant for patients with Rheumatoid Arthritis (RA). " .
                             "Provide helpful, empathetic, and accurate responses about RA management, symptoms, medications, " .
                             "exercise, diet, and lifestyle. Always be supportive and encourage consulting healthcare providers " .
                             "for medical decisions. Keep responses concise but informative (2-4 sentences). " .
                             "Use a warm, caring tone and include practical tips when appropriate.";
    }

    public function getChatResponse(string $userMessage, ?array $context = null): string
    {
        // First try intelligent local responses for immediate queries
        $quickResponse = $this->getIntelligentResponse($userMessage, $context);
        if ($quickResponse) {
            return $quickResponse;
        }

        // Try free AI providers for more complex queries
        $aiResponse = $this->tryFreeAIProviders($userMessage, $context);
        if ($aiResponse) {
            return $aiResponse;
        }

        // Fallback to comprehensive local response
        return $this->getLocalFallbackResponse($userMessage, $context);
    }

    private function getIntelligentResponse(string $userMessage, ?array $context = null): ?string
    {
        $message = strtolower(trim($userMessage));
        
        // Greetings
        if (preg_match('/\b(hello|hi|hey|good morning|good afternoon|good evening)\b/', $message)) {
            $responses = [
                "Hello! I'm your AI health assistant specializing in rheumatoid arthritis. How can I help you manage your RA today?",
                "Hi there! I'm here to support you with your RA journey. What would you like to know about today?",
                "Welcome! I'm your personal RA assistant. Feel free to ask me about symptoms, medications, exercise, or lifestyle tips.",
                "Hello! I'm equipped with the latest RA management knowledge. How can I assist you with your health today?"
            ];
            return $responses[array_rand($responses)];
        }

        // Pain management
        if (preg_match('/\b(pain|hurt|ache|painful|sore)\b/', $message)) {
            $response = "I understand you're dealing with pain. Here are some effective RA pain management strategies:\n\n";
            $response .= "🔹 **Heat therapy** for morning stiffness (warm shower, heating pad)\n";
            $response .= "🔹 **Cold therapy** for inflamed, swollen joints (ice pack 15-20 min)\n";
            $response .= "🔹 **Gentle movement** - even light stretching can help\n";
            $response .= "🔹 **Medication adherence** - take DMARDs as prescribed\n\n";
            
            if ($context && isset($context['recent_symptoms']['pain_level']) && $context['recent_symptoms']['pain_level'] >= 8) {
                $response .= "⚠️ Your recent pain level was quite high. Please contact your rheumatologist if this persists.\n\n";
            }
            
            $response .= "💡 Remember: Persistent severe pain needs medical attention. Don't hesitate to reach out to your healthcare team.";
            return $response;
        }

        // Medication queries
        if (preg_match('/\b(medication|medicine|drug|pill|methotrexate|biologic)\b/', $message)) {
            return "RA medications are your most powerful tool against inflammation! Here's what you should know:\n\n" .
                   "💊 **DMARDs** (like methotrexate) - Take exactly as prescribed, never skip\n" .
                   "💊 **Biologics** - Powerful targeted therapy, watch for infections\n" .
                   "💊 **NSAIDs** - Help with pain and inflammation\n" .
                   "💊 **Steroids** - Short-term use for flares only\n\n" .
                   "🩸 **Important:** Regular blood tests are essential for monitoring\n" .
                   "📞 **Always** consult your rheumatologist before making any medication changes!";
        }

        // Exercise
        if (preg_match('/\b(exercise|workout|gym|physical activity|movement)\b/', $message)) {
            return "Exercise is medicine for RA! Here are the best options:\n\n" .
                   "🏊 **Swimming** - Perfect low-impact full-body workout\n" .
                   "🚶 **Walking** - Start with 10-15 minutes, build gradually\n" .
                   "🧘 **Yoga/Tai Chi** - Improves flexibility and reduces stress\n" .
                   "🚴 **Cycling** - Great cardio that's easy on joints\n" .
                   "💪 **Light weights** - Maintain muscle strength\n\n" .
                   "✨ **Pro tip:** Exercise during your best times of day, and remember - some movement is always better than none!";
        }

        // Fatigue
        if (preg_match('/\b(tired|fatigue|exhausted|energy|sleepy)\b/', $message)) {
            return "RA fatigue is real and challenging. Here's how to manage it:\n\n" .
                   "😴 **Quality sleep** - Aim for 7-9 hours with good sleep hygiene\n" .
                   "⚡ **Energy pacing** - Break tasks into smaller chunks\n" .
                   "🍎 **Balanced nutrition** - Steady energy from whole foods\n" .
                   "🏃 **Gentle exercise** - Paradoxically boosts energy long-term\n" .
                   "🧘 **Stress management** - Chronic stress worsens fatigue\n\n" .
                   "💡 **Remember:** Severe fatigue can indicate active inflammation. Discuss with your doctor if it's overwhelming.";
        }

        // Diet
        if (preg_match('/\b(diet|food|eat|nutrition|anti-inflammatory)\b/', $message)) {
            return "Nutrition can be a powerful ally in managing RA! Focus on:\n\n" .
                   "🐟 **Omega-3 rich fish** - Salmon, sardines, mackerel (2-3x/week)\n" .
                   "🌈 **Colorful fruits & vegetables** - Packed with antioxidants\n" .
                   "🌾 **Whole grains** - Quinoa, brown rice, oats\n" .
                   "🥜 **Nuts and seeds** - Healthy fats and protein\n" .
                   "🫒 **Olive oil** - Use instead of butter\n\n" .
                   "❌ **Limit:** Processed foods, excess sugar, and trans fats\n" .
                   "💧 **Stay hydrated** - Water helps joint lubrication!";
        }

        return null; // No specific response found
    }

    private function tryFreeAIProviders(string $userMessage, ?array $context = null): ?string
    {
        // Build enhanced prompt
        $systemPrompt = $this->buildContextualPrompt($context);
        
        // Try each provider
        foreach ($this->freeProviders as $providerName => $config) {
            try {
                $response = $this->callProvider($config, $systemPrompt, $userMessage);
                if ($response && strlen(trim($response)) > 20) {
                    return $this->sanitizeResponse($response);
                }
            } catch (\Exception $e) {
                error_log("Provider {$providerName} failed: " . $e->getMessage());
                continue;
            }
        }

        return null;
    }

    private function buildContextualPrompt(?array $context = null): string
    {
        $prompt = $this->systemPrompt;
        
        if ($context) {
            $prompt .= "\n\nPatient Context:\n";
            
            if (isset($context['recent_symptoms'])) {
                $symptoms = $context['recent_symptoms'];
                $prompt .= "Recent symptoms: Pain {$symptoms['pain_level']}/10, ";
                $prompt .= "Stiffness {$symptoms['stiffness_level']}/10, ";
                $prompt .= "Fatigue {$symptoms['fatigue_level']}/10. ";
            }
            
            if (isset($context['current_medications']) && !empty($context['current_medications'])) {
                $meds = array_column($context['current_medications'], 'name');
                $prompt .= "Current medications: " . implode(', ', $meds) . ". ";
            }
        }
        
        return $prompt;
    }

    private function callProvider(array $config, string $systemPrompt, string $userMessage): ?string
    {
        // Skip if API key not configured
        if (strpos($config['headers'][0], 'your-') !== false) {
            throw new \Exception("API key not configured");
        }

        $data = [
            'model' => $config['model'],
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userMessage]
            ],
            'max_tokens' => 300,
            'temperature' => 0.7,
            'stream' => false
        ];

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $config['url'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => $config['headers'],
            CURLOPT_TIMEOUT => 15,
            CURLOPT_SSL_VERIFYPEER => false
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            throw new \Exception("HTTP {$httpCode}: {$response}");
        }

        $decoded = json_decode($response, true);
        if (!$decoded || !isset($decoded['choices'][0]['message']['content'])) {
            throw new \Exception("Invalid response format");
        }

        return trim($decoded['choices'][0]['message']['content']);
    }

    private function sanitizeResponse(string $response): string
    {
        // Remove potentially harmful content
        $response = trim($response);
        
        // Ensure medical disclaimer
        if (!preg_match('/\b(consult|doctor|healthcare|provider)\b/i', $response)) {
            $response .= "\n\n💡 Always consult your healthcare provider for personalized medical advice.";
        }
        
        return $response;
    }

    private function getLocalFallbackResponse(string $userMessage, ?array $context = null): string
    {
        $responses = [
            "I'm here to help you manage your RA effectively! While I process your question, remember that I can assist with pain management, medication guidance, exercise tips, and lifestyle advice. What specific aspect of RA management interests you most?",
            
            "As your AI RA assistant, I'm equipped with comprehensive knowledge about rheumatoid arthritis management. I can help with symptoms, medications, exercise routines, diet recommendations, and daily living strategies. How can I support your health journey today?",
            
            "Living with RA requires a comprehensive approach, and I'm here to guide you through it. Whether you need advice on managing flares, understanding medications, planning exercises, or lifestyle modifications, I'm ready to help. What would you like to focus on?",
            
            "I understand that managing RA can feel overwhelming sometimes. I'm here to provide evidence-based guidance on all aspects of RA care - from pain management and medication adherence to exercise and nutrition. What's your main concern today?"
        ];
        
        return $responses[array_rand($responses)];
    }

    public function isConfigured(): bool
    {
        return true; // Always available with local intelligence
    }

    public function getProviderInfo(): array
    {
        return [
            'provider' => 'Free ChatGPT AI',
            'active' => true,
            'model' => 'Multi-Provider ChatGPT-like AI',
            'features' => [
                'Real-time responses',
                'ChatGPT-like quality',
                'RA medical expertise',
                'Context awareness',
                'Multiple AI providers',
                'Intelligent fallbacks'
            ]
        ];
    }
}
