<?php

namespace App\Handlers\UpdateHandlers\Hashtags;

use Telegram\Bot\Api;
use App\Models\Hashtag;
use App\Services\UserState;
use App\Keyboards;
use Illuminate\Support\Facades\Log;

class CreateHashtagHandler
{
    public function handle(Api $telegram, int $chatId, int $userId, string $messageText)
    {
        if ($messageText === 'Назад'){
            UserState::setState($userId, 'updateHashtags');
            $telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => 'Вы вернулись в меню настроек хэштегов',
                'reply_markup' => Keyboards::hashtagSettingsKeyboard()
            ]);
            return;
        }
        $parts = explode(',', $messageText);

        // Если введено два значения
        if (count($parts) == 2) {
            $hashtag = trim($parts[0]);
            $reportTitle = trim($parts[1]);

            // Проверяем, что хэштег начинается с #
            if (!str_starts_with($hashtag, '#')) {
                $telegram->sendMessage([
                    'chat_id' => $chatId,
                    'text' => 'Хэштег должен начинаться с символа #. Попробуйте ещё раз.',
                ]);
                return;
            }

            // Проверяем, существует ли хэштег
            $existingHashtag = Hashtag::where('hashtag', $hashtag)->first();
            if ($existingHashtag) {
                $telegram->sendMessage([
                    'chat_id' => $chatId,
                    'text' => 'Такой хэштег уже существует.',
                ]);
                return;
            }

            try {
                // Создаем хэштег
                Hashtag::create([
                    'hashtag' => $hashtag,
                    'report_title' => $reportTitle,
                ]);

                // Отправляем сообщение об успешном создании
                $telegram->sendMessage([
                    'chat_id' => $chatId,
                    'text' => 'Хэштег успешно создан!',
                    'reply_markup' => Keyboards::hashtagSettingsKeyboard(),
                ]);

                // Устанавливаем состояние пользователя
                UserState::setState($userId, 'updateHashtags');
            } catch (\Exception $e) {

                $telegram->sendMessage([
                    'chat_id' => $chatId,
                    'text' => 'Произошла ошибка при создании хэштега. Попробуйте ещё раз.',
                ]);
            }
        } else {
            $telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => 'Неверный формат. Введите хэштег и заголовок через запятую (например, #example, Пример).',
            ]);
        }
    }
}