<?php

namespace App;

use Telegram\Bot\Keyboard\Keyboard;
use App\Models\Setting_Hashtag;
use App\Enums\RoleEnum;
use App\Helpers\KeyboardHelper;

class Keyboards
{
    use KeyboardHelper;

    public static function mainSuperAdminKeyboard(): Keyboard
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

    public static function mainAdminKeyboard(): Keyboard
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

    public static function settingsAdminKeyboard(): Keyboard
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

    public static function updateSettingsKeyboard(): Keyboard
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

    public static function hashtagSettingsKeyboard(): Keyboard
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

    public static function backAdminKeyboard(): Keyboard
    {
        return Keyboard::make()
            ->setResizeKeyboard(true)
            ->setOneTimeKeyboard(true)
            ->row([
                Keyboard::button(['text' => 'Назад', 'callback_data' => 'Назад']),
            ]);
    }

    public static function getDaysOfWeekKeyboard(bool $settingsExist = true): Keyboard
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

    public static function leaveTheCurrentKeyboard(): Keyboard
    {
        return Keyboard::make()->inline()->row([
            Keyboard::inlineButton(['text' => 'Оставить текущее', 'callback_data' => 'Оставить текущее']),
        ]);
    }

    public static function hashtagsInlineKeyboard($hashtags, int $currentPage = 1, int $itemsPerPage = 2): Keyboard
    {
        $keyboard = Keyboard::make()->inline();

        $totalHashtags = count($hashtags);
        $totalPages = ceil($totalHashtags / $itemsPerPage);

        $startIndex = ($currentPage - 1) * $itemsPerPage;
        $endIndex = min($startIndex + $itemsPerPage, $totalHashtags);

        for ($i = $startIndex; $i < $endIndex; $i++) {
            $hashtag = $hashtags[$i];
            $isAttached = Setting_Hashtag::where('hashtag_id', $hashtag->id)->exists();

            $keyboard->row([
                Keyboard::inlineButton([
                    'text' => $isAttached ? "Отвязать хэштег: {$hashtag->hashtag}" : "Привязать хэштег: {$hashtag->hashtag}",
                    'callback_data' => $isAttached ? "detach_{$hashtag->id}" : "attach_{$hashtag->id}",
                ]),
            ]);
        }

        $keyboard = self::addPagination($keyboard, $currentPage, $totalPages, 'page');
        $keyboard = self::addExitButton($keyboard);

        return $keyboard;
    }

    public static function deleteHashtagsInlineKeyboard($hashtags, int $currentPage = 1, int $itemsPerPage = 2): Keyboard
    {
        $keyboard = Keyboard::make()->inline();

        $totalHashtags = count($hashtags);
        $totalPages = ceil($totalHashtags / $itemsPerPage);

        $startIndex = ($currentPage - 1) * $itemsPerPage;
        $endIndex = min($startIndex + $itemsPerPage, $totalHashtags);

        for ($i = $startIndex; $i < $endIndex; $i++) {
            $hashtag = $hashtags[$i];
            $keyboard->row([
                Keyboard::inlineButton([
                    'text' => $hashtag->hashtag,
                    'callback_data' => "delete_{$hashtag->id}",
                ]),
            ]);
        }

        $keyboard = self::addPagination($keyboard, $currentPage, $totalPages, 'page');
        $keyboard = self::addExitButton($keyboard);

        return $keyboard;
    }

    public static function userSettingsKeyboard(): Keyboard
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

    public static function userRoleChangeKeyboard($users, int $page = 1, int $itemsPerPage = 2): Keyboard
    {
        $keyboard = Keyboard::make()->inline();

        $totalUsers = count($users);
        $totalPages = ceil($totalUsers / $itemsPerPage);

        $startIndex = ($page - 1) * $itemsPerPage;
        $endIndex = min($startIndex + $itemsPerPage, $totalUsers);

        for ($i = $startIndex; $i < $endIndex; $i++) {
            $user = $users[$i];
            $keyboard->row([
                Keyboard::inlineButton([
                    'text' => "{$user->username} ({$user->role})",
                    'callback_data' => "change_role_{$user->telegram_id}",
                ]),
            ]);
        }

        $keyboard = self::addPagination($keyboard, $page, $totalPages, 'role_page');

        $keyboard = self::addExitButton($keyboard);

        return $keyboard;
    }

    public static function roleSelectionKeyboard(): Keyboard
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

        $keyboard = self::addExitButton($keyboard);

        return $keyboard;
    }

    public static function userBlockKeyboard($users, int $currentPage = 1, int $itemsPerPage = 2): Keyboard
    {
        $keyboard = Keyboard::make()->inline();

        $totalUsers = count($users);
        $totalPages = ceil($totalUsers / $itemsPerPage);

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

        $keyboard = self::addPagination($keyboard, $currentPage, $totalPages, 'page');

        $keyboard = self::addExitButton($keyboard);

        return $keyboard;
    }

    public static function confirmationKeyboard(): Keyboard
    {
        return Keyboard::make()->inline()->row([
            Keyboard::inlineButton([
                'text' => 'Да',
                'callback_data' => 'confirm_yes',
            ]),
            Keyboard::inlineButton([
                'text' => 'Нет',
                'callback_data' => 'confirm_no',
            ]),
        ]);
    }
}