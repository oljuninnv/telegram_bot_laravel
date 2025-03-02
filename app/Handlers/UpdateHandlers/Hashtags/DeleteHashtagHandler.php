<?php

namespace App\Handlers\UpdateHandlers\Hashtags;

use Telegram\Bot\Api;
use App\Models\Hashtag;
use App\Models\Setting_Hashtag;
use App\Keyboards;
use App\Services\UserState;
use App\Services\UserDataService; 
use App\Helpers\MessageHelper;

class DeleteHashtagHandler
{
    use MessageHelper;

    public function handle(Api $telegram, int $chatId, int $userId, string $messageText, ?int $messageId = null)
    {
        if ($messageText === 'back_to_settings') {
            UserState::setState($userId, 'updateHashtags');
            $this->deleteMessage($telegram, $chatId, $messageId);
            $this->sendMessage($telegram, $chatId, 'Вы вернулись в меню настроек хэштегов', Keyboards::hashtagSettingsKeyboard());
            return;
        }

        if ($messageText === 'ignore') {
            return;
        }

        if (strpos($messageText, 'page_') === 0) {
            $page = (int) str_replace('page_', '', $messageText);
            $hashtags = Hashtag::all();

            $this->deleteMessage($telegram, $chatId, $messageId);
            $this->sendMessage($telegram, $chatId, 'Выберите хэштег для удаления:', Keyboards::DeleteHashTagsInlineKeyboard($hashtags, $page));
            return;
        }

        if (strpos($messageText, 'delete_') === 0) {
            $hashtagId = (int) str_replace('delete_', '', $messageText);
            $hashtagModel = Hashtag::find($hashtagId);

            if (!$hashtagModel) {
                $this->sendMessage($telegram, $chatId, 'Хэштег не найден.');
                return;
            }

            // Удаляем сообщение с предыдущей клавиатурой
            $this->deleteMessage($telegram, $chatId, $messageId);

            $this->sendMessage($telegram, $chatId, "Вы действительно хотите удалить хэштег {$hashtagModel->hashtag}?", Keyboards::confirmationKeyboard());

            // Сохраняем ID хэштега в UserDataService
            UserDataService::setData($userId, ['hashtag_id' => $hashtagId]);
            return;
        }

        
        if ($messageText === 'confirm_yes') {
    
            $data = UserDataService::getData($userId);
            $hashtagId = $data['hashtag_id'] ?? null;

            if ($hashtagId) {
                $hashtagModel = Hashtag::find($hashtagId);

                if ($hashtagModel) {
                    // Удаляем хэштег
                    Setting_Hashtag::where('hashtag_id', $hashtagModel->id)->delete();
                    $hashtagModel->delete();

                    // Удаляем сообщение с подтверждением
                    $this->deleteMessage($telegram, $chatId, $messageId);

                    // Получаем обновлённый список хэштегов
                    $hashtags = Hashtag::all();

                    if ($hashtags->isEmpty()) {
                        // Если хэштегов больше нет, выходим из режима удаления
                        $this->sendMessage($telegram, $chatId, 'Все хэштеги удалены. Вы вернулись в меню настроек хэштегов.', Keyboards::hashtagSettingsKeyboard());
                        UserState::setState($userId, 'updateHashtags');
                        return;
                    }

                    // Показываем обновлённую клавиатуру с оставшимися хэштегами
                    $this->sendMessage($telegram, $chatId, 'Хэштег успешно удалён! Выберите следующий хэштег для удаления:', Keyboards::DeleteHashTagsInlineKeyboard($hashtags));
                    return;
                }
            }

            // Если хэштег не найден, возвращаемся в меню
            $this->sendMessage($telegram, $chatId, 'Хэштег не найден.', Keyboards::hashtagSettingsKeyboard());
            UserState::setState($userId, 'updateHashtags');
            return;
        }

        if ($messageText === 'confirm_no') {
            // Удаляем сообщение с подтверждением
            $this->deleteMessage($telegram, $chatId, $messageId);

            // Получаем обновлённый список хэштегов
            $hashtags = Hashtag::all();

            // Показываем клавиатуру с хэштегами
            $this->sendMessage($telegram, $chatId, 'Выберите хэштег для удаления:', Keyboards::DeleteHashTagsInlineKeyboard($hashtags));
            return;
        }

        // Если текст не распознан, отправляем сообщение об ошибке
        $telegram->sendMessage([
            'chat_id' => $chatId,
            'text' => 'Данный текст не является хэштегом. Если хотите выйти из настройки, нажмите кнопку "Закончить настройку"',
        ]);
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