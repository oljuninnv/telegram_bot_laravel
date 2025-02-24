<?php

namespace App\Handlers\UpdateHandlers\Hashtags;

use Telegram\Bot\Api;
use App\Models\Hashtag;
use App\Models\Setting_Hashtag;
use App\Models\Setting;
use App\Keyboards;
use App\Services\UserState;
use App\Models\Chat;

class AttachHashtagHandler
{
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
            $chats = Chat::all();

            // Формируем сообщение об изменении настроек
            $message = "Настройки были обновлены:\n"
                . "Добавлен новый хэштег: {$hashtag}\n";

            foreach ($chats as $chat) {
                try {
                    $telegram->getChat(['chat_id' => $chat->chat_id]); // Проверяем, существует ли чат
                    $telegram->sendMessage([
                        'chat_id' => $chat->chat_id,
                        'text' => $message,
                    ]);
                } catch (\Telegram\Bot\Exceptions\TelegramResponseException $e) {
                    \Log::error('Ошибка: ' . $e->getMessage());
                }
            }
        } else {
            $telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => 'Хэштег не найден.',
            ]);
        }
    }
}