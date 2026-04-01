<?php
declare(strict_types=1);

namespace Src\Utils;

class ChatGPTClone
{
    private array $freeAPIs;
    private string $systemPrompt;

    public function __construct()
    {
        $this->initializeFreeAPIs();
        $this->initializeSystemPrompt();
    }

    private function initializeFreeAPIs(): void
    {
        $this->freeAPIs = [
            'gpt4free' => [
                'url' => 'https://api.g4f.icu/v1/chat/completions',
                'model' => 'gpt-3.5-turbo',
                'headers' => [
                    'Content-Type: application/json'
                ]
            ],
            'chatgpt_demo' => [
                'url' => 'https://chatgpt-api.shn.hk/v1/',
                'model' => 'gpt-3.5-turbo',
                'headers' => [
                    'Content-Type: application/json'
                ]
            ],
            'free_gpt' => [
                'url' => 'https://api.freegpt.one/v1/chat/completions',
                'model' => 'gpt-3.5-turbo',
                'headers' => [
                    'Content-Type: application/json'
                ]
            ]
        ];
    }

    private function initializeSystemPrompt(): void
    {
        $this->systemPrompt = "You are a helpful AI assistant specializing in rheumatoid arthritis (RA) healthcare. " .
                             "Answer questions directly and specifically. If someone asks about forgetting medication, " .
                             "give specific advice about what to do when a dose is missed. If they ask about pain, " .
                             "give specific pain management advice. Always be direct, helpful, and specific to their " .
                             "exact question. Keep responses concise but comprehensive (2-4 sentences).";
    }

    public function getChatResponse(string $userMessage, ?array $context = null): string
    {
        // Try real ChatGPT APIs first
        $chatgptResponse = $this->tryRealChatGPT($userMessage, $context);
        if ($chatgptResponse) {
            return $this->postProcessResponse($chatgptResponse);
        }

        // Fallback to specific intelligent responses
        return $this->getSpecificResponse($userMessage, $context);
    }

    private function tryRealChatGPT(string $userMessage, ?array $context = null): ?string
    {
        $enhancedPrompt = $this->buildContextualPrompt($userMessage, $context);
        
        foreach ($this->freeAPIs as $apiName => $config) {
            try {
                error_log("Trying ChatGPT API: {$apiName}");
                
                $response = $this->callChatGPTAPI($config, $enhancedPrompt, $userMessage);
                
                if ($response && strlen(trim($response)) > 20) {
                    error_log("Success with ChatGPT API: {$apiName}");
                    return $response;
                }
                
            } catch (\Exception $e) {
                error_log("ChatGPT API {$apiName} failed: " . $e->getMessage());
                continue;
            }
        }
        
        return null;
    }

    private function buildContextualPrompt(string $userMessage, ?array $context = null): string
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
        
        $prompt .= "\n\nBe specific and direct in your response to: " . $userMessage;
        
