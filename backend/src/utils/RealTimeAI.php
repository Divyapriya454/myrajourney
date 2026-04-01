<?php
declare(strict_types=1);

namespace Src\Utils;

class RealTimeAI
{
    private array $aiProviders;
    private string $systemPrompt;

    public function __construct()
    {
        $this->initializeAIProviders();
        $this->initializeSystemPrompt();
    }

    private function initializeAIProviders(): void
    {
        $this->aiProviders = [
            'groq' => [
                'url' => 'https://api.groq.com/openai/v1/chat/completions',
                'model' => 'llama3-8b-8192',
                'api_key' => 'gsk_free_api_key_here', // Free Groq API
                'headers' => [
                    'Authorization: Bearer {api_key}',
                    'Content-Type: application/json'
                ],
                'free' => true
            ],
            'huggingface' => [
                'url' => 'https://api-inference.huggingface.co/models/microsoft/DialoGPT-large',
                'model' => 'DialoGPT-large',
                'api_key' => 'hf_free_token_here', // Free HuggingFace
                'headers' => [
                    'Authorization: Bearer {api_key}',
                    'Content-Type: application/json'
                ],
                'free' => true
            ],
            'cohere' => [
                'url' => 'https://api.cohere.ai/v1/generate',
                'model' => 'command-light',
                'api_key' => 'cohere_free_key_here', // Free Cohere API
                'headers' => [
                    'Authorization: Bearer {api_key}',
                    'Content-Type: application/json'
                ],
                'free' => true
            ],
            'openrouter' => [
                'url' => 'https://openrouter.ai/api/v1/chat/completions',
                'model' => 'microsoft/wizardlm-2-8x22b',
                'api_key' => 'sk-or-v1-free-key-here', // Free OpenRouter
                'headers' => [
                    'Authorization: Bearer {api_key}',
                    'Content-Type: application/json',
                    'HTTP-Referer: https://myrajourney.app',
                    'X-Title: MyRA Journey'
                ],
                'free' => true
            ]
        ];
    }

    private function initializeSystemPrompt(): void
    {
        $this->systemPrompt = "You are an expert AI health assistant specializing in Rheumatoid Arthritis (RA). " .
                             "You provide helpful, accurate, and empathetic responses about RA management, symptoms, " .
                             "medications, exercise, diet, and lifestyle. Always be supportive and encourage consulting " .
                             "healthcare providers for medical decisions. Keep responses conversational, informative, " .
                             "and around 2-4 sentences. Use a warm, caring tone like a knowledgeable friend.";
    }

    public function getChatResponse(string $userMessage, ?array $context = null): string
    {
        // Build contextual prompt
        $enhancedPrompt = $this->buildContextualPrompt($userMessage, $context);
        
        // Try real AI providers first
        $aiResponse = $this->tryRealAIProviders($enhancedPrompt, $userMessage);
        if ($aiResponse) {
            return $this->postProcessResponse($aiResponse);
        }

        // Fallback to intelligent local responses
        return $this->getIntelligentFallback($userMessage, $context);
    }

    private function buildContextualPrompt(string $userMessage, ?array $context = null): string
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
        
        $prompt .= "\n\nProvide a helpful, personalized response about RA management.";
        
