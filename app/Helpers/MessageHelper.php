<?php

namespace App\Helpers;

use Telegram\Bot\Api;

trait MessageHelper
{
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