        return $prompt;
    }

    private function callChatGPTAPI(array $config, string $systemPrompt, string $userMessage): ?string
    {
        $data = [
            'model' => $config['model'],
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userMessage]
            ],
            'max_tokens' => 400,
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
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_USERAGENT => 'MyRA-Journey-ChatGPT-Clone/1.0'
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
        if (!$decoded || json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception("Invalid JSON response");
        }
        
        if (isset($decoded['choices'][0]['message']['content'])) {
            return trim($decoded['choices'][0]['message']['content']);
        }
        
        throw new \Exception("No content in response");
    }

    private function getSpecificResponse(string $userMessage, ?array $context = null): string
    {
        $message = strtolower(trim($userMessage));
        
        // Specific responses for dizziness
        if (preg_match('/\b(dizzy|dizziness|lightheaded|vertigo|spinning|balance)\b/', $message)) {
            return "Dizziness can be concerning, especially with RA. Here's what to consider:\n\n" .
                   "**Possible RA-related causes:**\n" .
                   "• Some RA medications (especially methotrexate) can cause dizziness\n" .
                   "• Anemia from chronic inflammation\n" .
                   "• Dehydration from medication side effects\n" .
                   "• Blood pressure changes from NSAIDs\n\n" .
                   "**Immediate steps:**\n" .
                   "• Sit or lie down safely\n" .
                   "• Stay hydrated\n" .
                   "• Avoid sudden position changes\n" .
                   "• Check if you've eaten recently\n\n" .
                   "⚠️ **Contact your doctor today** - dizziness with RA medications needs evaluation. Don't drive until it resolves.";
        }

        // Specific responses for fatigue/tiredness
        if (preg_match('/\b(tired|exhausted|fatigue|no energy|sleepy|worn out|drained)\b/', $message)) {
            return "RA fatigue is real and can be overwhelming. Here's immediate help:\n\n" .
                   "**Right now:**\n" .
                   "• Take a 20-30 minute power nap if possible\n" .
                   "• Prioritize only essential tasks today\n" .
                   "• Drink water and have a healthy snack\n" .
                   "• Do 5 minutes of gentle stretching\n\n" .
                   "**RA fatigue management:**\n" .
                   "• Plan demanding activities for your best energy times\n" .
                   "• Break large tasks into smaller chunks\n" .
                   "• Ask for help when you need it\n" .
                   "• Maintain consistent sleep schedule (7-9 hours)\n\n" .
                   "💡 Severe fatigue may indicate active inflammation. If overwhelming, discuss with your rheumatologist about adjusting treatment.";
        }

        // Specific responses for pain
        if (preg_match('/\b(pain|hurt|ache|sore|painful|aching|throbbing)\b/', $message)) {
            return "I understand you're experiencing pain right now. Here's immediate relief:\n\n" .
                   "**For current pain:**\n" .
                   "• **Heat therapy**: Heating pad, warm shower, or warm compress for stiffness\n" .
                   "• **Cold therapy**: Ice pack (15-20 min) for hot, swollen joints\n" .
                   "• **Medication**: Take prescribed pain relievers if it's time\n" .
                   "• **Gentle movement**: Light stretching or range-of-motion exercises\n" .
                   "• **Rest**: Avoid activities that stress painful joints\n\n" .
                   "**Pain scale guidance:**\n" .
                   "• 1-3: Mild - manage with heat/cold and gentle movement\n" .
                   "• 4-6: Moderate - use prescribed pain medication\n" .
                   "• 7-10: Severe - contact your doctor immediately\n\n" .
                   "🚨 If pain is sudden, severe, or accompanied by fever, call your doctor now.";
        }

        // Specific responses for stiffness
        if (preg_match('/\b(stiff|stiffness|rigid|tight|can\'t move)\b/', $message)) {
            return "RA stiffness, especially in the morning, is very common. Here's what helps:\n\n" .
                   "**Immediate relief:**\n" .
                   "• Take a warm shower or bath\n" .
                   "• Use heating pads on stiff joints\n" .
                   "• Do gentle range-of-motion exercises in bed\n" .
                   "• Move slowly and give yourself extra time\n\n" .
                   "**Morning stiffness tips:**\n" .
                   "• Keep joints moving with gentle exercises\n" .
                   "• Take morning medications with food\n" .
                   "• Plan easier activities for morning hours\n" .
                   "• Use assistive devices if needed\n\n" .
                   "⏰ **Normal**: Stiffness lasting 30-60 minutes\n" .
                   "⚠️ **Concerning**: Stiffness lasting over 2 hours - contact your rheumatologist.";
        }

        // Specific responses for swelling
        if (preg_match('/\b(swollen|swelling|puffy|inflamed|inflammation)\b/', $message)) {
            return "Joint swelling indicates active inflammation. Here's what to do:\n\n" .
                   "**Immediate care:**\n" .
                   "• Apply ice packs (15-20 minutes) to reduce swelling\n" .
                   "• Elevate the swollen joint if possible\n" .
                   "• Avoid activities that stress the joint\n" .
                   "• Take anti-inflammatory medication if prescribed\n\n" .
                   "**Monitor for:**\n" .
                   "• Increasing redness or warmth (signs of infection)\n" .
                   "• Fever with joint swelling\n" .
                   "• Inability to move the joint\n" .
                   "• Severe pain with swelling\n\n" .
                   "📞 **Contact your rheumatologist within 24-48 hours** for new or worsening swelling. This may indicate a flare requiring treatment adjustment.";
        }

        // Specific responses for missed medication
        if (preg_match('/\b(missed|forgot|skip|late).*(dose|medication|medicine|pill|methotrexate|mtx)\b/', $message)) {
            if (strpos($message, 'methotrexate') !== false || strpos($message, 'mtx') !== false) {
                return "For missed methotrexate (MTX):\n\n" .
                       "**If you remember the same day:**\n" .
                       "• Take it as soon as you remember\n" .
                       "• Continue with your normal weekly schedule\n\n" .
                       "**If it's the next day or later:**\n" .
                       "• Skip the missed dose completely\n" .
                       "• Take your next dose on the regular scheduled day\n" .
                       "• Never take a double dose\n\n" .
                       "**Prevention tips:**\n" .
                       "• Set phone alarms for your MTX day\n" .
                       "• Use a weekly pill organizer\n" .
                       "• Link it to a weekly routine (like Sunday dinner)\n\n" .
                       "💡 One missed dose won't cause major problems, but consistency is important for controlling RA.";
            } else {
                return "For missed RA medications:\n\n" .
                       "**DMARDs (weekly medications):**\n" .
                       "• Same day: Take when remembered\n" .
                       "• Next day: Skip and resume normal schedule\n\n" .
                       "**Daily medications:**\n" .
                       "• Take when remembered if less than 12 hours late\n" .
                       "• Skip if close to next dose time\n\n" .
                       "**Biologics (injections):**\n" .
                       "• Contact your doctor's office for guidance\n" .
                       "• Don't guess - timing matters for biologics\n\n" .
                       "**Never double dose any RA medication.** Set up reminders to prevent future missed doses.";
            }
        }

        // Specific responses for flare-ups
        if (preg_match('/\b(flare|flare-up|getting worse|worsening|attack)\b/', $message)) {
            return "RA flare management - act quickly:\n\n" .
                   "**Immediate actions (first 24 hours):**\n" .
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

        // Specific responses for exercise questions
        if (preg_match('/\b(exercise|workout|gym|activity|movement|physical therapy)\b/', $message)) {
            return "RA-friendly exercise recommendations:\n\n" .
                   "**Best exercises for RA:**\n" .
                   "• **Swimming/Water aerobics** - zero joint impact, full-body workout\n" .
                   "• **Walking** - start with 10-15 minutes, build gradually\n" .
                   "• **Yoga/Tai Chi** - improves flexibility, balance, and reduces stress\n" .
                   "• **Cycling** - low-impact cardio option\n" .
                   "• **Light strength training** - maintains muscle mass around joints\n\n" .
                   "**Exercise timing:**\n" .
                   "• Best time: Mid-morning when stiffness improves\n" .
                   "• During flares: Gentle range-of-motion only\n" .
                   "• Always warm up and cool down\n\n" .
                   "**Red flags to stop:**\n" .
                   "• Increased joint pain during or after exercise\n" .
                   "• New swelling or warmth in joints\n\n" .
                   "Consider working with a physical therapist experienced with RA.";
        }

        // Specific responses for medication questions (general)
        if (preg_match('/\b(medication|medicine|drug|pill|treatment)\b/', $message) && !preg_match('/missed|forgot/', $message)) {
            return "RA medication guidance:\n\n" .
                   "**Main RA medication types:**\n" .
                   "• **DMARDs** (methotrexate, sulfasalazine) - slow disease progression\n" .
                   "• **Biologics** (Humira, Enbrel, Remicade) - target specific immune pathways\n" .
                   "• **JAK inhibitors** (Xeljanz, Rinvoq) - oral targeted therapy\n" .
                   "• **NSAIDs** - reduce pain and inflammation\n" .
                   "• **Steroids** - short-term flare control only\n\n" .
                   "**Critical reminders:**\n" .
                   "• Never stop DMARDs or biologics without doctor approval\n" .
                   "• Regular blood tests monitor for side effects\n" .
                   "• Report infections, unusual symptoms immediately\n" .
                   "• Keep taking meds even when feeling good\n\n" .
                   "💡 Your medications prevent joint damage even when you feel fine. Consistency is key.";
        }

        // Specific responses for sleep issues
        if (preg_match('/\b(sleep|insomnia|can\'t sleep|wake up|restless)\b/', $message)) {
            return "RA can significantly impact sleep quality. Here's help:\n\n" .
                   "**Tonight's sleep tips:**\n" .
                   "• Take a warm bath before bed to ease joint stiffness\n" .
                   "• Use supportive pillows between knees and under arms\n" .
                   "• Keep room cool (65-68°F) and dark\n" .
                   "• Try gentle stretching or meditation\n\n" .
                   "**RA-specific sleep strategies:**\n" .
                   "• Time pain medication to cover nighttime hours\n" .
                   "• Use heated mattress pad for morning stiffness\n" .
                   "• Keep water and pain relief nearby\n" .
                   "• Consider afternoon nap (20-30 min max)\n\n" .
                   "**When to see your doctor:**\n" .
                   "• Chronic insomnia (3+ weeks)\n" .
                   "• Pain consistently disrupting sleep\n" .
                   "• Excessive daytime fatigue\n\n" .
                   "Good sleep is crucial for RA management and healing.";
        }

        // Specific responses for weather/seasonal issues
        if (preg_match('/\b(weather|cold|rain|winter|barometric|pressure|seasonal)\b/', $message)) {
            return "Weather sensitivity is very real with RA. Here's how to cope:\n\n" .
                   "**Cold weather management:**\n" .
                   "• Layer clothing to trap warm air\n" .
                   "• Use heated car seats and steering wheel covers\n" .
                   "• Wear compression gloves and warm socks\n" .
                   "• Keep joints moving with indoor exercises\n\n" .
                   "**Rainy/low pressure days:**\n" .
                   "• Plan lighter activities on weather-sensitive days\n" .
                   "• Use heating pads proactively\n" .
                   "• Stay consistent with medications\n" .
                   "• Track symptoms vs. weather patterns\n\n" .
                   "**Year-round strategies:**\n" .
                   "• Maintain consistent indoor temperature\n" .
                   "• Use humidifier in dry conditions\n" .
                   "• Plan outdoor activities for better weather days\n\n" .
                   "💡 Weather doesn't cause RA flares, but it can make symptoms more noticeable.";
        }

        // Specific responses for diet/nutrition
        if (preg_match('/\b(diet|food|eat|nutrition|anti-inflammatory|weight)\b/', $message)) {
            return "Nutrition plays a key role in RA management:\n\n" .
                   "**Anti-inflammatory foods (eat more):**\n" .
                   "• **Fatty fish** - salmon, sardines, mackerel (2-3x/week)\n" .
                   "• **Colorful vegetables** - leafy greens, berries, tomatoes\n" .
                   "• **Healthy fats** - olive oil, avocados, nuts, seeds\n" .
                   "• **Whole grains** - quinoa, brown rice, oats\n\n" .
                   "**Pro-inflammatory foods (limit):**\n" .
                   "• Processed meats and fried foods\n" .
                   "• Sugary drinks and refined carbs\n" .
                   "• Excessive red meat\n" .
                   "• Trans fats and processed snacks\n\n" .
                   "**RA-specific tips:**\n" .
                   "• Stay hydrated (8-10 glasses water daily)\n" .
                   "• Consider omega-3 supplements (ask your doctor)\n" .
                   "• Maintain healthy weight to reduce joint stress\n\n" .
                   "💡 No single food cures RA, but overall dietary patterns can help reduce inflammation.";
        }

        // Specific responses for stress/mental health
        if (preg_match('/\b(stress|anxiety|depressed|overwhelmed|mental|emotional)\b/', $message)) {
            return "RA and mental health are closely connected. Here's support:\n\n" .
                   "**Immediate stress relief:**\n" .
                   "• Practice 4-7-8 breathing (inhale 4, hold 7, exhale 8)\n" .
                   "• Try 5-minute meditation or mindfulness\n" .
                   "• Take a warm bath or shower\n" .
                   "• Listen to calming music or nature sounds\n\n" .
                   "**RA-specific mental health:**\n" .
                   "• Join RA support groups (online or local)\n" .
                   "• Connect with others who understand chronic illness\n" .
                   "• Practice self-compassion on difficult days\n" .
                   "• Celebrate small wins and good days\n\n" .
                   "**When to seek help:**\n" .
                   "• Persistent sadness or hopelessness\n" .
                   "• Loss of interest in activities\n" .
                   "• Difficulty coping with RA symptoms\n\n" .
                   "🤝 Mental health support is part of comprehensive RA care. Don't hesitate to reach out to counselors familiar with chronic illness.";
        }

        // Greeting responses
        if (preg_match('/\b(hello|hi|hey|good morning|good afternoon|good evening)\b/', $message)) {
            $greetings = [
                "Hello! I'm your RA health assistant. I can help with specific questions about pain, medications, symptoms, or daily management. What's going on with your RA today?",
                "Hi there! I specialize in rheumatoid arthritis support. Whether you're dealing with pain, fatigue, medication questions, or need lifestyle tips, I'm here to help. What can I assist you with?",
                "Hey! I'm here to provide specific, helpful guidance for managing your RA. Tell me what you're experiencing - pain, stiffness, medication concerns, or anything else RA-related.",
                "Good to see you! As your RA assistant, I can give you targeted advice for whatever you're dealing with today. Are you having symptoms, medication questions, or need general RA guidance?"
            ];
            return $greetings[array_rand($greetings)];
        }

        // Default response - much more specific
        return "I want to give you the most helpful, specific advice for your situation. Could you tell me exactly what you're experiencing right now?\n\n" .
               "**For example:**\n" .
               "• \"I'm having joint pain in my hands\"\n" .
               "• \"I forgot to take my methotrexate yesterday\"\n" .
               "• \"I'm feeling very tired and dizzy\"\n" .
               "• \"My joints are more swollen than usual\"\n" .
               "• \"I think I'm having a flare-up\"\n\n" .
               "The more specific you are about your symptoms or concerns, the better I can help you manage your RA effectively. What's bothering you most today?";
    }

    private function postProcessResponse(string $response): string
    {
        $response = trim($response);
        
        // Ensure medical disclaimer if needed
        if (!preg_match('/\b(consult|doctor|healthcare|provider|rheumatologist)\b/i', $response)) {
            $response .= "\n\n💡 Always consult your rheumatologist for personalized medical advice.";
        }
        
        return $response;
    }

    public function isConfigured(): bool
    {
        return true;
    }

    public function getProviderInfo(): array
    {
        return [
            'provider' => 'ChatGPT Clone',
            'active' => true,
            'model' => 'GPT-3.5-Turbo via Free APIs',
            'features' => [
                'Real ChatGPT integration',
                'Specific contextual answers',
                'Direct question responses',
                'RA medical expertise',
                'Free API access',
                'ChatGPT-like behavior'
            ]
        ];
    }
}
