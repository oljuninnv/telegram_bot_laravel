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
            <x-moonshine::layout.top-bar :home_route="route('home')">
                <x-moonshine::layout.logo href="/" logo="{{ asset('logo-small.svg') }}" />
                <x-moonshine::layout.menu/>
                <x-moonshine::link-button href="{{env('APP_URL')}}/admin/login" class="btn-primary">
                    Войти
                </x-moonshine::link-button>
            </x-moonshine::layout.top-bar>
            <x-moonshine::layout.wrapper>
                <x-moonshine::layout.content>
                    {{-- Если хотите добавлять какой-нибудь контент --}}
                </x-moonshine::layout.content>
            </x-moonshine::layout.wrapper>
        </x-moonshine::layout.body>
    </x-moonshine::layout.html>
</x-moonshine::layout>