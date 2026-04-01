<?php
declare(strict_types=1);

namespace Src\Utils;

class EnhancedFreeAI
{
    private array $freeAIProviders;
    private array $medicalKnowledge;
    private string $currentProvider;

    public function __construct()
    {
        $this->initializeFreeAIProviders();
        $this->initializeMedicalKnowledge();
        $this->currentProvider = 'groq'; // Start with Groq as primary
    }

    private function initializeFreeAIProviders(): void
    {
        $this->freeAIProviders = [
            'groq' => [
                'url' => 'https://api.groq.com/openai/v1/chat/completions',
                'model' => 'llama3-8b-8192',
                'api_key' => 'gsk_' . 'your_groq_api_key_here', // Free tier available
                'headers' => [
                    'Authorization: Bearer {api_key}',
                    'Content-Type: application/json'
                ]
            ],
            'cohere' => [
                'url' => 'https://api.cohere.ai/v1/generate',
                'model' => 'command-light',
                'api_key' => 'your_cohere_api_key_here', // Free tier available
                'headers' => [
                    'Authorization: Bearer {api_key}',
                    'Content-Type: application/json'
                ]
            ],
            'huggingface' => [
                'url' => 'https://api-inference.huggingface.co/models/microsoft/DialoGPT-medium',
                'model' => 'DialoGPT-medium',
                'api_key' => 'your_hf_token_here', // Free tier available
                'headers' => [
                    'Authorization: Bearer {api_key}',
                    'Content-Type: application/json'
                ]
            ]
        ];
    }

    private function initializeMedicalKnowledge(): void
    {
        $this->medicalKnowledge = [
            'ra_context' => "You are a specialized AI health assistant for patients with Rheumatoid Arthritis (RA). " .
                          "Provide empathetic, accurate, and helpful responses about RA management, symptoms, medications, " .
                          "exercise, diet, and lifestyle. Always remind users to consult healthcare providers for medical decisions. " .
                          "Keep responses concise but informative (2-4 sentences max). Use emojis sparingly for better readability.",
            
            'safety_guidelines' => [
                'Always recommend consulting healthcare providers for medical decisions',
                'Never diagnose or provide specific medical advice',
                'Emphasize medication adherence and regular monitoring',
                'Encourage evidence-based lifestyle modifications',
                'Be empathetic and supportive'
            ],
            
            'common_topics' => [
                'pain_management' => 'RA pain can be managed through medications, heat/cold therapy, gentle exercise, and stress reduction.',
                'medications' => 'DMARDs like methotrexate are cornerstone treatments. Biologics target specific immune pathways. Always take as prescribed.',
                'exercise' => 'Low-impact activities like swimming, walking, and yoga help maintain joint function and reduce stiffness.',
                'diet' => 'Anti-inflammatory foods (fish, fruits, vegetables) may help reduce inflammation. Stay hydrated.',
                'flares' => 'During flares, rest affected joints, apply ice, take medications as prescribed, and contact your rheumatologist.',
                'fatigue' => 'RA fatigue is common. Pace activities, prioritize rest, and discuss persistent fatigue with your doctor.'
            ]
        ];
    }

    public function getChatResponse(string $userMessage, ?array $context = null): string
    {
        // First, try intelligent local response for immediate common queries
        $quickResponse = $this->getQuickIntelligentResponse($userMessage, $context);
        if ($quickResponse) {
            return $quickResponse;
        }

        // For complex queries, use free AI services
        $aiResponse = $this->getAIResponse($userMessage, $context);
        if ($aiResponse) {
            return $aiResponse;
        }

        // Final fallback to comprehensive local response
        return $this->getComprehensiveLocalResponse($userMessage, $context);
    }

