<?php

namespace App\Enums;

enum RoleEnum: string
{
    case USER = 'user';
    case ADMIN = 'admin';
    case SUPER_ADMIN = 'super-admin';

    /**
     * Получить все роли в виде массива значений.
     *
     * @return array
     */
    public static function getAllRoles(): array
    {
        return array_column(self::cases(), 'value');
    }
}