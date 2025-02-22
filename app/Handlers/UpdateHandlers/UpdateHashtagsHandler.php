<?php

namespace App\Handlers\UpdateHandlers;

use Telegram\Bot\Api;
use App\Models\Setting;
use App\Services\UserState;

class UpdateHashtagsHandler
{
    public function handle(Api $telegram, int $chatId, int $userId, string $messageText)
    {
        $settings = Setting::all()->last();

        if ($settings) {
            $settings->update(['hashtags' => $messageText]);
            $telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => 'Хэштеги успешно обновлены!',
            ]);
            UserState::setState($userId, 'settings');
            $telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => "Текущие настройки:\n"
                    . "День недели: {$settings->report_day}\n"
                    . "Время: {$settings->report_time}\n"
                    . "Период сбора: {$settings->weeks_in_period}\n"
                    . "Что вы хотите обновить?",
            ]);
        } else {
            $telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => 'Настройки отсутствуют. Пожалуйста, создайте новую настройку.',
            ]);
        }
    }
}