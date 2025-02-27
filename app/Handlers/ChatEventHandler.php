<?php

namespace App\Handlers;

use Telegram\Bot\Api;
use App\Models\Chat;
use App\Models\Hashtag;
use App\Models\Setting;
use App\Models\Setting_Hashtag;
use Telegram\Bot\BotsManager;
use Carbon\Carbon;
use App\Services\ReportService;
use App\Helpers\MessageHelper;
use App\Models\Report;

class ChatEventHandler
{
    use MessageHelper;

    protected $reportService;

    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    public function handle(Api $telegram, $update, BotsManager $botsManager)
    {
        $botsManager->bot()->commandsHandler(true);

        $chatMember = $update?->myChatMember;
        $status = $chatMember?->newChatMember?->status;
        $chatId = $chatMember?->chat?->id;
        $userId = $chatMember?->from?->id;

        if ($chatId && $userId) {
            if ($userId == env('TELEGRAM_USER_ADMIN_ID')) {
                if (in_array($status, ['left', 'kicked'])) {
                    $this->handleChatRemoval($chatId);
                    return 'Чат удален';
                }

                if (!Chat::where('chat_id', $chatId)->exists()) {
                    $this->handleNewChat($telegram, $chatMember, $chatId);
                    return 'Чат добавлен';
                }
            } elseif ($status != 'left') {
                $telegram->leaveChat(['chat_id' => $chatId]);
                return 'Бот покинул чат';
            }
        }

        $this->handleHashtagMessage($telegram, $update);
        return null;
    }

    private function handleChatRemoval(int $chatId)
    {
        Chat::where('chat_id', $chatId)->delete();
        Report::where('chat_id', $chatId)->delete();
    }

    private function handleNewChat(Api $telegram, $chatMember, int $chatId)
    {
        $chatLink = $chatMember?->chat?->username?'t.me/'. $chatMember?->chat?->username:'';
        Chat::create(['name' => $chatMember->chat->title, 'chat_id' => $chatId,'chat_link' => $chatLink]);

        $hashtags = Hashtag::whereIn('id', function ($query) {
            $query->select('hashtag_id')->from('setting_hashtags');
        })->pluck('hashtag')->toArray();

        $hashtagsText = implode(' ', array_map(function ($hashtag) {
            return $hashtag;
        }, $hashtags));

        $this->sendMessage($telegram, $chatId, 'Для отправки отчётов необходимо записывать хэштеги: ' . $hashtagsText . '. Пример записи: #хэштег {ссылка на google таблицу} или прикрепите файл с отчётом с подписью #хэштег');
    }

    private function handleHashtagMessage(Api $telegram, $update)
    {
        $messageText = $update?->message?->text;

        if ($messageText && $update?->message?->entities) {
            foreach ($update->message->entities as $entity) {
                if ($entity->type === 'hashtag') {
                    $parts = explode(' ', $messageText);

                    if (count($parts) < 2) {
                        $this->sendMessage($telegram, $update->message->chat->id, 'Неверный формат сообщения. Пример: #хэштег {ссылка на google таблицу}');
                        return;
                    }

                    $hashtagText = $parts[0];
                    $googleSheetUrl = $parts[1];

                    $allowedHashtagIds = Setting_Hashtag::pluck('hashtag_id')->toArray();
                    $hashtag = Hashtag::where('hashtag', $hashtagText)
                        ->whereIn('id', $allowedHashtagIds)
                        ->first();

                    if ($hashtag) {
                        $this->handleReportSubmission($telegram, $update, $hashtag, $googleSheetUrl);
                    }
                }
            }
        }
        else if ($update?->message?->caption && $update?->message?->caption_entities)
        {
            foreach ($update->message->caption_entities as $entity) {
                if ($entity->type === 'hashtag') {

                    $hashtagText = $update->message->caption;
                    $allowedHashtagIds = Setting_Hashtag::pluck('hashtag_id')->toArray();
                    $reportTitle = $update->message->document->file_name; 
                    $reportTitle = strtok($reportTitle, '.'); 
                    $hashtag = Hashtag::where('hashtag', $hashtagText)
                        ->whereIn('id', $allowedHashtagIds)
                        ->where('report_title',$reportTitle)
                        ->first();
                    if ($hashtag) {
                        $this->handleReportSubmission($telegram, $update, $hashtag, "");
                    }
                }
            }
        }
    }

    private function handleReportSubmission(Api $telegram, $update, $hashtag, $googleSheetUrl)
    {
        $settings = Setting::latest()->first();
        $reportTime = $settings ? $settings->report_time : '10:00';

        // Преобразуем строку в объект Carbon, если это необходимо
        $endDate = $settings
            ? Carbon::parse($settings->current_period_end_date)
            : Carbon::now()->endOfWeek()->setTimeFromTimeString($reportTime)->subSecond();

        $startDate = $settings
            ? Carbon::parse($endDate)->subWeeks($settings->weeks_in_period)->setTimeFromTimeString($reportTime)
            : Carbon::now()->startOfWeek()->setTimeFromTimeString($reportTime);

        $chat = Chat::where('chat_id', $update->message->chat->id)->first();
        $this->reportService->createReport($googleSheetUrl, $chat, $hashtag, $startDate, $endDate);

        $this->sendMessage($telegram, $update->message->chat->id, 'Сообщение с отчётом было отправлено - ' . $hashtag->hashtag);
        $this->sendMessage($telegram, env('TELEGRAM_USER_ADMIN_ID'), 'В чате ' . $update->message->chat->title . " был отправлен отчёт с хэштегом " . $hashtag->hashtag);
    }
}