    private function getQuickIntelligentResponse(string $userMessage, ?array $context = null): ?string
    {
        $message = strtolower(trim($userMessage));
        
        // Emergency/urgent responses - highest priority
        if (preg_match('/\b(emergency|urgent|severe pain|can\'t move|fever|infection|chest pain|breathing|911|ambulance)\b/', $message)) {
            return "🚨 **This sounds urgent!** Please contact your doctor immediately or go to the emergency room if you're experiencing:\n" .
                   "• Severe joint pain or inability to move joints\n" .
                   "• Fever with joint symptoms (possible infection)\n" .
                   "• Signs of infection (redness, warmth, pus)\n" .
                   "• Chest pain or breathing difficulties\n" .
                   "• Severe allergic reactions\n\n" .
                   "🏥 **Don't wait - seek immediate medical attention!**\n" .
                   "📞 Call emergency services if life-threatening!";
        }

        // Medication-specific responses
        if (preg_match('/\b(missed dose|forgot medication|skip dose|late dose)\b/', $message)) {
            return "If you missed a dose:\n\n" .
                   "💊 **For DMARDs (like methotrexate):**\n" .
                   "• Take as soon as you remember if it's the same day\n" .
                   "• If it's the next day, skip and take next scheduled dose\n" .
                   "• Never double dose\n\n" .
                   "💊 **For biologics:** Contact your doctor's office for guidance\n" .
                   "💊 **For daily medications:** Take when you remember, then resume normal schedule\n\n" .
                   "⏰ **Prevention:** Set phone alarms or use pill organizers!";
        }

        if (preg_match('/\b(methotrexate|mtx)\b/', $message)) {
            return "**Methotrexate (MTX) - Key Information:**\n\n" .
                   "📅 **Dosing:** Once weekly, same day each week\n" .
                   "🍃 **Folic Acid:** Take as prescribed (usually day after MTX)\n" .
                   "🚫 **Avoid:** Alcohol (increases liver risk)\n" .
                   "🩸 **Monitoring:** Regular blood tests essential\n" .
                   "⚠️ **Report immediately:** Nausea, mouth sores, unusual fatigue, fever\n\n" .
                   "💡 **Tip:** Take with food to reduce stomach upset\n" .
                   "📞 Always follow your doctor's specific instructions!";
        }

        // Side effects - enhanced detection
        if (preg_match('/\b(side effect|nausea|vomit|hair loss|mouth sore|ulcer|liver|rash|infection|fever)\b/', $message)) {
            return "⚠️ **Side effects should be reported to your doctor immediately!**\n\n" .
                   "**Common RA medication side effects:**\n" .
                   "🔹 **Methotrexate:** Nausea, fatigue, mouth sores, hair thinning\n" .
                   "🔹 **Biologics:** Increased infection risk, injection site reactions\n" .
                   "🔹 **NSAIDs:** Stomach upset, kidney/heart concerns\n" .
                   "🔹 **Steroids:** Weight gain, mood changes, bone thinning\n\n" .
                   "🚨 **Call doctor immediately for:** Fever, severe nausea, unusual bleeding, severe rash\n" .
                   "📞 Contact your rheumatologist's office today!";
        }

        // Weather/seasonal - enhanced
        if (preg_match('/\b(weather|cold|rain|winter|barometric|pressure|humidity|storm)\b/', $message)) {
            return "🌦️ **Weather & RA - You're not imagining it!**\n\n" .
                   "Many people with RA are weather-sensitive. Here's how to manage:\n\n" .
                   "**Cold Weather:**\n" .
                   "• Layer clothing, keep joints warm\n" .
                   "• Use heating pads or warm baths\n" .
                   "• Stay active indoors (yoga, stretching)\n\n" .
                   "**Rainy/Low Pressure:**\n" .
                   "• Plan lighter activities on bad weather days\n" .
                   "• Use compression gloves/sleeves\n" .
                   "• Track symptoms vs. weather patterns\n\n" .
                   "💊 **Most important:** Stay consistent with medications regardless of weather!";
        }

        // Sleep and fatigue - enhanced
        if (preg_match('/\b(tired|exhausted|fatigue|sleep|insomnia|can\'t sleep|wake up)\b/', $message)) {
            $response = "😴 **RA Fatigue & Sleep - You're Not Alone!**\n\n";
            
            if ($context && isset($context['recent_symptoms']['fatigue_level']) && $context['recent_symptoms']['fatigue_level'] >= 7) {
                $response .= "I see your recent fatigue level was high ({$context['recent_symptoms']['fatigue_level']}/10). This is concerning.\n\n";
            }
            
            $response .= "**Managing RA Fatigue:**\n" .
                        "🛌 **Sleep Hygiene:** 7-9 hours, consistent schedule\n" .
                        "⚡ **Energy Management:** Pace activities, take breaks\n" .
                        "🏃 **Gentle Exercise:** Helps energy levels long-term\n" .
                        "🍎 **Nutrition:** Balanced meals, avoid sugar crashes\n\n" .
                        "**Better Sleep Tips:**\n" .
                        "• Cool, dark room with supportive pillows\n" .
                        "• No screens 1 hour before bed\n" .
                        "• Warm bath or gentle stretching before sleep\n\n" .
                        "⚠️ **Persistent severe fatigue may indicate active inflammation - discuss with your doctor!**";
            
            return $response;
        }

        // Flare management - enhanced
        if (preg_match('/\b(flare|flare-up|worse|worsening|getting worse|inflammation|swollen)\b/', $message)) {
            return "🔥 **RA Flare Management - Act Fast!**\n\n" .
                   "**Immediate Actions:**\n" .
                   "🛌 **Rest:** Affected joints need immediate rest\n" .
                   "🧊 **Ice:** 15-20 minutes for swollen, hot joints\n" .
                   "💊 **Medications:** Take prescribed flare medications\n" .
                   "📞 **Contact Doctor:** Don't wait - early intervention is key!\n\n" .
                   "**Flare Prevention:**\n" .
                   "• Never skip DMARD medications\n" .
                   "• Manage stress levels\n" .
                   "• Get adequate sleep (7-9 hours)\n" .
                   "• Avoid known triggers\n\n" .
                   "🚨 **Seek immediate help if:** Fever, severe swelling, inability to move joints, or symptoms worsen rapidly!";
        }

        // Exercise queries - enhanced
        if (preg_match('/\b(exercise|workout|gym|walk|swim|yoga|stretch|physical therapy)\b/', $message)) {
            return "🏃 **Exercise & RA - Movement is Medicine!**\n\n" .
                   "**Best RA-Friendly Exercises:**\n" .
                   "🏊 **Swimming:** Excellent full-body, joint-friendly workout\n" .
                   "🚶 **Walking:** Start with 10-15 minutes, build gradually\n" .
                   "🧘 **Yoga/Tai Chi:** Improves flexibility, strength, and stress\n" .
                   "🚴 **Cycling:** Low-impact cardio option\n" .
                   "🏋️ **Light Weights:** Maintain muscle strength\n\n" .
                   "**Exercise Guidelines:**\n" .
                   "• Start slowly, listen to your body\n" .
                   "• Avoid high-impact during flares\n" .
                   "• Warm up before, cool down after\n" .
                   "• Range-of-motion exercises daily\n\n" .
                   "💡 **Consult your healthcare team before starting new programs!**";
        }

        return null; // No quick response available - will use comprehensive AI response
    }

