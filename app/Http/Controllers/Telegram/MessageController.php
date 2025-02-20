<?php

namespace App\Http\Controllers\Telegram;

use App\Http\Controllers\Controller;
use Telegram\Bot\Api;
use App\Services\UserState;
use App\Handlers\MainStateHandler;
use App\Handlers\SettingsStateHandler;
use App\Handlers\ChatEventHandler;
use Telegram\Bot\BotsManager;

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

        $chatId = $update?->message?->chat?->id;

        $chatType = $update?->message?->chat?->type;

        if ($chatType == 'private') {
            $mainHandler = new MainStateHandler();
            $settingsHandler = new SettingsStateHandler();

            $userId = $update->message->from->id;

            $currentState = UserState::getState($userId);

            $messageText = $update?->message?->text;

            if ($messageText && $chatId == env('TELEGRAM_USER_ADMIN_ID')) {
                switch ($currentState) {
                    case 'main':
                        $mainHandler->handle($telegram, $chatId, $userId, $messageText, $this->botsManager);
                        break;

                    case 'settings':
                        $settingsHandler->handle($telegram, $chatId, $userId, $messageText, $this->botsManager);
                        break;
                }
            }
        } else {
            $chatEventHandler = new ChatEventHandler();

            $chatResponse = $chatEventHandler->handle($telegram, $update);
            if ($chatResponse) {
                return response($chatResponse, 200);
            }
        }
        return response(null, 200);
    }
}