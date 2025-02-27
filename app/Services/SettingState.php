<?php

namespace App\Services;

use Cache;

class SettingState
{
    protected const STATE_KEY_PREFIX = 'setting_state_';

    public static function setStep(int $userId, int $step): void
    {
        Cache::set(self::STATE_KEY_PREFIX . $userId . '_step', $step);
    }

    public static function getStep(int $userId): ?int
    {
        return Cache::get(self::STATE_KEY_PREFIX . $userId . '_step');
    }

    public static function setDayOfWeek(int $userId, string $dayOfWeek): void
    {
        Cache::set(self::STATE_KEY_PREFIX . $userId . '_day', $dayOfWeek);
    }

    public static function getDayOfWeek(int $userId): ?string
    {
        return Cache::get(self::STATE_KEY_PREFIX . $userId . '_day');
    }

    public static function setWeeksInPeriod(int $userId, int $weeksInPeriod): void
    {
        Cache::set(self::STATE_KEY_PREFIX . $userId . '_weeks', $weeksInPeriod);
    }

    public static function getWeeksInPeriod(int $userId): ?int
    {
        return Cache::get(self::STATE_KEY_PREFIX . $userId . '_weeks');
    }

    public static function setReportTime(int $userId, string $time): void
    {
        Cache::set(self::STATE_KEY_PREFIX . $userId . '_time', $time);
    }

    public static function getReportTime(int $userId): ?string
    {
        return Cache::get(self::STATE_KEY_PREFIX . $userId . '_time');
    }

    public static function clearAll(int $userId): void
    {
        Cache::forget(self::STATE_KEY_PREFIX . $userId . '_step');
        Cache::forget(self::STATE_KEY_PREFIX . $userId . '_day');
        Cache::forget(self::STATE_KEY_PREFIX . $userId . '_weeks');
        Cache::forget(self::STATE_KEY_PREFIX . $userId . '_time');
    }
}