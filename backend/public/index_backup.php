<?php
declare(strict_types=1);

require __DIR__ . '/../src/bootstrap.php';

use Src\Utils\Response;
use Src\Middlewares\Auth;
use Src\Controllers\AuthController;
use Src\Controllers\UserController;
use Src\Controllers\PatientController;
use Src\Controllers\DoctorController;
use Src\Controllers\AppointmentController;
use Src\Controllers\ReportController;
use Src\Controllers\MedicationController;
use Src\Controllers\RehabController;
use Src\Controllers\NotificationController;
use Src\Controllers\EducationController;
use Src\Controllers\SymptomController;
use Src\Controllers\MetricController;
use Src\Controllers\SettingsController;
use Src\Controllers\AdminController;
use Src\Controllers\ReportNoteController;
use Src\Controllers\CrpController;
use Src\Controllers\ChatbotController;
use Src\Controllers\ExerciseController;
use Src\Controllers\ExerciseSessionController;
use Src\Controllers\RehabV2Controller;


// Basic CORS handling for preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    Src\Config\Cors::preflight();
    exit;
}
Src\Config\Cors::allow();

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$uri = $_SERVER['PATH_INFO'] ?? parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

// DEBUG: Force write to file to see what's happening
$debugInfo = [
    'method' => $method,
    'uri' => $uri,
    'PATH_INFO' => $_SERVER['PATH_INFO'] ?? 'not set',
    'REQUEST_URI' => $_SERVER['REQUEST_URI'] ?? 'not set',
    'SCRIPT_NAME' => $_SERVER['SCRIPT_NAME'] ?? 'not set'
];
file_put_contents(__DIR__ . '/debug_uri.txt', date('[Y-m-d H:i:s] ') . json_encode($debugInfo) . PHP_EOL, FILE_APPEND);

// CRITICAL: Log all requests
file_put_contents(__DIR__ . '/api_log.txt', date('[Y-m-d H:i:s] ') . $method . ' ' . $uri . ' (PATH_INFO: ' . ($_SERVER['PATH_INFO'] ?? 'not set') . ', REQUEST_URI: ' . ($_SERVER['REQUEST_URI'] ?? 'not set') . ')' . PHP_EOL, FILE_APPEND);

// DEBUG: Log delete requests specifically
if ($method === 'DELETE' || strpos($uri, 'delete') !== false) {
    file_put_contents(__DIR__ . '/api_log.txt', date('[Y-m-d H:i:s] DELETE_DEBUG - METHOD: ') . $method . ', URI: ' . $uri . ', REQUEST_URI: ' . ($_SERVER['REQUEST_URI'] ?? 'not set') . PHP_EOL, FILE_APPEND);
}

// ADDITIONAL: Log POST data for report endpoints
if ($method === 'POST' && strpos($uri, 'reports') !== false) {
    file_put_contents(__DIR__ . '/api_log.txt', date('[Y-m-d H:i:s] REPORTS_POST - FILES: ') . print_r($_FILES, true) . PHP_EOL, FILE_APPEND);
    file_put_contents(__DIR__ . '/api_log.txt', date('[Y-m-d H:i:s] REPORTS_POST - POST: ') . print_r($_POST, true) . PHP_EOL, FILE_APPEND);
}

// Ensure URI starts with /
if ($uri === '' || ($uri[0] !== '/')) {
    $uri = '/' . $uri;
}

// Exclude certain helper files
$allowedFiles = ['admin-api.php', 'doctor-patients.php', 'clear-cache.php'];

// For XAMPP compatibility, we don't need the file serving logic
// since we're using PATH_INFO for routing

// If request targets non-API path and matches known test files or explicitly allowed filenames, return 404 JSON
if (strpos($uri, '/api/v1/') !== 0) {
    $testFiles = ['test-', 'debug-', 'api-info.php'];
    $isTestFile = false;
    foreach ($testFiles as $prefix) {
        if (strpos(basename($uri), $prefix) === 0) {
            $isTestFile = true;
            break;
        }
    }

    if ($isTestFile || in_array(basename($uri), $allowedFiles)) {
        http_response_code(404);
        Response::json([
            'success' => false,
            'error' => [
                'code' => 'NOT_FOUND',
                'message' => 'File not found. If this is a test file, access it directly via browser.'
            ]
        ], 404);
        exit;
    }
}

