<?php

namespace App\Console\Commands;

use Telegram\Bot\Commands\Command;
use Telegram\Bot\Api;
use App\Keyboards;
use App\Services\UserState;
use App\Services\SettingState;
use App\Services\UserDataService;
use App\Models\TelegramUser;
use App\Enums\RoleEnum;
use MoonShine\Laravel\Models\MoonshineUser;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class StartCommand extends Command
{
    protected string $name = 'start';
    protected string $description = 'Введите команду, чтобы начать!';

    public function handle()
    {
        try {
            $telegram = new Api(config('telegram.bot_token'));
            $message = $this->getUpdate()->getMessage();
            $chatId = $message->getChat()->id;
            $userId = $message->from->id;
            $username = $message->from->username;
            $firstName = $message->from?->first_name;
            $lastName = $message->from?->last_name;
            $chatType = $message->getChat()->type;

            $this->resetUserStates($userId);

            if ($chatType === 'private') {
                $user = Cache::remember("telegram_user_{$userId}", 3600, function () use ($userId, $username, $chatId, $firstName, $lastName) {
                    return TelegramUser::firstOrCreate(
                        ['telegram_id' => $userId],
                        [
                            'first_name' => $firstName,
                            'last_name' => $lastName ?? null,
                            'username' => $username ?? null,
                            'role' => $chatId == env('TELEGRAM_USER_ADMIN_ID') ? RoleEnum::SUPER_ADMIN->value : RoleEnum::USER->value,
                            'banned' => false,
                        ]
                    );
                });

                if ($user && $user->banned) {
                    $telegram->sendMessage([
                        'chat_id' => $chatId,
                        'text' => "Ваш аккаунт заблокирован. Обратитесь к администратору для решения данной проблемы.",
                        'reply_markup' => json_encode(['remove_keyboard' => true]),
                    ]);
                    return;
                }

                if ($user->role === RoleEnum::SUPER_ADMIN->value) {
                    $moonshineUser = MoonshineUser::where('telegram_user_id', $user->id)->first();
                    $telegram->sendMessage([
                        'chat_id' => $chatId,
                        'text' => 'Добрый день, рад вас видеть!',
                        'reply_markup' => Keyboards::mainSuperAdminKeyboard(),
                    ]);

                    if (!$moonshineUser) {
                        $sentMessage = $telegram->sendMessage([
                            'chat_id' => $chatId,
                            'text' => "Вы можете привязать свой аккаунт к админ-панели.",
                            'reply_markup' => Keyboards::bindAdminKeyboard($user, $userId),
                        ]);

                        $messageId = $sentMessage->getMessageId();

                        $telegram->editMessageReplyMarkup([
                            'chat_id' => $chatId,
                            'message_id' => $messageId,
                            'reply_markup' => Keyboards::bindAdminKeyboard($user, $userId, $messageId),
                        ]);

                        $telegram->pinChatMessage([
                            'chat_id' => $chatId,
                            'message_id' => $sentMessage->getMessageId(),
                        ]);
                    } else {
                        $sentMessage = $telegram->sendMessage([
                            'chat_id' => $chatId,
                            'text' => "Ссылка на админ-панель: " . env('WEBHOOK_URL'),
                        ]);

                        $telegram->pinChatMessage([
                            'chat_id' => $chatId,
                            'message_id' => $sentMessage->getMessageId(),
                        ]);
                    }
                } else if ($user->role === RoleEnum::ADMIN->value) {
                    $telegram->sendMessage([
                        'chat_id' => $chatId,
                        'text' => 'Добрый день, рад вас видеть!',
                        'reply_markup' => Keyboards::mainAdminKeyboard(),
                    ]);
                } else {
                    $telegram->sendMessage([
                        'chat_id' => $chatId,
                        'text' => "Добрый день. Вы не являетесь администратором, поэтому функционал бота вам не доступен. Свяжитесь с администратором, чтобы поменять роль.",
                        'reply_markup' => json_encode(['remove_keyboard' => true]),
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error("Error in StartCommand: " . $e->getMessage());
        }
    }

    private function resetUserStates(int $userId): void
    {
        UserState::resetState($userId);
        SettingState::clearAll($userId);
        UserDataService::clearData($userId);
    }
}