<?php

namespace App;

use Telegram\Bot\Keyboard\Keyboard;
use App\Models\Setting_Hashtag;
use App\Models\TelegramUser;
use App\Enums\RoleEnum;

class Keyboards
{
    public static function mainSuperAdminKeyboard()
    {
        return Keyboard::make()
            ->setResizeKeyboard(true)
            ->setOneTimeKeyboard(true)
            ->row([
                Keyboard::button('Получить список чатов'),
            ])
            ->row([
                Keyboard::button('Настройки'),
                Keyboard::button('Проверить отчеты'),
            ])
            ->row([
                Keyboard::button('Помощь'),
            ]);
    }

    public static function mainAdminKeyboard()
    {
        return Keyboard::make()
            ->setResizeKeyboard(true)
            ->setOneTimeKeyboard(true)
            ->row([
                Keyboard::button('Получить список чатов'),
            ])
            ->row([
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
                Keyboard::button(['text' => 'Настройка пользователей', 'callback_data' => 'Настройка пользователей']),
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

    public static function userSettingsKeyboard()
    {
        return Keyboard::make([
            'keyboard' => [
                ['Редактировать пользователя', 'Заблокировать пользователя'],
                ['Назад'],
            ],
            'resize_keyboard' => true,
            'one_time_keyboard' => true,
        ]);
    }

    public static function userRoleChangeKeyboard($users, $page = 1, $itemsPerPage = 5)
    {
        $totalUsers = count($users);
        $totalPages = ceil($totalUsers / $itemsPerPage);

        $keyboard = Keyboard::make()->inline();

        foreach ($users as $user) {
            $keyboard->row([
                Keyboard::inlineButton([
                    'text' => "{$user->username} ({$user->role})",
                    'callback_data' => "change_role_{$user->telegram_id}",
                ]),
            ]);
        }

        // Добавляем пагинацию
        $paginationRow = [];
        if ($page > 1) {
            $paginationRow[] = Keyboard::inlineButton([
                'text' => 'Назад',
                'callback_data' => "role_page_" . ($page - 1),
            ]);
        }

        $paginationRow[] = Keyboard::inlineButton([
            'text' => "Страница {$page} из {$totalPages}",
            'callback_data' => 'ignore',
        ]);

        if ($page < $totalPages) {
            $paginationRow[] = Keyboard::inlineButton([
                'text' => 'Вперед ',
                'callback_data' => "role_page_" . ($page + 1),
            ]);
        }

        $keyboard->row($paginationRow);

        $keyboard->row([
            Keyboard::inlineButton([
                'text' => 'Отменить изменение',
                'callback_data' => 'cancel_role_change',
            ]),
        ]);

        return $keyboard;
    }

    public static function roleSelectionKeyboard()
    {
        $keyboard = Keyboard::make()->inline();

        foreach (RoleEnum::cases() as $role) {
            $keyboard->row([
                Keyboard::inlineButton([
                    'text' => $role->value,
                    'callback_data' => "select_role_{$role->value}",
                ]),
            ]);
        }

        $keyboard->row([
            Keyboard::inlineButton([
                'text' => 'Отменить изменение',
                'callback_data' => 'cancel_role_change',
            ]),
        ]);

        return $keyboard;
    }

    public static function userBlockKeyboard($users, $currentPage = 1, $itemsPerPage = 5)
    {
        $totalUsers = count($users);
        $totalPages = ceil($totalUsers / $itemsPerPage);

        $keyboard = Keyboard::make()->inline();

        $startIndex = ($currentPage - 1) * $itemsPerPage;
        $endIndex = min($startIndex + $itemsPerPage, $totalUsers);

        for ($i = $startIndex; $i < $endIndex; $i++) {
            $user = $users[$i];
            $status = $user->banned ? ' (Заблокирован)' : ' (Активен)';
            $keyboard->row([
                Keyboard::inlineButton([
                    'text' => "{$user->username} {$status}", 
                    'callback_data' => "toggle_block_{$user->telegram_id}",
                ]),
            ]);
        }

        $paginationRow = [];
        if ($currentPage > 1) {
            $paginationRow[] = Keyboard::inlineButton([
                'text' => 'Назад',
                'callback_data' => "page_" . ($currentPage - 1),
            ]);
        }

        $paginationRow[] = Keyboard::inlineButton([
            'text' => "Страница {$currentPage} из {$totalPages}",
            'callback_data' => 'ignore',
        ]);

        if ($currentPage < $totalPages) {
            $paginationRow[] = Keyboard::inlineButton([
                'text' => 'Вперед',
                'callback_data' => "page_" . ($currentPage + 1),
            ]);
        }

        $keyboard->row($paginationRow);

        $keyboard->row([
            Keyboard::inlineButton([
                'text' => 'Отменить блокировку',
                'callback_data' => 'cancel_block',
            ]),
        ]);

        return $keyboard;
    }

    public static function confirmationKeyboard()
    {
        $keyboard = Keyboard::make()->inline();

        $keyboard->row([
            Keyboard::inlineButton([
                'text' => 'Да',
                'callback_data' => 'confirm_yes',
            ]),
            Keyboard::inlineButton([
                'text' => 'Нет',
                'callback_data' => 'confirm_no',
            ]),
        ]);

        return $keyboard;
    }
}