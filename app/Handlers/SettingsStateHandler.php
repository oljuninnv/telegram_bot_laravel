<?php

namespace App\Handlers;

use Telegram\Bot\Api;
use App\Keyboards;
use App\Services\UserState;
use Telegram\Bot\BotsManager;
use App\Handlers\CreateSettingHandler;
use App\Models\Setting;
use App\Handlers\UpdateHandlers\UpdatePeriodHandler;
use App\Handlers\UpdateHandlers\UpdateTimeHandler;
use App\Handlers\UpdateHandlers\UpdateDayOfWeekHandler;
use App\Handlers\UpdateHandlers\UpdateHashtagsSettingsHandler;

class SettingsStateHandler
{
    public function handle(Api $telegram, int $chatId, int $userId, string $messageText, BotsManager $botsManager)
    {
        $botsManager->bot()->commandsHandler(true);
        $update = $telegram->getWebhookUpdate();

        $isBotCommand = false;

        // Проверка является ли текст командой
        if (!empty($update->message->entities)) {
            foreach ($update->message->entities as $entity) {
                if ($entity->type === 'bot_command') {
                    $isBotCommand = true;
                    break;
                }
            }
        }

        if (!$isBotCommand) {
            $settings = Setting::all()->last();
            switch ($messageText) {
                case 'Настроить сбор отчётов':
                    $telegram->sendMessage([
                        'chat_id' => $chatId,
                        'text' => 'Хорошо, давайте настроим сбор отчётов.',
                        'reply_markup' => Keyboards::backAdminKeyboard(),
                    ]);
                    UserState::setState($userId, 'createSettings');
                    $createSettingsHandler = new CreateSettingHandler();
                    $createSettingsHandler->handle($telegram, $chatId, $userId, 'Создать настройку', $botsManager);
                    break;

                case 'Обновить период':
                    if (!$settings) {
                        $telegram->sendMessage([
                            'chat_id' => $chatId,
                            'text' => 'У вас нет настроек. Сначала создайте настройку.',
                            'reply_markup' => Keyboards::settingsAdminKeyboard(),
                        ]);
                        return;
                    }
                    $telegram->sendMessage([
                        'chat_id' => $chatId,
                        'text' => 'Введите новое количество периодов (от 1 до 10):',
                    ]);
                    UserState::setState($userId, 'updatePeriod'); // Устанавливаем состояние для обновления периода
                    break;

                case 'Обновить время':
                    if (!$settings) {
                        $telegram->sendMessage([
                            'chat_id' => $chatId,
                            'text' => 'У вас нет настроек. Сначала создайте настройку.',
                            'reply_markup' => Keyboards::settingsAdminKeyboard(),
                        ]);
                        return;
                    }
                    $telegram->sendMessage([
                        'chat_id' => $chatId,
                        'text' => 'Введите новое время сбора отчёта (например, 14:00):',
                    ]);
                    UserState::setState($userId, 'updateTime'); // Устанавливаем состояние для обновления времени
                    break;

                case 'Обновить день недели':
                    if (!$settings) {
                        $telegram->sendMessage([
                            'chat_id' => $chatId,
                            'text' => 'У вас нет настроек. Сначала создайте настройку.',
                            'reply_markup' => Keyboards::getDaysOfWeekKeyboard(),
                        ]);
                        return;
                    }
                    $telegram->sendMessage([
                        'chat_id' => $chatId,
                        'text' => 'Выберите новый день недели сбора отчётов:',
                        'reply_markup' => Keyboards::getDaysOfWeekKeyboard(),
                    ]);
                    UserState::setState($userId, 'updateDayOfWeek'); // Устанавливаем состояние для обновления дня недели
                    break;

                case 'Обновить хэштеги':
                    if (!$settings) {
                        $telegram->sendMessage([
                            'chat_id' => $chatId,
                            'text' => 'У вас нет настроек. Сначала создайте настройку.',
                            'reply_markup' => Keyboards::settingsAdminKeyboard(),
                        ]);
                        return;
                    }
                    $telegram->sendMessage([
                        'chat_id' => $chatId,
                        'text' => 'Выберите действие для настройки хэштегов:',
                        'reply_markup' => Keyboards::hashtagSettingsKeyboard(),
                    ]);
                    UserState::setState($userId, 'updateHashtags'); // Устанавливаем состояние для обновления хэштегов
                    break;

                case 'Назад':
                    UserState::resetState($userId);
                    $telegram->sendMessage([
                        'chat_id' => $chatId,
                        'text' => 'Вы вернулись в главное меню.',
                        'reply_markup' => Keyboards::mainAdminKeyboard(),
                    ]);
                    break;

                default:
                    // Обработка ввода данных для обновления
                    $currentState = UserState::getState($userId);

                    switch ($currentState) {
                        case 'updatePeriod':
                            $handler = new UpdatePeriodHandler();
                            $handler->handle($telegram, $chatId, $userId, $messageText);
                            break;

                        case 'updateTime':
                            $handler = new UpdateTimeHandler();
                            $handler->handle($telegram, $chatId, $userId, $messageText);
                            break;

                        case 'updateDayOfWeek':
                            $handler = new UpdateDayOfWeekHandler();
                            $handler->handle($telegram, $chatId, $userId, $messageText);
                            break;

                        case 'updateHashtags':
                            $handler = new UpdateHashtagsHandler();
                            $handler->handle($telegram, $chatId, $userId, $messageText);
                            break;

                        default:
                            $telegram->sendMessage([
                                'chat_id' => $chatId,
                                'text' => 'Неизвестная команда. Пожалуйста, выберите действие из меню.',
                                'reply_markup' => Keyboards::settingsAdminKeyboard(),
                            ]);
                            break;
                    }
                    break;
            }
        }
    }
}