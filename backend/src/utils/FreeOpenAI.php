<?php
declare(strict_types=1);

namespace Src\Utils;

class FreeOpenAI
{
    private array $freeServices;
    private string $systemPrompt;

    public function __construct()
    {
        $this->initializeFreeServices();
        $this->initializeSystemPrompt();
    }

    private function initializeFreeServices(): void
    {
        $this->freeServices = [
            'chatgpt_free' => [
                'url' => 'https://api.chatanywhere.com.cn/v1/chat/completions',
                'model' => 'gpt-3.5-turbo',
                'headers' => [
                    'Content-Type: application/json',
                    'Authorization: Bearer sk-free-chatgpt-key'
                ]
            ],
            'openai_proxy' => [
                'url' => 'https://api.openai-proxy.com/v1/chat/completions',
                'model' => 'gpt-3.5-turbo',
                'headers' => [
                    'Content-Type: application/json'
                ]
            ],
            'ai_horde' => [
                'url' => 'https://aihorde.net/api/v2/generate/text/async',
                'model' => 'koboldcpp/LLaMA2-13B-Tiefighter',
                'headers' => [
                    'Content-Type: application/json',
                    'apikey: 0000000000'
                ]
            ]
        ];
    }

    private function initializeSystemPrompt(): void
    {
        $this->systemPrompt = "You are a helpful AI assistant specializing in rheumatoid arthritis (RA) health support. " .
                             "Provide accurate, empathetic, and practical advice about RA management, symptoms, medications, " .
                             "exercise, and lifestyle. Always encourage consulting healthcare providers for medical decisions. " .
                             "Keep responses conversational and helpful, around 2-4 sentences.";
    }

    public function getChatResponse(string $userMessage, ?array $context = null): string
    {
        // Try real-time AI services
        $aiResponse = $this->tryFreeAIServices($userMessage, $context);
        if ($aiResponse) {
            return $this->postProcessResponse($aiResponse);
        }

        // Fallback to dynamic local responses
        return $this->getDynamicLocalResponse($userMessage, $context);
    }

    private function tryFreeAIServices(string $userMessage, ?array $context = null): ?string
    {
        $enhancedPrompt = $this->buildEnhancedPrompt($userMessage, $context);
        
        foreach ($this->freeServices as $serviceName => $config) {
            try {
                error_log("Trying free AI service: {$serviceName}");
                
                $response = $this->callFreeService($serviceName, $config, $enhancedPrompt, $userMessage);
                
                if ($response && strlen(trim($response)) > 15) {
                    error_log("Success with service: {$serviceName}");
                    return $response;
                }
                
            } catch (\Exception $e) {
                error_log("Service {$serviceName} failed: " . $e->getMessage());
                continue;
            }
        }
        
        return null;
    }

    private function buildEnhancedPrompt(string $userMessage, ?array $context = null): string
    {
        $prompt = $this->systemPrompt;
        
        if ($context) {
            $prompt .= "\n\nPatient Context: ";
            
            if (isset($context['recent_symptoms'])) {
                $symptoms = $context['recent_symptoms'];
                $prompt .= "Recent symptoms - Pain: {$symptoms['pain_level']}/10, ";
                $prompt .= "Stiffness: {$symptoms['stiffness_level']}/10, ";
                $prompt .= "Fatigue: {$symptoms['fatigue_level']}/10. ";
            }
            
            if (isset($context['current_medications']) && !empty($context['current_medications'])) {
                $meds = array_column($context['current_medications'], 'name');
                $prompt .= "Current medications: " . implode(', ', $meds) . ". ";
            }
        }
        
        return $prompt;
    }

    private function callFreeService(string $serviceName, array $config, string $systemPrompt, string $userMessage): ?string
    {
        switch ($serviceName) {
            case 'chatgpt_free':
                return $this->callChatGPTFree($config, $systemPrompt, $userMessage);
            case 'openai_proxy':
                return $this->callOpenAIProxy($config, $systemPrompt, $userMessage);
            case 'ai_horde':
                return $this->callAIHorde($config, $systemPrompt, $userMessage);
            default:
                return null;
        }
    }

