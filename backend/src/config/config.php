<?php
declare(strict_types=1);

namespace Src\Config;

class Config
{
	public static function get(string $key, $default = null)
	{
		return $_ENV[$key] ?? $default;
	}
}




















