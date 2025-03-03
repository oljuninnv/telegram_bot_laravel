<?php

namespace App\Helpers;

use Telegram\Bot\Api;
use App\Models\Chat;

trait MessageHelper
{
    /**
     * Отправить сообщение всем чатам.
     *
     * @param Api $telegram
     * @param string $message
     */
    public function sendMessageToAllChats(Api $telegram, string $message)
    {
        $chats = Chat::all();

        foreach ($chats as $chat) {
            try {
                $telegram->getChat(['chat_id' => $chat->chat_id]);
                $telegram->sendMessage([
                    'chat_id' => $chat->chat_id,
                    'text' => $message,
                ]);
            } catch (\Telegram\Bot\Exceptions\TelegramResponseException $e) {
                \Log::error('Ошибка: ' . $e->getMessage());
            }
        }
    }

    /**
     * Отправить сообщение в чат.
     *
     * @param Api $telegram
     * @param int $chatId
     * @param string $text
     * @param mixed $replyMarkup (опционально)
     */
    protected function sendMessage(Api $telegram, int $chatId, string $text, $replyMarkup = null): void
    {
        $params = [
            'chat_id' => $chatId,
            'text' => $text,
        ];

        if ($replyMarkup !== null) {
            $params['reply_markup'] = $replyMarkup;
        }

        $telegram->sendMessage($params);
    }

    protected function deleteMessage(Api $telegram, int $chatId, ?int $messageId): void
    {
        if ($messageId) {
            $telegram->deleteMessage([
                'chat_id' => $chatId,
                'message_id' => $messageId,
            ]);
        }
    }
    
}