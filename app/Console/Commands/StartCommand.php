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
                    if ($user->role === RoleEnum::SUPER_ADMIN->value) {
                        $telegram->sendMessage([
                            'chat_id' => $chatId,
                            'text' => 'Добрый день, рад вас видеть!',
                            'reply_markup' => Keyboards::mainSuperAdminKeyboard(),
                        ]);
                        return;
                    } else if ($user->role === RoleEnum::ADMIN->value) {
                        $telegram->sendMessage([
                            'chat_id' => $chatId,
                            'text' => 'Добрый день, рад вас видеть!',
                            'reply_markup' => Keyboards::mainAdminKeyboard(),
                        ]);
                        return;
                    } else {
                        $response = "Добрый день. Вы не являетесь администратором, поэтому функционал бота вам не доступен. Свяжитесь с администратором, чтобы поменять роль.";
                        $telegram->sendMessage([
                            'chat_id' => $chatId,
                            'text' => $response,
                        ]);
                    }

                }
                if ($userId == env('TELEGRAM_USER_ADMIN_ID')) {
                    $user = TelegramUser::create([
                        'telegram_id' => $userId,
                        'first_name' => $message->from->first_name,
                        'last_name' => $message->from->last_name ?? null,
                        'username' => $message->from->username ?? null,
                        'role' => RoleEnum::SUPER_ADMIN->value,
                        'banned' => false,
                    ]);

                    $response = "Добрый день, рад вас видеть!";
                    $telegram->sendMessage([
                        'chat_id' => $chatId,
                        'text' => $response,
                        'reply_markup' => Keyboards::mainSuperAdminKeyboard(),
                    ]);
                } else {
                    $user = TelegramUser::create([
                        'telegram_id' => $userId,
                        'first_name' => $message->from->first_name,
                        'last_name' => $message->from->last_name ?? null,
                        'username' => $message->from->username ?? null,
                        'role' => RoleEnum::USER->value,
                        'banned' => false,
                    ]);

                    $adminMessage = "Новый пользователь:\n"
                        . "Имя: {$user->first_name}\n"
                        . "Фамилия: {$user->last_name}\n"
                        . "Username: @{$user->username}";

                    $admins = TelegramUser::where('role', RoleEnum::SUPER_ADMIN->value)->get();
                    foreach ($admins as $admin) {
                        $telegram->sendMessage([
                            'chat_id' => $admin->telegram_id,
                            'text' => $adminMessage,
                        ]);
                    }

                    $response = "Добро пожаловать! Вы зарегистрированы как обычный пользователь. Если вам нужен доступ к функционалу бота, свяжитесь с администратором.";
                    $telegram->sendMessage([
                        'chat_id' => $chatId,
                        'text' => $response,
                        'reply_markup' => json_encode(['remove_keyboard' => true]),
                    ]);
                }
            } else {
                if ($user->role != RoleEnum::USER->value) {
                    if ($user->role == RoleEnum::SUPER_ADMIN->value) {
                        $response = "Добрый день, рад вас видеть!";
                        $telegram->sendMessage([
                            'chat_id' => $chatId,
                            'text' => $response,
                            'reply_markup' => Keyboards::mainSuperAdminKeyboard(),
                        ]);
                    } else {
                        $response = "Добрый день, рад вас видеть!";
                        $telegram->sendMessage([
                            'chat_id' => $chatId,
                            'text' => $response,
                            'reply_markup' => Keyboards::mainAdminKeyboard(),
                        ]);
                    }
                } else {
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
}