<?php

namespace App\Handlers\UpdateHandlers;

use Telegram\Bot\Api;
use App\Models\Setting;
use App\Services\UserState;
use App\Models\Hashtag;
use App\Models\Setting_Hashtag;
use App\Models\Chat;

class UpdateTimeHandler
{
    public function handle(Api $telegram, int $chatId, int $userId, string $messageText)
    {
        $settings = Setting::all()->last();

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