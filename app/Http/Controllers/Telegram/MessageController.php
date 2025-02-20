<?php

namespace App\Http\Controllers\Telegram;

use App\Http\Controllers\Controller;
use Telegram\Bot\Api;
use App\Models\Chat;
use App\Models\Hashtag;
use Telegram\Bot\BotsManager;

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

        // Проверка, является ли данный чат приватным
        $isPrivate = $update?->myChatMember?->chat?->type == 'private';

        // Проверка, был ли бот добавлен в чат и то,что чат не является приватным
        if ($botChatId && !$isPrivate) {
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

        // Проверка на наличие текста в сообщении
        if ($update->message?->text) {
            $messageText = $update->message->text;
            $hashtags = Hashtag::where('hashtag', $messageText)->first();
            if ($hashtags) {
                $telegram->sendMessage([
                    'chat_id' => $update->message->chat->id,
                    'text' => 'Сообщение содержит хэштег - '. $hashtags['hashtag'],
                ]);
                return response('Сообщение содержит хэштеги', 200);
            }

            $isBotCommand = false;

            // Проверка является ли текст командой
            if (!empty($update->message->entities)) {
                foreach ($update->message->entities as $entity) {
                    if ($entity->type === 'bot_command') {
                        $isBotCommand = true;
                        break;
                    }
                }
            }

            if (!$isBotCommand && $update->message->from->id == env('TELEGRAM_USER_ADMIN_ID')){
                switch ($messageText) {
                    case 'Настройка сбора отчетов':
                        $response = 'Вы выбрали настройку сбора отчетов.';
                        break;
                    case 'Проверить отчеты':
                        $response = 'Вы выбрали проверку отчетов.';
                        break;
                    case 'Получить отчеты':
                        $response = 'Вы выбрали получение отчетов.';
                        break;
                    case 'Получить список чатов':
                        $chats = Chat::all();
                        $response = '';
                        foreach ($chats as $chat) {
                            $response .= "\nНазвание: {$chat->name} - ссылка: " . (!empty($chat->chat_link) ? $chat->chat_link : 'отсутствует');
                        }
                        break;
                        case 'Помощь':
                            $response = "Данный бот предназначен для управления отчетами и взаимодействия с чатами. Вот список доступных команд:\n\n" .
                                        "1. **Настройка сбора отчетов** - Позволяет настроить параметры сбора отчетов.\n" .
                                        "2. **Проверить отчеты** - Проверяет текущие отчеты и их статус.\n" .
                                        "3. **Получить отчеты** - Получает и отображает отчеты по заданным параметрам.\n" .
                                        "4. **Получить список чатов** - Выводит список всех доступных чатов с их названиями и ссылками.\n\n" ;
                            break;         
                }
    
                $telegram->sendMessage([
                    'chat_id' => $update->message->chat->id,
                    'text' => $response,
                ]);
            }
        }

        return response(null, 200);
    }
}