// Route helper
function route(string $method, string $path): bool {
    global $uri;
    return $_SERVER['REQUEST_METHOD'] === $method && $uri === $path;
}

// ======================
// HEALTH CHECK
// ======================
if (route('GET', '/api/v1/health')) { 
    Response::json([
        'success' => true,
        'status' => 'ok',
        'timestamp' => date('Y-m-d H:i:s'),
        'server' => 'MyRA Journey API - UPDATED VERSION 2.0',
        'debug' => [
            'uri' => $uri,
            'method' => $method,
            'PATH_INFO' => $_SERVER['PATH_INFO'] ?? 'not set',
            'REQUEST_URI' => $_SERVER['REQUEST_URI'] ?? 'not set'
        ]
    ]); 
    exit; 
}

// ======================
// DEBUG ENDPOINT
// ======================
if (route('GET', '/api/v1/debug')) {
    Response::json([
        'success' => true,
        'uri' => $uri,
        'method' => $method,
        'path_info' => $_SERVER['PATH_INFO'] ?? 'not set',
        'request_uri' => $_SERVER['REQUEST_URI'] ?? 'not set',
        'script_name' => $_SERVER['SCRIPT_NAME'] ?? 'not set'
    ]);
    exit;
}

// ======================
// AUTH ROUTES
// ======================
if (route('POST', '/api/v1/auth/register')) { (new AuthController())->register(); exit; }
if (route('POST', '/api/v1/auth/login')) { 
    file_put_contents(__DIR__ . '/api_log.txt', date('[Y-m-d H:i:s] LOGIN_ROUTE_MATCHED - Calling AuthController->login()') . PHP_EOL, FILE_APPEND);
    try {
        (new AuthController())->login(); 
    } catch (\Exception $e) {
        file_put_contents(__DIR__ . '/api_log.txt', date('[Y-m-d H:i:s] LOGIN_ERROR: ') . $e->getMessage() . PHP_EOL, FILE_APPEND);
        Response::json([
            'success' => false,
            'error' => [
                'code' => 'SERVER_ERROR',
                'message' => 'Login failed: ' . $e->getMessage()
            ]
        ], 500);
    }
    exit; 
}
if (route('GET', '/api/v1/auth/me')) { Auth::requireAuth(); (new AuthController())->me(); exit; }
if (route('POST', '/api/v1/auth/forgot-password')) { (new AuthController())->forgotPassword(); exit; }
if (route('POST', '/api/v1/auth/reset-password')) { (new AuthController())->resetPassword(); exit; }
if (route('POST', '/api/v1/auth/change-password')) { Auth::requireAuth(); (new AuthController())->changePassword(); exit; }

// ======================
// USER ROUTES
// ======================
if (route('PUT', '/api/v1/users/me')) { Auth::requireAuth(); (new UserController())->updateMe(); exit; }
// POST alternative for Android compatibility
if (route('POST', '/api/v1/users/me/update')) { Auth::requireAuth(); (new UserController())->updateMe(); exit; }
if (route('GET', '/api/v1/users')) { Auth::requireAuth(); (new AdminController())->listUsers(); exit; }

// ======================
// PATIENT ROUTES
// ======================
if (route('GET', '/api/v1/patients/me/overview')) { Auth::requireAuth(); (new PatientController())->overviewMe(); exit; }
if (route('GET', '/api/v1/patients')) { Auth::requireAuth(); (new PatientController())->listAll(); exit; }
if (preg_match('#^/api/v1/patients/(\d+)$#', $uri, $m) && $_SERVER['REQUEST_METHOD'] === 'GET') {
    Auth::requireAuth(); (new PatientController())->get((int)$m[1]); exit;
}
// Use POST for update instead of PUT (Android compatibility)
if (preg_match('#^/api/v1/patients/(\d+)/update$#', $uri, $m) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    Auth::requireAuth(); (new PatientController())->update((int)$m[1]); exit;
}

