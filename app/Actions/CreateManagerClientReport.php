<?php

namespace App\Actions;

use App\Helpers\GoogleHelper;
use App\Models\Report;
use App\Models\Setting;
use App\Models\Hashtag;
use App\Models\Chat;
use Carbon\Carbon;
use Google\Service\Sheets;
use Google\Service\Sheets\ClearValuesRequest;
use Google\Client;

class CreateManagerClientReport
{
    use GoogleHelper;

    public function execute(): string
    {
        $settings = Setting::latest()->first();

        \Log::info($settings);

        if (!$settings) {
            return 'Настройки отсутствуют.';
        }

        // Извлекаем настройки
        $reportDay = $settings->report_day;
        $reportTime = $settings->report_time;
        $weeksInPeriod = $settings->weeks_in_period;
        $currentPeriodEndDate = Carbon::parse($settings->current_period_end_date);

        // Вычисляем startDate
        $startDate = $currentPeriodEndDate->copy()
            ->subWeeks($weeksInPeriod)
            ->setTimeFromTimeString($reportTime);

        // Получаем хэштеги, связанные с настройками
        $hashtags = Hashtag::whereHas('Setting_Hashtag', function ($query) use ($settings) {
            $query->where('setting_id', $settings->id);
        })->get();

        // Данные для Google таблицы
        $googleSheetData = [['Хэштег', 'Заголовок отчета', 'Ссылки на чаты']];

        // Формируем отчёт для каждого хэштега
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

        // Создаем Google таблицу
        $client = new Client();
        $client->setApplicationName(env('GOOGLE_APPLICATION_NAME'));
        $client->setScopes(Sheets::SPREADSHEETS);
        $client->setAuthConfig(storage_path('credentials.json'));
        $client->setAccessType('offline');
        $service = new Sheets($client);
        $service = new Sheets($client);

        $spreadsheetId = config('services.google.sheet_id');
        if (empty($spreadsheetId)) {
            return 'GOOGLE_SHEET_ID не задан в .env';
        }

        $sheetName = $startDate->format('d.m.Y H:i') . ' - ' . $currentPeriodEndDate->format('d.m.Y H:i');

        // Проверяем, существует ли лист с таким названием
        $sheetExists = $this->checkIfSheetExists($service, $spreadsheetId, $sheetName);

        if ($sheetExists) {
            // Если лист существует, очищаем его и обновляем данные
            $this->clearSheet($service, $spreadsheetId, $sheetName);
            $this->fillGoogleSheet($service, $spreadsheetId, $sheetName, $googleSheetData);
            return 'Отчёт успешно обновлён.';
        } else {
            // Если лист не существует, создаем новый
            $this->createGoogleSheet($service, $spreadsheetId, $sheetName);
            $this->fillGoogleSheet($service, $spreadsheetId, $sheetName, $googleSheetData);
            return 'Отчёт успешно создан и отправлен.';
        }
    }
}