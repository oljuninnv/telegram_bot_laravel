<?php

namespace App\Console\Commands;

use Telegram\Bot\Commands\Command;
use Telegram\Bot\Api;
use App\Keyboards;
use App\Services\UserState;
use App\Services\SettingState;

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

        // Очистка кэша пользователя
        UserState::resetState($userId);
        SettingState::clearAll($userId);

        if ($chatId == env('TELEGRAM_USER_ADMIN_ID')) {
            $response = "Добрый день, рад вас видеть";
            $telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => $response,
                'reply_markup' => Keyboards::mainAdminKeyboard()
            ]);
        } else if ($userId == env('TELEGRAM_USER_ADMIN_ID') && $chatId != env('TELEGRAM_USER_ADMIN_ID')) {
            $response = "Чтобы сохранить ссылку на чат, необходимо прописать /add_chat_link {ссылка}";
            $telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => $response,
            ]);
        } else {
            $response = "Добрый день, рад вас видеть";
            $telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => $response
            ]);
        }
    }
}