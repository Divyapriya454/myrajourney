<?php
declare(strict_types=1);

namespace Src\Utils;

class Upload
{
    private static function debug($msg)
    {
        $logDir = __DIR__ . '/../../storage/logs';
        if (!is_dir($logDir)) mkdir($logDir, 0775, true);
        $file = $logDir . '/upload_debug.log';

        file_put_contents($file, "[" . date('Y-m-d H:i:s') . "] $msg\n", FILE_APPEND);
    }

    public static function saveReport(array $file): array
    {
        self::debug("=== Upload attempt ===");
        self::debug("Incoming file: " . print_r($file, true));

        if (!isset($file['error'])) {
            self::debug("ERROR: No error field in file array");
            throw new \Exception("Upload error: No error field");
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            self::debug("ERROR: Upload error code: " . $file['error']);
            throw new \Exception("Upload error: " . $file['error']);
        }

        // Allowed MIME
        $allowed = [
            'application/pdf',
            'application/octet-stream',
            'image/png',
            'image/jpeg',
            'image/jpg',
            'image/webp',
            'text/plain'
        ];

        self::debug("MIME OK: " . $file['type']);

        if (!in_array($file['type'], $allowed)) {
            self::debug("Blocked MIME: " . $file['type']);
            throw new \Exception("Invalid MIME");
        }

        // ===========================================================
        // ✔ FIXED DIRECTORY PATH - Use public directory (working path)
        // ===========================================================
        $subDir = date('Y/m');
        $base = __DIR__ . '/../../public/uploads/reports/' . $subDir;

        if (!is_dir($base)) {
            mkdir($base, 0775, true);
            self::debug("Created directory: $base");
        }

        // Detect extension
        $ext = pathinfo($file['name'] ?? '', PATHINFO_EXTENSION);
        if (!$ext) {
            $ext = match ($file['type']) {
                'application/pdf' => 'pdf',
                'image/png' => 'png',
                'image/jpeg', 'image/jpg' => 'jpg',
                'image/webp' => 'webp',
                default => 'bin'
            };
        }

        $name = bin2hex(random_bytes(8)) . '.' . $ext;
        $path = "$base/$name";

        self::debug("Saving to $path");

        if (!move_uploaded_file($file['tmp_name'], $path)) {
            self::debug("move_uploaded_file FAILED!");
            throw new \Exception("Move failed");
        }

        self::debug("Upload success!");

        // Return correct public URL
        return [
            'file_url'   => "/uploads/reports/$subDir/$name",
            'mime_type'  => $file['type'],
            'size_bytes' => (int)$file['size']
        ];
    }
}
