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
use App\Helpers\MessageHelper;
use App\Helpers\HashtagHelper;

class UpdateHashtagsHandler
{
    use MessageHelper,HashtagHelper;
    public function handle(Api $telegram, int $chatId, int $userId, string $messageText, ?int $messageId = null)
    {
        $settings = Setting::all()->last();

        if (!$settings) {
            $this->sendMessage($telegram, $chatId, 'У вас нет настроек. Сначала создайте настройку.');
            return;
        }

        switch ($messageText) {
            case 'Создать хэштег':
                $this->sendMessage($telegram, $chatId, "Чтобы создать хэштег, введите хэштег и заголовок к отчёту через запятую (например, #example, Пример).", Keyboards::backAdminKeyboard());
                UserState::setState($userId, 'createHashtag');
                break;

            case 'Удалить хэштег':
                $hashtags = Hashtag::all();

                if ($hashtags->isEmpty()) {
                    $this->sendMessage($telegram, $chatId, 'У вас нет хэштегов. Сначала создайте хэштег.');
                    return;
                }

                $this->sendMessage($telegram, $chatId, "Выберите хэштег для удаления. Также вы можете ввести название хэштега для его поиска (Пример: #хэштег):", Keyboards::DeleteHashTagsInlineKeyboard($hashtags));
                UserState::setState($userId, 'deleteHashtag');
                break;

            case 'Привязка хэштега':
                $hashtags = Hashtag::all();

                if ($hashtags->isEmpty()) {
                    $this->sendMessage($telegram, $chatId, 'У вас нет хэштегов. Сначала создайте хэштег.');
                    return;
                }

                $this->sendMessage($telegram, $chatId, "Выберите хэштег для привязки. Также вы можете ввести название хэштега для его поиска (Пример: #хэштег):", Keyboards::HashTagsInlineKeyboard($hashtags));
                UserState::setState($userId, 'attachHashtag');
                break;

            case 'Назад':
                UserState::setState($userId, 'settings');
                $telegram->sendMessage([
                    'chat_id' => $chatId,
                    'text' => "Вы вернулись в меню настроек.\n"
                        ."Текущие настройки:\n"
                        . "Дата окончания текущего сбора: {$settings->current_period_end_date}\n"
                        . "День недели: {$settings->report_day}\n"
                        . "Время: {$settings->report_time}\n"
                        . "Период сбора: {$settings->weeks_in_period}\n"
                        . "Все хэштеги в базе данных:\n"
                        . $this->getAllHashtags() . "\n"
                        . "Подключённые хэштеги:\n"
                        . $this->getAttachedHashtags($settings) . "\n",
                    'reply_markup' => Keyboards::updateSettingsKeyboard(),
                ]);
                break;

            default:
                $this->handleDefault($telegram, $chatId, $userId, $messageText, $messageId);
                break;
        }
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
}