<?php

namespace App\Console\Commands;

use Telegram\Bot\Commands\Command;
use Telegram\Bot\Api;
use App\Keyboards;

class StartCommand extends Command
{
    protected string $name = 'start';
    protected string $description = 'Введите команду, чтобы начать!';

    public function handle()
    {
        $telegram = new Api(config('telegram.bot_token'));
        $message = $this->getUpdate()->getMessage();
        $chatId = $message->getChat()->id;

        if ($chatId == env('TELEGRAM_USER_ADMIN_ID')) {
            $response = "Добрый день, рад вас видеть";
            $telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => $response,
                'reply_markup' => Keyboards::mainAdminKeyboard() // Добавляем клавиатуру
            ]);
        }
        else{
            $response = "Добрый день, рад вас видеть";
            $telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => $response
            ]);
        }

    }
}