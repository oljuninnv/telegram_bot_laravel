<?php

namespace App\Handlers\UpdateHandlers;

use Telegram\Bot\Api;
use App\Keyboards;
use App\Services\UserState;
use App\Models\TelegramUser;
use App\Handlers\UpdateHandlers\Users\AddUserHandler;
use App\Handlers\UpdateHandlers\Users\EditUserHandler;
use App\Handlers\UpdateHandlers\Users\BlockUserHandler;
use App\Helpers\HashtagHelper;
use App\Models\Setting;

class UpdateUserHandler
{
    use HashtagHelper;
    public function handle(Api $telegram, int $chatId, int $userId, string $messageText, ?int $messageId = null)
    {
        switch ($messageText) {
            case 'Добавить пользователя':
                $telegram->sendMessage([
                    'chat_id' => $chatId,
                    'text' => 'Введите username пользователя, которого вы хотите добавить ',
                    'reply_markup' => Keyboards::backAdminKeyboard(),
                ]);

                UserState::setState($userId, 'addUser');
                break;

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
                $settings = Setting::all()->last();
                $telegram->sendMessage([
                    'chat_id' => $chatId,
                    'text' => "Вы вернулись в меню настроек.\n"
                        ."Текущие настройки:\n"
                        . "Дата окончания текущего сбора: {$settings->current_period_end_date}\n"
                        . "День недели: {$settings->report_day}\n"
                        . "Время: {$settings->report_time}\n"
                        . "Период сбора: {$settings->weeks_in_period}\n"
                        . "Все хэштеги в базе данных:\n"
                        . $this->getAllHashtags() . "\n"
                        . "Подключённые хэштеги:\n"
                        . $this->getAttachedHashtags($settings) . "\n",
                    'reply_markup' => Keyboards::updateSettingsKeyboard(),
                ]);
                break;

            default:
                $currentState = UserState::getState($userId);

                switch ($currentState) {
                    case 'addUser':
                        $handler = new AddUserHandler();
                        $handler->handle($telegram, $chatId, $userId, $messageText, $messageId);
                        break;
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