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

class StartCommand extends Command
{
    protected string $name = 'start';
    protected string $description = 'Введите команду, чтобы начать!';

    public function handle()
    {
        $telegram = new Api(config('telegram.bot_token'));
        $message = $this->getUpdate()->getMessage();
        $chatId = $message->getChat()->id;
        $userId = $message->from->id;
        $username = $message->from->username;
        $firstName = $message->from?->first_name;
        $lastName = $message->from?->last_name;
        $chatType = $message->getChat()->type;

        UserState::resetState($userId);
        SettingState::clearAll($userId);
        UserDataService::clearData($userId);

        if ($chatType === 'private') {
            $user = TelegramUser::where('telegram_id', $userId)->first();

            if ($user && $user->banned) {
                $response = "Ваш аккаунт заблокирован. Обратитесь к администратору для решения данной проблемы.";
                $telegram->sendMessage([
                    'chat_id' => $chatId,
                    'text' => $response,
                    'reply_markup' => json_encode(['remove_keyboard' => true]),
                ]);
                return;
            }

            if (!$user) {
                $user = TelegramUser::where('username', $username)->first();
                if ($user) {
                    $user->update(['telegram_id' => $chatId, 'first_name' => $firstName, 'last_name' => $lastName]);
                } else {
                    if($chatId == env('TELEGRAM_USER_ADMIN_ID'))
                    {
                        $user = TelegramUser::create([
                            'telegram_id' => $userId,
                            'first_name' => $firstName,
                            'last_name' => $lastName ?? null,
                            'username' => $username ?? null,
                            'role' => RoleEnum::SUPER_ADMIN->value,
                            'banned' => false,
                        ]);
                    } 
                    else{
                        $user = TelegramUser::create([
                            'telegram_id' => $userId,
                            'first_name' => $firstName,
                            'last_name' => $lastName ?? null,
                            'username' => $username ?? null,
                            'role' => RoleEnum::USER->value,
                            'banned' => false,
                        ]);
                    }                 
                }
            }
            if ($user->role === RoleEnum::SUPER_ADMIN->value) {
                $moonshineUser = MoonshineUser::where('telegram_user_id', $user->id)->first();
                $telegram->sendMessage([
                    'chat_id' => $chatId,
                    'text' => 'Добрый день, рад вас видеть!',
                    'reply_markup' => Keyboards::mainSuperAdminKeyboard(),
                ]);
                if (!$moonshineUser) {
                    $response = "Вы можете привязать свой аккаунт к админ-панели.";
                
                    $sentMessage = $telegram->sendMessage([
                        'chat_id' => $chatId,
                        'text' => $response,
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
                        'message_id' => $messageId,
                    ]);
                }else {
                    $response = "Ссылка на админ-панель: " . env('WEBHOOK_URL');
                
                    $sentMessage = $telegram->sendMessage([
                        'chat_id' => $chatId,
                        'text' => $response,
                    ]);
                
                    $messageId = $sentMessage->getMessageId();
                
                    $telegram->pinChatMessage([
                        'chat_id' => $chatId,
                        'message_id' => $messageId,
                    ]);
                }
            } else if ($user->role === RoleEnum::ADMIN->value) {
                $telegram->sendMessage([
                    'chat_id' => $chatId,
                    'text' => 'Добрый день, рад вас видеть!',
                    'reply_markup' => Keyboards::mainAdminKeyboard(),
                ]);
                return;
            }
            else {
                $response = "Добрый день. Вы не являетесь администратором, поэтому функционал бота вам не доступен. Свяжитесь с администратором, чтобы поменять роль.";
                $telegram->sendMessage([
                    'chat_id' => $chatId,
                    'text' => $response,
                    'reply_markup' => json_encode(['remove_keyboard' => true]),
                ]);
            }
        } 
    }
}