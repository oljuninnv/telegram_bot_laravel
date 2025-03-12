<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use MoonShine\Laravel\Models\MoonshineUser;
use MoonShine\Laravel\Models\MoonshineUserRole;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function guard()
    {
        return Auth::guard('admins');
    }
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $values = $request->all();

        if ($this->guard()->attempt(['email' => $values['email'], 'password' => $values['password']])) {
            \Log::info('Привязан');
            return response("Привязан");
        }

        return response("Не привязан.", 401);
    }

    public function showRegistrationForm()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        MoonshineUser::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        return redirect('/');
    }
}
