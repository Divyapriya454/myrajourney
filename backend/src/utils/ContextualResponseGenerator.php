<?php
declare(strict_types=1);

namespace Src\Utils;

class ContextualResponseGenerator
{
    private IntentAnalysisEngine $intentEngine;
    private array $symptomResponses = [];
    private array $responseTemplates = [];

    public function __construct()
    {
        $this->intentEngine = new IntentAnalysisEngine();
        $this->initializeSymptomResponses();
        $this->initializeResponseTemplates();
    }

    private function initializeSymptomResponses(): void
    {
        $this->symptomResponses = [
            'dizziness' => [
                'immediate_advice' => [
                    "Dizziness can be concerning. Here's what you can do right now:",
                    "• Sit or lie down immediately to prevent falling",
                    "• Stay hydrated - drink water slowly",
                    "• Avoid sudden movements or position changes",
                    "• Check if you've eaten recently - low blood sugar can cause dizziness"
                ],
                'when_to_worry' => "Contact your doctor if dizziness is severe, persistent, or accompanied by chest pain, shortness of breath, or fainting.",
                'ra_connection' => "Dizziness can sometimes be related to RA medications or inflammation affecting blood vessels. It's important to track when it occurs."
            ],
            'fatigue' => [
                'immediate_advice' => [
                    "RA fatigue is very real and can be overwhelming. Here's how to manage it:",
                    "• Rest when your body tells you to - don't push through extreme tiredness",
                    "• Take short 15-20 minute breaks throughout the day",
                    "• Prioritize your most important tasks for when you have energy",
                    "• Gentle movement like stretching can actually help boost energy"
                ],
                'when_to_worry' => "If fatigue is suddenly much worse or interfering with daily activities, discuss with your rheumatologist.",
                'ra_connection' => "Fatigue is one of the most common RA symptoms, often indicating active inflammation. Tracking your fatigue levels helps your doctor adjust treatment."
            ],
            'pain' => [
                'immediate_advice' => [
                    "For pain relief right now:",
                    "• Apply heat for stiffness or cold for acute inflammation",
                    "• Take your prescribed pain medication as directed",
                    "• Try gentle stretching or range-of-motion exercises",
                    "• Practice deep breathing or relaxation techniques",
                    "• Rest the affected joints if possible"
                ],
                'when_to_worry' => "Seek immediate care if pain is sudden and severe, or if you have signs of infection (fever, redness, warmth).",
                'ra_connection' => "RA pain often varies throughout the day and can indicate disease activity. Keep track of pain patterns to share with your doctor."
            ],
            'swelling' => [
                'immediate_advice' => [
                    "For joint swelling:",
                    "• Apply ice packs for 15-20 minutes to reduce inflammation",
                    "• Elevate the swollen joint if possible",
                    "• Avoid activities that stress the swollen joints",
                    "• Take anti-inflammatory medication as prescribed",
                    "• Gentle movement can help prevent stiffness"
                ],
                'when_to_worry' => "Contact your doctor if swelling is sudden, severe, or accompanied by fever, as this could indicate a flare or infection.",
                'ra_connection' => "Joint swelling is a key sign of RA inflammation. New or worsening swelling may mean your treatment needs adjustment."
            ],
            'stiffness' => [
                'immediate_advice' => [
                    "To reduce stiffness:",
                    "• Take a warm shower or bath to loosen joints",
                    "• Do gentle stretching exercises",
                    "• Move joints through their full range of motion",
                    "• Apply heat packs to stiff areas",
                    "• Take morning medications with food to reduce stomach upset"
                ],
                'when_to_worry' => "If morning stiffness lasts more than 1-2 hours, this may indicate active RA inflammation.",
                'ra_connection' => "Morning stiffness lasting over an hour is a classic RA symptom. The duration of stiffness helps doctors assess disease activity."
            ],
            'nausea' => [
                'immediate_advice' => [
                    "For nausea relief:",
                    "• Eat small, frequent meals instead of large ones",
                    "• Try bland foods like crackers, toast, or rice",
                    "• Sip clear fluids slowly (water, ginger tea, clear broth)",
                    "• Avoid strong smells and greasy foods",
                    "• Rest in a comfortable position"
                ],
                'when_to_worry' => "Contact your doctor if nausea is severe, persistent, or if you can't keep fluids down.",
                'ra_connection' => "Nausea can be a side effect of RA medications like methotrexate. Your doctor may be able to adjust timing or add anti-nausea medication."
            ],
            'headache' => [
                'immediate_advice' => [
                    "For headache relief:",
                    "• Rest in a quiet, dark room",
                    "• Apply a cold compress to your forehead or warm compress to neck",
                    "• Stay hydrated - drink water slowly",
                    "• Try gentle neck and shoulder stretches",
                    "• Take over-the-counter pain relief as directed"
                ],
                'when_to_worry' => "Seek immediate care for sudden severe headaches, headaches with fever, or vision changes.",
                'ra_connection' => "Headaches can be related to RA inflammation, medication side effects, or stress from managing chronic illness."
            ],
            'fever' => [
                'immediate_advice' => [
                    "For fever management:",
                    "• Rest and stay hydrated with clear fluids",
                    "• Take acetaminophen or ibuprofen as directed",
                    "• Use cool compresses or take a lukewarm bath",
                    "• Wear light, breathable clothing",
                    "• Monitor your temperature regularly"
                ],
                'when_to_worry' => "⚠️ Contact your doctor immediately if you have fever with RA - this could indicate infection or a serious flare.",
                'ra_connection' => "Fever with RA can be serious, especially if you're on immunosuppressive medications. Always report fever to your healthcare team."
            ]
        ];
    }