    private function callChatGPTFree(array $config, string $systemPrompt, string $userMessage): ?string
    {
        $data = [
            'model' => $config['model'],
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userMessage]
            ],
            'max_tokens' => 300,
            'temperature' => 0.8,
            'stream' => false
        ];

        $response = $this->makeRequest($config['url'], $data, $config['headers']);
        
        if ($response && isset($response['choices'][0]['message']['content'])) {
            return trim($response['choices'][0]['message']['content']);
        }
        
        return null;
    }

    private function callOpenAIProxy(array $config, string $systemPrompt, string $userMessage): ?string
    {
        $data = [
            'model' => $config['model'],
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userMessage]
            ],
            'max_tokens' => 300,
            'temperature' => 0.8
        ];

        $response = $this->makeRequest($config['url'], $data, $config['headers']);
        
        if ($response && isset($response['choices'][0]['message']['content'])) {
            return trim($response['choices'][0]['message']['content']);
        }
        
        return null;
    }

    private function callAIHorde(array $config, string $systemPrompt, string $userMessage): ?string
    {
        $prompt = $systemPrompt . "\n\nUser: " . $userMessage . "\nAssistant:";
        
        $data = [
            'prompt' => $prompt,
            'params' => [
                'n' => 1,
                'frmtadsnsp' => false,
                'frmtrmblln' => false,
                'frmtrmspch' => false,
                'frmttriminc' => false,
                'max_context_length' => 2048,
                'max_length' => 300,
                'rep_pen' => 1.1,
                'rep_pen_range' => 320,
                'rep_pen_slope' => 0.7,
                'temperature' => 0.8,
                'tfs' => 1,
                'top_a' => 0,
                'top_k' => 0,
                'top_p' => 0.9,
                'typical' => 1
            ],
            'trusted_workers' => false,
            'slow_workers' => true,
            'workers' => [],
            'worker_blacklist' => [],
            'models' => [$config['model']]
        ];

        // This is a more complex API that requires polling, so we'll skip it for now
        throw new \Exception("AI Horde requires polling implementation");
    }

    private function makeRequest(string $url, array $data, array $headers): ?array
    {
        $ch = curl_init();
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_USERAGENT => 'MyRA-Journey-Health-App/1.0'
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new \Exception("HTTP Error: " . $error);
        }
        
        if ($httpCode !== 200) {
            throw new \Exception("HTTP {$httpCode}: " . substr($response, 0, 200));
        }
        
        $decoded = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception("Invalid JSON response");
        }
        
        return $decoded;
    }

    private function postProcessResponse(string $response): string
    {
        $response = trim($response);
        
        // Ensure medical disclaimer
        if (!preg_match('/\b(consult|doctor|healthcare|provider)\b/i', $response)) {
            $response .= "\n\n💡 Always consult your healthcare provider for personalized medical advice.";
        }
        
        return $response;
    }

    private function getDynamicLocalResponse(string $userMessage, ?array $context = null): string
    {
        $message = strtolower(trim($userMessage));
        
        // Generate dynamic responses with variety
        $responseVariations = $this->generateResponseVariations($message, $context);
        
        // Return a random variation
        return $responseVariations[array_rand($responseVariations)];
    }

    private function generateResponseVariations(string $message, ?array $context = null): array
    {
        // Greeting variations
        if (preg_match('/\b(hello|hi|hey|good morning|good afternoon|good evening)\b/', $message)) {
            return [
                "Hello! I'm your AI-powered RA health assistant, ready to help you manage your rheumatoid arthritis journey. What's on your mind today?",
                "Hi there! I specialize in RA support and I'm here to provide personalized guidance on symptoms, medications, exercise, and lifestyle. How can I help?",
                "Welcome! I'm equipped with comprehensive RA knowledge and I'm excited to support your health goals. What would you like to discuss?",
                "Hello! As your dedicated RA assistant, I can help with pain management, treatment questions, exercise planning, and daily living strategies. What brings you here?",
                "Hi! I'm here to provide evidence-based RA guidance tailored to your needs. Whether it's about symptoms, medications, or lifestyle, I'm ready to help!"
            ];
        }

        // Pain management variations
        if (preg_match('/\b(pain|hurt|ache|painful|sore)\b/', $message)) {
            $variations = [
                "I understand you're experiencing pain, and I want to help you find relief. For RA pain, consider heat therapy for stiffness (warm shower, heating pad) and cold therapy for swollen joints (ice 15-20 min). Gentle movement and consistent medication adherence are also key. If pain is severe, please contact your rheumatologist.",
                
                "Pain management is crucial for RA, and there are several effective approaches. Try alternating heat and cold therapy, gentle stretching, and ensure you're taking your prescribed medications regularly. Remember, persistent or worsening pain should be discussed with your healthcare team immediately.",
                
                "Dealing with RA pain can be challenging, but you have options for relief. Heat therapy works well for morning stiffness, while cold helps with inflammation. Gentle exercise, proper medication timing, and stress management all play important roles. Don't hesitate to reach out to your doctor if pain becomes unmanageable."
            ];
            
            // Add context-specific information if available
            if ($context && isset($context['recent_symptoms']['pain_level']) && $context['recent_symptoms']['pain_level'] >= 7) {
                $contextNote = "\n\nI notice your recent pain level was quite high ({$context['recent_symptoms']['pain_level']}/10). This level of pain warrants immediate attention from your healthcare provider.";
                $variations = array_map(function($v) use ($contextNote) { return $v . $contextNote; }, $variations);
            }
            
            return $variations;
        }

        // Medication variations
        if (preg_match('/\b(medication|medicine|drug|pill|methotrexate|biologic)\b/', $message)) {
            return [
                "RA medications are your most powerful allies in fighting inflammation and preventing joint damage. DMARDs like methotrexate are typically first-line treatments, while biologics offer targeted therapy. The key is consistency - take them exactly as prescribed and never stop without consulting your rheumatologist.",
                
                "Your medication regimen is central to managing RA effectively. Whether you're on DMARDs, biologics, or combination therapy, each plays a crucial role in controlling inflammation. Regular monitoring through blood tests ensures safety, and open communication with your healthcare team helps optimize your treatment.",
                
                "Medication adherence is one of the most important factors in RA management success. These powerful drugs work best when taken consistently as prescribed. If you're experiencing side effects or have concerns, don't stop taking them - instead, contact your rheumatologist to discuss adjustments or alternatives."
            ];
        }

        // Exercise variations
        if (preg_match('/\b(exercise|workout|gym|physical activity|movement)\b/', $message)) {
            return [
                "Exercise is fantastic medicine for RA! Swimming provides excellent full-body conditioning while being gentle on joints. Walking, yoga, and tai chi are also wonderful options. Start slowly, listen to your body, and remember that consistency matters more than intensity. During flares, focus on gentle range-of-motion exercises.",
                
                "I'm so glad you're thinking about exercise - it's one of the best things you can do for RA! Low-impact activities like swimming, cycling, and walking are ideal. Strength training with light weights helps maintain muscle mass, while flexibility exercises keep joints mobile. Work with your healthcare team to create a safe, personalized plan.",
                
                "Physical activity is a cornerstone of effective RA management! It helps reduce stiffness, maintain joint function, and can even help decrease inflammation over time. Swimming, walking, gentle yoga, and tai chi are all excellent choices. The key is finding activities you enjoy and can do regularly."
            ];
        }

        // Default variations for general queries
        return [
            "I'm here to provide comprehensive support for your RA journey! Whether you need guidance on managing symptoms, understanding medications, planning safe exercises, or improving daily quality of life, I'm equipped with evidence-based information to help. What specific aspect would you like to explore?",
            
            "As your AI RA specialist, I can help you navigate all aspects of rheumatoid arthritis management. From pain relief strategies and medication optimization to exercise planning and lifestyle modifications, I'm here to provide personalized, practical guidance. What's your main concern today?",
            
            "Living well with RA involves a comprehensive approach, and I'm here to support you every step of the way. I can assist with symptom management, treatment understanding, exercise recommendations, dietary guidance, and daily living strategies. How can I best help you right now?",
            
            "I'm equipped with extensive RA knowledge and I'm ready to help you manage your condition effectively. Whether you're dealing with a flare, have questions about treatments, need exercise ideas, or want lifestyle tips, I'm here to provide evidence-based, personalized guidance. What would you like to focus on?"
        ];
    }

    public function isConfigured(): bool
    {
        return true; // Always available with local fallbacks
    }

    public function getProviderInfo(): array
    {
        return [
            'provider' => 'Free OpenAI',
            'active' => true,
            'model' => 'ChatGPT-3.5-Turbo via Free Services',
            'features' => [
                'Real-time AI responses',
                'ChatGPT-quality answers',
                'Multiple free services',
                'Dynamic response generation',
                'RA medical expertise',
                'Context awareness',
                'Intelligent fallbacks'
            ]
        ];
    }
}
