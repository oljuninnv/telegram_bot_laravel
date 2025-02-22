<?php

namespace App\Handlers\UpdateHandlers;

use Telegram\Bot\Api;
use App\Models\Setting;
use App\Services\UserState;
use App\Models\Hashtag;
use App\Models\Setting_Hashtag;

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