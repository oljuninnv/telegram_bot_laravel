<?php

namespace App\Handlers\UpdateHandlers\Hashtags;

use Telegram\Bot\Api;
use App\Models\Hashtag;
use App\Models\Setting_Hashtag;
use App\Keyboards;
use App\Services\UserState;

class DeleteHashtagHandler
{
    public function handle(Api $telegram, int $chatId, int $userId, string $messageText)
    {
        $hashtag = trim($messageText);
        $hashtagModel = Hashtag::where('hashtag', $hashtag)->first();
        \Log::info($hashtagModel);
        if ($hashtagModel) {
            Setting_Hashtag::where('hashtag_id', $hashtagModel->id)->delete();
            $hashtagModel->delete();

            $telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => 'Хэштег успешно удалён!',
                'reply_markup' => Keyboards::hashtagSettingsKeyboard(),
            ]);
            UserState::setState($userId, 'updateHashtags');
        } else {
            $telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => 'Хэштег не найден.',
            ]);
        }
    }
}