<?php

namespace App\Handlers\UpdateHandlers\Users;

use Telegram\Bot\Api;
use App\Models\TelegramUser;
use App\Keyboards;
use App\Services\UserState;
use App\Services\UserDataService;
use App\Helpers\MessageHelper;

class EditUserHandler
{
    use MessageHelper;

    public function handle(Api $telegram, int $chatId, int $userId, string $messageText, ?int $messageId = null)
    {
        if ($this->handleExit($telegram, $chatId, $userId, $messageText, $messageId))
            return;
        if ($messageText === 'ignore')
            return;

        if (strpos($messageText, 'role_page_') === 0) {
            $this->handlePageChange($telegram, $chatId, $userId, $messageText, $messageId);
            return;
        }

        if (strpos($messageText, 'change_role_') === 0) {
            $this->handleRoleChange($telegram, $chatId, $userId, $messageText, $messageId);
            return;
        }

        if (strpos($messageText, 'select_role_') === 0) {
            $this->handleRoleSelection($telegram, $chatId, $userId, $messageText, $messageId);
            return;
        }

        if ($messageText === 'confirm_yes') {
            $this->handleConfirmYes($telegram, $chatId, $userId, $messageId);
            return;
        }

        if ($messageText === 'confirm_no') {
            $this->handleConfirmNo($telegram, $chatId, $userId, $messageId);
            return;
        }

        $this->handleUserSearch($telegram, $chatId, $userId, $messageText);
    }

    private function handleExit(Api $telegram, int $chatId, int $userId, string $messageText, ?int $messageId): bool
    {
        if ($messageText === 'exit') {
            UserState::setState($userId, 'updateUsers');
            UserDataService::clearData($userId);
            $this->deleteMessage($telegram, $chatId, $messageId);
            $this->sendMessage($telegram, $chatId, 'Вы вернулись в меню настроек пользователей', Keyboards::userSettingsKeyboard());
            return true;
        }
        return false;
    }

    private function handlePageChange(Api $telegram, int $chatId, int $userId, string $messageText, ?int $messageId): void
    {
        $page = (int) str_replace('role_page_', '', $messageText);
        $users = TelegramUser::where('telegram_id', '!=', $userId)->get();

        $this->deleteMessage($telegram, $chatId, $messageId);
        $this->sendMessage($telegram, $chatId, 'Выберите пользователя для изменения роли:', Keyboards::userRoleChangeKeyboard($users, $page));
    }

    private function handleRoleChange(Api $telegram, int $chatId, int $userId, string $messageText, ?int $messageId): void
    {
        $userTelegramId = (int) str_replace('change_role_', '', $messageText);
        $userModel = TelegramUser::where('telegram_id', $userTelegramId)->first();

        if (!$userModel) {
            $this->sendMessage($telegram, $chatId, 'Пользователь не найден.');
            return;
        }

        $this->deleteMessage($telegram, $chatId, $messageId);
        $this->sendMessage($telegram, $chatId, "Выберите роль для @{$userModel->username}?", Keyboards::roleSelectionKeyboard());

        UserDataService::setData($userId, ['telegram_id' => $userTelegramId]);
    }

    private function handleRoleSelection(Api $telegram, int $chatId, int $userId, string $messageText, ?int $messageId): void
    {
        $role = str_replace('select_role_', '', $messageText);
        $user = TelegramUser::where('telegram_id', UserDataService::getData($userId))->first();

        $this->deleteMessage($telegram, $chatId, $messageId);
        $this->sendMessage($telegram, $chatId, "Вы действительно хотите установить роль {$role} для @{$user->username}?", Keyboards::confirmationKeyboard());

        UserDataService::setData($userId, ['role' => $role, 'telegram_id' => $user->telegram_id]);
    }

    private function handleConfirmYes(Api $telegram, int $chatId, int $userId, ?int $messageId): void
    {
        $data = UserDataService::getData($userId);
        $userTelegramId = $data['telegram_id'] ?? null;
        $userRole = $data['role'] ?? null;

        if ($userTelegramId) {
            $userModel = TelegramUser::where('telegram_id', $userTelegramId)->first();

            if ($userModel) {
                $userModel->update(['role' => $userRole]);
                $this->sendMessage($telegram, $userTelegramId, "Ваш уровень доступа был изменён на {$userRole}, нажмите команду /start, чтобы перезапустить бота");

                $this->deleteMessage($telegram, $chatId, $messageId);

                $users = TelegramUser::where('telegram_id', '!=', $userId)->get();
                UserDataService::clearData($userId);

                $this->sendMessage($telegram, $chatId, 'Пользователь успешно изменён! Выберите следующего пользователя для изменения роли:', Keyboards::userRoleChangeKeyboard($users));
                return;
            }
        }

        UserDataService::clearData($userId);
        $this->sendMessage($telegram, $chatId, 'Пользователь не найден.', Keyboards::hashtagSettingsKeyboard());
        UserState::setState($userId, 'updateHashtags');
    }

    private function handleConfirmNo(Api $telegram, int $chatId, int $userId, ?int $messageId): void
    {
        $this->deleteMessage($telegram, $chatId, $messageId);

        $users = TelegramUser::where('telegram_id', '!=', $userId)->get();
        UserDataService::clearData($userId);

        $this->sendMessage($telegram, $chatId, 'Выберите пользователя для изменения роли:', Keyboards::userRoleChangeKeyboard($users));
    }

    private function handleUserSearch(Api $telegram, int $chatId, int $userId, string $messageText): void
    {
        $usersSearch = TelegramUser::where('username', 'LIKE', $messageText . '%')->get();

        if ($usersSearch->isEmpty()) {
            $users = TelegramUser::where('telegram_id', '!=', $chatId)->get();
            $this->sendMessage($telegram, $chatId, 'Схожие username пользователей не были найдены. Если вы хотите выйти из настройки, нажмите кнопку "Отменить удаление"', Keyboards::userRoleChangeKeyboard($users));
        } else {
            $this->sendMessage($telegram, $chatId, "Ищем схожие username с {$messageText}", Keyboards::userRoleChangeKeyboard($usersSearch));
        }
    }
}