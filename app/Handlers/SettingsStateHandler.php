<?php

namespace App\Handlers;

use Telegram\Bot\Api;
use App\Keyboards;
use App\Services\UserState;
use Telegram\Bot\BotsManager;

class SettingsStateHandler
{

    public function handle(Api $telegram, int $chatId, int $userId, string $messageText, BotsManager $botsManager)    {
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
                case 'Установить день недели':
                    $telegram->sendMessage([
                        'chat_id' => $chatId,
                        'text' => 'Пожалуйста, укажите день недели для сбора отчетов.',
                    ]);
                    break;

                case 'Установить период':
                    $telegram->sendMessage([
                        'chat_id' => $chatId,
                        'text' => 'Пожалуйста, укажите период для сбора отчетов.',
                    ]);
                    break;

                case 'Установить время сбора отчёта':
                    $telegram->sendMessage([
                        'chat_id' => $chatId,
                        'text' => 'Пожалуйста, укажите время сбора отчета.',
                    ]);
                    break;

                case 'Управление хэштегами':
                    $telegram->sendMessage([
                        'chat_id' => $chatId,
                        'text' => 'Выберите действие для управления хэштегами.',
                    ]);
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
                    $telegram->sendMessage([
                        'chat_id' => $chatId,
                        'text' => 'Неизвестная команда. Пожалуйста, выберите действие из меню.',
                        'reply_markup' => Keyboards::settingsAdminKeyboard(),
                    ]);
                    break;
            }
        }
    }
}