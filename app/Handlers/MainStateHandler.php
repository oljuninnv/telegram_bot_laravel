<?php

namespace App\Handlers;

use Telegram\Bot\Api;
use App\Keyboards;
use App\Services\UserState;
use App\Models\Chat;
use App\Models\Setting;
use App\Models\Hashtag;
use App\Helpers\HashtagHelper;
use App\Models\Report;
use Carbon\Carbon;
use App\Models\TelegramUser;
use App\Enums\RoleEnum;
use Illuminate\Support\Facades\Log;

class MainStateHandler
{
    use HashtagHelper;

    public function handle(Api $telegram, int $chatId, int $userId, string $messageText)
    {
        try {
            $user = TelegramUser::where('telegram_id', $chatId)->first();
            if ($user->role == RoleEnum::SUPER_ADMIN->value) {
                switch ($messageText) {
                    case 'Получить список чатов':
                        $this->handleGetChatsList($telegram, $chatId);
                        break;

                    case 'Настройки':
                        $this->handleReportSettings($telegram, $chatId, $userId);
                        break;

                    case 'Проверить отчеты':
                        $this->handleCheckReports($telegram, $chatId);
                        break;

                    case 'Помощь':
                        $this->handleHelp($telegram, $chatId, $user);
                        break;

                    default:
                        $telegram->sendMessage([
                            'chat_id' => $chatId,
                            'text' => 'Неизвестная команда. Пожалуйста, выберите действие из меню.',
                            'reply_markup' => Keyboards::mainSuperAdminKeyboard(),
                        ]);
                        break;
                }
            } else {
                switch ($messageText) {
                    case 'Получить список чатов':
                        $this->handleGetChatsList($telegram, $chatId);
                        break;

                    case 'Проверить отчеты':
                        $this->handleCheckReports($telegram, $chatId);
                        break;

                    case 'Помощь':
                        $this->handleHelp($telegram, $chatId, $user);
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
        } catch (\Exception $e) {
            Log::error('Error in MainStateHandler: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
        }
    }

    private function handleGetChatsList(Api $telegram, int $chatId)
    {
        $chats = Chat::all();

        if ($chats->isEmpty()) {
            $telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => 'Список чатов пуст.',
            ]);
            return;
        }

        $response = '';
        foreach ($chats as $chat) {
            $chatLink = !empty($chat->chat_link) ? $chat->chat_link : 'отсутствует';
            $response .= "Название: {$chat->name} - ссылка: {$chatLink}\n";
        }

        if (!empty($response)) {
            $telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => $response,
            ]);
        } else {
            $telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => 'Не удалось сформировать список чатов.',
            ]);
        }
    }


    private function handleReportSettings(Api $telegram, int $chatId, int $userId)
    {
        UserState::setState($userId, 'settings');
        $set = Setting::all()->last();

        if (!$set) {
            $telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => 'Настройки отсутствуют. Создайте новую.',
                'reply_markup' => Keyboards::settingsAdminKeyboard(),
            ]);
        } else {
            $telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => "Текущие настройки:\n"
                    . "Дата окончания текущего сбора: {$set->current_period_end_date}\n"
                    . "День недели: {$set->report_day}\n"
                    . "Время: {$set->report_time}\n"
                    . "Период сбора: {$set->weeks_in_period}\n"
                    . "Все хэштеги в базе данных:\n"
                    . $this->getAllHashtags() . "\n"
                    . "Подключённые хэштеги:\n"
                    . $this->getAttachedHashtags($set) . "\n",
                'reply_markup' => Keyboards::updateSettingsKeyboard(),
            ]);
        }
    }

    private function handleCheckReports(Api $telegram, int $chatId)
    {
        $chats = Chat::all();
        if ($chats->isEmpty()) {
            $telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => 'Список чатов пуст.',
            ]);
            return;
        }

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
                $report = Report::where('chat_id', $chat->id)
                    ->where('hashtag_id', $hashtag->id)
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->first();

                $message .= "Хэштег: " . $hashtag->hashtag . " - ";

                if ($report) {
                    if ($report->sheet_url) {
                        $message .= "есть отчёт. Ссылка: " . $report->sheet_url . "\n";
                    } else {
                        $message .= "Ссылка отсутствует. Отчёт представлен документом в чате \n";
                    }

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
    }

    private function handleHelp(Api $telegram, int $chatId, $user)
    {
        if ($user->role == RoleEnum::SUPER_ADMIN->value) {
            $telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => "Данный бот предназначен для управления отчетами и взаимодействия с чатами. Вот список доступных команд:\n\n" .
                    "1. Получить список чатов - Позволяет получить список доступных чатов.\n" .
                    "2. Настройки - Позволяет настроить параметры сбора отчетов, хэштеги и пользователей системы.\n" .
                    "3. Проверить отчеты - Проверяет отчёты за текущий период и их статус.\n",
            ]);
        }
        else{
            $telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => "Данный бот предназначен для управления отчетами и взаимодействия с чатами. Вот список доступных команд:\n\n" .
                    "1. Получить список чатов - Позволяет получить список доступных чатов.\n" .
                    "2. Проверить отчеты - Проверяет отчёты за текущий период и их статус.\n",
            ]);
        }
    }
}