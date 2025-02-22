<?php

namespace App\Handlers\UpdateHandlers\Hashtags;

use Telegram\Bot\Api;
use App\Models\Hashtag;
use App\Models\Setting;
use App\Keyboards;
use App\Services\UserState;

class UpdateHashtagsSettingHandler
{
    public function handle(Api $telegram, int $chatId, int $userId, string $messageText)
    {
        $settings = Setting::all()->last();
        $hashtags = explode(',', $messageText);
        $hashtags = array_map('trim', $hashtags);

        $settings->hashtags()->detach();

        foreach ($hashtags as $hashtag) {
            $hashtagModel = Hashtag::firstOrCreate(['hashtag' => $hashtag]);
            $settings->hashtags()->attach($hashtagModel->id);
        }

        $telegram->sendMessage([
            'chat_id' => $chatId,
            'text' => 'Хэштеги успешно обновлены!',
            'reply_markup' => Keyboards::hashtagSettingsKeyboard(),
        ]);
        UserState::setState($userId, 'updateHashtags');
    }
}