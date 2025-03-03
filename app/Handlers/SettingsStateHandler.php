<?php

namespace App\Handlers;

use Telegram\Bot\Api;
use App\Keyboards;
use App\Services\UserState;
use App\Handlers\UpdateHandlers\UpdateUserHandler;
use App\Handlers\UpdateHandlers\UpdateDayOfWeekHandler;
use App\Handlers\UpdateHandlers\UpdateHashtagsHandler;
use App\Models\Setting;

class SettingsStateHandler
{
    public function handle(Api $telegram, int $chatId, int $userId, string $messageText, ?int $messageId = null)
    {
        $settingsExist = Setting::exists();
        switch ($messageText) {
            case 'Настроить сбор отчётов':
                $telegram->sendMessage([
                    'chat_id' => $chatId,
                    'text' => 'Вы выбрали настройку сбора отчётов',
                    'reply_markup' => Keyboards::backAdminKeyboard(),
                ]);

                $telegram->sendMessage([
                    'chat_id' => $chatId,
                    'text' => 'Выберите день недели сбора отчётов:',
                    'reply_markup' => Keyboards::getDaysOfWeekKeyboard($settingsExist),
                ]);

                UserState::setState($userId, 'updateDayOfWeek');
                break;

            case 'Обновить хэштеги':
                if (!$settingsExist) {
                    $telegram->sendMessage([
                        'chat_id' => $chatId,
                        'text' => 'У вас нет настроек. Сначала создайте настройку.',
                        'reply_markup' => Keyboards::settingsAdminKeyboard(),
                    ]);
                    return;
                }
                $telegram->sendMessage([
                    'chat_id' => $chatId,
                    'text' => 'Выберите действие для настройки хэштегов:',
                    'reply_markup' => Keyboards::hashtagSettingsKeyboard(),
                ]);
                UserState::setState($userId, 'updateHashtags');
                break;

            case 'Настройка пользователей':
                $telegram->sendMessage([
                    'chat_id' => $chatId,
                    'text' => 'Вы выбрали настройку пользователей',
                    'reply_markup' => Keyboards::userSettingsKeyboard(),
                ]);
                UserState::setState($userId, 'updateUsers');
                break;

            case 'Назад':
                UserState::resetState($userId);
                $telegram->sendMessage([
                    'chat_id' => $chatId,
                    'text' => 'Вы вернулись в главное меню.',
                    'reply_markup' => Keyboards::mainAdminKeyboard(),
                ]);
                break;

            default:
                $currentState = UserState::getState($userId);

                switch ($currentState) {
                    case 'updateDayOfWeek':
                        $handler = new UpdateDayOfWeekHandler();
                        $handler->handle($telegram, $chatId, $userId, $messageText, $messageId);
                        break;

                    case 'updateHashtags':
                        $handler = new UpdateHashtagsHandler();
                        $handler->handle($telegram, $chatId, $userId, $messageText, $messageId);
                        break;

                    case 'updateUsers':
                        $handler = new UpdateUserHandler();
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