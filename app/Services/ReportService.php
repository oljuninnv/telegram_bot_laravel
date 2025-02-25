<?php

namespace App\Services;

use App\Models\Report;
use App\Models\Report_Detail;
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
        $report = Report::create([
            'start_date' => $startDate,
            'end_date' => $endDate,
            'google_sheet_url' => $googleSheetUrl,
        ]);

        Report_Detail::create([
            'report_id' => $report->id,
            'chat_id' => $chat->id,
            'hashtag_id' => $hashtag->id,
        ]);
    }
}