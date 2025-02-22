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
                Keyboard::button('Получить отчеты'),
            ])
            ->row([
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

    public static function updateSettingsKeyboard()
    {
        return Keyboard::make()
            ->row([
                Keyboard::button(['text' => 'Обновить период', 'callback_data' => 'Обновить период']),
                Keyboard::button(['text' => 'Обновить время', 'callback_data' => 'Обновить время']),
            ])
            ->row([
                Keyboard::button(['text' => 'Обновить день недели', 'callback_data' => 'Обновить день недели']),
                Keyboard::button(['text' => 'Обновить хэштеги', 'callback_data' => 'Обновить хэштеги']),
            ])
            ->row([
                Keyboard::button(['text' => 'Назад', 'callback_data' => 'Назад']),
            ]);
    }

    public static function hashtagSettingsKeyboard()
    {
        return Keyboard::make()
            ->row([
                Keyboard::button(['text' => 'Создать хэштег']),
            ])
            ->row([
                Keyboard::button(['text' => 'Удалить хэштег']),
                Keyboard::button(['text' => 'Привязать хэштег']),
            ])
            ->row([
                Keyboard::button(['text' => 'Отвязать хэштег']),
                Keyboard::button(['text' => 'Назад']),
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
                Keyboard::inlineButton(['text' => 'Понедельник', 'callback_data' => 'понедельник']),
                Keyboard::inlineButton(['text' => 'Вторник', 'callback_data' => 'вторник']),
                Keyboard::inlineButton(['text' => 'Среда', 'callback_data' => 'среда']),
            ])
            ->row([
                Keyboard::inlineButton(['text' => 'Четверг', 'callback_data' => 'четверг']),
                Keyboard::inlineButton(['text' => 'Пятница', 'callback_data' => 'пятница']),
                Keyboard::inlineButton(['text' => 'Суббота', 'callback_data' => 'суббота']),
                Keyboard::inlineButton(['text' => 'Воскресенье', 'callback_data' => 'воскресенье']),
            ]);
    }
}