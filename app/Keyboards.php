<?php

namespace App;

use Telegram\Bot\Keyboard\Keyboard;

class Keyboards
{
    public static function adminKeyboard()
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
                Keyboard::button('Помощь'),
            ]);

        return $reply_markup; 
    }
}