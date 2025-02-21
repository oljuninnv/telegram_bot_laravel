<?php

namespace App\Handlers;

use Telegram\Bot\Api;
use App\Enums\DayOfWeekEnums; // Импортируйте ваш enum для дней недели
use App\Models\Setting; // Импортируйте модель для сохранения настроек
use Telegram\Bot\BotsManager;
use App\Services\UserState;
use App\Services\SettingState;
use App\Keyboards;

class CreateSettingHandler
{
    private $step = 0; // Шаг для отслеживания текущего состояния

    public function handle(Api $telegram, int $chatId, int $userId, string $messageText, BotsManager $botsManager)
    {
        $botsManager->bot()->commandsHandler(true);
        $update = $telegram->getWebhookUpdate();

        if (!empty($update->message->entities)) {
            foreach ($update->message->entities as $entity) {
                if ($entity->type === 'bot_command') {
                    return;
                }
            }
        }

        // return response($this->step);
        switch ($this->step) {
            
            case 0:
                if ($messageText === 'Создать настройку') {
                    $telegram->sendMessage([
                        'chat_id' => $chatId,
                        'text' => 'Выберите день недели сбора отчётов:',
                        'reply_markup' => Keyboards::getDaysOfWeekKeyboard(),
                    ]);
                    $this->step++;
                    
                }
                
                break;

                case 1:
                    // Обработка нажатия на кнопку с днем недели
                    if ($update->callback_query) {
                        $dayOfWeek = $update->callback_query->data; // Получаем данные из callback_query
    
                        // Проверяем, является ли выбранный день корректным
                        if (DayOfWeekEnums::tryFrom($dayOfWeek)) {
                            $this->step++;
                            $telegram->sendMessage([
                                'chat_id' => $chatId,
                                'text' => 'Введите время сбора отчёта (например, 14:00):',
                            ]);
                            SettingState::setDayOfWeek($userId, $dayOfWeek); // Сохраняем выбранный день недели
                        } else {
                            $telegram->sendMessage([
                                'chat_id' => $chatId,
                                'text' => 'Пожалуйста, выберите корректный день недели.',
                            ]);
                        }
                    }
                    else{
                        $telegram->sendMessage([
                            'chat_id' => $chatId,
                            'text' => 'Ошика обработки',
                        ]);
                    }
                    break;

            case 2:
                // Сохранение времени
                if ($this->isValidTime($messageText)) {
                    $this->step++;
                    $telegram->sendMessage([
                        'chat_id' => $chatId,
                        'text' => 'Введите количество периодов:',
                    ]);
                    // Сохраните время в состоянии пользователя
                    SettingState::setReportTime($userId, $messageText);
                } else {
                    $telegram->sendMessage([
                        'chat_id' => $chatId,
                        'text' => 'Пожалуйста, введите корректное время в формате HH:MM.',
                    ]);
                }
                break;

            case 3:
                // Сохранение количества периодов
                if (is_numeric($messageText) && (int)$messageText > 0) {
                    // Сохраните все настройки в базе данных
                    $dayOfWeek = SettingState::getDayOfWeek($userId);
                    $reportTime = SettingState::getReportTime($userId);
                    Setting::create([
                        'chat_id' => $chatId,
                        'user_id' => $userId,
                        'day_of_week' => $dayOfWeek,
                        'report_time' => $reportTime,
                        'periods' => (int)$messageText,
                    ]);
                    $telegram->sendMessage([
                        'chat_id' => $chatId,
                        'text' => 'Настройка успешно создана!',
                    ]);
                    // Сброс состояния
                    UserState::resetState($userId);
                    $this->step = 0;
                } else {
                    $telegram->sendMessage([
                        'chat_id' => $chatId,
                        'text' => 'Пожалуйста, введите корректное количество периодов.',
                    ]);
                }
                break;

            default:
                // Сброс состояния, если что-то пошло не так
                UserState::resetState($userId);
                $this->step = 0;
                break;
        }
    }

    private function isValidTime($time)
    {
        // Проверка корректности времени в формате HH:MM
        return preg_match('/^(2[0-3]|[01]?[0-9]):([0-5][0-9])$/', $time);
    }
}
