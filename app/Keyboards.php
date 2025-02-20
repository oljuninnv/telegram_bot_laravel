<?php

namespace App;

use Telegram\Bot\Keyboard\Keyboard;
use Telegram\Bot\Keyboard\Button;

class Keyboards
{
    public static function mainAdminKeyboard()
    {
        $reply_markup = Keyboard::make()
            ->setResizeKeyboard(true)
            ->setOneTimeKeyboard(true)
            ->row([
                Keyboard::button('Настройка сбора отчетов'),
                Keyboard::button('Проверить отчеты'),
            ])
            ->row([
                Keyboard::button('Получить отчеты'),
                Keyboard::button('Получить список чатов'),
            ])
            ->row([
                KeyBoard::button('Помощь')
            ]);

        return $reply_markup;
    }

    public function settingsAdminKeyboard()
    {
        $reply_markup = Keyboard::make()
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
            ])
            ->row([
                Keyboard::button('Назад'),
            ]);

        return $reply_markup;
    }
}