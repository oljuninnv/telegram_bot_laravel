<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\TelegramUser;

class DeleteBlockUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'delete_ban_user:send';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Удаляет заблокированных пользователей срок блокировки, которых больше 2-ух недель';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $users = TelegramUser::where('banned', true)->where('updated_at', '<', now()->subDays(14))->get();

        foreach ($users as $user) {
            $user->delete();
        }

        $this->info('Заблокированные пользователи успешно удалены.');
    }
}
