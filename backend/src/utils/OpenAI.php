<?php
declare(strict_types=1);

namespace Src\Utils;

class OpenAI
{
    private string $apiKey;
    private string $model;
    private string $baseUrl = 'https://api.openai.com/v1/chat/completions';

    public function __construct()
    {
        $this->apiKey = $_ENV['OPENAI_API_KEY'] ?? '';
        $this->model = $_ENV['OPENAI_MODEL'] ?? 'gpt-3.5-turbo';
    }

    public function getChatResponse(string $userMessage, ?array $context = null): string
    {
        if (empty($this->apiKey) || $this->apiKey === 'your_openai_api_key_here') {
            throw new \Exception('OpenAI API key not configured');
        }

        $systemPrompt = $this->buildSystemPrompt($context);
        
        $data = [
            'model' => $this->model,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => $systemPrompt
                ],
                [
                    'role' => 'user',
                    'content' => $userMessage
                ]
            ],
            'max_tokens' => 500,
            'temperature' => 0.7,
            'top_p' => 0.9
        ];

        $response = $this->makeRequest($data);
        
        if (isset($response['choices'][0]['message']['content'])) {
            return trim($response['choices'][0]['message']['content']);
        }
        
        throw new \Exception('Invalid response from OpenAI API');
    }

    private function buildSystemPrompt(?array $context = null): string
    {
        $prompt = "You are a specialized AI health assistant for rheumatoid arthritis (RA) patients. Your role is to provide helpful, accurate, and empathetic information about RA management.

IMPORTANT GUIDELINES:
- Always emphasize consulting healthcare professionals for medical decisions
- Provide evidence-based information about RA
- Be empathetic and supportive
- Keep responses concise but informative (under 300 words)
- Use bullet points for clarity when appropriate
- Never diagnose or prescribe medications
- Focus on lifestyle management, symptom understanding, and general guidance

AREAS YOU CAN HELP WITH:
- RA symptoms and management strategies
- Medication adherence and general information
- Exercise and physical therapy guidance
- Diet and nutrition for RA
- Stress management and mental health
- Sleep hygiene
- Flare management
- Appointment preparation
- Daily living tips

SAFETY NOTES:
- For emergencies, always direct to immediate medical care
- For severe symptoms, recommend contacting their doctor
- Remind that this is informational support, not medical advice";

        if ($context && !empty($context)) {
            $prompt .= "\n\nPATIENT CONTEXT (from recent symptom log):";
            
            if (isset($context['pain_level'])) {
                $prompt .= "\n- Recent pain level: {$context['pain_level']}/10";
            }
            if (isset($context['stiffness_level'])) {
                $prompt .= "\n- Recent stiffness level: {$context['stiffness_level']}/10";
            }
            if (isset($context['fatigue_level'])) {
                $prompt .= "\n- Recent fatigue level: {$context['fatigue_level']}/10";
            }
            if (isset($context['notes']) && !empty($context['notes'])) {
                $prompt .= "\n- Patient notes: {$context['notes']}";
            }
            
            $prompt .= "\n\nUse this context to provide more personalized advice when relevant.";
        }

        return $prompt;
    }

    private function makeRequest(array $data): array
    {
        $ch = curl_init();
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->baseUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->apiKey
            ],
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        
        curl_close($ch);

        if ($error) {
            throw new \Exception('cURL error: ' . $error);
        }

        if ($httpCode !== 200) {
            throw new \Exception('OpenAI API error: HTTP ' . $httpCode);
        }

        $decoded = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Invalid JSON response from OpenAI API');
        }

        return $decoded;
    }

    public function isConfigured(): bool
    {
        return !empty($this->apiKey) && $this->apiKey !== 'your_openai_api_key_here';
    }
}
