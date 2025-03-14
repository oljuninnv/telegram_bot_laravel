<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('delete_ban_user:send')->hourly();

        try {
            $endDate = Cache::remember('current_period_end_date', 3600, function () {
                return DB::table('settings')
                    ->orderBy('id', 'desc')
                    ->value('current_period_end_date');
            });

            if (!$endDate) {
                Log::warning('Settings not found in Kernel schedule.');
                return;
            }

            $schedule->command('reports:send')
                ->when(function () use ($endDate) {
                    return Carbon::now()->startOfHour()->greaterThanOrEqualTo($endDate);
                })->hourly();
        } catch (\Exception $e) {
            Log::error('Error in Kernel schedule: ' . $e->getMessage());
        }
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