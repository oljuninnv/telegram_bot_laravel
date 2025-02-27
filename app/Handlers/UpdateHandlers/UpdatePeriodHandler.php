<?php

namespace App\Handlers\UpdateHandlers;

use Telegram\Bot\Api;
use App\Models\Setting;
use App\Services\UserState;
use App\Services\SettingState;
use App\Keyboards;

class UpdatePeriodHandler
{
    public function handle(Api $telegram, int $chatId, int $userId, string $messageText)
    {
        $settings = Setting::latest()->first();

        if ($messageText === 'Оставить текущее') {
            if ($settings) {
                $telegram->sendMessage([
                    'chat_id' => $chatId,
                    'text' => 'Период остался без изменений.',
                ]);
                SettingState::setWeeksInPeriod($userId, $settings->weeks_in_period);
                $this->promptForTime($telegram, $chatId, $userId);
            } else {
                $telegram->sendMessage([
                    'chat_id' => $chatId,
                    'text' => 'Настройки не были найдены.',
                ]);
            }
        } elseif (is_numeric($messageText) && (int) $messageText > 0 && (int) $messageText < 10) {
            $telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => 'Период был изменён.',
            ]);
            SettingState::setWeeksInPeriod($userId, $messageText);
            $this->promptForTime($telegram, $chatId, $userId);
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
                'text' => 'Пожалуйста, введите корректное количество периодов.',
            ]);
        }
    }

    private function promptForTime(Api $telegram, int $chatId, int $userId)
    {
        $settingsExist = Setting::exists();

        $replyMarkup = null;
        if ($settingsExist) {
            $replyMarkup = Keyboards::LeaveTheCurrentKeyboard();
        }
        $telegram->sendMessage([
            'chat_id' => $chatId,
            'text' => "Введите корректное время (например, 14:00):",
            'reply_markup' => $replyMarkup ,
        ]);
        UserState::setState($userId, 'updateTime');
    }
}