// ======================
// ADMIN ROUTES
// ======================
if (route('GET', '/api/v1/admin/test')) { Response::json(['success'=>true,'message'=>'Admin routes working','uri'=>$uri]); exit; }
if (route('POST', '/api/v1/admin/users')) { Auth::requireAuth(); (new AdminController())->createUser(); exit; }
if (route('POST', '/api/v1/admin/assign-patient')) { Auth::requireAuth(); (new AdminController())->assignPatientToDoctor(); exit; }
if (route('GET', '/api/v1/admin/doctors')) { Auth::requireAuth(); (new AdminController())->listDoctors(); exit; }

// Delete user routes
if (preg_match('#^/api/v1/admin/users/(\d+)/delete$#', $uri, $m) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    Auth::requireAuth(); (new AdminController())->deleteUser(); exit;
}
if (preg_match('#^/api/v1/admin/users/(\d+)$#', $uri, $m) && $_SERVER['REQUEST_METHOD'] === 'DELETE') {
    Auth::requireAuth(); (new AdminController())->deleteUser(); exit;
}

// Get user by ID route
if (preg_match('#^/api/v1/admin/users/(\d+)$#', $uri, $m) && $_SERVER['REQUEST_METHOD'] === 'GET') {
    Auth::requireAuth(); (new AdminController())->getUserById(); exit;
}

// Update user route
if (preg_match('#^/api/v1/admin/users/(\d+)$#', $uri, $m) && $_SERVER['REQUEST_METHOD'] === 'PUT') {
    Auth::requireAuth(); (new AdminController())->updateUser(); exit;
}

// ======================
// DOCTOR ROUTES
// ======================
if (route('GET', '/api/v1/doctor/overview')) { Auth::requireAuth(); (new DoctorController())->overview(); exit; }
if (route('POST', '/api/v1/doctor/assign-medication')) { Auth::requireAuth(); (new MedicationController())->assign(); exit; }

// ======================
// APPOINTMENTS
// ======================
if (route('GET', '/api/v1/appointments')) { Auth::requireAuth(); (new AppointmentController())->list(); exit; }
if (route('POST', '/api/v1/appointments')) { Auth::requireAuth(); (new AppointmentController())->create(); exit; }
if (preg_match('#^/api/v1/appointments/(\d+)$#', $uri, $m) && $_SERVER['REQUEST_METHOD'] === 'GET') {
    Auth::requireAuth(); (new AppointmentController())->get((int)$m[1]); exit;
}
if (preg_match('#^/api/v1/appointments/(\d+)$#', $uri, $m) && $_SERVER['REQUEST_METHOD'] === 'PUT') {
    Auth::requireAuth(); (new AppointmentController())->update((int)$m[1]); exit;
}
if (preg_match('#^/api/v1/appointments/(\d+)/delete$#', $uri, $m) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    Auth::requireAuth(); (new AppointmentController())->delete((int)$m[1]); exit;
}

// ======================
// REPORT ROUTES
// ======================
if (route('GET', '/api/v1/reports')) { Auth::requireAuth(); (new ReportController())->list(); exit; }
if (route('POST', '/api/v1/reports')) { Auth::requireAuth(); (new ReportController())->create(); exit; }
if (preg_match('#^/api/v1/reports/(\d+)$#', $uri, $m) && $_SERVER['REQUEST_METHOD'] === 'GET') {
    Auth::requireAuth(); (new ReportController())->get((int)$m[1]); exit;
}
if (preg_match('#^/api/v1/reports/(\d+)$#', $uri, $m) && $_SERVER['REQUEST_METHOD'] === 'DELETE') {
    Auth::requireAuth(); (new ReportController())->delete((int)$m[1]); exit;
}
// POST alternative for delete (Android compatibility)
if (preg_match('#^/api/v1/reports/(\d+)/delete$#', $uri, $m) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    Auth::requireAuth(); (new ReportController())->delete((int)$m[1]); exit;
}
if (route('POST', '/api/v1/reports/notes')) { Auth::requireAuth(); (new ReportNoteController())->create(); exit; }
if (preg_match('#^/api/v1/reports/(\d+)/notes$#', $uri, $m) && $_SERVER['REQUEST_METHOD'] === 'GET') {
    Auth::requireAuth(); (new ReportNoteController())->get((int)$m[1]); exit;
}

