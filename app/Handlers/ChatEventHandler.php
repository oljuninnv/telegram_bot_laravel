<?php

namespace App\Handlers;

use Telegram\Bot\Api;
use App\Models\Chat;
use App\Models\Hashtag;
use Telegram\Bot\BotsManager;

class ChatEventHandler
{
    public function handle(Api $telegram, $update, BotsManager $botsManager)
    {
        // return response()->json($update?->myChatMember);
        $botsManager->bot()->commandsHandler(true);

        $chatMember = $update?->myChatMember;
        $status = $chatMember?->newChatMember?->status;
        $chatId = $chatMember?->chat?->id;
        $userId = $chatMember?->from?->id;

        // Обработка событий добавления/удаления бота
        if ($chatId && $userId) {
            if ($userId == env('TELEGRAM_USER_ADMIN_ID')) {
                if (in_array($status, ['left', 'kicked'])) {
                    Chat::where('chat_id', $chatId)->delete();
                    return 'Чат удален';
                }

                if (!Chat::where('chat_id', $chatId)->exists()) {
                    Chat::create(['name' => $chatMember->chat->title, 'chat_id' => $chatId]);
                    return 'Чат добавлен';
                }
            } elseif ($status != 'left') {
                $telegram->leaveChat(['chat_id' => $chatId]);
                return 'Бот покинул чат';
            }
        }

        // Обработка сообщений с хэштегами
        $messageText = $update?->message?->text;
        if ($messageText && $hashtag = Hashtag::where('hashtag', $messageText)->first()) {
            $telegram->sendMessage([
                'chat_id' => $update->message->chat->id,
                'text' => 'Сообщение содержит хэштег - ' . $hashtag->hashtag,
            ]);
            return 'Сообщение содержит хэштег - ' . $hashtag->hashtag;
        }

        return null;
    }
}