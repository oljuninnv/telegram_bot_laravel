<?php

namespace App\Services;

use Cache;

class UserState
{
    protected static array $states = [];

    protected const STATE_KEY = 'user_state_';

    public static function setState(int $userId, string $state): void
    {
        self::$states[$userId] = $state;
        Cache::set(self::STATE_KEY . $userId, $state);
    }

    public static function getState(int $userId): string
    {
        return Cache::get(self::STATE_KEY.$userId) ?? 'main';
    }

    public static function resetState(int $userId): void
    {
        self::$states[$userId] = 'main';
        Cache::forget(self::STATE_KEY.$userId);
    }
}