<?php

namespace App\Handlers\UpdateHandlers;

use Telegram\Bot\Api;
use App\Models\Setting;
use App\Services\UserState;
use App\Helpers\HashtagHelper;
use App\Models\Chat;

class UpdatePeriodHandler
{
    use HashtagHelper;
    public function handle(Api $telegram, int $chatId, int $userId, string $messageText)
    {
        $settings = Setting::latest()->first();

        if ($settings) {
            if (is_numeric($messageText) && (int)$messageText > 0 && (int)$messageText < 10) {
                $settings->update(['weeks_in_period' => (int)$messageText]);
                $telegram->sendMessage([
                    'chat_id' => $chatId,
                    'text' => 'Период успешно обновлён!',
                ]);
                UserState::setState($userId, 'settings');
                $telegram->sendMessage([
                    'chat_id' => $chatId,
                    'text' => "Текущие настройки:\n"
                        . "День недели: {$settings->report_day}\n"
                        . "Время: {$settings->report_time}\n"
                        . "Период сбора: {$settings->weeks_in_period}\n\n"
                        . "Все хэштеги в базе данных:\n"
                        . $this->getAllHashtags() . "\n\n"
                        . "Подключённые хэштеги:\n"
                        . $this->getAttachedHashtags($settings) . "\n\n"
                        . "Что вы хотите обновить?",
                ]);
                $chats = Chat::all();

                UserState::setState($userId, 'settings');

                // Формируем сообщение об изменении настроек
                $message = "Настройки были обновлены:\n"
                    . "Период сбора был обнавлён, теперь он осуществляется каждые {$settings->weeks_in_period} недели\n"
                    . "Они вступят в силу после окончания текущего периода\n";

                    foreach ($chats as $chat) {
                        try {
                            $telegram->getChat(['chat_id' => $chat->chat_id]); // Проверяем, существует ли чат
                            $telegram->sendMessage([
                                'chat_id' => $chat->chat_id,
                                'text' => $message,
                            ]);
                        } catch (\Telegram\Bot\Exceptions\TelegramResponseException $e) {
                            \Log::error('Ошибка: ' . $e->getMessage());
                        }
                    }

            } else {
                $telegram->sendMessage([
                    'chat_id' => $chatId,
                    'text' => 'Пожалуйста, введите корректное количество периодов.',
                ]);
            }
        } else {
            $telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => 'Настройки отсутствуют. Пожалуйста, создайте новую настройку.',
            ]);
        }
    }
}