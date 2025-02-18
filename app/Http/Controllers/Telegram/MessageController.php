<?php

namespace App\Http\Controllers\Telegram;

use App\Http\Controllers\Controller;
use Telegram\Bot\Api;
use App\Models\Chat;
use Telegram\Bot\BotsManager;

class MessageController extends Controller
{
    protected BotsManager $botsManager;

    public function __construct(BotsManager $botsManager){
        $this->botsManager = $botsManager;
    }

    public function __invoke(){
        $telegram = new Api(config('telegram.bot_token'));
        $this->botsManager->bot()->commandsHandler(true);
        $update = $telegram->getWebhookUpdate();
    
        $botChatId = $update?->myChatMember?->newChatMember?->user?->id;

        $isLeft = $update?->myChatMember?->newChatMember?->status == 'left';
        
        if ($botChatId) {
            $chatId = $update->myChatMember->chat->id;
            $chat_name = $update->myChatMember->chat->title;

           if ($isLeft) {               
                Chat::where('chat_id',$chatId)->delete();
                return response('Чат удален', 200);
            }
            $chatExists = Chat::where('chat_id', $chatId)->exists();
            if (!$chatExists) {
                Chat::create(['name' => $chat_name, 'chat_id' => $chatId]);
                return response('Чат добавлен', 200);
            }
        }

    
        return response(null, 200);
    }

    // public function __invoke(){
    //     $this->botsManager->bot()->commandsHandler(true);
    //     return response(null,200);
    // }
}