    private function initializeResponseTemplates(): void
    {
        $this->responseTemplates = [
            'symptom_acknowledgment' => [
                "I understand you're experiencing {symptom}. That must be {severity_word} for you.",
                "Thank you for sharing that you're feeling {symptom}. Let me help you with some guidance.",
                "I hear that you're having {symptom}. Here's what I can suggest:"
            ],
            'medication_response' => [
                "Regarding {medication}, here's what you should know:",
                "For questions about {medication}:",
                "Let me provide some information about {medication}:"
            ],
            'appointment_response' => [
                "For scheduling appointments:",
                "Regarding your appointment needs:",
                "Here's how to handle your appointment:"
            ],
            'emergency_response' => [
                "⚠️ This sounds like it could be serious. Please:",
                "⚠️ Based on what you're describing, you should:",
                "⚠️ This requires immediate attention:"
            ]
        ];
    }

    public function generateResponse(string $userMessage, ?array $context = null): string
    {
        // Analyze the user's intent and extract entities
        $analysis = $this->intentEngine->analyzeIntent($userMessage);
        
        $intent = $analysis['primary_intent']['intent'];
        $confidence = $analysis['primary_intent']['confidence'];
        $entities = $analysis['entities'];

        // Generate response based on intent and entities
        switch ($intent) {
            case 'symptom_report':
                return $this->generateSymptomResponse($entities, $userMessage);
            
            case 'pain_inquiry':
                return $this->generatePainManagementResponse($entities);
            
            case 'medication_inquiry':
                return $this->generateMedicationResponse($entities);
            
            case 'appointment_inquiry':
                return $this->generateAppointmentResponse($entities);
            
            case 'emergency':
                return $this->generateEmergencyResponse($entities);
            
            case 'exercise_inquiry':
                return $this->generateExerciseResponse($entities);
            
            case 'diet_inquiry':
                return $this->generateDietResponse($entities);
            
            default:
                return $this->generateGeneralResponse($userMessage, $entities);
        }
    }

    private function generateSymptomResponse(array $entities, string $originalMessage): string
    {
        $symptoms = $entities['symptoms'];
        $severity = $entities['severity'];
        $timeContext = $entities['time_context'];

        if (empty($symptoms)) {
            return "I understand you're not feeling well. Could you tell me more specifically about what symptoms you're experiencing? For example, are you having pain, fatigue, stiffness, or something else?";
        }

        // Handle multiple symptoms
        if (count($symptoms) > 1) {
            return $this->generateMultiSymptomResponse($symptoms, $severity, $timeContext);
        }

        // Handle single symptom
        $symptom = $symptoms[0];
        $response = $this->buildSymptomResponse($symptom, $severity, $timeContext);

        return $response;
    }

