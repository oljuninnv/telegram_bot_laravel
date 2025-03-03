<?php

namespace App\Handlers\UpdateHandlers\Users;

use Telegram\Bot\Api;
use App\Models\TelegramUser;
use App\Keyboards;
use App\Services\UserState;
use App\Services\UserDataService;
use App\Helpers\MessageHelper;

class BlockUserHandler
{
    use MessageHelper;

    public function handle(Api $telegram, int $chatId, int $userId, string $messageText, ?int $messageId = null)
    {
        if ($messageText === 'exit') {
            UserState::setState($userId, 'updateUsers');
            UserDataService::clearData($userId);
            $this->deleteMessage($telegram, $chatId, $messageId);
            $this->sendMessage($telegram, $chatId, 'Вы вернулись в меню настроек пользователей', Keyboards::userSettingsKeyboard());
            return;
        }

        if ($messageText === 'ignore')
            return;

        if (strpos($messageText, 'page_') === 0) {
            $page = (int) str_replace('page_', '', $messageText);
            $users = TelegramUser::where('telegram_id', '!=', $userId)->get();

            if ($users->isEmpty()) {
                $this->sendMessage($telegram, $chatId, 'Нет пользователей для блокировки.', Keyboards::userSettingsKeyboard());
                return;
            }

            $this->deleteMessage($telegram, $chatId, $messageId);
            $this->sendMessage($telegram, $chatId, 'Выберите пользователя для блокировки:', Keyboards::userBlockKeyboard($users, $page));
            return;
        }

        if (strpos($messageText, 'toggle_block_') === 0) {
            $userTelegramId = (int) str_replace('toggle_block_', '', $messageText);
            $userModel = TelegramUser::where('telegram_id', $userTelegramId)->first();

            if (!$userModel) {
                $this->sendMessage($telegram, $chatId, 'Пользователь не найден.');
                return;
            }

            UserDataService::setData($userId, [
                'telegram_id' => $userTelegramId,
                'username' => $userModel->username,
                'is_banned' => $userModel->banned,
            ]);

            $action = $userModel->banned ? 'разблокировать' : 'заблокировать';
            $this->deleteMessage($telegram, $chatId, $messageId);
            $this->sendMessage($telegram, $chatId, "Вы действительно хотите {$action} пользователя @{$userModel->username}?", Keyboards::confirmationKeyboard());
            return;
        }

        if ($messageText === 'confirm_yes') {
            $data = UserDataService::getData($userId);
            $userTelegramId = $data['telegram_id'] ?? null;
            $username = $data['username'] ?? null;
            $isBanned = $data['is_banned'] ?? false;

            if ($userTelegramId) {
                $userModel = TelegramUser::where('telegram_id', $userTelegramId)->first();

                if ($userModel) {
                    $userModel->update(['banned' => !$isBanned]);
                    $statusMessage = $isBanned ? "Пользователь @{$username} был разблокирован." : "Пользователь @{$username} был заблокирован.";
                    $userMessage = $isBanned ? "Ваш аккаунт был разблокирован администратором." : "Ваш аккаунт был заблокирован администратором. Обратитесь к администратору для решения данной проблемы.";

                    $this->sendMessage($telegram, $userTelegramId, $userMessage);

                    $users = TelegramUser::where('telegram_id', '!=', $userId)->get();

                    if ($users->isEmpty()) {
                        $this->sendMessage($telegram, $chatId, 'Нет пользователей для блокировки.', Keyboards::userSettingsKeyboard());
                        return;
                    }

                    $this->deleteMessage($telegram, $chatId, $messageId);
                    $this->sendMessage($telegram, $chatId, $statusMessage, Keyboards::userBlockKeyboard($users));
                    return;
                }
            }

            $this->sendMessage($telegram, $chatId, 'Пользователь не найден.', Keyboards::userSettingsKeyboard());
            return;
        }

        if ($messageText === 'confirm_no') {
            $users = TelegramUser::where('telegram_id', '!=', $userId)->get();

            $this->deleteMessage($telegram, $chatId, $messageId);
            $this->sendMessage($telegram, $chatId, 'Выберите пользователя для блокировки:', Keyboards::userBlockKeyboard($users));
            return;
        }

        $usersSearch = TelegramUser::where('username', 'LIKE', $messageText . '%')->get();

        if ($usersSearch->isEmpty()) {
            $users = TelegramUser::where('telegram_id', '!=', $chatId)->get();
            $this->sendMessage($telegram, $chatId, 'Схожие username пользователей не были найдены. Если вы хотите выйти из настройки, нажмите кнопку "Отменить блокировку"', Keyboards::userBlockKeyboard($users));
        } else {
            $this->sendMessage($telegram, $chatId, "Ищем схожие username с {$messageText}", Keyboards::userBlockKeyboard($usersSearch));
        }
    }
}