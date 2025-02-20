<?php

namespace App;

use Telegram\Bot\Keyboard\Keyboard;

class Keyboards
{
    public static function mainAdminKeyboard()
    {
        return Keyboard::make()
            ->setResizeKeyboard(true)
            ->setOneTimeKeyboard(true)
            ->row([
                Keyboard::button('Получить список чатов'),
            ])
            ->row([
                Keyboard::button('Настройка сбора отчетов'),
                Keyboard::button('Проверить отчеты'),
            ])
            ->row([
                Keyboard::button('Получить отчеты'),
                Keyboard::button('Помощь'),
            ]);
    }

    public static function settingsAdminKeyboard()
    {
        return Keyboard::make()
            ->setResizeKeyboard(true)
            ->setOneTimeKeyboard(true)
            ->row([
                Keyboard::button('Установить день недели'),
                Keyboard::button('Установить период'),
            ])
            ->row([
                Keyboard::button('Установить время сбора отчёта'),
                Keyboard::button('Управление хэштегами'),
                Keyboard::button('Назад'),
            ]);
    }
}