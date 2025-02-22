<?php

namespace App\Handlers\UpdateHandlers;

use Telegram\Bot\Api;
use App\Models\Setting;
use App\Services\UserState;

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
                        . "Период сбора: {$settings->weeks_in_period}\n"
                        . "Что вы хотите обновить?",
                ]);
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