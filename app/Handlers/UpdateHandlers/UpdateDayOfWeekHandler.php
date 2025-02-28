<?php

namespace App\Handlers\UpdateHandlers;

use Telegram\Bot\Api;
use App\Models\Setting;
use App\Services\UserState;
use App\Enums\DayOfWeekEnums;
use App\Services\SettingState;
use App\Keyboards;

class UpdateDayOfWeekHandler
{
    public function handle(Api $telegram, int $chatId, int $userId, string $messageText, ?int $messageId = null)
    {
        $settings = Setting::latest()->first();
        $update = $telegram->getWebhookUpdate();

        if ($update->callback_query) {
            $messageText = $update->callback_query->data;
            $chatId = $update->callback_query->message->chat->id;
        }

        if ($messageText === 'Оставить текущее') {
            if ($settings) {
                $telegram->sendMessage([
                    'chat_id' => $chatId,
                    'text' => 'День недели остался текущим.',
                ]);
                SettingState::setDayOfWeek($userId, $settings->report_day);
                $this->promptForWeeksInPeriod($telegram, $chatId, $userId);
            } else {
                $telegram->sendMessage([
                    'chat_id' => $chatId,
                    'text' => 'Настройки не были найдены.',
                ]);
            }
        } elseif (DayOfWeekEnums::tryFrom($messageText)) {
            $telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => 'День недели добавлен.',
            ]);
            SettingState::setDayOfWeek($userId, $messageText);
            $this->promptForWeeksInPeriod($telegram, $chatId, $userId);
        } elseif ($messageText === 'Назад') {
            if ($settings) {
                $telegram->sendMessage([
                    'chat_id' => $chatId,
                    'text' => 'Вы вернулись в меню настроек',
                    'reply_markup' => Keyboards::updateSettingsKeyboard(),
                ]);
                UserState::setState($userId, 'settings');
                SettingState::clearAll($userId);
            } else {
                $telegram->sendMessage([
                    'chat_id' => $chatId,
                    'text' => 'Вы вернулись в меню настроек',
                    'reply_markup' => Keyboards::settingsAdminKeyboard(),
                ]);
                UserState::setState($userId, 'settings');
                SettingState::clearAll($userId);
            }
        } else {
            $telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => 'Пожалуйста, выберите корректный день недели.',
            ]);
        }
    }

    private function promptForWeeksInPeriod(Api $telegram, int $chatId, int $userId)
    {
        $settingsExist = Setting::exists();

        $replyMarkup = null;
        if ($settingsExist) {
            $replyMarkup = Keyboards::LeaveTheCurrentKeyboard();
        }

        $telegram->sendMessage([
            'chat_id' => $chatId,
            'text' => "Введите через сколько недель будет период сбора (от 1 до 10):",
            'reply_markup' => $replyMarkup,
        ]);

        UserState::setState($userId, 'updatePeriod');
    }
}