// ⭐ STATUS ROUTE — required by mobile app to update report status
if (route('POST', '/api/v1/reports/status')) {
    Auth::requireAuth();
    (new ReportController())->updateStatus();
    exit;
}

// ======================
// CRP ROUTES
// ======================
if (route('GET', '/api/v1/crp/test')) { Response::json(['success'=>true,'message'=>'CRP routes working','timestamp'=>date('Y-m-d H:i:s')]); exit; }
if (preg_match('#^/api/v1/crp/history/(\d+)$#', $uri, $m) && $_SERVER['REQUEST_METHOD'] === 'GET') {
    Auth::requireAuth(); (new CrpController())->getHistory((int)$m[1]); exit;
}
if (route('POST', '/api/v1/crp')) { Auth::requireAuth(); (new CrpController())->create(); exit; }

// ======================
// MEDICATION
// ======================
if (route('GET', '/api/v1/medications')) { Auth::requireAuth(); (new MedicationController())->search(); exit; }
if (route('GET', '/api/v1/patient-medications')) { Auth::requireAuth(); (new MedicationController())->listForPatient(); exit; }
if (route('POST', '/api/v1/patient-medications')) { Auth::requireAuth(); (new MedicationController())->assign(); exit; }
if (preg_match('#^/api/v1/patient-medications/(\d+)$#', $uri, $m) && $_SERVER['REQUEST_METHOD'] === 'PATCH') {
    Auth::requireAuth(); (new MedicationController())->setActive((int)$m[1]); exit;
}
if (preg_match('#^/api/v1/patient-medications/(\d+)$#', $uri, $m) && $_SERVER['REQUEST_METHOD'] === 'DELETE') {
    Auth::requireAuth(); (new MedicationController())->delete((int)$m[1]); exit;
}
// POST alternative for delete (Android compatibility)
if (preg_match('#^/api/v1/patient-medications/(\d+)/delete$#', $uri, $m) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    Auth::requireAuth(); (new MedicationController())->delete((int)$m[1]); exit;
}
if (route('GET', '/api/v1/medication-logs')) { Auth::requireAuth(); (new MedicationController())->listLogs(); exit; }
if (route('POST', '/api/v1/medication-logs')) { Auth::requireAuth(); (new MedicationController())->logIntake(); exit; }
if (route('POST', '/api/v1/medications/log')) { Auth::requireAuth(); (new MedicationController())->logIntake(); exit; }
if (route('GET', '/api/v1/medication-logs/test')) { Auth::requireAuth(); Response::json(['success'=>true,'message'=>'Medication logs endpoint working','timestamp'=>date('Y-m-d H:i:s')]); exit; }
if (route('POST', '/api/v1/medication-logs/debug')) { 
    Auth::requireAuth(); 
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    Response::json([
        'success'=>true,
        'message'=>'Debug endpoint working',
        'received_data'=>$input,
        'auth'=>$_SERVER['auth'] ?? [],
        'timestamp'=>date('Y-m-d H:i:s')
    ]); 
    exit; 
}

// Admin medication management
if (route('DELETE', '/api/v1/admin/patient-medications/clear-all')) { Auth::requireAuth(); (new MedicationController())->clearAllPatientMedications(); exit; }
if (route('GET', '/api/v1/admin/patient-medications/all')) { Auth::requireAuth(); (new MedicationController())->getAllPatientMedications(); exit; }

// ======================
// REHAB SYSTEM (NEW)
// ======================
// Test endpoint without auth
if (route('GET', '/api/v1/rehabs-test')) {
    Response::json(['success' => true, 'message' => 'Rehab test endpoint working', 'uri' => $uri]);
    exit;
}

