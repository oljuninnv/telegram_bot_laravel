<?php

namespace App\Actions;

use App\Helpers\GoogleHelper;
use App\Models\Report;
use App\Models\Setting;
use App\Models\Hashtag;
use App\Models\Chat;
use Carbon\Carbon;
use Google\Service\Sheets;
use Google\Client;

class CreateManagerClientReport
{
    use GoogleHelper;

    public function execute(): string
    {
        $settings = Setting::latest()->first();

        if (!$settings) {
            return 'Настройки отсутствуют.';
        }

        $reportTime = $settings->report_time;
        $weeksInPeriod = $settings->weeks_in_period;
        $currentPeriodEndDate = Carbon::parse($settings->current_period_end_date);

        $startDate = $currentPeriodEndDate->copy()
            ->subWeeks($weeksInPeriod)
            ->setTimeFromTimeString($reportTime);

        $hashtags = Hashtag::whereHas('Setting_Hashtag', function ($query) use ($settings) {
            $query->where('setting_id', $settings->id);
        })->get();

        $googleSheetData = [['Хэштег', 'Заголовок отчета', 'Ссылки на чаты']];

        foreach ($hashtags as $hashtag) {
            $reportDetails = Report::where('hashtag_id', $hashtag->id)
                ->whereBetween('created_at', [$startDate, $currentPeriodEndDate])
                ->get();

            $chatsWithHashtags = $reportDetails->pluck('chat_id')->unique();
            $allChats = Chat::pluck('id')->toArray();
            $chatsWithoutHashtags = array_diff($allChats, $chatsWithHashtags->toArray());

            $message = "Отчёт за период: {$startDate} - {$currentPeriodEndDate}\n";
            $message .= "Хэштег: {$hashtag->hashtag}\n";
            $message .= "Чаты без хэштега:\n";

            $chatLinks = [];
            foreach ($chatsWithoutHashtags as $chatId) {
                $chat = Chat::find($chatId);
                $chatLink = $chat ? ($chat->chat_link ? "{$chat->name} - {$chat->chat_link}" : $chat->name) : "Чат с ID $chatId не найден";
                $message .= "Чат: {$chatLink}\n";
                $chatLinks[] = $chatLink;
            }

            $googleSheetData[] = [
                $hashtag->hashtag,
                "Тут не было {$hashtag->hashtag}",
                implode("\n", $chatLinks)
            ];
        }

        $client = new Client();
        $client->setApplicationName(env('GOOGLE_APPLICATION_NAME'));
        $client->setScopes(Sheets::SPREADSHEETS);
        $client->setAuthConfig(storage_path('credentials.json'));
        $client->setAccessType('offline');
        $service = new Sheets($client);

        $spreadsheetId = config('services.google.sheet_id');
        if (empty($spreadsheetId)) {
            return 'GOOGLE_SHEET_ID не задан в .env';
        }

        $sheetName = $startDate->format('d.m.Y H:i') . ' - ' . $currentPeriodEndDate->format('d.m.Y H:i');

        $this->getOrCreateSheet($service, $spreadsheetId, $sheetName);
        $this->fillGoogleSheet($service, $spreadsheetId, $sheetName, $googleSheetData);

        return 'https://docs.google.com/spreadsheets/d/' . $spreadsheetId . '/edit#gid=' . $this->getSheetId($service, $spreadsheetId, $sheetName);
    }

    protected function getSheetId(Sheets $service, string $spreadsheetId, string $sheetName): int
    {
        $spreadsheet = $service->spreadsheets->get($spreadsheetId);
        foreach ($spreadsheet->getSheets() as $sheet) {
            if ($sheet->getProperties()->getTitle() === $sheetName) {
                return $sheet->getProperties()->getSheetId();
            }
        }
        throw new \RuntimeException("Sheet {$sheetName} not found");
    }
}