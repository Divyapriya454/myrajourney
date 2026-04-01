<?php
declare(strict_types=1);

namespace Src\Config;

use PDO;
use PDOException;

class DB
{
    private static ?PDO $pdo = null;

    public static function conn(): PDO
    {
        if (self::$pdo !== null) {
            try {
                self::$pdo->query('SELECT 1');
                return self::$pdo;
            } catch (PDOException $e) {
                self::$pdo = null;
            }
        }

        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=utf8mb4',
            Config::get('DB_HOST', '127.0.0.1'),
            Config::get('DB_NAME', 'myrajourney')
        );

        self::$pdo = new PDO(
            $dsn,
            Config::get('DB_USER', 'root'),
            Config::get('DB_PASS', ''),
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_PERSISTENT         => false,
            ]
        );

        return self::$pdo;
    }
}
