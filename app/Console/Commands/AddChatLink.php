<?php

namespace App\Console\Commands;

use Telegram\Bot\Commands\Command;
use Telegram\Bot\Api;
use App\Models\Chat;

class AddChatLink extends Command
{
    protected string $name = 'add_chat_link';
    protected string $pattern = '{link}';
    protected string $description = 'Добавить пригласительную ссылку в базу данных';

    public function handle()
    {
        $telegram = new Api(config('telegram.bot_token'));
        $fromId = $this->getUpdate()->message->from->id;

        // Проверяем, является ли пользователь администратором
        if ($fromId != env('TELEGRAM_USER_ADMIN_ID')) {
            $response = 'Вам не доступен функционал этой команды';
            $telegram->sendMessage([
                'chat_id' => $this->getUpdate()->message->chat->id,
                'text' => $response,
            ]);
            return;
        }

        if ($this->getUpdate()->message->chat->type == 'chat') 
        {

        // Получаем пригласительную ссылку из сообщения
        $inviteLink = $this->argument('link', 'error');

        // Сохраняем ссылку в базу данных
        Chat::where('chat_id', $this->getUpdate()->message->chat->id)->update([
            'chat_link' => $inviteLink,
        ]);

        $response = "Пригласительная ссылка успешно добавлена в базу данных.";
        $telegram->sendMessage([
            'chat_id' => $this->getUpdate()->message->chat->id,
            'text' => $response,
        ]);
    }else{
        $telegram->sendMessage([
            'chat_id' => $this->getUpdate()->message->chat->id,
            'text' => "Вы не можете в личном сообщении менять ссылку на чат",
        ]);
    }
}

}