    private function buildSymptomResponse(string $symptom, ?string $severity, ?string $timeContext): string
    {
        if (!isset($this->symptomResponses[$symptom])) {
            return $this->generateGenericSymptomResponse($symptom, $severity);
        }

        $symptomData = $this->symptomResponses[$symptom];
        $severityWord = $this->getSeverityWord($severity);
        
        // Build personalized response
        $response = "I understand you're experiencing {$symptom}";
        if ($severity) {
            $response .= " that feels {$severityWord}";
        }
        if ($timeContext) {
            $response .= " {$timeContext}";
        }
        $response .= ". ";

        // Add immediate advice
        $response .= "\n\n" . implode("\n", $symptomData['immediate_advice']);

        // Add when to worry section
        $response .= "\n\n**When to contact your doctor:**\n" . $symptomData['when_to_worry'];

        // Add RA connection
        $response .= "\n\n**About this symptom and RA:**\n" . $symptomData['ra_connection'];

        return $response;
    }

    private function generateMultiSymptomResponse(array $symptoms, ?string $severity, ?string $timeContext): string
    {
        $symptomList = implode(', ', array_slice($symptoms, 0, -1)) . ' and ' . end($symptoms);
        
        $response = "I see you're dealing with multiple symptoms: {$symptomList}";
        if ($timeContext) {
            $response .= " {$timeContext}";
        }
        $response .= ". This combination can be particularly challenging with RA.\n\n";

        $response .= "**Here's what you can do:**\n";
        $response .= "• Focus on rest and gentle self-care\n";
        $response .= "• Take your medications as prescribed\n";
        $response .= "• Apply heat or cold therapy as appropriate\n";
        $response .= "• Stay hydrated and eat nutritious foods\n";
        $response .= "• Track these symptoms to discuss with your doctor\n\n";

        $response .= "**Important:** Multiple symptoms occurring together may indicate increased RA activity. Consider contacting your rheumatologist to discuss whether your treatment plan needs adjustment.";

        return $response;
    }

    private function generateGenericSymptomResponse(string $symptom, ?string $severity): string
    {
        $severityWord = $this->getSeverityWord($severity);
        
        return "I understand you're experiencing {$symptom}" . ($severity ? " that feels {$severityWord}" : "") . ". While I don't have specific guidance for this symptom, here are some general suggestions:\n\n" .
               "• Rest and take care of yourself\n" .
               "• Stay hydrated\n" .
               "• Take your prescribed medications\n" .
               "• Monitor how you're feeling\n\n" .
               "If this symptom is new, severe, or concerning to you, please contact your healthcare provider for personalized advice.";
    }

    private function getSeverityWord(?string $severity): string
    {
        switch ($severity) {
            case 'severe': return 'severe';
            case 'moderate': return 'moderate';
            case 'mild': return 'mild';
            default: return 'uncomfortable';
        }
    }

    private function generatePainManagementResponse(array $entities): string
    {
        $response = "Here are evidence-based pain management strategies for RA:\n\n";
        $response .= "**Immediate Relief:**\n";
        $response .= "• Heat therapy for stiffness (warm shower, heating pad)\n";
        $response .= "• Cold therapy for acute inflammation (ice pack for 15-20 min)\n";
        $response .= "• Gentle range-of-motion exercises\n";
        $response .= "• Relaxation techniques or meditation\n\n";
        
        $response .= "**Medication Management:**\n";
        $response .= "• Take prescribed pain medications as directed\n";
        $response .= "• Don't wait until pain is severe to take medication\n";
        $response .= "• Track what works best for you\n\n";
        
        $response .= "**Long-term Strategies:**\n";
        $response .= "• Regular gentle exercise\n";
        $response .= "• Stress management\n";
        $response .= "• Adequate sleep\n";
        $response .= "• Anti-inflammatory diet\n\n";
        
        $response .= "Remember: Persistent or worsening pain may indicate that your RA treatment needs adjustment. Keep a pain diary to share with your rheumatologist.";

        return $response;
    }

