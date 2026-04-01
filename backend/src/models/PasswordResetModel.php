<?php
declare(strict_types=1);

namespace Src\Models;

class PasswordResetModel
{
    // Token-based reset removed.
    // Keeping an empty class for compatibility so nothing breaks.

    public function createToken(int $userId, string $token, int $ttlSeconds): void
    {
        // No longer used
    }

    public function findValid(string $token): ?array
    {
        // No longer used
        return null;
    }

    public function consume(int $id): void
    {
        // No longer used
    }
}
