<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DeleteBlockUser extends Command
{
    protected $signature = 'delete_ban_user:send';
    protected $description = 'Удаляет заблокированных пользователей срок блокировки, которых больше 2-ух недель';

    public function handle()
    {
        try {
            DB::table('telegram_user')
                ->where('banned', true)
                ->where('updated_at', '<', now()->subDays(14))
                ->delete();

            $this->info("Заблокированные пользователи успешно удалены.");

        } catch (\Exception $e) {
            Log::error('Error in DeleteBlockUser command: ' . $e->getMessage());
            $this->error('Произошла ошибка при удалении пользователей.');
        } 
    }
}