    private function generateMedicationResponse(array $entities): string
    {
        $medications = $entities['medications'];
        
        if (!empty($medications)) {
            $medication = $medications[0];
            return "Regarding {$medication}: I recommend discussing specific questions about this medication with your pharmacist or doctor, as they can provide personalized advice based on your medical history. \n\nGeneral medication tips:\n• Take as prescribed\n• Don't stop without consulting your doctor\n• Report any side effects\n• Keep regular follow-up appointments\n\nWould you like me to help you prepare questions for your next appointment?";
        }

        return "For medication questions:\n\n• Always consult your pharmacist or doctor for specific advice\n• Keep an updated list of all medications\n• Take medications exactly as prescribed\n• Report side effects promptly\n• Don't stop RA medications without medical supervision\n• Set reminders to take medications on time\n\nIs there a specific medication concern I can help you prepare to discuss with your healthcare provider?";
    }

    private function generateAppointmentResponse(array $entities): string
    {
        return "For appointments and healthcare visits:\n\n**Scheduling:**\n• Call your rheumatologist's office directly\n• Ask about cancellation lists for earlier appointments\n• Schedule follow-ups before leaving your current appointment\n\n**Preparing for visits:**\n• List current symptoms and concerns\n• Bring updated medication list\n• Note any side effects\n• Prepare questions in advance\n• Bring a family member if helpful\n\n**Questions to ask:**\n• Is my current treatment working?\n• Are there new treatment options?\n• What tests do I need?\n• How can I better manage symptoms?\n\nWould you like help preparing specific questions for your next appointment?";
    }

    private function generateEmergencyResponse(array $entities): string
    {
        return "⚠️ **SEEK IMMEDIATE MEDICAL ATTENTION IF YOU HAVE:**\n\n" .
               "• Severe chest pain or difficulty breathing\n" .
               "• Signs of serious infection (high fever, chills, severe fatigue)\n" .
               "• Sudden severe joint pain or swelling\n" .
               "• Inability to move a joint\n" .
               "• Severe allergic reaction to medication\n" .
               "• Uncontrolled bleeding\n" .
               "• Severe abdominal pain\n\n" .
               "**For urgent but non-emergency concerns:**\n" .
               "• Contact your rheumatologist's office\n" .
               "• Call the after-hours nurse line\n" .
               "• Visit urgent care\n\n" .
               "**Remember:** This chatbot provides information only. Always trust your instincts - if something feels seriously wrong, seek immediate medical care.";
    }

    private function generateExerciseResponse(array $entities): string
    {
        return "Exercise is crucial for RA management! Here's what's safe and beneficial:\n\n**Recommended Activities:**\n• Walking (start with 10-15 minutes)\n• Swimming or water aerobics\n• Gentle yoga or tai chi\n• Cycling or stationary bike\n• Range-of-motion exercises\n\n**Exercise Tips:**\n• Start slowly and gradually increase\n• Exercise when you feel your best\n• Warm up before and cool down after\n• Listen to your body\n• Modify during flares\n• Aim for 30 minutes most days\n\n**Avoid during flares:**\n• High-impact activities\n• Heavy weightlifting\n• Activities that stress inflamed joints\n\nConsider working with a physical therapist to develop a personalized exercise program that's safe for your specific needs.";
    }

    private function generateDietResponse(array $entities): string
    {
        return "Nutrition plays an important role in managing RA inflammation:\n\n**Anti-inflammatory foods to include:**\n• Fatty fish (salmon, sardines, mackerel)\n• Colorful fruits and vegetables\n• Whole grains\n• Nuts and seeds\n• Olive oil\n• Green tea\n\n**Foods that may increase inflammation:**\n• Processed and fried foods\n• Sugary drinks and snacks\n• Refined carbohydrates\n• Excessive red meat\n• Trans fats\n\n**General tips:**\n• Follow a Mediterranean-style diet\n• Stay hydrated\n• Maintain a healthy weight\n• Consider keeping a food diary to identify triggers\n• Get adequate calcium and vitamin D\n\nConsult with a registered dietitian who understands RA for personalized nutrition advice.";
    }

    private function generateGeneralResponse(string $message, array $entities): string
    {
        return "I'm here to help with your RA-related questions. I can provide information about:\n\n" .
               "• Symptom management (pain, stiffness, fatigue)\n" .
               "• Medications and treatments\n" .
               "• Exercise and physical activity\n" .
               "• Diet and nutrition\n" .
               "• Preparing for appointments\n" .
               "• When to contact your doctor\n\n" .
               "Could you tell me more specifically what you'd like help with today? For example, are you experiencing any particular symptoms or do you have questions about managing your RA?";
    }
}
