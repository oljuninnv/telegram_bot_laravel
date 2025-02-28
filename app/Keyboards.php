<?php

namespace App;

use Telegram\Bot\Keyboard\Keyboard;
use App\Models\Setting_Hashtag;

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
                Keyboard::button(['text' => 'Назад', 'callback_data' => 'Назад']),
            ]);
    }

    public static function updateSettingsKeyboard()
    {
        return Keyboard::make()
            ->row([
                Keyboard::button(['text' => 'Настроить сбор отчётов', 'callback_data' => 'Настроить сбор отчётов']),
                Keyboard::button(['text' => 'Обновить хэштеги', 'callback_data' => 'Обновить хэштеги']),
            ])
            ->row([
                Keyboard::button(['text' => 'Назад', 'callback_data' => 'Назад']),
            ]);
    }

    // public static function hashtagSettingsKeyboard()
    // {
    //     return Keyboard::make()
    //         ->row([
    //             Keyboard::button(['text' => 'Создать хэштег']),
    //         ])
    //         ->row([
    //             Keyboard::button(['text' => 'Удалить хэштег']),
    //             Keyboard::button(['text' => 'Привязать хэштег']),
    //         ])
    //         ->row([
    //             Keyboard::button(['text' => 'Отвязать хэштег']),
    //             Keyboard::button(['text' => 'Назад']),
    //         ]);
    // }

    public static function hashtagSettingsKeyboard()
    {
        return Keyboard::make()
            ->row([
                Keyboard::button(['text' => 'Создать хэштег']),
                Keyboard::button(['text' => 'Удалить хэштег']),
                Keyboard::button(['text' => 'Привязка хэштега']),
            ])
            ->row([
                Keyboard::button(['text' => 'Назад']),
            ]);
    }

    public static function backAdminKeyboard()
    {
        return Keyboard::make()
            ->setResizeKeyboard(true)
            ->setOneTimeKeyboard(true)
            ->row([
                Keyboard::button(['text' => 'Назад', 'callback_data' => 'Назад']),
            ]);
    }

    public static function getDaysOfWeekKeyboard(bool $settingsExist = true)
    {
        $keyboard = Keyboard::make()->inline();

        // Добавляем кнопку "Оставить текущее" только если настройки существуют
        if ($settingsExist) {
            $keyboard->row([
                Keyboard::inlineButton(['text' => 'Оставить текущее', 'callback_data' => 'Оставить текущее']),
            ]);
        }

        $keyboard->row([
            Keyboard::inlineButton(['text' => 'Понедельник', 'callback_data' => 'понедельник']),
            Keyboard::inlineButton(['text' => 'Вторник', 'callback_data' => 'вторник']),
            Keyboard::inlineButton(['text' => 'Среда', 'callback_data' => 'среда']),
        ])->row([
                    Keyboard::inlineButton(['text' => 'Четверг', 'callback_data' => 'четверг']),
                    Keyboard::inlineButton(['text' => 'Пятница', 'callback_data' => 'пятница']),
                    Keyboard::inlineButton(['text' => 'Суббота', 'callback_data' => 'суббота']),
                    Keyboard::inlineButton(['text' => 'Воскресенье', 'callback_data' => 'воскресенье']),
                ]);

        return $keyboard;
    }

    public static function LeaveTheCurrentKeyboard()
    {
        $keyboard = Keyboard::make()->inline();

        $keyboard->row([
            Keyboard::inlineButton(['text' => 'Оставить текущее', 'callback_data' => 'Оставить текущее']),
        ]);

        return $keyboard;
    }

    public static function HashTagsInlineKeyboard($hashtags, $currentPage = 1, $itemsPerPage = 2)
    {
        $keyboard = Keyboard::make()->inline();

        $totalHashtags = count($hashtags);
        $totalPages = ceil($totalHashtags / $itemsPerPage);

        $startIndex = ($currentPage - 1) * $itemsPerPage;
        $endIndex = min($startIndex + $itemsPerPage, $totalHashtags);

        // Добавляем хэштеги на текущей странице
        for ($i = $startIndex; $i < $endIndex; $i++) {
            $hashtag = $hashtags[$i];
            $isAttached = Setting_Hashtag::where('hashtag_id', $hashtag->id)->exists();

            if ($isAttached) {
                $keyboard->row([
                    Keyboard::inlineButton(['text' => 'Отвязать хэштег: ' . $hashtag->hashtag, 'callback_data' => 'detach_' . $hashtag->id]),
                ]);
            } else {
                $keyboard->row([
                    Keyboard::inlineButton(['text' => 'Привязать хэштег: ' . $hashtag->hashtag, 'callback_data' => 'attach_' . $hashtag->id]),
                ]);
            }
        }

        $row = [];

        if ($currentPage > 1) {
            $row[] = Keyboard::inlineButton(['text' => 'Назад', 'callback_data' => 'page_' . ($currentPage - 1)]);
        }

        // Кнопка счётчика (неактивная)
        $row[] = Keyboard::inlineButton(['text' => "Страница {$currentPage} из {$totalPages}", 'callback_data' => 'ignore']);

        if ($currentPage < $totalPages) {
            $row[] = Keyboard::inlineButton(['text' => 'Вперед', 'callback_data' => 'page_' . ($currentPage + 1)]);
        }

        $keyboard->row($row);

        $keyboard->row([
            Keyboard::inlineButton(['text' => 'Закончить настройку', 'callback_data' => 'back_to_settings']),
        ]);

        return $keyboard;
    }

    public static function DeleteHashTagsInlineKeyboard($hashtags, $currentPage = 1, $itemsPerPage = 2)
    {
        $keyboard = Keyboard::make()->inline();

        $totalHashtags = count($hashtags);
        $totalPages = ceil($totalHashtags / $itemsPerPage);

        $startIndex = ($currentPage - 1) * $itemsPerPage;
        $endIndex = min($startIndex + $itemsPerPage, $totalHashtags);

        for ($i = $startIndex; $i < $endIndex; $i++) {
            $hashtag = $hashtags[$i];

            $keyboard->row([
                Keyboard::inlineButton(['text' => $hashtag->hashtag, 'callback_data' => 'delete_' . $hashtag->id]),
            ]);
        }

        $row = [];

        if ($currentPage > 1) {
            $row[] = Keyboard::inlineButton(['text' => 'Назад', 'callback_data' => 'page_' . ($currentPage - 1)]);
        }

        $row[] = Keyboard::inlineButton(['text' => "Страница {$currentPage} из {$totalPages}", 'callback_data' => 'ignore']);

        if ($currentPage < $totalPages) {
            $row[] = Keyboard::inlineButton(['text' => 'Вперед', 'callback_data' => 'page_' . ($currentPage + 1)]);
        }

        $keyboard->row($row);

        $keyboard->row([
            Keyboard::inlineButton(['text' => 'Закончить', 'callback_data' => 'back_to_settings']),
        ]);

        return $keyboard;
    }
}