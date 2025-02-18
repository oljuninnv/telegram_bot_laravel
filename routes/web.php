<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Telegram\MessageController;
use Telegram\Bot\Api;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

// Route::post('/telegram-webhook', [MessageController::class, 'index']);
Route::post('/telegram-webhook', [MessageController::class, '__invoke']); //запуск команды

Route::get('/send', function () {
    $telegram = new Api(config('telegram.bot_token'));
    $update = $telegram->getWebhookUpdate();

    $message = $update->getMessage();

    Telegram::sendMessage([
        'chat_id' => '618692024',
        'text' => 'Hello from Laravel!',
    ]);

    return 'Message sent!';
});
