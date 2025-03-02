<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class UserDataService
{
    protected const DATA_KEY = 'user_data_';

    /**
     * Устанавливает данные для пользователя.
     *
     * @param int $userId
     * @param array $data
     */
    public static function setData(int $userId, array $data): void
    {
        Cache::put(self::DATA_KEY . $userId, $data);
    }

    /**
     * Получает данные пользователя.
     *
     * @param int $userId
     * @return array|null
     */
    public static function getData(int $userId): ?array
    {
        return Cache::get(self::DATA_KEY . $userId);
    }

    /**
     * Очищает данные пользователя.
     *
     * @param int $userId
     */
    public static function clearData(int $userId): void
    {
        Cache::forget(self::DATA_KEY . $userId);
    }
}