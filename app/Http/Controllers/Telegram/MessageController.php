<?php

namespace App\Http\Controllers\Telegram;

use App\Http\Controllers\Controller;
use Telegram\Bot\Api;
use App\Models\Chat;
use Telegram\Bot\BotsManager;
use Telegram\Bot\Laravel\Facades\Telegram;
use Telegram\Bot\Keyboard\Keyboard;

class MessageController extends Controller
{
    protected BotsManager $botsManager;

    public function __construct(BotsManager $botsManager)
    {
        $this->botsManager = $botsManager;
    }

    public function __invoke()
    {
        // Инициализация API Telegram с использованием токена бота из конфигурации
        $telegram = new Api(config('telegram.bot_token'));

        // Обработка команд, если они есть
        $this->botsManager->bot()->commandsHandler(true);

        // Получение обновлений из вебхука
        $update = $telegram->getWebhookUpdate();

        // Получение ID бота
        $botChatId = $update?->myChatMember?->newChatMember?->user?->id;

        // Проверка, покинул ли бот чат
        $isLeft = $update?->myChatMember?->newChatMember?->status == 'left';

        // Проверка на наличие текста в сообщении
        if ($update->message?->text) {
            $messageText = $update->message->text;
            // Проверка на наличие хэштегов в сообщении
            if (strpos($messageText, '#митрепорт') !== false || strpos($messageText, '#еженедельныйотчет') !== false) {
                return response('Сообщение содержит хэштеги: #митрепорт или #еженедельныйотчет', 200);
            }
        }

        // Проверка, был ли бот добавлен в чат
        if ($botChatId) {
            // Проверка, является ли пользователь, добавивший бота, администратором
            if ($update->myChatMember->from->id == env('TELEGRAM_USER_ADMIN_ID')) {
                $chatId = $update->myChatMember->chat->id;
                $chat_name = $update->myChatMember->chat->title;

                // Если бот покинул чат, удаляем информацию о чате из базы данных
                if ($isLeft) {
                    Chat::where('chat_id', $chatId)->delete();
                    return response('Чат удален', 200);
                }

                // Проверка, существует ли уже запись о чате в базе данных
                $chatExists = Chat::where('chat_id', $chatId)->exists();
                if (!$chatExists) {
                    // Если чата нет, создаем новую запись
                    Chat::create(['name' => $chat_name, 'chat_id' => $chatId]);
                    return response('Чат добавлен', 200);
                }
                $chatId = $update?->myChatMember?->chat?->id;

            } else {
                // Если пользователь не является администратором и бот ещё не покинул чат, он покидает чат
                if (!$isLeft) {
                    $telegram->leaveChat(['chat_id' => $update->myChatMember->chat->id]);
                    $chatId = null;
                    return response('Бот покинул чат', 200);
                }
            }
        }

        // Возвращает пустой ответ, если ни одно из условий не выполнено
        return response(null, 200);
    }

    // public function __invoke(){
    //     $this->botsManager->bot()->commandsHandler(true);
    //     return response(null,200);
    // }
}
