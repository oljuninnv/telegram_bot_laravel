<?php

namespace App\Handlers;

use Telegram\Bot\Api;
use App\Keyboards;
use App\Services\UserState;
use Telegram\Bot\BotsManager;
use App\Handlers\CreateSettingHandler;
use App\Models\Setting;

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
                case 'Настроить сбор отчётов':
                    $telegram->sendMessage([
                        'chat_id' => $chatId,
                        'text' => 'Хорошо, давайте настроим сбор отчётов.',
                        'reply_markup' => Keyboards::backAdminKeyboard(),
                    ]);
                    UserState::setState($userId, 'createSettings'); // Устанавливаем состояние
    
                    // Автоматически запускаем первый шаг настройки
                    $createSettingsHandler = new CreateSettingHandler();
                    $createSettingsHandler->handle($telegram, $chatId, $userId, 'Создать настройку', $botsManager);
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