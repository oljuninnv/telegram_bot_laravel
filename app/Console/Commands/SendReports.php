<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Setting;
use App\Models\Report;
use App\Models\Chat;
use App\Models\Hashtag;
use App\Models\Report_Detail;
use Carbon\Carbon;
use Telegram\Bot\Api;
use App\Enums\DayOfWeekEnums;
use Google\Client;
use Google\Service\Sheets;

class SendReports extends Command
{
    protected $signature = 'reports:send';
    protected $description = 'Send reports for the past period';

    public function handle()
    {
        $telegram = new Api(config('telegram.bot_token'));

        // Получаем настройки из базы данных
        $settings = Setting::all()->last();

        if (!$settings) {
            $this->error('Настройки отсутствуют.');
            return;
        }

        $reportDay = $settings->report_day;
        $reportTime = $settings->report_time;
        $weeksInPeriod = $settings->weeks_in_period;
        $currentPeriodEndDate = $settings->current_period_end_date;

        $startDate = Carbon::now()->startOfWeek()->setTimeFromTimeString($reportTime);
        $endDate = $currentPeriodEndDate;

        // Получаем все отчёты за период
        $reports = Report::whereBetween('start_date', [$startDate, $endDate])->get();
        \Log::info('Найдено отчетов за период: ' . $reports->count());

        // Получаем хэштеги, которые нужно искать
        $hashtags = Hashtag::whereHas('Setting_Hashtag', function ($query) use ($settings) {
            $query->where('setting_id', $settings->id);
        })->get();

        // Данные для Google таблицы
        $googleSheetData = [];

        // Добавляем заголовки в начало массива
        $googleSheetData[] = ['Хэштег', 'Заголовок отчета', 'Ссылки на чаты'];

        // Формируем отчёт для каждого хэштега
        foreach ($hashtags as $hashtag) {
            $reportDetails = Report_Detail::where('hashtag_id', $hashtag->id)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->get();

            $chatsWithHashtags = $reportDetails->pluck('chat_id')->unique();
            $allChats = Chat::pluck('id')->toArray();
            $chatsWithoutHashtags = array_diff($allChats, $chatsWithHashtags->toArray());

            $message = "Отчёт за период: " . $startDate . " - " . $endDate . "\n";
            $message .= "Хэштег: " . $hashtag->hashtag . "\n";
            $message .= "Чаты без хэштега:\n";

            $chatLinks = []; // Массив для хранения ссылок на чаты

            foreach ($chatsWithoutHashtags as $chatId) {
                // Используем метод find для поиска чата по ID
                $chat = Chat::find($chatId);

                // Проверяем, что чат существует
                if ($chat) {
                    $chatLink = $chat->chat_link ? "{$chat->name} - {$chat->chat_link}" : $chat->name;
                    $message .= "Чат: " . $chatLink . "\n";
                    $chatLinks[] = $chatLink; // Добавляем ссылку на чат в массив
                } else {
                    $message .= "Чат с ID $chatId не найден.\n";
                    $chatLinks[] = "Чат с ID $chatId не найден"; // Добавляем сообщение об ошибке
                }
            }

            // Объединяем ссылки на чаты в одну строку с разделителем
            $chatLinksString = implode("\n", $chatLinks);

            // Добавляем данные в массив для Google таблицы
            $googleSheetData[] = [
                $hashtag->hashtag,
                "Тут не было " . $hashtag->hashtag,
                $chatLinksString // Все ссылки на чаты в одной ячейке
            ];

            // Отправляем отчёт в личку
            $telegram->sendMessage([
                'chat_id' => env('TELEGRAM_USER_ADMIN_ID'),
                'text' => $message,
            ]);
            \Log::info('Отправлено сообщение для хэштега: ' . $hashtag->hashtag);
        }

        // Создаем Google таблицу
        $client = new Client();
        $client->setApplicationName(env('GOOGLE_APPLICATION_NAME'));
        $client->setScopes(Sheets::SPREADSHEETS);
        $client->setAuthConfig(env('GOOGLE_SERVICE_ACCOUNT_JSON_LOCATION'));
        $client->setAccessType('offline');

        $service = new Sheets($client);

        $spreadsheetId = config('services.google.sheet_id');
        $startDate = Carbon::parse($startDate)->setTimeFromTimeString($reportTime);
        $endDate = Carbon::parse($currentPeriodEndDate);
        $sheetName = $startDate->format('d.m.Y H:i') . ' - ' . $endDate->format('d.m.Y H:i');

        if (empty($spreadsheetId)) {
            $this->error('GOOGLE_SHEET_ID не задан в .env');
            return;
        }

        // Создаем новый лист
        $batchUpdateRequest = new \Google\Service\Sheets\BatchUpdateSpreadsheetRequest([
            'requests' => [
                'addSheet' => [
                    'properties' => [
                        'title' => $sheetName
                    ]
                ]
            ]
        ]);

        $service->spreadsheets->batchUpdate($spreadsheetId, $batchUpdateRequest);

        // Заполняем лист данными
        $range = $sheetName . '!A1';
        $body = new \Google\Service\Sheets\ValueRange([
            'values' => $googleSheetData
        ]);

        $result = $service->spreadsheets_values->append($spreadsheetId, $range, $body, ['valueInputOption' => 'RAW']);

        // Отправляем ссылку на таблицу
        $spreadsheetUrl = "https://docs.google.com/spreadsheets/d/{$spreadsheetId}/edit#gid=0";
        $telegram->sendMessage([
            'chat_id' => env('TELEGRAM_USER_ADMIN_ID'),
            'text' => "Ссылка на Google таблицу: " . $spreadsheetUrl,
        ]);

        // Обновляем current_period_end_date
        $dayOfWeekNumber = array_search($reportDay, array_map(fn($day) => $day->value, DayOfWeekEnums::getAllDays()));
        if ($dayOfWeekNumber + 1 < 7) {
            $dayOfWeekNumber = 0;
        }
        \Log::info($dayOfWeekNumber);
        $newPeriodEndDate = Carbon::parse($currentPeriodEndDate)
            ->addWeeks($weeksInPeriod)
            ->next($dayOfWeekNumber + 1)
            ->setTimeFromTimeString($reportTime)
            ->subSecond();

        $settings->update([
            'current_period_end_date' => $newPeriodEndDate,
        ]);

        $this->info('Reports sent successfully.');
        \Log::info('Все отчёты успешно отправлены.');
    }
}