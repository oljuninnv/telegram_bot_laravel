<?php

namespace App\Handlers;

use Telegram\Bot\Api;
use App\Keyboards;
use App\Services\UserState;
use Telegram\Bot\BotsManager;
use App\Handlers\UpdateHandlers\UpdatePeriodHandler;
use App\Handlers\UpdateHandlers\UpdateTimeHandler;
use App\Handlers\UpdateHandlers\UpdateDayOfWeekHandler;
use App\Models\Setting;

class SettingsStateHandler
{
    public function handle(Api $telegram, int $chatId, int $userId, string $messageText, BotsManager $botsManager)
    {
        $botsManager->bot()->commandsHandler(true);
        $update = $telegram->getWebhookUpdate();

        $isBotCommand = false;

        // Проверка является ли текст командой
        if (!empty($update->message->entities)) {
            foreach ($update->message->entities as $entity) {
                if ($entity->type === 'bot_command') {
                    $isBotCommand = true;
                    break;
                }
            }
        }

        if (!$isBotCommand) {
            switch ($messageText) {
                case 'Настроить сбор отчётов':
                    $telegram->sendMessage([
                        'chat_id' => $chatId,
                        'text' => 'Вы выбрали настройку сбора отчётов',
                        'reply_markup' => Keyboards::backAdminKeyboard(),
                    ]);
                
                    $settingsExist = Setting::exists();
                
                    $telegram->sendMessage([
                        'chat_id' => $chatId,
                        'text' => 'Выберите день недели сбора отчётов:',
                        'reply_markup' => Keyboards::getDaysOfWeekKeyboard($settingsExist),
                    ]);
                
                    UserState::setState($userId, 'updateDayOfWeek');
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
                        case 'updatePeriod':
                            $handler = new UpdatePeriodHandler();
                            $handler->handle($telegram, $chatId, $userId, $messageText);
                            break;

                        case 'updateTime':
                            $handler = new UpdateTimeHandler();
                            $handler->handle($telegram, $chatId, $userId, $messageText);
                            break;

                        case 'updateDayOfWeek':
                            $handler = new UpdateDayOfWeekHandler();
                            $handler->handle($telegram, $chatId, $userId, $messageText);
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
}