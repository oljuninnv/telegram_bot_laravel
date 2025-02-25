<?php

namespace App\Handlers\UpdateHandlers;

use Telegram\Bot\Api;
use App\Models\Setting;
use App\Services\UserState;
use App\Enums\DayOfWeekEnums;
use App\Helpers\HashtagHelper;
use App\Helpers\MessageHelper;

class UpdateDayOfWeekHandler
{
    use HashtagHelper, MessageHelper;

    public function handle(Api $telegram, int $chatId, int $userId, string $messageText)
    {
        $settings = Setting::latest()->first();
        $update = $telegram->getWebhookUpdate();

        if ($update->callback_query) {
            $callbackData = $update->callback_query->data;
            $messageText = $callbackData;
            $chatId = $update->callback_query->message->chat->id;
        }

        if ($settings) {
            if (DayOfWeekEnums::tryFrom($messageText)) {
                $settings->update(['report_day' => $messageText]);
                $telegram->sendMessage([
                    'chat_id' => $chatId,
                    'text' => 'День недели успешно обновлён!',
                ]);

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

                UserState::setState($userId, 'settings');

                $message = "Настройки были обновлены:\n"
                    . "Сбор отчётов осуществляется в: {$settings->report_day}\n"
                    . "Они вступят в силу после окончания текущего периода\n";

                $this->sendMessageToAllChats($telegram, $message);
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