<?php

namespace App\Http\Controllers\Telegram;

use App\Http\Controllers\Controller;
use Telegram\Bot\Api;
use App\Models\TelegramUser;
use App\Services\UserState;
use App\Handlers\MainStateHandler;
use App\Handlers\SettingsStateHandler;
use App\Handlers\ChatEventHandler;
use Telegram\Bot\BotsManager;
use App\Handlers\UpdateHandlers\Report_Period\UpdatePeriodHandler;
use App\Handlers\UpdateHandlers\Report_Period\UpdateTimeHandler;
use App\Handlers\UpdateHandlers\Report_Period\UpdateDayOfWeekHandler;
use App\Handlers\UpdateHandlers\UpdateHashtagsHandler;
use App\Handlers\UpdateHandlers\UpdateUserHandler;
use App\Handlers\UpdateHandlers\Users\AddUserHandler;
use App\Handlers\UpdateHandlers\Users\EditUserHandler;
use App\Handlers\UpdateHandlers\Users\BlockUserHandler;
use App\Handlers\UpdateHandlers\Hashtags\AttachHashtagHandler;
use App\Handlers\UpdateHandlers\Hashtags\CreateHashtagHandler;
use App\Handlers\UpdateHandlers\Hashtags\DeleteHashtagHandler;
use App\Enums\RoleEnum;

class MessageController extends Controller
{
    protected BotsManager $botsManager;
    protected ChatEventHandler $chatEventHandler;

    public function __construct(BotsManager $botsManager, ChatEventHandler $chatEventHandler)
    {
        $this->botsManager = $botsManager;
        $this->chatEventHandler = $chatEventHandler;
    }

    public function __invoke()
    {
        $telegram = new Api(config('telegram.bot_token'));
        $update = $telegram->getWebhookUpdate();

        if ($update?->myChatMember?->chat?->type && $update?->myChatMember?->chat?->type != 'private') {
            $chatResponse = $this->chatEventHandler->handle($telegram, $update);
            return $chatResponse ? response($chatResponse, 200) : response(null, 200);
        }

        if (!$update->message && !$update->callback_query) {
            return response(null, 200);
        }

        $chatId = $update->callback_query ? $update->callback_query->message->chat->id : $update?->message?->chat?->id;
        $userId = $update->callback_query ? $update->callback_query->from->id : $update?->message?->from?->id;
        $messageText = $update->callback_query ? $update->callback_query->data : $update?->message?->text;
        $chatType = $update?->message?->chat?->type ?? $update->callback_query->message->chat->type;
        $messageId = $update?->message?->message_id ?? $update->callback_query->message->message_id;

        if ($chatType !== 'private') {
            $chatResponse = $this->chatEventHandler->handle($telegram, $update);
            return $chatResponse ? response($chatResponse, 200) : response(null, 200);
        }

        $this->botsManager->bot()->commandsHandler(true);

        $hasCommand = false;
        if (!empty($update->message->entities)) {
            foreach ($update->message->entities as $entity) {
                if ($entity->type === 'bot_command') {
                    $hasCommand = true;
                    break;
                }
            }
        }

        $user = TelegramUser::where('telegram_id', $chatId)->first();

        if (!$hasCommand && !$user->banned && ($user->role !== RoleEnum::USER->value)) {
            if($user->role === RoleEnum::SUPER_ADMIN->value || $chatId == env('TELEGRAM_USER_ADMIN_ID'))
            {
                $this->handleUserState($telegram, $chatId, $userId, $messageText, $messageId);
            }
            else{
                $handler = new MainStateHandler();
                $handler->handle($telegram, $chatId, $userId, $messageText);
            }
        } else if (!$hasCommand && $user->role === RoleEnum::USER->value && !$user->banned) {
            $telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => 'Вам, как обычному пользователю, не доступен функционал бота. Обратитесь к администратору.',
            ]);
        }
        else if (!$hasCommand && $user->banned){
            $telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => 'Вы, заблокированы. Чтобы снять блокировку, обратитесь к администратору.',
            ]);
        }

        return response(null, 200);
    }

    private function handleUserState(Api $telegram, int $chatId, int $userId, string $messageText, int $messageId)
    {
        $handlers = [
            'main' => MainStateHandler::class,
            'settings' => SettingsStateHandler::class,
            'updatePeriod' => UpdatePeriodHandler::class,
            'updateDayOfWeek' => UpdateDayOfWeekHandler::class,
            'updateHashtags' => UpdateHashtagsHandler::class,
            'updateTime' => UpdateTimeHandler::class,
            'createHashtag' => CreateHashtagHandler::class,
            'deleteHashtag' => DeleteHashtagHandler::class,
            'attachHashtag' => AttachHashtagHandler::class,
            'updateUsers' => UpdateUserHandler::class,
            'addUser' => AddUserHandler::class,
            'editUser' => EditUserHandler::class,
            'blockUser' => BlockUserHandler::class,
        ];

        $currentState = UserState::getState($userId);
        if (isset($handlers[$currentState])) {
            $handler = new $handlers[$currentState]();
            $handler->handle($telegram, $chatId, $userId, $messageText, $messageId);
        }

        return response(null, 200);
    }
}