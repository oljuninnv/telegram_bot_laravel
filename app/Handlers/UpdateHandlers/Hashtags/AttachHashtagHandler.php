<?php

namespace App\Handlers\UpdateHandlers\Hashtags;

use Telegram\Bot\Api;
use App\Models\Hashtag;
use App\Models\Setting_Hashtag;
use App\Models\Setting;
use App\Keyboards;
use App\Services\UserState;
use App\Helpers\MessageHelper;

class AttachHashtagHandler
{
    use MessageHelper;
    public function handle(Api $telegram, int $chatId, int $userId, string $messageText)
    {
        $hashtag = trim($messageText);
        $hashtagModel = Hashtag::where('hashtag', $hashtag)->first();
        $settings = Setting::all()->last();

        if ($hashtagModel) {
            Setting_Hashtag::create([
                'setting_id' => $settings->id,
                'hashtag_id' => $hashtagModel->id,
            ]);

            $telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => 'Хэштег успешно привязан к настройке!',
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