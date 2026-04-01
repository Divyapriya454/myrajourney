<?php
declare(strict_types=1);

namespace Src\Utils;

class Env
{
    public static function load(string $path): void
    {
        if (!file_exists($path)) return;

        // Read file into array
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lines as $line) {
            // 1. Clean whitespace from the line
            $line = trim($line);

            // 2. Skip comments (#) or lines that became empty after trim
            if ($line === '' || str_starts_with($line, '#')) continue;

            // 3. CRITICAL FIX: Check if the line actually has an '=' sign
            // If not, skip it to prevent the "Undefined array key" error
            if (!str_contains($line, '=')) continue;

            // 4. Safely split into Key and Value
            [$key, $value] = explode('=', $line, 2);

            // 5. Store in $_ENV, cleaning up quotes and spaces
            $_ENV[trim($key)] = trim(trim($value), "\"' ");
        }
    }
}
