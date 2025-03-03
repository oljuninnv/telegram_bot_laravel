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
        if ($messageText === 'cancel_role_change') {
            UserState::setState($userId, 'updateUsers');
            UserDataService::clearData($userId);
            $this->deleteMessage($telegram, $chatId, $messageId);
            $this->sendMessage($telegram, $chatId, 'Вы вернулись в меню настроек пользователей', Keyboards::userSettingsKeyboard());
            return;
        }

        if ($messageText === 'ignore') {
            return;
        }

        if (strpos($messageText, 'page_') === 0) {
            $page = (int) str_replace('page_', '', $messageText);
            $users = TelegramUser::all()->Where('telegram_id','!=',$userId);

            $this->deleteMessage($telegram, $chatId, $messageId);
            $this->sendMessage($telegram, $chatId, 'Выберите пользователя для изменения роли:', Keyboards::DeleteHashTagsInlineKeyboard($users, $page));
            return;
        }

        if (strpos($messageText, 'change_role_') === 0) {
            $userTelegramId = (int) str_replace('change_role_', '', $messageText);
            $userModel = TelegramUser::where('telegram_id', $userTelegramId)->first();  

            if (!$userModel) {
                $this->sendMessage($telegram, $chatId, 'Пользователь не найден.');
                return;
            }

            $this->deleteMessage($telegram, $chatId, $messageId);

            $this->sendMessage($telegram, $chatId, "Выберите роль для @{$userModel->username}?", Keyboards::roleSelectionKeyboard());

            UserDataService::setData($userId, ['telegram_id' => $userTelegramId]);
            return;
        }

        if (strpos($messageText, 'select_role_') === 0) {
            $role = str_replace('select_role_', '', $messageText);

            $this->deleteMessage($telegram, $chatId, $messageId);

            $user = TelegramUser::where('telegram_id',UserDataService::getData($userId))->first();

            $this->sendMessage($telegram, $chatId, "Вы действительно хотите установить роль {$role} для @{$user->username}?", Keyboards::confirmationKeyboard());

            UserDataService::setData($userId, ['role' => $role, 'telegram_id' => $user->telegram_id]);
            
            return;
        }

        
        if ($messageText === 'confirm_yes') {

            $data = UserDataService::getData($userId);
            \Log::info($data);
            $userTelegramId = $data['telegram_id'] ?? null;
            $userRole = $data['role'] ?? null;

            if ($userTelegramId) {
                $userModel = TelegramUser::where('telegram_id', $userTelegramId);

                if ($userModel) {
                    $userModel->update(['role'=>$userRole]);
                    $this->sendMessage($telegram, $userTelegramId,"Ваш уровень доступа был изменён на {$userRole}, нажмите команду /start, чтобы перезапустить бота");

                    $this->deleteMessage($telegram, $chatId, $messageId);

                    $users = TelegramUser::all()->Where('telegram_id','!=', $userId);

                    UserDataService::clearData($userId);
                    $this->sendMessage($telegram, $chatId, 'Пользователь успешно изменён! Выберите следующего пользователя для изменения роли:', Keyboards::userRoleChangeKeyboard($users));
                    return;
                }
            }

            UserDataService::clearData($userId);
            $this->sendMessage($telegram, $chatId, 'Пользователь не найден.', Keyboards::hashtagSettingsKeyboard());
            UserState::setState($userId, 'updateHashtags');
            return;
        }

        if ($messageText === 'confirm_no') {
            $this->deleteMessage($telegram, $chatId, $messageId);

            $users = TelegramUser::all()->Where('telegram_id','!=', $userId);

            UserDataService::clearData($userId);
            $this->sendMessage($telegram, $chatId, 'Выберите пользователя для изменения роли:', Keyboards::userRoleChangeKeyboard($users));
            return;
        }

        $usersSearch = TelegramUser::where('username', 'LIKE', $messageText . '%')->get();
        if($usersSearch->isEmpty()) {
            $users = TelegramUser::all()->where('telegram_id','!=',$chatId);
        
            $telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => 'Схожие username пользователей не были найдены. Если вы хотите выйти из настройки, нажмите кнопку "Отменить удаление"',
                'reply_markup' => Keyboards::userRoleChangeKeyboard($users)
            ]);
        } else {
            $telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => "Ищем схожие username с {$messageText}",
                'reply_markup' => Keyboards::userRoleChangeKeyboard($usersSearch)
            ]);
        }
    }

    private function deleteMessage(Api $telegram, int $chatId, ?int $messageId)
    {
        if ($messageId) {
            $telegram->deleteMessage([
                'chat_id' => $chatId,
                'message_id' => $messageId,
            ]);
        }
    }

    private function sendMessage(Api $telegram, int $chatId, string $text, $replyMarkup = null)
    {
        $telegram->sendMessage([
            'chat_id' => $chatId,
            'text' => $text,
            'reply_markup' => $replyMarkup,
        ]);
    }
}