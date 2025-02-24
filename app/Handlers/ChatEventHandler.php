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
            if ($update?->message?->entities['type' == 'hashtag']) {
                $parts = explode(' ', $messageText);

                if (count($parts) >= 2) {
                    $hashtagText = $parts[0];
                    $googleSheetUrl = $parts[1];
                } else {
                    $telegram->sendMessage([
                        'chat_id' => $update->message->chat->id,
                        'text' => 'Неверный формат сообщения. Пример: #хэштег {ссылка на google таблицу}',
                    ]);
                    return 'Неверный формат сообщения. Пример: #хэштег {ссылка на google таблицу}';
                }
                $allowedHashtagIds = Setting_Hashtag::pluck('hashtag_id')->toArray();

                // Ищем хэштег, который есть в списке разрешенных
                $hashtag = Hashtag::where('hashtag', $hashtagText)
                    ->whereIn('id', $allowedHashtagIds)
                    ->first();

                if ($hashtag) {
                    $settings = Setting::all()->last();

                    // Если записей нет, используем значения по умолчанию
                    if (!$settings) {
                        $reportTime = '10:00'; // Значение по умолчанию
                    } else {
                        $reportTime = $settings->report_time;
                    }

                    $report = Report::Create([
                        'start_date' => Carbon::now()->startOfWeek()->setTimeFromTimeString($reportTime),
                        'end_date' => Carbon::now()->endOfWeek()->setTimeFromTimeString($reportTime)->subSecond(),
                        'google_sheet_url' => $googleSheetUrl
                    ]);

                    $chat = Chat::where('chat_id', $update->message->chat->id)->first();
                    Report_Detail::create([
                        'report_id' => $report->id,
                        'chat_id' => $chat->id,
                        'hashtag_id' => $hashtag->id,
                    ]);

                    $telegram->sendMessage([
                        'chat_id' => $update->message->chat->id,
                        'text' => 'Сообщение с отчётом было отправлено - ' . $hashtag->hashtag,
                    ]);

                    $telegram->sendMessage([
                        'chat_id' => env('TELEGRAM_USER_ADMIN_ID'),
                        'text' => 'В чате ' . $update->message->chat->title . " был отправлен отчёт с хэштегом " . $hashtag->hashtag,
                    ]);
                    return 'Сообщение с отчётом было отправлено - ' . $hashtag->hashtag;
                }
            }
        }

        return null;
    }
}