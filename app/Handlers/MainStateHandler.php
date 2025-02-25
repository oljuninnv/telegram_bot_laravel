<?php

namespace App\Handlers;

use Telegram\Bot\Api;
use App\Keyboards;
use App\Services\UserState;
use App\Models\Chat;
use Telegram\Bot\BotsManager;
use App\Models\Setting;
use App\Models\Hashtag;
use App\Models\Setting_Hashtag;
use App\Models\Report_Detail;
use Carbon\Carbon;
class MainStateHandler
{
    public function handle(Api $telegram, int $chatId, int $userId, string $messageText, BotsManager $botsManager)
    {
        $update = $telegram->getWebhookUpdate();
        $botsManager->bot()->commandsHandler(true);
        $isBotCommand = false;

        if (!empty($update->message->entities)) {
            foreach ($update->message->entities as $entity) {
                if ($entity->type === 'bot_command') {
                    $isBotCommand = true;
                    break;
                }
            }
        }

        if (!$isBotCommand) {
            switch ($messageText) {
                case 'Получить список чатов':
                    $chats = Chat::all();
                    $response = '';
                    foreach ($chats as $chat) {
                        $response .= "\nНазвание: {$chat->name} - ссылка: " . (!empty($chat->chat_link) ? $chat->chat_link : 'отсутствует');
                    }
                    $telegram->sendMessage([
                        'chat_id' => $chatId,
                        'text' => $response,
                    ]);
                    break;
                case 'Настройка сбора отчетов':
                    UserState::setState($userId, 'settings');
                    $set = Setting::all()->last();

                    if (!$set) {
                        $telegram->sendMessage([
                            'chat_id' => $chatId,
                            'text' => 'Настройки отсутствуют. Хотите создать новую?',
                            'reply_markup' => Keyboards::settingsAdminKeyboard(),
                        ]);
                    } else {
                        $telegram->sendMessage([
                            'chat_id' => $chatId,
                            'text' => "Текущие настройки:\n"
                                . "День недели: {$set->report_day}\n"
                                . "Время: {$set->report_time}\n"
                                . "Период сбора: {$set->weeks_in_period}\n\n"
                                . "Все хэштеги в базе данных:\n"
                                . $this->getAllHashtags() . "\n\n"
                                . "Подключённые хэштеги:\n"
                                . $this->getAttachedHashtags($set) . "\n\n"
                                . "Что вы хотите обновить?",
                            'reply_markup' => Keyboards::updateSettingsKeyboard(),
                        ]);
                    }
                    break;

                case 'Проверить отчеты':

                    $telegram->sendMessage([
                        'chat_id' => $chatId,
                        'text' => 'Вы выбрали проверку отчетов. Начинаю проверку...',
                    ]);

                    // Получаем все чаты
                    $chats = Chat::all();

                    $settings = Setting::all()->last();

                    $hashtags = Hashtag::whereHas('Setting_Hashtag', function ($query) use ($settings) {
                        $query->where('setting_id', $settings->id);
                    })->get();

                    $startDate = Carbon::parse($settings->current_period_end_date)
                        ->subWeeks($settings->weeks_in_period)
                        ->setTimeFromTimeString($settings->report_time);
                    $endDate = $settings->current_period_end_date;

                    $message = "Результаты проверки отчетов:\n\n";

                    foreach ($chats as $chat) {
                        $message .= "Чат: " . $chat->name . "\n";

                        foreach ($hashtags as $hashtag) {
                            $reportDetail = Report_Detail::where('chat_id', $chat->id)
                                ->where('hashtag_id', $hashtag->id)
                                ->whereBetween('created_at', [$startDate, $endDate])
                                ->first();

                            // Добавляем информацию о хэштеге и статусе отчета
                            $message .= "Хэштег: " . $hashtag->hashtag . " - ";

                            if ($reportDetail) {
                                $message .= "есть отчёт. Ссылка: " . $reportDetail->report->google_sheet_url . "\n";
                            } else {
                                $message .= "нет отчёта\n";
                            }
                        }

                        $telegram->sendMessage([
                            'chat_id' => $chatId,
                            'text' => $message,
                        ]);

                        $message = "Результаты проверки отчетов:\n\n";
                    }
                    break;
                case 'Помощь':
                    $telegram->sendMessage([
                        'chat_id' => $chatId,
                        'text' => "Данный бот предназначен для управления отчетами и взаимодействия с чатами. Вот список доступных команд:\n\n" .
                            "1. Получить список чатов - Позволяет получить список доступных чатов.\n" .
                            "2. Настройка сбора отчетов - Позволяет настроить параметры сбора отчетов.\n" .
                            "3. Проверить отчеты - Проверяет отчёты за текущий период и их статус.\n",
                    ]);
                    break;

                default:
                    $telegram->sendMessage([
                        'chat_id' => $chatId,
                        'text' => 'Неизвестная команда. Пожалуйста, выберите действие из меню.',
                        'reply_markup' => Keyboards::mainAdminKeyboard(),
                    ]);
                    break;
            }
        }
    }

    private function getAllHashtags(): string
    {
        $hashtags = Hashtag::all();
        $hashtagList = [];

        foreach ($hashtags as $hashtag) {
            $hashtagList[] = $hashtag->hashtag;
        }

        return implode(', ', $hashtagList);
    }

    // Метод для получения подключённых хэштегов к текущей настройке
    private function getAttachedHashtags(Setting $setting): string
    {
        // Используем модель Setting_Hashtag для получения привязанных хэштегов
        $attachedHashtags = Setting_Hashtag::where('setting_id', $setting->id)
            ->with('hashtag')
            ->get()
            ->pluck('hashtag.hashtag')
            ->toArray();

        if (!empty($attachedHashtags)) {
            return implode(', ', $attachedHashtags);
        }

        return 'Нет подключённых хэштегов';
    }
}