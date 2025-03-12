<x-moonshine::layout>
    <x-moonshine::layout.html :with-alpine-js="true" :with-themes="false">
        <x-moonshine::layout.head>
            <x-moonshine::layout.meta name="csrf-token" :content="csrf_token()"/>
            <x-moonshine::layout.favicon />
            <x-moonshine::layout.assets>
                @vite([
                    'resources/css/main.css',
                    'resources/js/app.js',
                ], 'vendor/moonshine')
            </x-moonshine::layout.assets>
        </x-moonshine::layout.head>
        <x-moonshine::layout.body>
            <x-moonshine::layout.wrapper>
                <x-moonshine::layout.div class="layout-page">
                    <div class="container mx-auto px-4 py-8">
                        <div class="max-w-md mx-auto bg-white shadow-lg rounded-lg p-6">
                            <h2 class="text-2xl font-bold mb-6 text-center">Регистрация</h2>
                            <form method="POST" action="{{ route('bind_register') }}">
                                @csrf
                                <input type="hidden" name="user_id" value="{{ request()->query('user_id') }}">
                                <input type="hidden" name="chat_id" value="{{ request()->query('chat_id') }}">
                                <div class="mb-4">
                                    <label for="name" class="block text-sm font-medium text-gray-700">Имя</label>
                                    <input id="name" type="text" name="name" value="{{ old('name') }}" required autocomplete="name" autofocus
                                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    @error('name')
                                        <span class="text-red-500 text-sm">{{ $message }}</span>
                                    @enderror
                                </div>
                                <div class="mb-4">
                                    <label for="email" class="block text-sm font-medium text-gray-700">E-Mail</label>
                                    <input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="email"
                                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    @error('email')
                                        <span class="text-red-500 text-sm">{{ $message }}</span>
                                    @enderror
                                </div>
                                <div class="mb-4">
                                    <label for="password" class="block text-sm font-medium text-gray-700">Пароль</label>
                                    <input id="password" type="password" name="password" required autocomplete="new-password"
                                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    @error('password')
                                        <span class="text-red-500 text-sm">{{ $message }}</span>
                                    @enderror
                                </div>
                                <div class="mb-4">
                                    <label for="password-confirm" class="block text-sm font-medium text-gray-700">Подтвердите пароль</label>
                                    <input id="password-confirm" type="password" name="password_confirmation" required autocomplete="new-password"
                                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                </div>
                                <div class="flex items-center justify-between">
                                    <button type="submit" class="w-full text-black py-2 px-4">
                                        Зарегистрироваться
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </x-moonshine::layout.div>
            </x-moonshine::layout.wrapper>
        </x-moonshine::layout.body>
    </x-moonshine::layout.html>
</x-moonshine::layout>
