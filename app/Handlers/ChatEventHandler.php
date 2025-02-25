<?php

namespace App\Handlers;

use Telegram\Bot\Api;
use App\Models\Chat;
use App\Models\Report;
use App\Models\Hashtag;
use App\Models\Setting;
use App\Models\Setting_Hashtag;
use App\Models\Report_Detail;
use Telegram\Bot\BotsManager;
use Carbon\Carbon;

class ChatEventHandler
{
    public function handle(Api $telegram, $update, BotsManager $botsManager)
    {
        $botsManager->bot()->commandsHandler(true);

        $chatMember = $update?->myChatMember;
        $status = $chatMember?->newChatMember?->status;
        $chatId = $chatMember?->chat?->id;
        $userId = $chatMember?->from?->id;

        // Обработка событий добавления/удаления бота
        if ($chatId && $userId) {
            if ($userId == env('TELEGRAM_USER_ADMIN_ID')) {
                if (in_array($status, ['left', 'kicked'])) {
                    Chat::where('chat_id', $chatId)->delete();
                    Report_Detail::where('chat_id', $chatId)->delete();
                    return 'Чат удален';
                }

                if (!Chat::where('chat_id', $chatId)->exists()) {
                    // Создаем новый чат
                    Chat::create(['name' => $chatMember->chat->title, 'chat_id' => $chatId]);

                    // Получаем названия хэштегов из таблицы Hahtag, id которых содержатся в Setting_Hashtag
                    $hashtags = Hashtag::whereIn('id', function ($query) {
                        $query->select('hashtag_id')->from('setting_hashtags');
                    })->pluck('hashtag')->toArray();

                    // Формируем строку с хэштегами
                    $hashtagsText = implode(' ', array_map(function ($hashtag) {
                        return $hashtag;
                    }, $hashtags));

                    // Отправляем сообщение с хэштегами
                    $telegram->sendMessage([
                        'chat_id' => $chatId,
                        'text' => 'Для отправки отчётов необходимо записывать хэштеги: ' . $hashtagsText . '.Пример записи: #хэштег {ссылка на google таблицу}',
                    ]);

                    return 'Чат добавлен';
                }
            } elseif ($status != 'left') {
                $telegram->leaveChat(['chat_id' => $chatId]);
                return 'Бот покинул чат';
            }
        }

        // Обработка сообщений с хэштегами
        $messageText = $update?->message?->text;

        if ($messageText) {
            
            if ($update?->message?->entities) {
                
                foreach ($update->message->entities as $entity) {
                    if ($entity->type === 'hashtag') {
                        $parts = explode(' ', $messageText);

                        if (count($parts) < 2) {
                            $telegram->sendMessage([
                                'chat_id' => $update->message->chat->id,
                                'text' => 'Неверный формат сообщения. Пример: #хэштег {ссылка на google таблицу}',
                            ]);
                            return 'Неверный формат сообщения. Пример: #хэштег {ссылка на google таблицу}';
                        }

                        $hashtagText = $parts[0];
                        $googleSheetUrl = $parts[1];

                        // Получаем разрешенные хэштеги
                        $allowedHashtagIds = Setting_Hashtag::pluck('hashtag_id')->toArray();

                        // Ищем хэштег в базе данных
                        $hashtag = Hashtag::where('hashtag', $hashtagText)
                            ->whereIn('id', $allowedHashtagIds)
                            ->first();

                        if ($hashtag) {
                            // Получаем настройки
                            $settings = Setting::latest()->first();

                            // Устанавливаем время и даты отчета
                            if (!$settings) {
                                $reportTime = '10:00';
                                $startDate = Carbon::now()->startOfWeek()->setTimeFromTimeString($reportTime);
                                $endDate = Carbon::now()->endOfWeek()->setTimeFromTimeString($reportTime)->subHour();
                            } else {
                                $reportTime = $settings->report_time;
                                $endDate = $settings->current_period_end_date;
                                $startDate = $startDate = Carbon::parse($endDate)
                                    ->subWeeks($settings->weeks_in_period) 
                                    ->setTimeFromTimeString($reportTime); 
                            }

                            $report = Report::create([
                                'start_date' => $startDate,
                                'end_date' => $endDate,
                                'google_sheet_url' => $googleSheetUrl,
                            ]);

                            $chat = Chat::where('chat_id', $update->message->chat->id)->first();
                            Report_Detail::create([
                                'report_id' => $report->id,
                                'chat_id' => $chat->id,
                                'hashtag_id' => $hashtag->id,
                            ]);

                            // Отправляем сообщение пользователю
                            $telegram->sendMessage([
                                'chat_id' => $update->message->chat->id,
                                'text' => 'Сообщение с отчётом было отправлено - ' . $hashtag->hashtag,
                            ]);

                            // Уведомляем администратора
                            $telegram->sendMessage([
                                'chat_id' => env('TELEGRAM_USER_ADMIN_ID'),
                                'text' => 'В чате ' . $update->message->chat->title . " был отправлен отчёт с хэштегом " . $hashtag->hashtag,
                            ]);

                            return 'Сообщение с отчётом было отправлено - ' . $hashtag->hashtag;
                        }
                    }
                }
            }
        }

        return null;
    }
}