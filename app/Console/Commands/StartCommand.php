<?php

namespace App\Console\Commands;

use Telegram\Bot\Commands\Command;
use Telegram\Bot\Api;
use App\Keyboards;
use App\Services\UserState;
use App\Services\SettingState;
use App\Services\UserDataService;
use App\Models\TelegramUser;
use App\Enums\RoleEnum;
use Illuminate\Support\Facades\Log;

class StartCommand extends Command
{
    protected string $name = 'start';
    protected string $description = 'Введите команду, чтобы начать!';

    public function handle()
    {
        $telegram = new Api(config('telegram.bot_token'));
        $message = $this->getUpdate()->getMessage();
        $chatId = $message->getChat()->id;
        $userId = $message->from->id;
        $chatType = $message->getChat()->type;

        UserState::resetState($userId);
        SettingState::clearAll($userId);
        UserDataService::clearData($userId);

        if ($chatType === 'private') {
            $user = TelegramUser::where('telegram_id', $userId)->first();

            if (!$user) {
                $user = TelegramUser::create([
                    'telegram_id' => $userId,
                    'first_name' => $message->from->first_name,
                    'last_name' => $message->from->last_name ?? null,
                    'username' => $message->from->username ?? null,
                    'role' => RoleEnum::USER->value,
                ]);

                $adminChatId = env('TELEGRAM_USER_ADMIN_ID');
                if ($adminChatId) {
                    $adminMessage = "Новый пользователь:\n"
                        . "Имя: {$user->first_name}\n"
                        . "Фамилия: {$user->last_name}\n"
                        . "Username: @{$user->username}";

                    $telegram->sendMessage([
                        'chat_id' => $adminChatId,
                        'text' => $adminMessage,
                    ]);
                }

            }
        }

        if ($chatId == env('TELEGRAM_USER_ADMIN_ID')) {
            $response = "Добрый день, рад вас видеть";
            $telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => $response,
                'reply_markup' => Keyboards::mainAdminKeyboard(),
            ]);
        } else {
            $response = "Добрый день. Вы не являетесь администратором, поэтому функционал бота вам не доступен. Свяжитесь с администратором, чтобы поменять роль.";
            $telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => $response,
            ]);
        }
    }
}