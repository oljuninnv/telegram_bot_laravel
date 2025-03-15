<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Bus\Dispatchable;

class DeleteBlockUserJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle()
    {
        try {
            DB::table('telegram_user')
                ->where('banned', true)
                ->where('updated_at', '<', now()->subDays(14))
                ->delete();

            Log::info("Заблокированные пользователи успешно удалены.");

        } catch (\Exception $e) {
            Log::error('Error in DeleteBlockUserJob: ' . $e->getMessage());
        }
    }
}