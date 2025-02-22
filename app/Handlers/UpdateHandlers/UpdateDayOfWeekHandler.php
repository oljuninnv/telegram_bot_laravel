<?php

namespace App\Handlers\UpdateHandlers;

use Telegram\Bot\Api;
use App\Models\Setting;
use App\Services\UserState;
use App\Enums\DayOfWeekEnums;

class UpdateDayOfWeekHandler
{
    public function handle(Api $telegram, int $chatId, int $userId, string $messageText)
    {
        $settings = Setting::all()->last();
        $update = $telegram->getWebhookUpdate();

        if ($update->callback_query) {
            $callbackData = $update->callback_query->data;
            $messageText = $callbackData; // Используем данные из callback_query как текст сообщения
            $chatId = $update->callback_query->message->chat->id; // Обновляем chatId из callback_query
        }

        if ($settings) {
            if (DayOfWeekEnums::tryFrom($messageText)) {
                $settings->update(['report_day' => $messageText]);
                $telegram->sendMessage([
                    'chat_id' => $chatId,
                    'text' => 'День недели успешно обновлён!',
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
                    'text' => 'Пожалуйста, выберите корректный день недели.',
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