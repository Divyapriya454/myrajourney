<?php
declare(strict_types=1);

namespace Src\Config;

class Cors
{
	public static function allow(): void
	{
		header('Access-Control-Allow-Origin: ' . ($_ENV['CORS_ORIGINS'] ?? '*'));
		header('Access-Control-Allow-Headers: Content-Type, Authorization');
		header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
		header('Content-Type: application/json; charset=utf-8');
	}

	public static function preflight(): void
	{
		self::allow();
		http_response_code(204);
	}
}




















