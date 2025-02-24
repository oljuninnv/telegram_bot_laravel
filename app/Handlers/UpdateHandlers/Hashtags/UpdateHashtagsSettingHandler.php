<?php

namespace App\Handlers\UpdateHandlers\Hashtags;

use Telegram\Bot\Api;
use App\Models\Hashtag;
use App\Models\Setting;
use App\Models\Setting_Hashtag; // Импортируем модель связующей таблицы
use App\Keyboards;
use App\Services\UserState;
use App\Models\Chat;

class UpdateHashtagsSettingHandler
{
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

        // Получаем хэштег, который нужно отвязать
        $hashtagToDetach = trim($messageText);

        // Ищем хэштег в базе данных
        $hashtagModel = Hashtag::where('hashtag', $hashtagToDetach)->first();

        if ($hashtagModel) {
            // Ищем запись в связующей таблице
            $settingHashtag = Setting_Hashtag::where('setting_id', $settings->id)
                ->where('hashtag_id', $hashtagModel->id)
                ->first();

            if ($settingHashtag) {
                $settingHashtag->delete();

                $telegram->sendMessage([
                    'chat_id' => $chatId,
                    'text' => "Хэштег '{$hashtagToDetach}' успешно отвязан от настройки!",
                    'reply_markup' => Keyboards::hashtagSettingsKeyboard(),
                ]);
                UserState::setState($userId, 'updateHashtags');
                $chats = Chat::all();

                // Формируем сообщение об изменении настроек
                $message = "Настройки были обновлены:\n"
                    . "Хэштег {$hashtagToDetach} был отвязан\n";

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
                    'text' => "Хэштег '{$hashtagToDetach}' не привязан к настройке.",
                ]);
            }
        } else {
            $telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => "Хэштег '{$hashtagToDetach}' не найден.",
            ]);
        }
    }
}