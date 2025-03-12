<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Setting;
use App\Models\Chat;
use App\Models\Hashtag;
use App\Models\Report;
use Carbon\Carbon;
use Telegram\Bot\Api;
use App\Enums\DayOfWeekEnums;
use App\Models\TelegramUser;
use App\Enums\RoleEnum;
use Google\Service\Sheets;
use App\Helpers\GoogleHelper;

class SendReports extends Command
{
    use GoogleHelper;
    protected $signature = 'reports:send';
    protected $description = 'Отправляет данные об отчётах за текущий период';

    public function handle()
    {
        $telegram = new Api(config('telegram.bot_token'));

        // Получаем последние настройки
        $settings = Setting::latest()->first();

        if (!$settings) {
            $this->error('Настройки отсутствуют.');
            return;
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

            $users = TelegramUser::all()->where('role', '!=', RoleEnum::USER->value);
            foreach ($users as $user) {
                $telegram->sendMessage([
                    'chat_id' => $user->telegram_id,
                    'text' => $message,
                ]);
            }
        }

        // Создаем Google таблицу
        $client = $this->getGoogleClient();
        $service = new Sheets($client);

        $spreadsheetId = config('services.google.sheet_id');
        if (empty($spreadsheetId)) {
            $this->error('GOOGLE_SHEET_ID не задан в .env');
            return;
        }

        $sheetName = $startDate->format('d.m.Y H:i') . ' - ' . $currentPeriodEndDate->format('d.m.Y H:i');

        // Проверяем, существует ли лист с таким названием
        $sheetExists = $this->checkIfSheetExists($service, $spreadsheetId, $sheetName);
        if ($sheetExists) {
            $this->clearSheet($service, $spreadsheetId, $sheetName);
            $this->fillGoogleSheet($service, $spreadsheetId, $sheetName, $googleSheetData);
        } else {
            $this->createGoogleSheet($service, $spreadsheetId, $sheetName);
            $this->fillGoogleSheet($service, $spreadsheetId, $sheetName, $googleSheetData);
        }

        $this->updatePeriodEndDate($settings, $currentPeriodEndDate, $weeksInPeriod, $reportDay, $reportTime);

        $spreadsheetUrl = "https://docs.google.com/spreadsheets/d/{$spreadsheetId}/edit#gid=0";
        $users = TelegramUser::all()->where('role', '!=', RoleEnum::USER->value);
        foreach ($users as $user) {
            $telegram->sendMessage([
                'chat_id' => $user->telegram_id,
                'text' => "Ссылка на Google таблицу: {$spreadsheetUrl}",
            ]);
        }

        $this->info('Отчёт успешно отправлен.');
    }

    /**
     * Обновляет current_period_end_date в настройках.
     */
    protected function updatePeriodEndDate(Setting $settings, Carbon $currentPeriodEndDate, int $weeksInPeriod, string $reportDay, string $reportTime): void
    {
        $dayOfWeekNumber = array_search($reportDay, array_map(fn($day) => $day->value, DayOfWeekEnums::getAllDays()));
        if ($dayOfWeekNumber + 1 < 7) {
            $dayOfWeekNumber = 0;
        }

        $newPeriodEndDate = $currentPeriodEndDate->copy()
            ->addWeeks($weeksInPeriod)
            ->next($dayOfWeekNumber + 1)
            ->setTimeFromTimeString($reportTime)
            ->subSecond();

        $settings->update(['current_period_end_date' => $newPeriodEndDate]);
    }
}