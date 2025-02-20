<?php

namespace App\Handlers;

use Telegram\Bot\Api;
use App\Models\Chat;
use App\Models\Hashtag;

class ChatEventHandler
{
    public function handle(Api $telegram, $update)
    {
        // Проверка, покинул ли бот чат
        $status = $update?->myChatMember?->newChatMember?->status;

        // Проверка, был ли бот добавлен в чат
        if ($update?->myChatMember?->newChatMember?->user?->id) 
        {
            // Проверка, является ли пользователь, добавивший бота, администратором
            if ($update->myChatMember->from->id == env('TELEGRAM_USER_ADMIN_ID')) {
                $chatId = $update->myChatMember->chat->id;

                // Если бот покинул чат, удаляем информацию о чате из базы данных
                if ($status == 'left' || $status == 'kicked') {
                    Chat::where('chat_id', $chatId)->delete();
                    return 'Чат удален';
                }

                // Проверка, существует ли уже запись о чате в базе данных
                $chatExists = Chat::where('chat_id', $chatId)->exists();
                if (!$chatExists) {
                    Chat::create(['name' => $update->myChatMember->chat->title, 'chat_id' => $chatId]);
                    return 'Чат добавлен';
                }

            } else {
                // Если пользователь не является администратором и бот ещё не покинул чат, он покидает чат
                if ($status != 'left') {
                    $telegram->leaveChat(['chat_id' => $update->myChatMember->chat->id]);
                    return 'Бот покинул чат';
                }
            }
        }

        $messageText = $update?->message?->text;

        if ($messageText) {
            $hashtags = Hashtag::where('hashtag', $messageText)->first();
            if ($hashtags) {
                $telegram->sendMessage([
                    'chat_id' => $update->message->chat->id,
                    'text' => 'Сообщение содержит хэштег - '. $hashtags['hashtag'],
                ]);
                return 'Сообщение содержит хэштег - '. $hashtags['hashtag'];
            }
        }
        return null; 
    }
}