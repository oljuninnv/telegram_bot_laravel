<?php

namespace App\Enums;

enum DayOfWeekEnums: string
{
    case ПОНЕДЕЛЬНИК = 'понедельник';
    case ВТОРНИК = 'вторник';
    case СРЕДА = 'среда';
    case ЧЕТВЕРГ = 'четверг';
    case ПЯТНИЦА = 'пятница';
    case СУББОТА = 'суббота';
    case ВОСКРЕСЕНЬЕ = 'воскресенье';

    public static function getAllDays(): array
    {
        return [
            self::ПОНЕДЕЛЬНИК,
            self::ВТОРНИК,
            self::СРЕДА,
            self::ЧЕТВЕРГ,
            self::ПЯТНИЦА,
            self::СУББОТА,
            self::ВОСКРЕСЕНЬЕ,
        ];
    }
}