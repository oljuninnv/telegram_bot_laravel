<?php

namespace App\Services;

class UserState
{
    protected static array $states = [];

    public static function setState(int $userId, string $state): void
    {
        self::$states[$userId] = $state;
    }

    public static function getState(int $userId): string
    {
        return self::$states[$userId] ?? 'main';
    }

    public static function resetState(int $userId): void
    {
        self::$states[$userId] = 'main';
    }
}