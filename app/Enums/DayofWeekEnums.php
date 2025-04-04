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

    public static function getAllDaysAdmin(): array
    {
        return [
            self::ПОНЕДЕЛЬНИК->value => self::ПОНЕДЕЛЬНИК->name,
            self::ВТОРНИК->value => self::ВТОРНИК->name,
            self::СРЕДА->value => self::СРЕДА->name,
            self::ЧЕТВЕРГ->value => self::ЧЕТВЕРГ->name,
            self::ПЯТНИЦА->value => self::ПЯТНИЦА->name,
            self::СУББОТА->value => self::СУББОТА->name,
            self::ВОСКРЕСЕНЬЕ->value => self::ВОСКРЕСЕНЬЕ->name,
        ];
    }
}