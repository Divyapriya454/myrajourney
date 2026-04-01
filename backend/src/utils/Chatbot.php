<?php
declare(strict_types=1);

namespace Src\Utils;

class Chatbot
{
    private array $responses = [];
    private array $keywords = [];

    public function __construct()
    {
        $this->initializeResponses();
    }

    private function initializeResponses(): void
    {
        // Greetings
        $this->responses['greeting'] = [
            'keywords' => ['hello', 'hi', 'hey', 'good morning', 'good afternoon', 'good evening'],
            'responses' => [
                "Hello! I'm here to help you with information about rheumatoid arthritis. How can I assist you today?",
                "Hi there! I can answer questions about RA symptoms, medications, exercises, and daily management. What would you like to know?",
                "Welcome! I'm your RA health assistant. Feel free to ask me about pain management, medications, or lifestyle tips."
            ]
        ];

        // Pain Management
        $this->responses['pain'] = [
            'keywords' => ['pain', 'hurt', 'ache', 'painful', 'sore', 'discomfort'],
            'responses' => [
                "For pain management:\n• Apply heat or cold packs to affected joints\n• Take prescribed pain medications as directed\n• Try gentle exercises and stretching\n• Rest when needed\n• Consider meditation or relaxation techniques\n\nAlways consult your doctor if pain worsens.",
                "Pain is common with RA. Here are some tips:\n• Use heat therapy for stiffness\n• Use cold therapy for inflammation\n• Maintain a healthy weight to reduce joint stress\n• Stay active with low-impact exercises\n• Follow your medication schedule\n\nContact your doctor if pain becomes severe."
            ]
        ];

        // Stiffness
        $this->responses['stiffness'] = [
            'keywords' => ['stiff', 'stiffness', 'rigid', 'tight', 'morning stiffness'],
            'responses' => [
                "Morning stiffness is a common RA symptom. Try these:\n• Take a warm shower or bath in the morning\n• Do gentle stretching exercises\n• Move your joints slowly and regularly\n• Take medications as prescribed\n• Use assistive devices if needed\n\nStiffness lasting more than 1 hour may indicate active inflammation - inform your doctor.",
                "To manage stiffness:\n• Apply heat before activities\n• Do range-of-motion exercises daily\n• Avoid staying in one position too long\n• Keep joints moving throughout the day\n• Consider physical therapy\n\nIf stiffness worsens, contact your healthcare provider."
            ]
        ];

        // Fatigue
        $this->responses['fatigue'] = [
            'keywords' => ['tired', 'fatigue', 'exhausted', 'weak', 'energy', 'sleepy'],
            'responses' => [
                "Fatigue is very common with RA. Here's how to manage it:\n• Get 7-9 hours of quality sleep\n• Take short rest breaks during the day\n• Pace your activities\n• Eat a balanced, nutritious diet\n• Stay hydrated\n• Exercise regularly (even light activity helps)\n\nPersistent fatigue may indicate disease activity - discuss with your doctor.",
                "To combat RA fatigue:\n• Prioritize important tasks\n• Ask for help when needed\n• Maintain a regular sleep schedule\n• Avoid caffeine late in the day\n• Practice stress management\n• Stay physically active within your limits\n\nIf fatigue is overwhelming, consult your rheumatologist."
            ]
        ];

        // Medications
        $this->responses['medication'] = [
            'keywords' => ['medication', 'medicine', 'drug', 'pill', 'methotrexate', 'dmard', 'biologic'],
            'responses' => [
                "About RA medications:\n• DMARDs (like methotrexate) slow disease progression\n• Biologics target specific immune responses\n• NSAIDs help with pain and inflammation\n• Corticosteroids reduce inflammation quickly\n\nImportant:\n• Take medications exactly as prescribed\n• Don't stop without consulting your doctor\n• Report side effects promptly\n• Keep regular follow-up appointments",
                "Medication tips:\n• Set reminders to take medications on time\n• Keep a medication log\n• Store medications properly\n• Refill prescriptions before running out\n• Inform your doctor of all medications you take\n• Never share medications\n\nAlways discuss medication concerns with your healthcare provider."
            ]
        ];

        // Exercise
        $this->responses['exercise'] = [
            'keywords' => ['exercise', 'workout', 'physical activity', 'movement', 'gym', 'walk'],
            'responses' => [
                "Exercise is beneficial for RA! Recommended activities:\n• Walking\n• Swimming or water aerobics\n• Cycling\n• Yoga or tai chi\n• Gentle stretching\n\nTips:\n• Start slowly and gradually increase\n• Listen to your body\n• Avoid high-impact activities during flares\n• Warm up before and cool down after\n• Aim for 30 minutes most days\n\nConsult a physical therapist for a personalized program.",
                "Safe exercises for RA:\n• Range-of-motion exercises daily\n• Strengthening exercises 2-3 times per week\n• Low-impact aerobic activities\n• Balance and flexibility exercises\n\nRemember:\n• Exercise during times when you feel best\n• Modify activities during flares\n• Use proper form to protect joints\n• Stay consistent\n\nWork with your healthcare team to create an exercise plan."
            ]
        ];

        // Diet/Nutrition
        $this->responses['diet'] = [
            'keywords' => ['diet', 'food', 'eat', 'nutrition', 'meal', 'anti-inflammatory'],
            'responses' => [
                "Anti-inflammatory diet for RA:\n\nFoods to include:\n• Fatty fish (salmon, sardines)\n• Fruits and vegetables\n• Whole grains\n• Nuts and seeds\n• Olive oil\n• Green tea\n\nFoods to limit:\n• Processed foods\n• Sugary drinks\n• Red meat\n• Fried foods\n• Alcohol\n\nStay hydrated and maintain a healthy weight.",
                "Nutrition tips for RA:\n• Eat a Mediterranean-style diet\n• Include omega-3 fatty acids\n• Get enough calcium and vitamin D\n• Eat colorful fruits and vegetables\n• Choose lean proteins\n• Limit salt and sugar\n\nSome people find certain foods trigger symptoms - keep a food diary to identify triggers.\n\nConsult a dietitian for personalized advice."
            ]
        ];

        // Flare
        $this->responses['flare'] = [
            'keywords' => ['flare', 'flare-up', 'worse', 'worsening', 'attack'],
            'responses' => [
                "During an RA flare:\n• Rest the affected joints\n• Apply ice to reduce inflammation\n• Take prescribed medications\n• Avoid activities that stress joints\n• Stay hydrated\n• Use assistive devices if needed\n• Contact your doctor if severe\n\nFlare triggers to avoid:\n• Stress\n• Overexertion\n• Infections\n• Poor sleep\n• Skipping medications",
                "Managing flares:\n• Recognize early warning signs\n• Have a flare action plan with your doctor\n• Keep emergency medications on hand\n• Adjust your schedule to allow rest\n• Use relaxation techniques\n• Track flare patterns in your symptom log\n\nSeek medical attention if:\n• Severe pain or swelling\n• Fever\n• Unable to move joints\n• Symptoms don't improve in 2-3 days"
            ]
        ];

        // Sleep
        $this->responses['sleep'] = [
            'keywords' => ['sleep', 'insomnia', 'rest', 'bed', 'sleeping'],
            'responses' => [
                "Better sleep with RA:\n• Maintain a regular sleep schedule\n• Create a comfortable sleep environment\n• Use supportive pillows for joints\n• Take pain medication before bed if needed\n• Avoid screens 1 hour before sleep\n• Try relaxation techniques\n• Keep bedroom cool and dark\n\nIf pain disrupts sleep, discuss with your doctor about adjusting medication timing.",
                "Sleep hygiene tips:\n• Go to bed and wake up at the same time\n• Limit daytime naps to 20-30 minutes\n• Avoid caffeine after 2 PM\n• Exercise earlier in the day\n• Take a warm bath before bed\n• Practice gentle stretching\n• Use white noise if helpful\n\nPoor sleep can worsen RA symptoms - prioritize good sleep habits."
            ]
        ];

        // Stress
        $this->responses['stress'] = [
            'keywords' => ['stress', 'anxiety', 'worried', 'overwhelmed', 'mental health'],
            'responses' => [
                "Managing stress with RA:\n• Practice deep breathing exercises\n• Try meditation or mindfulness\n• Stay connected with friends and family\n• Join a support group\n• Engage in hobbies you enjoy\n• Consider counseling or therapy\n• Set realistic goals\n• Ask for help when needed\n\nStress can trigger flares - make stress management a priority.",
                "Stress reduction techniques:\n• Progressive muscle relaxation\n• Guided imagery\n• Yoga or tai chi\n• Journaling\n• Spending time in nature\n• Listening to calming music\n• Limiting news and social media\n\nRemember: It's okay to have difficult days. Reach out to your healthcare team or a mental health professional if you're struggling."
            ]
        ];

        // Appointments
        $this->responses['appointment'] = [
            'keywords' => ['appointment', 'doctor', 'visit', 'checkup', 'rheumatologist'],
            'responses' => [
                "Preparing for your appointment:\n• List your current symptoms\n• Note any medication side effects\n• Bring your medication list\n• Write down questions\n• Track symptom patterns\n• Bring a family member if helpful\n\nQuestions to ask:\n• Is my treatment working?\n• Are there new treatment options?\n• What tests do I need?\n• How can I better manage symptoms?",
                "Making the most of appointments:\n• Keep a symptom diary\n• Be honest about medication adherence\n• Discuss any concerns or fears\n• Ask about lifestyle modifications\n• Request written instructions\n• Schedule follow-ups as recommended\n\nDon't hesitate to contact your doctor between appointments if symptoms worsen."
            ]
        ];

        // Emergency
        $this->responses['emergency'] = [
            'keywords' => ['emergency', 'urgent care', 'hospital', 'er', 'call ambulance', 'chest pain', 'can\'t breathe', 'severe bleeding'],
            'responses' => [
                "⚠️ SEEK IMMEDIATE MEDICAL ATTENTION IF:\n• Severe chest pain or difficulty breathing\n• Signs of infection (fever, chills, redness)\n• Sudden severe joint pain or swelling\n• Unable to move a joint\n• Severe allergic reaction to medication\n• Uncontrolled bleeding\n• Severe abdominal pain\n\nFor urgent but non-emergency concerns, contact your rheumatologist's office.\n\nThis chatbot is for information only - always consult healthcare professionals for medical advice."
            ]
        ];

        // General RA Info
        $this->responses['about_ra'] = [
            'keywords' => ['what is ra', 'rheumatoid arthritis', 'autoimmune', 'chronic'],
            'responses' => [
                "Rheumatoid Arthritis (RA) is:\n• An autoimmune disease\n• Causes joint inflammation and pain\n• Can affect multiple joints symmetrically\n• A chronic condition requiring ongoing management\n• Treatable with modern medications\n\nCommon symptoms:\n• Joint pain and swelling\n• Morning stiffness\n• Fatigue\n• Low-grade fever\n\nWith proper treatment, most people with RA can lead active, fulfilling lives.",
                "About RA:\n• Affects about 1% of the population\n• More common in women\n• Can start at any age\n• Not just an 'old person's disease'\n• Different from osteoarthritis\n\nGood news:\n• Early treatment prevents joint damage\n• Many effective medications available\n• Lifestyle changes make a big difference\n• Research is ongoing\n\nYou're not alone - millions manage RA successfully!"
            ]
        ];

        // Thanks
        $this->responses['thanks'] = [
            'keywords' => ['thank', 'thanks', 'appreciate'],
            'responses' => [
                "You're welcome! Feel free to ask if you have more questions about managing your RA.",
                "Happy to help! Remember to log your symptoms regularly and keep up with your appointments.",
                "Glad I could assist! Take care and don't hesitate to reach out to your healthcare team."
            ]
        ];

        // Default
        $this->responses['default'] = [
            'keywords' => [],
            'responses' => [
                "I can help you with information about:\n• Pain and stiffness management\n• Medications and treatments\n• Exercise and physical activity\n• Diet and nutrition\n• Managing flares\n• Sleep and fatigue\n• Stress management\n• Preparing for appointments\n\nWhat would you like to know more about?",
                "I'm here to provide information about rheumatoid arthritis management. You can ask me about:\n• Symptoms and how to manage them\n• Medications\n• Lifestyle tips\n• Exercise recommendations\n• Diet advice\n• When to contact your doctor\n\nHow can I help you today?"
            ]
        ];
    }