if (route('GET', '/api/v1/rehabs')) { Auth::requireAuth(); (new RehabController())->listAll(); exit; }
if (route('POST', '/api/v1/assign-rehab')) { Auth::requireAuth(); (new RehabController())->assign(); exit; }
if (preg_match('#^/api/v1/patient/(\d+)/rehabs$#', $uri, $m) && $_SERVER['REQUEST_METHOD'] === 'GET') {
    Auth::requireAuth(); (new RehabController())->listForPatient((int)$m[1]); exit;
}
if (route('POST', '/api/v1/rehab-status')) { Auth::requireAuth(); (new RehabController())->updateStatus(); exit; }


// ======================
// NOTIFICATIONS
// ======================
if (route('GET', '/api/v1/notifications')) { Auth::requireAuth(); (new NotificationController())->listMine(); exit; }
if (preg_match('#^/api/v1/notifications/(\d+)/read$#', $uri, $m) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    Auth::requireAuth(); (new NotificationController())->markRead((int)$m[1]); exit;
}

// ======================
// EDUCATION
// ======================
if (route('GET', '/api/v1/education/articles')) { (new EducationController())->list(); exit; }
if (preg_match('#^/api/v1/education/articles/([A-Za-z0-9_-]+)$#', $uri, $m)
    && $_SERVER['REQUEST_METHOD'] === 'GET') {
    (new EducationController())->getBySlug($m[1]); exit;
}

// ======================
// SYMPTOMS
// ======================
if (route('GET', '/api/v1/symptoms')) { Auth::requireAuth(); (new SymptomController())->list(); exit; }
if (route('POST', '/api/v1/symptoms')) { Auth::requireAuth(); (new SymptomController())->create(); exit; }

// ======================
// METRICS
// ======================
if (route('GET', '/api/v1/health-metrics')) { Auth::requireAuth(); (new MetricController())->list(); exit; }
if (route('POST', '/api/v1/health-metrics')) { Auth::requireAuth(); (new MetricController())->create(); exit; }

// ======================
// SETTINGS
// ======================
if (route('GET', '/api/v1/settings')) { Auth::requireAuth(); (new SettingsController())->getMine(); exit; }
if (route('PUT', '/api/v1/settings')) { Auth::requireAuth(); (new SettingsController())->putMine(); exit; }
// POST alternative for Android compatibility
if (route('POST', '/api/v1/settings/update')) { Auth::requireAuth(); (new SettingsController())->putMine(); exit; }

// ======================
// CHATBOT & CONVERSATION MANAGEMENT
// ======================
if (route('POST', '/api/v1/chatbot/chat')) { Auth::requireAuth(); (new ChatbotController())->chat(); exit; }
if (route('GET', '/api/v1/chatbot/history')) { Auth::requireAuth(); (new ChatbotController())->history(); exit; }
if (route('GET', '/api/v1/chatbot/session/history')) { Auth::requireAuth(); (new ChatbotController())->sessionHistory(); exit; }
if (route('POST', '/api/v1/chatbot/session/end')) { Auth::requireAuth(); (new ChatbotController())->endSession(); exit; }
if (route('GET', '/api/v1/chatbot/session/context')) { Auth::requireAuth(); (new ChatbotController())->getContext(); exit; }

// ======================
// EXERCISE TRACKING SYSTEM
// ======================
// Exercise Library Routes
if (route('GET', '/api/v1/exercises')) { Auth::requireAuth(); (new ExerciseController())->getAllExercises(); exit; }
if (preg_match('#^/api/v1/exercises/([A-Za-z0-9_-]+)$#', $uri, $m) && $_SERVER['REQUEST_METHOD'] === 'GET') {
    Auth::requireAuth(); (new ExerciseController())->getExerciseById($m[1]); exit;
}
if (preg_match('#^/api/v1/exercises/category/([A-Z]+)$#', $uri, $m) && $_SERVER['REQUEST_METHOD'] === 'GET') {
    Auth::requireAuth(); (new ExerciseController())->getExercisesByCategory($m[1]); exit;
}

