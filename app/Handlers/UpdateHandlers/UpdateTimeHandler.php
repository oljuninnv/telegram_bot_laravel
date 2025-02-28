<?php

namespace App\Handlers\UpdateHandlers;

use Telegram\Bot\Api;
use App\Models\Setting;
use App\Services\UserState;
use App\Services\SettingState;
use App\Keyboards;
use App\Helpers\HashtagHelper;
use Carbon\Carbon;
use App\Enums\DayOfWeekEnums;

class UpdateTimeHandler
{
    use HashtagHelper;
    public function handle(Api $telegram, int $chatId, int $userId, string $messageText, ?int $messageId = null)
    {
        $settings = Setting::latest()->first();

        if ($messageText === 'Оставить текущее') {
            if($settings){
                $telegram->sendMessage([
                    'chat_id' => $chatId,
                    'text' => 'Время осталось без изменений.',
                ]);
                SettingState::setReportTime($userId, $settings->report_time);
                $this->updateSettingsAndShowSummary($telegram, $chatId, $userId, $settings);
            }
            else{
                $telegram->sendMessage([
                    'chat_id' => $chatId,
                    'text' => 'Настройки не найдены.',
                ]);
            }
            
        } elseif (preg_match('/^(2[0-3]|[01]?[0-9]):([0-5][0-9])$/', $messageText)) {
            $telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => 'Время успешно обновлено!',
            ]);
            SettingState::setReportTime($userId, $messageText);
            $this->updateSettingsAndShowSummary($telegram, $chatId, $userId, $settings);
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
            $this->sendInvalidTimeMessage($telegram, $chatId);
        }
    }

    private function updateSettingsAndShowSummary(Api $telegram, int $chatId, int $userId, ?Setting $settings)
    {
        if ($settings) {
            $settings->update([
                'report_time' => SettingState::getReportTime($userId),
                'report_day' => SettingState::getDayOfWeek($userId),
                'weeks_in_period' => SettingState::getWeeksInPeriod($userId),
            ]);
        } else {
            $dayOfWeek = SettingState::getDayOfWeek($userId);
            $reportTime = SettingState::getReportTime($userId);
            $weeksInPeriod = SettingState::getWeeksInPeriod($userId);

            $dayOfWeekNumber = array_search($dayOfWeek, DayOfWeekEnums::getAllDays());
            if ($dayOfWeekNumber + 1 >= 7) {
                $dayOfWeekNumber = 0;
            }

            $currentPeriodEndDate = Carbon::now()
                ->addWeeks($weeksInPeriod)
                ->next($dayOfWeekNumber + 1)
                ->setTimeFromTimeString($reportTime)
                ->subSecond();

            $settings = Setting::create([
                'report_time' => $reportTime,
                'report_day' => $dayOfWeek,
                'weeks_in_period' => $weeksInPeriod,
                'current_period_end_date' => $currentPeriodEndDate,
            ]);
        }

        UserState::setState($userId, 'settings');

        $telegram->sendMessage([
            'chat_id' => $chatId,
            'text' => "Текущие настройки:\n"
                . "Дата окончания текущего сбора: {$settings->current_period_end_date}\n"
                . "День недели: {$settings->report_day}\n"
                . "Время: {$settings->report_time}\n"
                . "Период сбора: {$settings->weeks_in_period}\n\n"
                . "Все хэштеги в базе данных:\n"
                . $this->getAllHashtags() . "\n\n"
                . "Подключённые хэштеги:\n"
                . $this->getAttachedHashtags($settings) . "\n\n",
            'reply_markup' => Keyboards::updateSettingsKeyboard()
        ]);
    }
    private function sendInvalidTimeMessage(Api $telegram, int $chatId)
    {
        $telegram->sendMessage([
            'chat_id' => $chatId,
            'text' => 'Пожалуйста, введите корректное время в формате HH:MM.',
        ]);
    }
}