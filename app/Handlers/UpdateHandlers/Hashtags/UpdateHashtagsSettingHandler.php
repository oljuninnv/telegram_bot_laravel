<?php

namespace App\Handlers\UpdateHandlers\Hashtags;

use Telegram\Bot\Api;
use App\Models\Hashtag;
use App\Models\Setting;
use App\Models\Setting_Hashtag;
use App\Keyboards;
use App\Services\UserState;
use App\Helpers\MessageHelper;

class UpdateHashtagsSettingHandler
{
    use MessageHelper;
    public function handle(Api $telegram, int $chatId, int $userId, string $messageText)
    {
        // Получаем последнюю настройку
        $settings = Setting::all()->last();

        if (!$settings) {
            $telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => 'У вас нет настроек. Сначала создайте настройку.',
            ]);
            return;
        }

        $hashtagToDetach = trim($messageText);

        $hashtagModel = Hashtag::where('hashtag', $hashtagToDetach)->first();

        if ($hashtagModel) {
            $settingHashtag = Setting_Hashtag::where('setting_id', $settings->id)
                ->where('hashtag_id', $hashtagModel->id)
                ->first();

            if ($settingHashtag) {
                $settingHashtag->delete();

                $telegram->sendMessage([
                    'chat_id' => $chatId,
                    'text' => "Хэштег {$hashtagToDetach} успешно отвязан от настройки!",
                    'reply_markup' => Keyboards::hashtagSettingsKeyboard(),
                ]);
                UserState::setState($userId, 'updateHashtags');
            } else {
                $telegram->sendMessage([
                    'chat_id' => $chatId,
                    'text' => "Хэштег {$hashtagToDetach} не привязан к настройке.",
                ]);
            }
        } else {
            $telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => "Хэштег {$hashtagToDetach} не найден.",
            ]);
        }
    }
}