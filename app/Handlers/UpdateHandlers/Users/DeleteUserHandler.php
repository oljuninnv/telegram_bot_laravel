<?php

namespace App\Handlers\UpdateHandlers\Users;

use Telegram\Bot\Api;
use App\Models\TelegramUser;
use App\Keyboards;
use App\Services\UserState;
use App\Services\UserDataService; 
use App\Helpers\MessageHelper;

class DeleteUserHandler
{
    use MessageHelper;

    public function handle(Api $telegram, int $chatId, int $userId, string $messageText, ?int $messageId = null)
    {
        if ($messageText === 'cancel_delete') {
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
            $this->sendMessage($telegram, $chatId, 'Выберите пользователя для удаления:', Keyboards::userDeleteKeyboard($users, $page));
            return;
        }

        if (strpos($messageText, 'delete_user_') === 0) {
            $userTelegramId = (int) str_replace('delete_user_', '', $messageText);
            
            $userModel = TelegramUser::where('telegram_id', $userTelegramId)->first();           

            if (!$userModel) {
                $this->sendMessage($telegram, $chatId, 'Пользователь не найден.');
                return;
            }

            $this->deleteMessage($telegram, $chatId, $messageId);

            $this->sendMessage($telegram, $chatId, "Вы действительно хотите удалить пользователя @{$userModel->username}?", Keyboards::confirmationKeyboard());

            UserDataService::setData($userId, ['telegram_id' => $userTelegramId]);
            return;
        }

        
        if ($messageText === 'confirm_yes') {
            $data = UserDataService::getData($userId);

            $userTelegramId = $data['telegram_id'] ?? null;

            if ($userTelegramId) {
                $userModel = TelegramUser::where('telegram_id', $userTelegramId);

                if ($userModel) {
                    $this->sendMessage($telegram, $userTelegramId,'Вы были удалены из списка пользователей');
                    $userModel->delete();

                    $this->deleteMessage($telegram, $chatId, $messageId);

                    $users = TelegramUser::all()->Where('telegram_id','!=', $userId);

                    if ($users->isEmpty()) {
                        $this->sendMessage($telegram, $chatId, 'Все пользователи удалены. Вы вернулись в меню настроек пользователей.', Keyboards::userSettingsKeyboard());
                        UserDataService::clearData($userId);
                        UserState::setState($userId, 'updateUsers');
                        return;
                    }

                    UserDataService::clearData($userId);
                    $this->sendMessage($telegram, $chatId, 'Пользователь успешно удалён! Выберите следующего пользователя для удаления:', Keyboards::userDeleteKeyboard($users));
                    return;
                }
            }

            $this->sendMessage($telegram, $chatId, 'Пользователь не найден.', Keyboards::userSettingsKeyboard());
            UserState::setState($userId, 'updateUsers');
            UserDataService::clearData($userId);
            return;
        }

        if ($messageText === 'confirm_no') {
            $this->deleteMessage($telegram, $chatId, $messageId);

            $users = TelegramUser::all()->Where('telegram_id','!=', $userId);

            UserDataService::clearData($userId);
            $this->sendMessage($telegram, $chatId, 'Выберите пользователя для удаления:', Keyboards::userDeleteKeyboard($users));
            return;
        }

        $usersSearch = TelegramUser::where('username', 'LIKE', $messageText . '%')->get();
        if($usersSearch->isEmpty()) {
            $users = TelegramUser::all()->where('telegram_id','!=',$chatId);
        
            $telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => 'Схожие username пользователей не были найдены. Если вы хотите выйти из настройки, нажмите кнопку "Отменить удаление"',
                'reply_markup' => Keyboards::userDeleteKeyboard($users)
            ]);
        } else {
            $telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => "Ищем схожие username с {$messageText}",
                'reply_markup' => Keyboards::userDeleteKeyboard($usersSearch)
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