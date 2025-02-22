<?php

namespace App\Handlers\UpdateHandlers\Hashtags;

use Telegram\Bot\Api;
use App\Models\Hashtag;
use App\Services\UserState;
use App\Keyboards;

class CreateHashtagHandler
{
    public function handle(Api $telegram, int $chatId, int $userId, string $messageText)
    {
        $parts = explode(',', $messageText);
        if (count($parts) == 2) {
            $hashtag = trim($parts[0]);
            $reportTitle = trim($parts[1]);

            Hashtag::create([
                'hashtag' => $hashtag,
                'report_title' => $reportTitle,
            ]);

            $telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => 'Хэштег успешно создан!',
                'reply_markup' => Keyboards::hashtagSettingsKeyboard(),
            ]);
            UserState::setState($userId, 'updateHashtags');
        } else {
            $telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => 'Неверный формат. Введите хэштег и заголовок через запятую (например, #example, Пример).',
            ]);
        }
    }
}