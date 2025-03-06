<?php

namespace App\Handlers\UpdateHandlers\Users;

use Telegram\Bot\Api;
use App\Models\TelegramUser;
use App\Keyboards;
use App\Services\UserState;
use App\Services\UserDataService;
use App\Helpers\MessageHelper;

class AddUserHandler
{
    use MessageHelper;

    public function handle(Api $telegram, int $chatId, int $userId, string $messageText, ?int $messageId = null)
    {
        if ($messageText === 'Назад' || $messageText === 'exit') {
            UserState::setState($userId, 'updateUsers');
            $this->sendMessage($telegram, $chatId, 'Вы вернулись в меню настроек пользователя', Keyboards::userSettingsKeyboard());
            return;
        }

        if ($messageText === 'ignore')
            return;

        if (strpos($messageText, 'change_role_') === 0) {
            $this->deleteMessage($telegram, $chatId, $messageId);

            $data = UserDataService::getData($userId);
            $username = $data['username'] ?? null;
            if ($username) {
                $this->sendMessage($telegram, $chatId, "Выберите роль для @{$username}?", Keyboards::roleSelectionKeyboard());
            } else {
                $this->sendMessage($telegram, $chatId, 'Пользователь не найден.');
                return;
            }
        }

        if (strpos($messageText, 'select_role_') === 0) {
            $role = str_replace('select_role_', '', $messageText);

            $data = UserDataService::getData($userId);
            $username = $data['username'] ?? null;

            $this->deleteMessage($telegram, $chatId, $messageId);
            $this->sendMessage($telegram, $chatId, "Вы действительно хотите установить роль {$role} для @{$username}?", Keyboards::confirmationKeyboard());

            UserDataService::setData($userId, ['role' => $role, 'username' => $username]);
            return;
        }

        if ($messageText === 'confirm_yes') {
            $data = UserDataService::getData($userId);
            $username = $data['username'] ?? null;
            $userRole = $data['role'] ?? null;

            TelegramUser::create(['username' => $username, 'role' => $userRole]);
            $this->deleteMessage($telegram, $chatId, $messageId);

            $this->sendMessage($telegram, $chatId, "Пользователь @{$username} был добавлен с ролью {$userRole}.", Keyboards::userSettingsKeyboard());

            UserState::setState($userId, 'updateUsers');
            return;
        }

        if ($messageText === 'confirm_no') {
            $this->deleteMessage($telegram, $chatId, $messageId);

            UserDataService::clearData($userId);

            $this->sendMessage($telegram, $chatId, 'Хорошо, вы переходите в меню настроек пользователя.', Keyboards::userSettingsKeyboard());
            UserState::setState($userId, 'updateUsers');
            return;
        }


        $usernameMessageText = ltrim($messageText, '@');

        $usersSearch = TelegramUser::where('username', 'LIKE', $usernameMessageText)
            ->get();

        if ($usersSearch->isEmpty()) {
            $this->sendMessage($telegram, $chatId, 'Username нового пользователя был добавлен, теперь определите его роль', Keyboards::roleSelectionKeyboard());
            UserDataService::setData($userId, ['username' => $usernameMessageText]);
        } else {
            $this->sendMessage($telegram, $chatId, "Пользователь с таким username уже существует. Если вы хотите выйти в меню 'Настройки пользователей' нажмите на кнопку назад", Keyboards::backAdminKeyboard());
        }
    }

}