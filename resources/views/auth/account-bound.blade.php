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
                            <h2 class="text-2xl font-bold mb-6 text-center">Аккаунт успешно привязан!</h2>
                            <div class="flex items-center justify-between">
                                <x-moonshine::link-button href="{{env('TELEGRAM_BOT_URL')}}" class="btn-primary">
                                    Вернуться к боту
                                </x-moonshine::link-button>
                            </div>
                        </div>
                    </div>
                </x-moonshine::layout.div>
            </x-moonshine::layout.wrapper>
        </x-moonshine::layout.body>
    </x-moonshine::layout.html>
</x-moonshine::layout>