    private function getAIResponse(string $userMessage, ?array $context = null): ?string
    {
        // Build enhanced prompt with context
        $systemPrompt = $this->buildSystemPrompt($context);
        $enhancedMessage = $this->enhanceUserMessage($userMessage, $context);

        // Try each AI provider in order
        $providers = ['groq', 'cohere', 'huggingface'];
        
        foreach ($providers as $provider) {
            try {
                $response = $this->callAIProvider($provider, $systemPrompt, $enhancedMessage);
                if ($response && strlen($response) > 20) { // Valid response
                    return $this->postProcessAIResponse($response);
                }
            } catch (\Exception $e) {
                error_log("AI Provider {$provider} failed: " . $e->getMessage());
                continue; // Try next provider
            }
        }

        return null; // All AI providers failed
    }

    private function buildSystemPrompt(?array $context = null): string
    {
        $prompt = $this->medicalKnowledge['ra_context'];
        
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
        
        $prompt .= "\n\nProvide helpful, empathetic responses about RA management. Keep responses concise (2-4 sentences). " .
                  "Always remind users to consult their healthcare provider for medical decisions.";
        
        return $prompt;
    }

    private function enhanceUserMessage(string $userMessage, ?array $context = null): string
    {
        $enhanced = $userMessage;
        
        // Add context clues to help AI understand better
        if ($context && isset($context['recent_symptoms'])) {
            $symptoms = $context['recent_symptoms'];
            if ($symptoms['pain_level'] >= 7) {
                $enhanced .= " (Note: Patient recently reported high pain levels)";
            }
            if ($symptoms['fatigue_level'] >= 7) {
                $enhanced .= " (Note: Patient experiencing significant fatigue)";
            }
        }
        
        return $enhanced;
    }

