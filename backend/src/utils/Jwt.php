<?php
declare(strict_types=1);

namespace Src\Utils;

class Jwt
{
	public static function encode(array $payload, string $secret, string $alg = 'HS256'): string
	{
		$header = ['typ' => 'JWT', 'alg' => $alg];
		$segments = [
			self::b64(json_encode($header)),
			self::b64(json_encode($payload)),
		];
		$signingInput = implode('.', $segments);
		$signature = self::sign($signingInput, $secret, $alg);
		$segments[] = self::b64($signature, raw: true);
		return implode('.', $segments);
	}

	public static function decode(string $jwt, string $secret): array
	{
		[$h, $p, $s] = explode('.', $jwt);
		$payload = json_decode(self::ub64($p), true) ?? [];
		$header = json_decode(self::ub64($h), true) ?? [];
		$valid = hash_equals(self::b64(self::sign("$h.$p", $secret, $header['alg'] ?? 'HS256'), raw: true), $s);
		if (!$valid) throw new \Exception('Invalid token');
		if (isset($payload['exp']) && time() >= $payload['exp']) throw new \Exception('Token expired');
		return $payload;
	}

	private static function sign(string $input, string $secret, string $alg): string
	{
		return match ($alg) {
			'HS256' => hash_hmac('sha256', $input, $secret, true),
			default => throw new \Exception('Unsupported alg'),
		};
	}

	private static function b64(string $data, bool $raw = false): string
	{
		$enc = $raw ? rtrim(strtr(base64_encode($data), '+/', '-_'), '=') : rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
		return $enc;
	}

	private static function ub64(string $data): string
	{
		$remainder = strlen($data) % 4;
		if ($remainder) $data .= str_repeat('=', 4 - $remainder);
		return base64_decode(strtr($data, '-_', '+/')) ?: '';
	}
}




















