<?php

namespace App\Handlers;

use Telegram\Bot\Api;
use App\Keyboards;
use App\Services\UserState;
use App\Models\Chat;
use Telegram\Bot\BotsManager;
class MainStateHandler
{
    public function handle(Api $telegram, int $chatId, int $userId, string $messageText, BotsManager $botsManager)
    {
        $update = $telegram->getWebhookUpdate();
        $botsManager->bot()->commandsHandler(true);
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
                case 'Получить список чатов':
                    $chats = Chat::all();
                    $response = '';
                    foreach ($chats as $chat) {
                        $response .= "\nНазвание: {$chat->name} - ссылка: " . (!empty($chat->chat_link) ? $chat->chat_link : 'отсутствует');
                    }
                    $telegram->sendMessage([
                        'chat_id' => $chatId,
                        'text' => $response,
                    ]);
                    break;
                case 'Настройка сбора отчетов':
                    UserState::setState($userId, 'settings');
                    $telegram->sendMessage([
                        'chat_id' => $chatId,
                        'text' => 'Вы выбрали настройку сбора отчетов.',
                        'reply_markup' => Keyboards::settingsAdminKeyboard(),
                    ]);
                    break;

                case 'Проверить отчеты':
                    $telegram->sendMessage([
                        'chat_id' => $chatId,
                        'text' => 'Вы выбрали проверку отчетов.',
                    ]);
                    break;

                case 'Получить отчеты':
                    $telegram->sendMessage([
                        'chat_id' => $chatId,
                        'text' => 'Вы выбрали получение отчетов.',
                    ]);
                    break;

                case 'Помощь':
                    $telegram->sendMessage([
                        'chat_id' => $chatId,
                        'text' => "Данный бот предназначен для управления отчетами и взаимодействия с чатами. Вот список доступных команд:\n\n" .
                            "1. Получить список чатов - Позволяет получить список доступных чатов.\n" .
                            "2. Настройка сбора отчетов - Позволяет настроить параметры сбора отчетов.\n" .
                            "3. Проверить отчеты - Проверяет текущие отчеты и их статус.\n" .
                            "4. Получить отчеты - Получает и отображает отчеты по заданным параметрам.\n",
                    ]);
                    break;

                default:
                    $telegram->sendMessage([
                        'chat_id' => $chatId,
                        'text' => 'Неизвестная команда. Пожалуйста, выберите действие из меню.',
                        'reply_markup' => Keyboards::mainAdminKeyboard(),
                    ]);
                    break;
            }
        }
    }
}