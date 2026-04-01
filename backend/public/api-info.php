<?php
header('Content-Type: application/json');
echo json_encode([
	'base_url' => 'http://localhost/backend/public',
	'available_endpoints' => [
		'Auth' => [
			'POST /api/v1/auth/register' => 'Register new user',
			'POST /api/v1/auth/login' => 'Login user',
			'GET /api/v1/auth/me' => 'Get current user (requires auth)',
		],
		'Patient' => [
			'GET /api/v1/patients/me/overview' => 'Patient overview (requires auth)',
		],
		'Doctor' => [
			'GET /api/v1/doctor/overview' => 'Doctor overview (requires auth)',
		],
		'Appointments' => [
			'GET /api/v1/appointments' => 'List appointments (requires auth)',
			'POST /api/v1/appointments' => 'Create appointment (requires auth)',
			'GET /api/v1/appointments/{id}' => 'Get appointment (requires auth)',
		],
		'Reports' => [
			'GET /api/v1/reports' => 'List reports (requires auth)',
			'POST /api/v1/reports' => 'Create report (requires auth)',
			'GET /api/v1/reports/{id}' => 'Get report (requires auth)',
		],
		'Medications' => [
			'GET /api/v1/patient-medications' => 'List medications (requires auth)',
			'POST /api/v1/patient-medications' => 'Assign medication (requires auth)',
			'PATCH /api/v1/patient-medications/{id}' => 'Set medication active (requires auth)',
			'POST /api/v1/medication-logs' => 'Log medication intake (requires auth)',
		],
		'Rehab' => [
			'GET /api/v1/rehab-plans' => 'List rehab plans (requires auth)',
			'POST /api/v1/rehab-plans' => 'Create rehab plan (requires auth)',
			'GET /api/v1/rehab-plans/{id}' => 'Get rehab plan (requires auth)',
		],
		'Notifications' => [
			'GET /api/v1/notifications' => 'List notifications (requires auth)',
			'POST /api/v1/notifications/{id}/read' => 'Mark notification as read (requires auth)',
		],
		'Education' => [
			'GET /api/v1/education/articles' => 'List education articles (public)',
			'GET /api/v1/education/articles/{slug}' => 'Get article by slug (public)',
		],
		'Symptoms' => [
			'GET /api/v1/symptoms' => 'List symptoms (requires auth)',
			'POST /api/v1/symptoms' => 'Create symptom log (requires auth)',
		],
		'Metrics' => [
			'GET /api/v1/health-metrics' => 'List health metrics (requires auth)',
			'POST /api/v1/health-metrics' => 'Create health metric (requires auth)',
		],
		'Settings' => [
			'GET /api/v1/settings' => 'Get settings (requires auth)',
			'PUT /api/v1/settings' => 'Update settings (requires auth)',
		],
	],
	'notes' => [
		'All endpoints except /auth/register, /auth/login, and /education/* require authentication',
		'Use Bearer token in Authorization header: Authorization: Bearer {token}',
		'Test endpoints:',
		'  - GET http://localhost/backend/public/api/v1/education/articles (public)',
		'  - POST http://localhost/backend/public/api/v1/auth/register (public)',
		'  - POST http://localhost/backend/public/api/v1/auth/login (public)',
	]
], JSON_PRETTY_PRINT);


