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
                Keyboard::button('Настроить сбор отчётов'),
            ])
            ->row([
                Keyboard::button('Назад'),
            ]);
    }

    public static function backAdminKeyboard()
    {
        return Keyboard::make()
            ->setResizeKeyboard(true)
            ->setOneTimeKeyboard(true)
            ->row([
                Keyboard::button('Назад'),
            ]);
    }

    public static function getDaysOfWeekKeyboard()
    {
        return Keyboard::make()
            ->inline()
            ->row([
                Keyboard::inlineButton(['text' => 'Понедельник', 'callback_data' => 'Понедельник']),
                Keyboard::inlineButton(['text' => 'Вторник', 'callback_data' => 'Вторник']),
                Keyboard::inlineButton(['text' => 'Среда', 'callback_data' => 'Среда']),
            ])
            ->row([
                Keyboard::inlineButton(['text' => 'Четверг', 'callback_data' => 'Четверг']),
                Keyboard::inlineButton(['text' => 'Пятница', 'callback_data' => 'Пятница']),
                Keyboard::inlineButton(['text' => 'Суббота', 'callback_data' => 'Суббота']),
                Keyboard::inlineButton(['text' => 'Воскресенье', 'callback_data' => 'Воскресенье']),
            ]);
    }
}