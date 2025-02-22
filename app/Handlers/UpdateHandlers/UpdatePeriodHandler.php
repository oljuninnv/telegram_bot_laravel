<?php

namespace App\Handlers\UpdateHandlers;

use Telegram\Bot\Api;
use App\Models\Setting;
use App\Services\UserState;

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
                        . "Период сбора: {$settings->weeks_in_period}\n"
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
}