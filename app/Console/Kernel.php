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
        // Получаем настройки из базы данных
        $settings = Setting::all()->last();

        // Если записей нет, используем значения по умолчанию
        if (!$settings){
            $reportDay = DayOfWeekEnums::ПОНЕДЕЛЬНИК->value; // Значение по умолчанию
            $reportTime = '10:00';
            $weeksInPeriod = 1; // Значение по умолчанию
        } else {
            $reportDay = $settings->report_day;
            $reportTime = $settings->report_time;
            $weeksInPeriod = $settings->weeks_in_period;
        }

        // Преобразуем день недели в числовой формат для планировщика
        $dayOfWeek = DayOfWeekEnums::tryFrom($reportDay);

        if (!$dayOfWeek) {
            $dayOfWeek = DayOfWeekEnums::ПОНЕДЕЛЬНИК;
        }

        $dayOfWeekNumber = array_search($dayOfWeek, DayOfWeekEnums::getAllDays());

        // Настраиваем задачу с учетом weeks_in_period
        $schedule->command('reports:send')
            ->weeklyOn($dayOfWeekNumber, $reportTime)
            ->when(function () use ($weeksInPeriod) {
                $currentWeekNumber = Carbon::now()->weekOfYear;
                return ($currentWeekNumber % $weeksInPeriod) === 0;
            });

        // $schedule->command('reports:send')
        // ->everyFifteenSeconds();
        // $schedule->command('reports:send')
        // ->everyMinute();
        // $schedule->command('reports:send');
        \Log::info('Команда reports:send запущена в ' . now());
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
