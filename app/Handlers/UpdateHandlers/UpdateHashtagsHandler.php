<?php

namespace App\Handlers\UpdateHandlers;

use App\Models\Hashtag;
use Telegram\Bot\Api;
use App\Models\Setting;
use App\Services\UserState;
use App\Keyboards;
use App\Handlers\UpdateHandlers\Hashtags\CreateHashtagHandler;
use App\Handlers\UpdateHandlers\Hashtags\DeleteHashtagHandler;
use App\Handlers\UpdateHandlers\Hashtags\AttachHashtagHandler;

class UpdateHashtagsHandler
{
    public function handle(Api $telegram, int $chatId, int $userId, string $messageText, ?int $messageId = null)
    {
        $settings = Setting::all()->last();

        if (!$settings) {
            $this->sendMessage($telegram, $chatId, 'У вас нет настроек. Сначала создайте настройку.');
            return;
        }

        switch ($messageText) {
            case 'Создать хэштег':
                $this->sendMessage($telegram, $chatId, "Чтобы создать хэштег, введите хэштег и заголовок через запятую (например, #example, Пример).");
                UserState::setState($userId, 'createHashtag');
                break;

            case 'Удалить хэштег':
                $this->sendMessage($telegram, $chatId, "Чтобы удалить хэштег, введите хэштег, который хотите удалить.");
                UserState::setState($userId, 'deleteHashtag');
                break;

            case 'Привязка хэштега':
                $this->handleAttachHashtag($telegram, $chatId, $userId);
                break;

            case 'Назад':
                UserState::setState($userId, 'settings');
                $this->sendMessage($telegram, $chatId, 'Вы вернулись в меню настроек', Keyboards::updateSettingsKeyboard());
                break;

            default:
                $this->handleDefault($telegram, $chatId, $userId, $messageText, $messageId);
                break;
        }
    }


    private function handleAttachHashtag(Api $telegram, int $chatId, int $userId)
    {
        $hashtags = Hashtag::all();

        if ($hashtags->isEmpty()) {
            $this->sendMessage($telegram, $chatId, 'У вас нет хэштегов. Сначала создайте хэштег.');
            return;
        }

        $this->sendMessage($telegram, $chatId, "Чтобы привязать хэштег к настройке, введите хэштег, который хотите привязать.", Keyboards::HashTagsInlineKeyboard($hashtags));
        UserState::setState($userId, 'attachHashtag');
    }

    private function handleDefault(Api $telegram, int $chatId, int $userId, string $messageText, ?int $messageId)
    {
        $currentState = UserState::getState($userId);

        $handlers = [
            'createHashtag' => CreateHashtagHandler::class,
            'deleteHashtag' => DeleteHashtagHandler::class,
            'attachHashtag' => AttachHashtagHandler::class,
        ];

        if (isset($handlers[$currentState])) {
            $handler = new $handlers[$currentState]();
            $handler->handle($telegram, $chatId, $userId, $messageText, $messageId);
            return;
        }

        $this->sendMessage($telegram, $chatId, 'Неизвестная команда. Пожалуйста, выберите действие из меню.', Keyboards::hashtagSettingsKeyboard());
    }

    private function sendMessage(Api $telegram, int $chatId, string $text, $replyMarkup = null)
    {
        $telegram->sendMessage([
            'chat_id' => $chatId,
            'text' => $text,
            'reply_markup' => $replyMarkup,
        ]);
    }
}