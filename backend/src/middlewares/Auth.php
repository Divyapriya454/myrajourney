<?php
declare(strict_types=1);

namespace Src\Middlewares;

use Src\Utils\Jwt;
use Src\Utils\Response;

class Auth
{
	public static function bearer(): ?array
	{
		// Try multiple sources for Authorization header
		$hdr = '';
		
		// Check $_SERVER['HTTP_AUTHORIZATION'] (standard)
		if (!empty($_SERVER['HTTP_AUTHORIZATION'])) {
			$hdr = $_SERVER['HTTP_AUTHORIZATION'];
		}
		// Check $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] (Apache mod_rewrite)
		elseif (!empty($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
			$hdr = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
		}
		// Check apache_request_headers() (fallback)
		elseif (function_exists('apache_request_headers')) {
			$headers = apache_request_headers();
			if (isset($headers['Authorization'])) {
				$hdr = $headers['Authorization'];
			}
		}
		
		if (empty($hdr) || !preg_match('/Bearer\s+(.*)$/i', $hdr, $m)) {
			return null;
		}
		
		$token = trim($m[1]);
		try {
			$payload = Jwt::decode($token, $_ENV['JWT_SECRET'] ?? '');
			return $payload;
		} catch (\Throwable $e) {
			return null;
		}
	}

	public static function requireAuth(): void
	{
		$payload = self::bearer();
		if (!$payload) {
			Response::json(['success'=>false,'error'=>['code'=>'UNAUTHORIZED','message'=>'Unauthorized']], 401);
			exit;
		}
		$_SERVER['auth'] = $payload;
	}
}






