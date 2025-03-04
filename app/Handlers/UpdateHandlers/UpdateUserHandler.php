<?php

namespace App\Handlers\UpdateHandlers;

use Telegram\Bot\Api;
use App\Keyboards;
use App\Services\UserState;
use App\Models\TelegramUser;
use App\Handlers\UpdateHandlers\Users\EditUserHandler;
use App\Handlers\UpdateHandlers\Users\BlockUserHandler;


class UpdateUserHandler
{
    public function handle(Api $telegram, int $chatId, int $userId, string $messageText, ?int $messageId = null)
    {
        switch ($messageText) {
            case 'Редактировать пользователя':
                $users = TelegramUser::where('telegram_id', '!=', $userId)->get();
                $telegram->sendMessage([
                    'chat_id' => $chatId,
                    'text' => 'Выберите пользователя, которому вы бы хотели изменить роль. Также вы можете ввести его имя, фамилию или username пользователя, чтобы было проще найти подходящего пользователя:',
                    'reply_markup' => Keyboards::userRoleChangeKeyboard($users),
                ]);

                UserState::setState($userId, 'editUser');
                break;

            case 'Заблокировать пользователя':
                $users = TelegramUser::where('telegram_id', '!=', $userId)->get();
                $telegram->sendMessage([
                    'chat_id' => $chatId,
                    'text' => 'Выберите пользователя, которого вы бы хотели заблокировать. Также вы можете ввести его имя, фамилию или username пользователя, чтобы было проще найти подходящего пользователя:',
                    'reply_markup' => Keyboards::userBlockKeyboard($users),
                ]);

                UserState::setState($userId, 'blockUser');
                break;

            case 'Назад':
                UserState::setState($userId, 'settings');
                $telegram->sendMessage([
                    'chat_id' => $chatId,
                    'text' => 'Вы вернулись в меню настроек.',
                    'reply_markup' => Keyboards::updateSettingsKeyboard(),
                ]);
                break;

            default:
                $currentState = UserState::getState($userId);

                switch ($currentState) {
                    case 'editUser':
                        $handler = new EditUserHandler();
                        $handler->handle($telegram, $chatId, $userId, $messageText, $messageId);
                        break;

                    case 'blockUser':
                        $handler = new BlockUserHandler();
                        $handler->handle($telegram, $chatId, $userId, $messageText, $messageId);
                        break;

                    default:
                        $telegram->sendMessage([
                            'chat_id' => $chatId,
                            'text' => 'Неизвестная команда. Пожалуйста, выберите действие из меню.',
                        ]);
                        break;
                }
                break;
        }
    }
}