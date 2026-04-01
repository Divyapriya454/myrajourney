<?php
declare(strict_types=1);

namespace Src\Utils;

class Response
{
	public static function json($data, int $code = 200): void
	{
		// Clean any output buffer to prevent text before JSON
		if (ob_get_level() > 0) {
			ob_clean();
		}
		
		header('Content-Type: application/json');
		http_response_code($code);
		echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
		
		// Flush and end output buffering
		if (ob_get_level() > 0) {
			ob_end_flush();
		}
	}
}




















