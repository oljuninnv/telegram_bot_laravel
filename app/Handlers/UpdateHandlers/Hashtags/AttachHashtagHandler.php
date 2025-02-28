<?php

namespace App\Handlers\UpdateHandlers\Hashtags;

use Telegram\Bot\Api;
use App\Models\Hashtag;
use App\Models\Setting_Hashtag;
use App\Models\Setting;
use App\Keyboards;
use App\Services\UserState;
use App\Helpers\MessageHelper;

class AttachHashtagHandler
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
            $this->sendMessage($telegram, $chatId, 'Выберите хэштег для привязки:', Keyboards::HashTagsInlineKeyboard($hashtags, $page));
            return;
        }

        if (strpos($messageText, 'attach_') === 0 || strpos($messageText, 'detach_') === 0) {
            $hashtagId = (int) str_replace(['attach_', 'detach_'], '', $messageText);
            $hashtagModel = Hashtag::find($hashtagId);
            $settings = Setting::all()->last();

            if (!$hashtagModel) {
                $this->sendMessage($telegram, $chatId, 'Хэштег не найден.');
                return;
            }

            if (strpos($messageText, 'attach_') === 0) {
                Setting_Hashtag::create([
                    'setting_id' => $settings->id,
                    'hashtag_id' => $hashtagModel->id,
                ]);
                $responseText = 'Хэштег успешно привязан к настройке!';
            } else {
                $settingHashtag = Setting_Hashtag::where('setting_id', $settings->id)
                    ->where('hashtag_id', $hashtagModel->id)
                    ->first();

                if ($settingHashtag) {
                    $settingHashtag->delete();
                    return "Хэштег {$hashtagModel->hashtag} успешно отвязан от настройки!";
                }
                return 'Хэштег не был привязан.';
            }

            $this->deleteMessage($telegram, $chatId, $messageId);
            $this->sendMessage($telegram, $chatId, $responseText . "\nВыберите хэштег для привязки:", Keyboards::HashTagsInlineKeyboard(Hashtag::all()));
            return;
        } else {
            $telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => 'Данный текст не является хэштегом. Если хотите выйти из настройки нажмите кнопку "Закончить настройку"',
            ]);
            return;
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