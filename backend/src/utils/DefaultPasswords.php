<?php
declare(strict_types=1);

namespace Src\Utils;

class DefaultPasswords
{
    /**
     * Get default password for a role
     */
    public static function getDefaultPassword(string $role): string
    {
        $defaults = [
            'PATIENT' => 'Welcome@456',
            'DOCTOR' => 'Patrol@987',
            'ADMIN' => 'AD@Saveetha123'
        ];

        return $defaults[strtoupper($role)] ?? 'Welcome@456';
    }

    /**
     * Get hashed default password for a role
     */
    public static function getHashedDefaultPassword(string $role): string
    {
        return password_hash(self::getDefaultPassword($role), PASSWORD_BCRYPT);
    }
}
