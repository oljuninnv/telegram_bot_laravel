<?php

namespace App\Handlers\UpdateHandlers;

use Telegram\Bot\Api;
use App\Models\Setting;
use App\Services\UserState;
use App\Models\Hashtag;
use App\Models\Setting_Hashtag;
use App\Models\Chat;

class UpdatePeriodHandler
{
    public function handle(Api $telegram, int $chatId, int $userId, string $messageText)
    {
        $settings = Setting::all()->last();

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
    private function getAllHashtags(): string
    {
        $hashtags = Hashtag::all();
        $hashtagList = [];

        foreach ($hashtags as $hashtag) {
            $hashtagList[] = $hashtag->hashtag;
        }

        return implode(', ', $hashtagList);
    }

    // Метод для получения подключённых хэштегов к текущей настройке
    private function getAttachedHashtags(Setting $setting): string
    {
        // Используем модель Setting_Hashtag для получения привязанных хэштегов
        $attachedHashtags = Setting_Hashtag::where('setting_id', $setting->id)
            ->with('hashtag') 
            ->get()
            ->pluck('hashtag.hashtag')
            ->toArray();

        if (!empty($attachedHashtags)) {
            return implode(', ', $attachedHashtags);
        }

        return 'Нет подключённых хэштегов';
    }
}