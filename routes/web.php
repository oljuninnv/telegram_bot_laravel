<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Telegram\MessageController;
use App\Http\Controllers\AuthController;

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
})->name('home');

Route::get('/bind_register', [AuthController::class, 'showBindRegistrationForm'])->name('bind_register');
Route::post('/bind_register', [AuthController::class, 'bindRegister']);

Route::get('/bind_account', [AuthController::class, 'showBindAccountForm'])->name('bind_account');
Route::post('/bind_account', [AuthController::class, 'bindAccount']);

Route::get('/account-bound', function () {
    return view('auth.account-bound');
})->name('account-bound');

Route::post('/telegram-webhook', [MessageController::class, '__invoke']); //запуск команды
