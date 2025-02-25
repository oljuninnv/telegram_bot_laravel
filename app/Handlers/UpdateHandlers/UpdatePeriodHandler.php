<?php

namespace App\Handlers\UpdateHandlers;

use Telegram\Bot\Api;
use App\Models\Setting;
use App\Services\UserState;
use App\Helpers\HashtagHelper;
use App\Helpers\MessageHelper;

class UpdatePeriodHandler
{
    use HashtagHelper,MessageHelper;
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

                UserState::setState($userId, 'settings');

                $message = "Настройки были обновлены:\n"
                    . "Период сбора был обнавлён, теперь он осуществляется каждые {$settings->weeks_in_period} недели\n"
                    . "Они вступят в силу после окончания текущего периода\n";

                $this->sendMessageToAllChats($telegram, $message);

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