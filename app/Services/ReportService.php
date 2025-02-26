<?php

namespace App\Services;

use App\Models\Report;
use App\Models\Chat;
use App\Models\Hashtag;
use Carbon\Carbon;

class ReportService
{
    /**
     * Создать отчет и связать его с чатом и хэштегом.
     *
     * @param string $googleSheetUrl
     * @param Chat $chat
     * @param Hashtag $hashtag
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return void
     */
    public function createReport(string $googleSheetUrl, Chat $chat, Hashtag $hashtag, Carbon $startDate, Carbon $endDate)
    {
        $report = Report::where('start_date', $startDate)
        ->where('end_date', $endDate)
        ->where('chat_id', $chat->id)
        ->where('hashtag_id', $hashtag->id)
        ->first();

        if (!$report){
            $report = Report::create([
                'start_date' => $startDate,
                'end_date' => $endDate,
                'sheet_url' => $googleSheetUrl,
                'chat_id' => $chat->id,
                'hashtag_id' => $hashtag->id
            ]);
    
        }
    }
}