    private function callAIProvider(string $provider, string $systemPrompt, string $userMessage): ?string
    {
        $config = $this->freeAIProviders[$provider];
        
        switch ($provider) {
            case 'groq':
                return $this->callGroqAPI($config, $systemPrompt, $userMessage);
            case 'cohere':
                return $this->callCohereAPI($config, $systemPrompt, $userMessage);
            case 'huggingface':
                return $this->callHuggingFaceAPI($config, $userMessage);
            default:
                return null;
        }
    }

    private function callGroqAPI(array $config, string $systemPrompt, string $userMessage): ?string
    {
        // Note: This is a template - you'll need to get a free Groq API key
        if (strpos($config['api_key'], 'your_') !== false) {
            throw new \Exception("Groq API key not configured");
        }

        $data = [
            'model' => $config['model'],
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userMessage]
            ],
            'max_tokens' => 200,
            'temperature' => 0.7
        ];

        $response = $this->makeHTTPRequest($config['url'], $data, $config['headers'], $config['api_key']);
        
        if ($response && isset($response['choices'][0]['message']['content'])) {
            return trim($response['choices'][0]['message']['content']);
        }
        
        return null;
    }

    private function callCohereAPI(array $config, string $systemPrompt, string $userMessage): ?string
    {
        // Note: This is a template - you'll need to get a free Cohere API key
        if (strpos($config['api_key'], 'your_') !== false) {
            throw new \Exception("Cohere API key not configured");
        }

        $prompt = $systemPrompt . "\n\nUser: " . $userMessage . "\nAssistant:";
        
        $data = [
            'model' => $config['model'],
            'prompt' => $prompt,
            'max_tokens' => 200,
            'temperature' => 0.7,
            'stop_sequences' => ["\nUser:"]
        ];

        $response = $this->makeHTTPRequest($config['url'], $data, $config['headers'], $config['api_key']);
        
        if ($response && isset($response['generations'][0]['text'])) {
            return trim($response['generations'][0]['text']);
        }
        
        return null;
    }

    private function callHuggingFaceAPI(array $config, string $userMessage): ?string
    {
        // Simplified HuggingFace call - free tier
        $data = [
            'inputs' => $userMessage,
            'parameters' => [
                'max_length' => 200,
                'temperature' => 0.7,
                'do_sample' => true
            ]
        ];

        try {
            $response = $this->makeHTTPRequest($config['url'], $data, $config['headers'], $config['api_key']);
            
            if ($response && isset($response[0]['generated_text'])) {
                return trim($response[0]['generated_text']);
            }
        } catch (\Exception $e) {
            // HuggingFace free tier can be unreliable
            throw $e;
        }
        
        return null;
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
            CURLOPT_TIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => false
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
        
        return $decoded;
    }

    private function postProcessAIResponse(string $response): string
    {
        // Clean up AI response
        $response = trim($response);
        
        // Remove any potential harmful content
        $response = $this->sanitizeResponse($response);
        
        // Ensure medical disclaimer if needed
        if (!preg_match('/\b(consult|doctor|healthcare|provider)\b/i', $response)) {
            $response .= "\n\n💡 Always consult your healthcare provider for personalized medical advice.";
        }
        
        return $response;
    }

    private function sanitizeResponse(string $response): string
    {
        // Remove potentially harmful content
        $harmful_patterns = [
            '/\b(stop taking|discontinue|quit)\s+(medication|medicine|drug)/i',
            '/\b(diagnose|diagnosis)\b/i',
            '/\b(cure|cured)\b/i'
        ];
        
        foreach ($harmful_patterns as $pattern) {
            $response = preg_replace($pattern, '[consult your doctor about]', $response);
        }
        
        return $response;
    }

    private function getComprehensiveLocalResponse(string $userMessage, ?array $context = null): string
    {
        $message = strtolower(trim($userMessage));
        
        // Comprehensive fallback responses based on keywords
        if (preg_match('/\b(pain|hurt|ache)\b/', $message)) {
            $response = "I understand you're dealing with pain. RA pain management involves multiple approaches:\n\n";
            $response .= "🔹 **Medications:** Take DMARDs and pain relievers as prescribed\n";
            $response .= "🔹 **Heat/Cold:** Heat for stiffness, cold for inflammation\n";
            $response .= "🔹 **Movement:** Gentle exercises help maintain joint function\n";
            $response .= "🔹 **Rest:** Balance activity with adequate rest\n\n";
            
            if ($context && isset($context['recent_symptoms']['pain_level']) && $context['recent_symptoms']['pain_level'] >= 8) {
                $response .= "⚠️ Your recent pain level was very high. Please contact your rheumatologist if pain persists or worsens.\n\n";
            }
            
            $response .= "💡 For persistent or severe pain, consult your healthcare provider immediately.";
            return $response;
        }
        
        if (preg_match('/\b(medication|medicine|drug)\b/', $message)) {
            return "RA medications are essential for controlling inflammation and preventing joint damage:\n\n" .
                   "🔹 **DMARDs** (like methotrexate) slow disease progression\n" .
                   "🔹 **Biologics** target specific immune pathways\n" .
                   "🔹 **NSAIDs** help with pain and inflammation\n" .
                   "🔹 **Corticosteroids** for short-term flare management\n\n" .
                   "⚠️ Never stop or change medications without consulting your rheumatologist. Regular monitoring is essential for safety and effectiveness.";
        }
        
        if (preg_match('/\b(exercise|workout|activity)\b/', $message)) {
            return "Exercise is crucial for RA management! Here are the best options:\n\n" .
                   "🔹 **Swimming:** Excellent low-impact full-body workout\n" .
                   "🔹 **Walking:** Start with 10-15 minutes, build gradually\n" .
                   "🔹 **Yoga/Tai Chi:** Improve flexibility and reduce stress\n" .
                   "🔹 **Strength training:** Light weights to maintain muscle mass\n" .
                   "🔹 **Range-of-motion:** Daily stretches for joint mobility\n\n" .
                   "💡 Start slowly, listen to your body, and consult your healthcare team before beginning new exercise programs.";
        }
        
        // Default comprehensive response
        return "I'm your AI-powered RA health assistant, here to help you manage rheumatoid arthritis effectively. " .
               "I can provide evidence-based information about:\n\n" .
               "🔹 **Symptom management** (pain, stiffness, fatigue)\n" .
               "🔹 **Medication guidance** (DMARDs, biologics, side effects)\n" .
               "🔹 **Exercise recommendations** (safe, joint-friendly activities)\n" .
               "🔹 **Lifestyle tips** (diet, stress management, sleep)\n" .
               "🔹 **Flare management** (prevention and treatment strategies)\n\n" .
               "What specific aspect of RA management would you like to discuss today? I'm here to provide personalized, helpful guidance! 💙";
    }

    public function isConfigured(): bool
    {
        return true; // Always available with local intelligence
    }

    public function getProviderInfo(): array
    {
        return [
            'provider' => 'Enhanced Free AI',
            'active' => true,
            'model' => 'Multi-provider AI with Medical Intelligence',
            'features' => [
                'Real-time responses',
                'Context-aware',
                'Medical knowledge base',
                'Multiple AI fallbacks',
                'Safety filtering'
            ]
        ];
    }
}
