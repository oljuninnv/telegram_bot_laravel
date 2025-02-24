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

            foreach ($chatsWithoutHashtags as $chatId) {
                // Используем метод find для поиска чата по ID
                $chat = Chat::find($chatId);

                // Проверяем, что чат существует
                if ($chat) {
                    $message .= "Чат: " . $chat->name . " (" . $chat->chat_link . ")\n";
                } else {
                    $message .= "Чат с ID $chatId не найден.\n";
                }
            }

            // Отправляем отчёт в личку
            $telegram->sendMessage([
                'chat_id' => env('TELEGRAM_USER_ADMIN_ID'),
                'text' => $message,
            ]);
            \Log::info('Отправлено сообщение для хэштега: ' . $hashtag->hashtag);
        }

        // Обновляем current_period_end_date
        $dayOfWeekNumber = array_search($reportDay, array_map(fn($day) => $day->value, DayOfWeekEnums::getAllDays()));
        \Log::info($dayOfWeekNumber);
        $newPeriodEndDate = Carbon::parse($currentPeriodEndDate)
            ->addWeeks($weeksInPeriod)
            ->next($dayOfWeekNumber+1)
            ->setTimeFromTimeString($reportTime)
            ->subSecond();

        $settings->update([
            'current_period_end_date' => $newPeriodEndDate,
        ]);

        $this->info('Reports sent successfully.');
        \Log::info('Все отчёты успешно отправлены.');
    }
}