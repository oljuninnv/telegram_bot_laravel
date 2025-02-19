<?php

namespace App\Console\Commands;

use Telegram\Bot\Commands\Command;
use Telegram\Bot\Api;
use App\Keyboards; // Убедитесь, что пространство имен указано правильно

class StartCommand extends Command
{
    protected string $name = 'start';
    protected string $description = 'Введите команду, чтобы начать!';

    public function handle()
    {
        $telegram = new Api(config('telegram.bot_token'));
        $message = $this->getUpdate()->getMessage();
        $chatId = $message->getChat()->id;

        // Проверка, является ли пользователь администратором
        if ($chatId != env('TELEGRAM_USER_ADMIN_ID')) {
            $response = 'Вам не доступен функционал бота';
            $telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => $response,
            ]);
            return;
        }

        // Приветственное сообщение для администратора
        $response = "Добрый день, рад вас видеть";
        $telegram->sendMessage([
            'chat_id' => $chatId,
            'text' => $response,
            'reply_markup' => Keyboards::adminKeyboard() // Добавляем клавиатуру
        ]);
    }
}