// Exercise Assignment Routes
if (route('POST', '/api/v1/exercise-assignments')) { Auth::requireAuth(); (new ExerciseController())->createAssignment(); exit; }
if (route('GET', '/api/v1/exercise-assignments/patient')) { Auth::requireAuth(); (new ExerciseController())->getPatientAssignments(); exit; }
if (route('GET', '/api/v1/exercise-assignments/doctor')) { Auth::requireAuth(); (new ExerciseController())->getDoctorAssignments(); exit; }
if (preg_match('#^/api/v1/exercise-assignments/([A-Za-z0-9_-]+)$#', $uri, $m) && $_SERVER['REQUEST_METHOD'] === 'PUT') {
    Auth::requireAuth(); (new ExerciseController())->updateAssignment($m[1]); exit;
}
if (preg_match('#^/api/v1/exercise-assignments/([A-Za-z0-9_-]+)$#', $uri, $m) && $_SERVER['REQUEST_METHOD'] === 'DELETE') {
    Auth::requireAuth(); (new ExerciseController())->deleteAssignment($m[1]); exit;
}

// Exercise Session Routes
if (route('POST', '/api/v1/exercise-sessions')) { Auth::requireAuth(); (new ExerciseSessionController())->createSession(); exit; }
if (preg_match('#^/api/v1/exercise-sessions/patient/(\d+)$#', $uri, $m) && $_SERVER['REQUEST_METHOD'] === 'GET') {
    Auth::requireAuth(); (new ExerciseSessionController())->getPatientSessions((int)$m[1]); exit;
}
if (preg_match('#^/api/v1/exercise-sessions/([A-Za-z0-9_-]+)/report$#', $uri, $m) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    Auth::requireAuth(); (new ExerciseSessionController())->generateReport($m[1]); exit;
}

// Exercise Report Routes
if (preg_match('#^/api/v1/exercise-reports/patient/(\d+)$#', $uri, $m) && $_SERVER['REQUEST_METHOD'] === 'GET') {
    Auth::requireAuth(); (new ExerciseSessionController())->getPatientReports((int)$m[1]); exit;
}



// ======================
// AI ROUTES - Medical Report Analysis & Predictions
// ======================
use Src\Controllers\AIController;

// Process report with OCR
if (route('POST', '/api/v1/ai/reports/process')) { Auth::requireAuth(); (new AIController())->processReport(); exit; }

// Get extracted data from report
if (preg_match('#^/api/v1/ai/reports/(\d+)/extracted-data$#', $uri, $m) && $_SERVER['REQUEST_METHOD'] === 'GET') {
    Auth::requireAuth(); (new AIController())->getExtractedData((int)$m[1]); exit;
}

// Get trend analysis for patient
if (preg_match('#^/api/v1/ai/patients/(\d+)/trends$#', $uri, $m) && $_SERVER['REQUEST_METHOD'] === 'GET') {
    Auth::requireAuth(); (new AIController())->getTrends((int)$m[1]); exit;
}

// Verify/correct extracted lab value
if (preg_match('#^/api/v1/ai/lab-values/(\d+)/verify$#', $uri, $m) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    Auth::requireAuth(); (new AIController())->verifyLabValue((int)$m[1]); exit;
}

// Get flare-up prediction for patient
if (preg_match('#^/api/v1/ai/patients/(\d+)/prediction$#', $uri, $m) && $_SERVER['REQUEST_METHOD'] === 'GET') {
    Auth::requireAuth(); (new AIController())->getFlareUpPrediction((int)$m[1]); exit;
}

// Report actual flare-up occurrence
if (route('POST', '/api/v1/ai/flareup/report')) { Auth::requireAuth(); (new AIController())->reportFlareUp(); exit; }

// Get AI system status (for monitoring)
if (route('GET', '/api/v1/ai/status')) { Auth::requireAuth(); (new AIController())->getSystemStatus(); exit; }

// ======================
// 404
// ======================
Response::json([
    'success' => false,
    'error' => [
        'code' => 'NOT_FOUND',
        'message' => 'Endpoint not found'
    ]
], 404);