    public function getResponse(string $userMessage): string
    {
        // Try contextual response generator first for better responses
        try {
            $contextualGenerator = new ContextualResponseGenerator();
            return $contextualGenerator->generateResponse($userMessage);
        } catch (\Throwable $e) {
            error_log("Contextual Response Generator error: " . $e->getMessage());
        }

        // Fallback to original keyword-based system
        $userMessage = strtolower(trim($userMessage));

        // Check for emergency keywords first
        if ($this->containsKeywords($userMessage, $this->responses['emergency']['keywords'])) {
            return $this->getRandomResponse('emergency');
        }

        // Check each category
        foreach ($this->responses as $category => $data) {
            if ($category === 'default') continue;

            if ($this->containsKeywords($userMessage, $data['keywords'])) {
                return $this->getRandomResponse($category);
            }
        }

        // Default response
        return $this->getRandomResponse('default');
    }

    private function containsKeywords(string $message, array $keywords): bool
    {
        foreach ($keywords as $keyword) {
            if (strpos($message, $keyword) !== false) {
                return true;
            }
        }
        return false;
    }

    private function getRandomResponse(string $category): string
    {
        $responses = $this->responses[$category]['responses'];
        return $responses[array_rand($responses)];
    }

    /**
     * Get contextual response based on user's recent symptoms
     */
    public function getContextualResponse(string $userMessage, ?array $recentSymptoms = null): string
    {
        // Try the new contextual response generator first
        try {
            $contextualGenerator = new ContextualResponseGenerator();
            $response = $contextualGenerator->generateResponse($userMessage, $recentSymptoms);
            
            // Add personalized context if recent symptoms available
            if ($recentSymptoms && !empty($recentSymptoms)) {
                $context = $this->generateContext($recentSymptoms);
                if ($context) {
                    $response .= "\n\n**Based on your recent symptoms:** " . $context;
                }
            }
            
            return $response;
        } catch (\Throwable $e) {
            error_log("Contextual Response Generator error: " . $e->getMessage());
        }

        // Fallback to AI service
        try {
            $aiService = new AIService();
            return $aiService->getChatResponse($userMessage, $recentSymptoms);
        } catch (\Throwable $e) {
            // Log error but continue with fallback
            error_log("AI Service error: " . $e->getMessage());
        }

        // Final fallback to rule-based responses
        $response = $this->getResponse($userMessage);

        // Add personalized context if recent symptoms available
        if ($recentSymptoms && !empty($recentSymptoms)) {
            $context = $this->generateContext($recentSymptoms);
            if ($context) {
                $response .= "\n\n" . $context;
            }
        }

        return $response;
    }

    private function generateContext(array $symptoms): ?string
    {
        $painLevel = $symptoms['pain_level'] ?? 0;
        $stiffnessLevel = $symptoms['stiffness_level'] ?? 0;
        $fatigueLevel = $symptoms['fatigue_level'] ?? 0;

        $contexts = [];

        if ($painLevel >= 7) {
            $contexts[] = "I see you've been experiencing high pain levels recently. Make sure to follow your pain management plan and contact your doctor if it persists.";
        }

        if ($stiffnessLevel >= 7) {
            $contexts[] = "Your recent symptom log shows significant stiffness. Remember to do your morning stretches and apply heat therapy.";
        }

        if ($fatigueLevel >= 7) {
            $contexts[] = "You've reported high fatigue levels. Ensure you're getting adequate rest and pacing your activities throughout the day.";
        }

        return !empty($contexts) ? implode(' ', $contexts) : null;
    }
}
