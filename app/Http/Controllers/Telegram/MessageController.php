<?php

namespace App\Http\Controllers\Telegram;

use App\Http\Controllers\Controller;
use Telegram\Bot\Api;
use App\Services\UserState;
use App\Handlers\MainStateHandler;
use App\Handlers\SettingsStateHandler;
use App\Handlers\CreateSettingHandler;
use App\Handlers\ChatEventHandler;
use Telegram\Bot\BotsManager;
use App\Handlers\UpdateHandlers\UpdatePeriodHandler;
use App\Handlers\UpdateHandlers\UpdateTimeHandler;
use App\Handlers\UpdateHandlers\UpdateDayOfWeekHandler;
use App\Handlers\UpdateHandlers\UpdateHashtagsHandler;
use App\Handlers\UpdateHandlers\Hashtags\AttachHashtagHandler;
use App\Handlers\UpdateHandlers\Hashtags\CreateHashtagHandler;
use App\Handlers\UpdateHandlers\Hashtags\DeleteHashtagHandler;
use App\Handlers\UpdateHandlers\Hashtags\UpdateHashtagsSettingHandler;

class MessageController extends Controller
{
    protected BotsManager $botsManager;

    public function __construct(BotsManager $botsManager)
    {
        $this->botsManager = $botsManager;
    }

    public function __invoke()
    {
        $telegram = new Api(config('telegram.bot_token'));
        $update = $telegram->getWebhookUpdate();

        if ($update->callback_query) {
            $chatId = $update->callback_query->message->chat->id;
            $userId = $update->callback_query->from->id;
            $messageText = $update->callback_query->data;
        } else {
            $chatId = $update?->message?->chat?->id;
            $userId = $update?->message?->from?->id;
            $messageText = $update?->message?->text;
        }

        $chatType = $update?->message?->chat?->type ?? $update->callback_query->message->chat->type;

        if ($chatType == 'private') {
            $mainHandler = new MainStateHandler();
            $settingsHandler = new SettingsStateHandler();
            $createSettingsHandler = new CreateSettingHandler();

            $currentState = UserState::getState($userId);

            if ($messageText && $chatId == env('TELEGRAM_USER_ADMIN_ID')) {
                switch ($currentState) {
                    case 'main':
                        $mainHandler->handle($telegram, $chatId, $userId, $messageText, $this->botsManager);
                        break;

                    case 'settings':
                        $settingsHandler->handle($telegram, $chatId, $userId, $messageText, $this->botsManager);
                        break;

                    case 'createSettings':
                        $createSettingsHandler->handle($telegram, $chatId, $userId, $messageText, $this->botsManager);
                        break;
                    case 'updatePeriod':
                        $handler = new UpdatePeriodHandler();
                        $handler->handle($telegram, $chatId, $userId, $messageText);
                        break;
                    case 'updateDayOfWeek':
                        $handler = new UpdateDayOfWeekHandler();
                        $handler->handle($telegram, $chatId, $userId, $messageText);
                        break;
                    case 'updateHashtags':
                        $handler = new UpdateHashtagsHandler();
                        $handler->handle($telegram, $chatId, $userId, $messageText);
                        break;
                    case 'updateTime':
                        $handler = new UpdateTimeHandler();
                        $handler->handle($telegram, $chatId, $userId, $messageText);
                        break;
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
                }
            }
        } else {
            $chatEventHandler = new ChatEventHandler();
            $chatResponse = $chatEventHandler->handle($telegram, $update, $this->botsManager);
            if ($chatResponse) {
                return response($chatResponse, 200);
            }
        }
        return response(null, 200);
    }
}