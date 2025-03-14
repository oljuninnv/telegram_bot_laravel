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
                            <h2 class="text-2xl font-bold mb-6 text-center">Привязка аккаунта</h2>
                            @if ($errors->any())
                                <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
                                    <ul>
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                            <form method="POST" action="{{ route('bind_account') }}">
                                @csrf
                                <input type="hidden" name="user_id" value="{{ request()->query('user_id') }}">
                                <input type="hidden" name="chat_id" value="{{ request()->query('chat_id') }}">
                                <input type="hidden" name="message_id" value="{{ request()->query('message_id') }}">
                                <div class="mb-4">
                                    <label for="email" class="block text-sm font-medium text-gray-700">E-Mail</label>
                                    <input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="email" autofocus
                                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                </div>
                                <div class="mb-4">
                                    <label for="password" class="block text-sm font-medium text-gray-700">Пароль</label>
                                    <input id="password" type="password" name="password" required autocomplete="current-password"
                                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                </div>
                                <div class="flex items-center justify-between">
                                    <button type="submit" class="w-full py-2 px-4">
                                        Привязать аккаунт
                                    </button>
                                </div>
                            </form>
                            <div class="mt-4 text-center">
                                <p>Нет аккаунта? <a href="{{ route('bind_register', ['user_id' => request()->query('user_id'), 'chat_id' => request()->query('chat_id'), 'message_id' => request()->query('message_id')]) }}" class="text-indigo-600 hover:text-indigo-500">Зарегистрируйтесь</a></p>
                            </div>
                        </div>
                    </div>
                </x-moonshine::layout.div>
            </x-moonshine::layout.wrapper>
        </x-moonshine::layout.body>
    </x-moonshine::layout.html>
</x-moonshine::layout>