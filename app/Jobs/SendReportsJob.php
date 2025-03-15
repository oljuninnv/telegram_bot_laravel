<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Telegram\Bot\Api;
use App\Enums\DayOfWeekEnums;
use App\Enums\RoleEnum;
use Google\Service\Sheets;
use App\Helpers\GoogleHelper;
use Illuminate\Support\Facades\Cache;
use Illuminate\Foundation\Bus\Dispatchable;

class SendReportsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, GoogleHelper;

    public function handle()
    {
        try {
            $settings = DB::table('settings')->orderBy('id', 'desc')->first();
            if (!$settings) {
                Log::warning('Настройки отсутствуют.');
                return;
            }

            $currentPeriodEndDate = Carbon::parse($settings->current_period_end_date);

            // Проверка времени
            if (!Carbon::now()->startOfHour()->greaterThanOrEqualTo($currentPeriodEndDate)) {
                Log::info('Время для отправки отчёта ещё не наступило.');
                return;
            }

            $reportDay = $settings->report_day;
            $reportTime = $settings->report_time;
            $weeksInPeriod = $settings->weeks_in_period;
            $settingsId = $settings->id;

            $startDate = $currentPeriodEndDate->copy()
                ->subWeeks($weeksInPeriod)
                ->setTimeFromTimeString($reportTime);

            $allChats = Cache::remember('all_chats', 3600, function () {
                return DB::table('chats')
                    ->select('id', 'name', 'chat_link')
                    ->get()
                    ->keyBy('id');
            });

            $adminUsers = Cache::remember('admin_users', 3600, function () {
                return DB::table('telegram_user')
                    ->where('role', '!=', RoleEnum::USER->value)
                    ->select('telegram_id')
                    ->get();
            });

            $hashtags = Cache::remember("hashtags_{$settingsId}", 3600, function () use ($settingsId) {
                return DB::table('hashtags')
                    ->join('setting_hashtags', 'hashtags.id', '=', 'setting_hashtags.hashtag_id')
                    ->where('setting_hashtags.setting_id', $settingsId)
                    ->select('hashtags.id', 'hashtags.hashtag')
                    ->get();
            });

            $telegram = new Api(config('telegram.bot_token'));
            $googleSheetData = [['Хэштег', 'Заголовок отчета', 'Ссылки на чаты']];

            foreach ($hashtags as $hashtag) {
                $chatsWithHashtags = DB::table('reports')
                    ->where('hashtag_id', $hashtag->id)
                    ->whereBetween('created_at', [$startDate, $currentPeriodEndDate])
                    ->distinct()
                    ->pluck('chat_id')
                    ->toArray();

                $chatsWithoutHashtags = array_diff($allChats->pluck('id')->toArray(), $chatsWithHashtags);

                $message = "Отчёт за период: {$startDate->format('d.m.Y H:i')} - {$currentPeriodEndDate->format('d.m.Y H:i')}\n";
                $message .= "Хэштег: {$hashtag->hashtag}\n";
                $message .= "Чаты без хэштега:\n";

                $chatLinks = [];
                foreach ($chatsWithoutHashtags as $chatId) {
                    if (isset($allChats[$chatId])) {
                        $chat = $allChats[$chatId];
                        $chatLink = $chat->chat_link ? "{$chat->name} - {$chat->chat_link}" : $chat->name;
                    } else {
                        $chatLink = "Чат с ID $chatId не найден";
                    }
                    $message .= "Чат: {$chatLink}\n";
                    $chatLinks[] = $chatLink;
                }

                $googleSheetData[] = [
                    $hashtag->hashtag,
                    "Тут не было {$hashtag->hashtag}",
                    implode("\n", $chatLinks)
                ];

                foreach ($adminUsers as $user) {
                    $telegram->sendMessage([
                        'chat_id' => $user->telegram_id,
                        'text' => $message,
                    ]);
                }

                unset($chatsWithHashtags, $chatsWithoutHashtags, $message, $chatLinks);
            }

            $client = $this->getGoogleClient();
            $service = new Sheets($client);

            $spreadsheetId = config('services.google.sheet_id');
            if (empty($spreadsheetId)) {
                Log::error('GOOGLE_SHEET_ID не задан в .env');
                return;
            }

            $sheetName = $startDate->format('d.m.Y H:i') . ' - ' . $currentPeriodEndDate->format('d.m.Y H:i');
            $this->getOrCreateSheet($service, $spreadsheetId, $sheetName);
            $this->fillGoogleSheet($service, $spreadsheetId, $sheetName, $googleSheetData);

            $this->updatePeriodEndDate($settings, $currentPeriodEndDate, $weeksInPeriod, $reportDay, $reportTime);

            $spreadsheetUrl = "https://docs.google.com/spreadsheets/d/{$spreadsheetId}/edit#gid=0";
            foreach ($adminUsers as $user) {
                $telegram->sendMessage([
                    'chat_id' => $user->telegram_id,
                    'text' => "Ссылка на Google таблицу: {$spreadsheetUrl}",
                ]);
            }

            Log::info('Отчёт успешно отправлен.');
        } catch (\Exception $e) {
            Log::error('Ошибка в SendReportsJob: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
        }
    }

    protected function updatePeriodEndDate($settings, Carbon $currentPeriodEndDate, int $weeksInPeriod, string $reportDay, string $reportTime): void
    {
        $dayOfWeekNumber = array_search($reportDay, array_map(fn($day) => $day->value, DayOfWeekEnums::getAllDays()));

        $newPeriodEndDate = $currentPeriodEndDate->copy()
            ->addWeeks($weeksInPeriod)
            ->next($dayOfWeekNumber + 1)
            ->setTimeFromTimeString($reportTime)
            ->subSecond();

        DB::table('settings')->where('id', $settings->id)->update([
            'current_period_end_date' => $newPeriodEndDate
        ]);
    }
}