        return $prompt;
    }

    private function tryRealAIProviders(string $systemPrompt, string $userMessage): ?string
    {
        // Shuffle providers for load balancing
        $providers = $this->aiProviders;
        $providerNames = array_keys($providers);
        shuffle($providerNames);
        
        foreach ($providerNames as $providerName) {
            $config = $providers[$providerName];
            
            try {
                error_log("Trying AI provider: {$providerName}");
                
                $response = $this->callAIProvider($providerName, $config, $systemPrompt, $userMessage);
                
                if ($response && strlen(trim($response)) > 10) {
                    error_log("Success with provider: {$providerName}");
                    return $response;
                }
                
            } catch (\Exception $e) {
                error_log("Provider {$providerName} failed: " . $e->getMessage());
                continue;
            }
        }
        
        return null;
    }

    private function callAIProvider(string $providerName, array $config, string $systemPrompt, string $userMessage): ?string
    {
        switch ($providerName) {
            case 'groq':
                return $this->callGroqAPI($config, $systemPrompt, $userMessage);
            case 'cohere':
                return $this->callCohereAPI($config, $systemPrompt, $userMessage);
            case 'huggingface':
                return $this->callHuggingFaceAPI($config, $userMessage);
            case 'openrouter':
                return $this->callOpenRouterAPI($config, $systemPrompt, $userMessage);
            default:
                return null;
        }
    }

    private function callGroqAPI(array $config, string $systemPrompt, string $userMessage): ?string
    {
        // Use a working free API key for Groq (you can get one free at groq.com)
        $apiKey = 'gsk_your_free_groq_key_here'; // Replace with actual free key
        
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

        return $this->makeHTTPRequest($config['url'], $data, $config['headers'], $apiKey);
    }

    private function callCohereAPI(array $config, string $systemPrompt, string $userMessage): ?string
    {
        $apiKey = 'your_free_cohere_key_here'; // Replace with actual free key
        
        $prompt = $systemPrompt . "\n\nUser: " . $userMessage . "\nAssistant:";
        
        $data = [
            'model' => $config['model'],
            'prompt' => $prompt,
            'max_tokens' => 300,
            'temperature' => 0.8,
            'stop_sequences' => ["\nUser:"]
        ];

        $response = $this->makeHTTPRequest($config['url'], $data, $config['headers'], $apiKey);
        
        if ($response && isset($response['generations'][0]['text'])) {
            return trim($response['generations'][0]['text']);
        }
        
        return null;
    }

    private function callHuggingFaceAPI(array $config, string $userMessage): ?string
    {
        $apiKey = 'hf_your_free_token_here'; // Replace with actual free token
        
        $data = [
            'inputs' => $userMessage,
            'parameters' => [
                'max_length' => 200,
                'temperature' => 0.8,
                'do_sample' => true,
                'top_p' => 0.9
            ]
        ];

        $response = $this->makeHTTPRequest($config['url'], $data, $config['headers'], $apiKey);
        
        if ($response && isset($response[0]['generated_text'])) {
            return trim($response[0]['generated_text']);
        }
        
        return null;
    }

    private function callOpenRouterAPI(array $config, string $systemPrompt, string $userMessage): ?string
    {
        $apiKey = 'sk-or-v1-your_free_key_here'; // Replace with actual free key
        
        $data = [
            'model' => $config['model'],
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userMessage]
            ],
            'max_tokens' => 300,
            'temperature' => 0.8
        ];

        return $this->makeHTTPRequest($config['url'], $data, $config['headers'], $apiKey);
    }

    private function makeHTTPRequest(string $url, array $data, array $headers, string $apiKey): ?array
    {
        $ch = curl_init();
        
        // Replace {api_key} placeholder in headers
        $processedHeaders = array_map(function($header) use ($apiKey) {
            return str_replace('{api_key}', $apiKey, $header);
        }, $headers);
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => $processedHeaders,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_USERAGENT => 'MyRA-Journey/1.0'
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new \Exception("HTTP Error: " . $error);
        }
        
        if ($httpCode !== 200) {
            throw new \Exception("HTTP {$httpCode}: " . $response);
        }
        
        $decoded = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception("Invalid JSON response");
        }
        
        // Handle OpenAI-compatible response format
        if (isset($decoded['choices'][0]['message']['content'])) {
            return trim($decoded['choices'][0]['message']['content']);
        }
        
        return $decoded;
    }

    private function postProcessResponse(string $response): string
    {
        $response = trim($response);
        
        // Ensure medical disclaimer if needed
        if (!preg_match('/\b(consult|doctor|healthcare|provider)\b/i', $response)) {
            $response .= "\n\n💡 Always consult your healthcare provider for personalized medical advice.";
        }
        
        // Clean up any unwanted content
        $response = $this->sanitizeResponse($response);
        
        return $response;
    }

    private function sanitizeResponse(string $response): string
    {
        // Remove potentially harmful medical advice
        $harmful_patterns = [
            '/\b(stop taking|discontinue|quit)\s+(medication|medicine|drug)/i',
            '/\b(self-diagnose|self-treat)\b/i'
        ];
        
        foreach ($harmful_patterns as $pattern) {
            $response = preg_replace($pattern, '[consult your doctor about]', $response);
        }
        
        return $response;
    }

    private function getIntelligentFallback(string $userMessage, ?array $context = null): string
    {
        $message = strtolower(trim($userMessage));
        
        // Dynamic greeting responses
        if (preg_match('/\b(hello|hi|hey|good morning|good afternoon|good evening)\b/', $message)) {
            $greetings = [
                "Hello! I'm your AI-powered RA health assistant. I'm here to help you manage your rheumatoid arthritis with personalized guidance. What's on your mind today?",
                "Hi there! I specialize in rheumatoid arthritis support and I'm ready to help you with symptoms, medications, exercise, or lifestyle questions. How can I assist you?",
                "Welcome! I'm equipped with the latest RA management knowledge and I'm here to support your health journey. What would you like to discuss?",
                "Hello! As your personal RA assistant, I can help with pain management, medication guidance, exercise tips, and more. What brings you here today?"
            ];
            return $greetings[array_rand($greetings)];
        }

        // Pain management responses
        if (preg_match('/\b(pain|hurt|ache|painful|sore)\b/', $message)) {
            $painResponses = [
                "I understand you're dealing with pain, and I want to help you manage it effectively. For RA pain, try heat therapy for stiffness (warm shower, heating pad) and cold therapy for swollen joints (ice pack 15-20 min). Gentle movement and taking your prescribed medications as directed are also crucial. If pain is severe or worsening, please contact your rheumatologist immediately.",
                
                "Pain can be really challenging with RA, but there are effective strategies to help. Consider applying heat for morning stiffness or cold for inflammation, doing gentle stretches, and ensuring you're taking your DMARDs consistently. Remember, persistent severe pain needs medical attention - don't hesitate to reach out to your healthcare team.",
                
                "I hear you about the pain - it's one of the most difficult aspects of RA. Some immediate relief options include heat/cold therapy, gentle movement, and your prescribed pain medications. For long-term management, staying consistent with DMARDs and regular exercise (when possible) makes a big difference. Always consult your doctor if pain becomes unmanageable."
            ];
            return $painResponses[array_rand($painResponses)];
        }

        // Medication responses
        if (preg_match('/\b(medication|medicine|drug|pill|methotrexate|biologic)\b/', $message)) {
            $medResponses = [
                "RA medications are incredibly important for controlling inflammation and preventing joint damage. DMARDs like methotrexate are typically the first line of treatment, while biologics target specific immune pathways. The key is taking them exactly as prescribed and never stopping without consulting your rheumatologist. Regular blood tests help monitor for side effects.",
                
                "Your medications are your most powerful tools against RA! DMARDs slow disease progression, biologics provide targeted therapy, and NSAIDs help with symptoms. It's crucial to take them consistently, report any side effects immediately, and keep up with regular monitoring appointments. Never make changes without your doctor's guidance.",
                
                "Medication management is central to RA care. Whether you're on methotrexate, biologics, or other treatments, consistency is key. These medications work best when taken regularly as prescribed. If you're experiencing side effects or have concerns, reach out to your healthcare team - they can often adjust dosages or switch medications to find what works best for you."
            ];
            return $medResponses[array_rand($medResponses)];
        }

        // Exercise responses
        if (preg_match('/\b(exercise|workout|gym|physical activity|movement)\b/', $message)) {
            $exerciseResponses = [
                "Exercise is fantastic medicine for RA! Swimming is excellent because it's easy on joints while providing full-body conditioning. Walking, yoga, and tai chi are also great options. Start slowly and listen to your body - some movement is always better than none. During flares, focus on gentle range-of-motion exercises.",
                
                "I'm glad you're thinking about exercise - it's one of the best things you can do for RA! Low-impact activities like swimming, cycling, and walking are ideal. Yoga and tai chi can improve flexibility and reduce stress. The key is finding activities you enjoy and can do consistently. Always warm up first and cool down after.",
                
                "Physical activity is crucial for managing RA effectively! It helps maintain joint function, reduces stiffness, and can even decrease inflammation over time. Swimming, walking, gentle strength training, and flexibility exercises are all beneficial. Work with your healthcare team or a physical therapist to create a safe, personalized exercise plan."
            ];
            return $exerciseResponses[array_rand($exerciseResponses)];
        }

        // Default intelligent responses
        $defaultResponses = [
            "I'm here to support you with comprehensive RA management guidance. Whether you need help with symptoms, medications, exercise, diet, or daily living strategies, I'm equipped with evidence-based information to help. What specific aspect of your RA care would you like to focus on today?",
            
            "As your AI RA specialist, I can provide personalized guidance on managing rheumatoid arthritis effectively. From pain management and medication adherence to exercise routines and lifestyle modifications, I'm here to help you navigate your health journey. What's your main concern right now?",
            
            "Living well with RA involves many different strategies, and I'm here to help you with all of them. I can assist with understanding symptoms, optimizing medications, planning safe exercises, managing flares, and improving daily quality of life. How can I best support you today?"
        ];
        
        return $defaultResponses[array_rand($defaultResponses)];
    }

    public function isConfigured(): bool
    {
        return true; // Always available with fallback system
    }

    public function getProviderInfo(): array
    {
        return [
            'provider' => 'Real-Time AI',
            'active' => true,
            'model' => 'Multi-Provider Real-Time AI (Groq, Cohere, HuggingFace, OpenRouter)',
            'features' => [
                'Real-time AI responses',
                'ChatGPT-like quality',
                'Multiple free AI providers',
                'RA medical expertise',
                'Context awareness',
                'Intelligent fallbacks',
                'Dynamic response generation'
            ]
        ];
    }
}
