<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use MoonShine\Laravel\Models\MoonshineUser;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Telegram\Bot\Api;

class AuthController extends Controller
{
    public function guard()
    {
        return Auth::guard('admins');
    }

    public function showBindRegistrationForm()
    {
        return view('auth.bind_register');
    }

    public function bindRegister(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:moonshine_users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $userId = $request->input('user_id');
        $chatId = $request->input('chat_id');
        $messageId = $request->input('message_id');

        $moonshineUser = MoonshineUser::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'telegram_user_id' => $userId,
        ]);
        
        $telegram = new Api(config('telegram.bot_token'));
        $telegram->editMessageText([
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => 'Ваш аккаунт успешно привязан! Перейдите в админ-панель: ' . env('WEBHOOK_URL'),
        ]);

        $telegram->pinChatMessage([
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => 'Ваш аккаунт успешно привязан! Перейдите в админ-панель: ' . env('WEBHOOK_URL'),
        ]);

        return redirect('/account-bound');
    }

    public function showBindAccountForm()
    {
        return view('auth.bind-account');
    }

    public function bindAccount(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email|max:255',
            'password' => 'required|string|min:8',
            'user_id' => 'required|numeric',
            'chat_id' => 'required|numeric',
            'message_id' => 'required|numeric',
        ]);

        $credentials = $request->only('email', 'password');
        $userId = $request->input('user_id');
        $chatId = $request->input('chat_id');
        $messageId = $request->input('message_id');

        if (Auth::guard('admins')->attempt($credentials)) {
            $moonshineUser = MoonshineUser::where('email', $request->email)->first();
            $moonshineUser->telegram_user_id = $userId;
            $moonshineUser->save();

            $telegram = new Api(config('telegram.bot_token'));
            $telegram->editMessageText([
                'chat_id' => $chatId,
                'message_id' => $messageId,
                'text' => 'Ваш аккаунт успешно привязан! Перейдите в админ-панель: ' . env('WEBHOOK_URL'),
            ]);

            $telegram->pinChatMessage([
                'chat_id' => $chatId,
                'message_id' => $messageId,
                'text' => 'Ваш аккаунт успешно привязан! Перейдите в админ-панель: ' . env('WEBHOOK_URL'),
            ]);
            
            return redirect('/account-bound');
        }

        return back()->withErrors([
            'error' => 'Неверные учетные данные. Пожалуйста, проверьте введенные данные и попробуйте снова.',
        ]);
    }
}
