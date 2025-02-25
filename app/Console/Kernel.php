<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Models\Setting;
use App\Enums\DayOfWeekEnums;
use Carbon\Carbon;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule)
    {
        $settings = Setting::all()->last();

        if (!$settings) {
            \Log::warning('Настройки отсутствуют. Команда reports:send не будет запущена.');
            return;
        }

        $schedule->command('reports:send')
            ->when(function () use ($settings) {
                return Carbon::now()->greaterThanOrEqualTo($settings->current_period_end_date);
            })->hourly();

    }
    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
