<?php

namespace App\Enums;

enum RoleEnum: string
{
    case USER = 'user';
    case ADMIN = 'admin';
    case SUPER_ADMIN = 'super-admin';

    /**
     * Получить все возможные роли.
     *
     * @return array
     */
    public static function getAllRoles(): array
    {
        return [
            self::USER->value,
            self::ADMIN->value,
            self::SUPER_ADMIN->value,
        ];
    }
}