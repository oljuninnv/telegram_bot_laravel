<?php

namespace App\Handlers\UpdateHandlers;

use Telegram\Bot\Api;
use App\Models\Setting;
use App\Services\UserState;
use App\Keyboards;
use App\Handlers\UpdateHandlers\Hashtags\CreateHashtagHandler;
use App\Handlers\UpdateHandlers\Hashtags\DeleteHashtagHandler;
use App\Handlers\UpdateHandlers\Hashtags\AttachHashtagHandler;
use App\Handlers\UpdateHandlers\Hashtags\UpdateHashtagsSettingHandler;

class UpdateHashtagsHandler
{
    public function handle(Api $telegram, int $chatId, int $userId, string $messageText)
    {
        $settings = Setting::all()->last();

        if (!$settings) {
            $telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => 'У вас нет настроек. Сначала создайте настройку.',
            ]);
            return;
        }

        switch ($messageText) {
            case 'Создать хэштег':
                $telegram->sendMessage([
                    'chat_id' => $chatId,
                    'text' => "Чтобы создать хэштег, введите хэштег и заголовок через запятую (например, #example, Пример).",
                ]);
                UserState::setState($userId, 'createHashtag');
                break;

            case 'Удалить хэштег':
                $telegram->sendMessage([
                    'chat_id' => $chatId,
                    'text' => "Чтобы удалить хэштег, введите хэштег, который хотите удалить.",
                ]);
                UserState::setState($userId, 'deleteHashtag');
                break;

            case 'Привязать хэштег':
                $telegram->sendMessage([
                    'chat_id' => $chatId,
                    'text' => "Чтобы привязать хэштег к настройке, введите хэштег, который хотите привязать.",
                ]);
                UserState::setState($userId, 'attachHashtag');
                break;

            case 'Отвязать хэштег':
                $telegram->sendMessage([
                    'chat_id' => $chatId,
                    'text' => "Чтобы отвязать хэштег из настроек, введите хэштег, который хотите отвязать.",
                ]);
                UserState::setState($userId, 'updateHashtagsSetting');
                break;

            case 'Назад':
                UserState::resetState($userId);
                $telegram->sendMessage([
                    'chat_id' => $chatId,
                    'text' => 'Вы вернулись в главное меню.',
                    'reply_markup' => Keyboards::mainAdminKeyboard(),
                ]);
                break;

            default:
                $currentState = UserState::getState($userId);

                switch ($currentState) {
                    case 'createHashtag':
                        $handler = new CreateHashtagHandler();
                        $handler->handle($telegram, $chatId, $userId, $messageText);
                        break;

                    case 'deleteHashtag':
                        $handler = new DeleteHashtagHandler();
                        $handler->handle($telegram, $chatId, $userId, $messageText);
                        break;

                    case 'attachHashtag':
                        $handler = new AttachHashtagHandler();
                        $handler->handle($telegram, $chatId, $userId, $messageText);
                        break;

                    case 'updateHashtagsSetting':
                        $handler = new UpdateHashtagsSettingHandler();
                        $handler->handle($telegram, $chatId, $userId, $messageText);
                        break;

                    default:
                        $telegram->sendMessage([
                            'chat_id' => $chatId,
                            'text' => 'Неизвестная команда. Пожалуйста, выберите действие из меню.',
                            'reply_markup' => Keyboards::hashtagSettingsKeyboard(),
                        ]);
                        break;
                }
                break;
        }
    }
}