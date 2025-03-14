<?php

namespace App\Helpers;

use Telegram\Bot\Keyboard\Keyboard;

trait KeyboardHelper
{
    protected static function addPagination(Keyboard $keyboard, int $currentPage, int $totalPages, string $callbackPrefix): Keyboard
    {
        $paginationRow = [];

        if ($currentPage > 1) {
            $paginationRow[] = Keyboard::inlineButton([
                'text' => 'Назад',
                'callback_data' => "{$callbackPrefix}_" . ($currentPage - 1),
            ]);
        }

        $paginationRow[] = Keyboard::inlineButton([
            'text' => "Страница {$currentPage} из {$totalPages}",
            'callback_data' => 'ignore',
        ]);

        if ($currentPage < $totalPages) {
            $paginationRow[] = Keyboard::inlineButton([
                'text' => 'Вперед',
                'callback_data' => "{$callbackPrefix}_" . ($currentPage + 1),
            ]);
        }

        $keyboard->row($paginationRow);

        return $keyboard;
    }

    protected static function addExitButton(Keyboard $keyboard): Keyboard
    {
        $keyboard->row([
            Keyboard::inlineButton([
                'text' => 'Выйти',
                'callback_data' => 'exit',
            ]),
        ]);

        return $keyboard;
    }
}