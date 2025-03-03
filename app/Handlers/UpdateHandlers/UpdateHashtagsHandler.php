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
                $this->handleCreateHashtag($telegram, $chatId, $userId);
                break;

            case 'Удалить хэштег':
                $this->handleDeleteHashtag($telegram, $chatId, $userId);
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

    private function handleCreateHashtag(Api $telegram, int $chatId, int $userId)
    {
        $this->sendMessage($telegram, $chatId, "Чтобы создать хэштег, введите хэштег и заголовок через запятую (например, #example, Пример).",Keyboards::backAdminKeyboard());
        UserState::setState($userId, 'createHashtag');
    }

    private function handleDeleteHashtag(Api $telegram, int $chatId, int $userId)
    {
        $hashtags = Hashtag::all();

        if ($hashtags->isEmpty()) {
            $this->sendMessage($telegram, $chatId, 'У вас нет хэштегов. Сначала создайте хэштег.');
            return;
        }

        $this->sendMessage($telegram, $chatId, "Выберите хэштег для удаления:", Keyboards::DeleteHashTagsInlineKeyboard($hashtags));
        UserState::setState($userId, 'deleteHashtag');
    }

    private function handleAttachHashtag(Api $telegram, int $chatId, int $userId)
    {
        $hashtags = Hashtag::all();

        if ($hashtags->isEmpty()) {
            $this->sendMessage($telegram, $chatId, 'У вас нет хэштегов. Сначала создайте хэштег.');
            return;
        }

        // Удаляем текущую клавиатуру, отправляя пустой reply_markup
        $telegram->sendMessage([
            'chat_id' => $chatId,
            'text' => "Чтобы привязать хэштег к настройке, введите хэштег, который хотите привязать.",
            'reply_markup' => json_encode(['remove_keyboard' => true]), // Удаляем клавиатуру
        ]);

        // Отправляем клавиатуру с хэштегами
        $this->sendMessage($telegram, $chatId, "Выберите хэштег для привязки:", Keyboards::HashTagsInlineKeyboard($hashtags));
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