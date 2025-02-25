<?php

namespace App\Handlers\UpdateHandlers;

use Telegram\Bot\Api;
use App\Models\Setting;
use App\Services\UserState;
use App\Helpers\HashtagHelper;
use App\Models\Chat;

class UpdateTimeHandler
{
    use HashtagHelper;
    public function handle(Api $telegram, int $chatId, int $userId, string $messageText)
    {
        $settings = Setting::latest()->first();

        if ($settings) {
            if (preg_match('/^(2[0-3]|[01]?[0-9]):([0-5][0-9])$/', $messageText)) {
                $settings->update(['report_time' => $messageText]);
                $telegram->sendMessage([
                    'chat_id' => $chatId,
                    'text' => 'Время успешно обновлено!',
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

                $message = "Настройки были обновлены:\n"
                    . "Время сбора был обнавлён: {$settings->report_time}\n"
                    . "Они вступят в силу после окончания текущего периода\n";

                    foreach ($chats as $chat) {
                        try {
                            $telegram->getChat(['chat_id' => $chat->chat_id]);
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
                    'text' => 'Пожалуйста, введите корректное время в формате HH:MM.',
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