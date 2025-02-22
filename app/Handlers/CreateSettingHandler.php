<?php

namespace App\Handlers;

use Telegram\Bot\Api;
use App\Enums\DayOfWeekEnums;
use App\Models\Setting;
use Telegram\Bot\BotsManager;
use App\Services\UserState;
use App\Services\SettingState;
use App\Keyboards;

class CreateSettingHandler
{
    public function handle(Api $telegram, int $chatId, int $userId, string $messageText, BotsManager $botsManager)
    {
        $botsManager->bot()->commandsHandler(true);
        $update = $telegram->getWebhookUpdate();

        // Логируем update для отладки
        \Log::info('CreateSettingHandler received update:', [$update]);

        // Обработка callback_query (нажатие на inline-кнопку)
        if ($update->callback_query) {
            $callbackData = $update->callback_query->data;
            $messageText = $callbackData; // Используем данные из callback_query как текст сообщения
            $chatId = $update->callback_query->message->chat->id; // Обновляем chatId из callback_query

            // Логируем callback_query для отладки
            \Log::info('Callback query received:', [$callbackData]);
        }

        if (!empty($update->message->entities)) {
            foreach ($update->message->entities as $entity) {
                if ($entity->type === 'bot_command') {
                    return;
                }
            }
        }

        // Обработка команды "Назад"
        if ($messageText === 'Назад') {
            UserState::resetState($userId);
            SettingState::clearAll($userId);
            $telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => 'Вы вернулись в меню настроек.',
                'reply_markup' => Keyboards::settingsAdminKeyboard(),
            ]);
            return;
        }

        // Получаем текущий шаг из состояния пользователя
        $currentStep = SettingState::getStep($userId) ?? 0;

        switch ($currentStep) {
            case 0:
                // Первый шаг: выбор дня недели
                $telegram->sendMessage([
                    'chat_id' => $chatId,
                    'text' => 'Выберите день недели сбора отчётов:',
                    'reply_markup' => Keyboards::getDaysOfWeekKeyboard(),
                ]);
                SettingState::setStep($userId, 1); // Устанавливаем следующий шаг
                break;

            case 1:
                // Обработка выбора дня недели
                if (DayOfWeekEnums::tryFrom($messageText)) {
                    $telegram->sendMessage([
                        'chat_id' => $chatId,
                        'text' => 'Введите время сбора отчёта (например, 14:00):',
                    ]);
                    SettingState::setDayOfWeek($userId, $messageText);
                    SettingState::setStep($userId, 2); // Устанавливаем следующий шаг
                } else {
                    $telegram->sendMessage([
                        'chat_id' => $chatId,
                        'text' => 'Пожалуйста, выберите корректный день недели.',
                    ]);
                }
                break;

            case 2:
                // Обработка ввода времени
                if ($this->isValidTime($messageText)) {
                    $telegram->sendMessage([
                        'chat_id' => $chatId,
                        'text' => 'Введите количество периодов:',
                    ]);
                    SettingState::setReportTime($userId, $messageText);
                    SettingState::setStep($userId, 3); // Устанавливаем следующий шаг
                } else {
                    $telegram->sendMessage([
                        'chat_id' => $chatId,
                        'text' => 'Пожалуйста, введите корректное время в формате HH:MM.',
                    ]);
                }
                break;

            case 3:
                // Обработка ввода количества периодов
                if (is_numeric($messageText) && (int) $messageText > 0) {
                    $dayOfWeek = SettingState::getDayOfWeek($userId);
                    $reportTime = SettingState::getReportTime($userId);
                    Setting::create([
                        'report_day' => $dayOfWeek,
                        'report_time' => $reportTime,
                        'weeks_in_period' => (int) $messageText,
                    ]);
                    $telegram->sendMessage([
                        'chat_id' => $chatId,
                        'text' => 'Настройка успешно создана!',
                    ]);
                    UserState::resetState($userId);
                    SettingState::clearAll($userId); // Очищаем все данные
                } else {
                    $telegram->sendMessage([
                        'chat_id' => $chatId,
                        'text' => 'Пожалуйста, введите корректное количество периодов.',
                    ]);
                }
                break;

            default:
                UserState::resetState($userId);
                SettingState::clearAll($userId);
                break;
        }
    }

    private function isValidTime($time)
    {
        return preg_match('/^(2[0-3]|[01]?[0-9]):([0-5][0-9])$/', $time);
    }
}