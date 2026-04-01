<?php
declare(strict_types=1);

namespace Src\Utils;

class AIService
{
    private string $provider;
    private $aiInstance;

    public function __construct()
    {
        $this->provider = $_ENV['AI_PROVIDER'] ?? 'chatgptclone';
        $this->initializeAI();
    }

    private function initializeAI(): void
    {
        switch ($this->provider) {
            case 'smartai':
                $this->aiInstance = new SmartAI();
                break;
            case 'chatgptclone':
                $this->aiInstance = new ChatGPTClone();
                break;
            case 'freeopenai':
                $this->aiInstance = new FreeOpenAI();
                break;
            case 'realtime':
                $this->aiInstance = new RealTimeAI();
                break;
            case 'chatgpt':
                $this->aiInstance = new FreeChatGPTAI();
                break;
            case 'enhanced':
                $this->aiInstance = new EnhancedFreeAI();
                break;
            case 'huggingface':
                $this->aiInstance = new HuggingFaceAI();
                break;
            case 'openai':
                $this->aiInstance = new OpenAI();
                break;
            default:
                $this->aiInstance = new SmartAI(); // Default to SmartAI
                break;
        }
    }

    public function getChatResponse(string $userMessage, ?array $context = null): string
    {
        try {
            if ($this->aiInstance->isConfigured()) {
                // Use the generateResponse method for SmartAI
                if ($this->aiInstance instanceof SmartAI) {
                    return $this->aiInstance->generateResponse($userMessage, $context);
                } else {
                    return $this->aiInstance->getChatResponse($userMessage, $context);
                }
            }
        } catch (\Throwable $e) {
            error_log("AI Service error ({$this->provider}): " . $e->getMessage());
        }

        // Fallback to basic chatbot
        $fallback = new Chatbot();
        return $fallback->getResponse($userMessage);
    }

    public function isAIActive(): bool
    {
        return $this->aiInstance && $this->aiInstance->isConfigured();
    }

    public function getProviderInfo(): array
    {
        return [
            'provider' => $this->provider,
            'active' => $this->isAIActive(),
            'model' => $this->getModelInfo()
        ];
    }

    private function getModelInfo(): string
    {
        switch ($this->provider) {
            case 'smartai':
                return 'Advanced AI - Dynamic Response Generation with Medical Knowledge';
            case 'chatgptclone':
                return 'ChatGPT Clone - Real GPT-3.5-Turbo Integration';
            case 'freeopenai':
                return 'Free OpenAI - ChatGPT-3.5-Turbo Clone';
            case 'realtime':
                return 'Real-Time AI - ChatGPT Clone with Multiple Providers';
            case 'chatgpt':
                return 'Free ChatGPT-like AI - Dynamic Responses';
            case 'enhanced':
                return 'Enhanced Free AI - Multi-Provider Intelligence';
            case 'huggingface':
                return 'Free AI - Medical Knowledge Base';
            case 'openai':
                return $_ENV['OPENAI_MODEL'] ?? 'gpt-3.5-turbo';
            default:
                return 'Advanced AI - Dynamic Response Generation with Medical Knowledge';
        }
    }
}
