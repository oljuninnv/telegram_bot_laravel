## Краткое описание
Разработанный бот предназначен для сбора отчётов из чатов и отправки данных о присутствии сообщений с хэштегами и ссылками на отчёт в отдельную google таблицу, написанный на telegram bot sdk + Laravel

## Требования
Перед началом работы убедитесь, что у вас установлены следующие компоненты:
- PHP (версия 8.0 или выше);
- Composer (менеджер зависимостей для PHP);
- Установленный Git;
- Установленная база данных MySQL;
- ngrok - для публичного доступа к локальному серверу;

## Создание Telegram-бота:
1. Перейдите в Telegram и запустите бота @BotFather;
2. Запустите команду /newbot для создания нового бота;
3. Введите имя боту;
4. Скопируйте token бота и ссылку на самого бота;

## Создание Telegram-бота:
1. Перейдите на Google Cloud Console;
2. Создайте новый проект;
3. Активируйте API Google Sheets;
4. Создайте Service Account:
- Перейдите в раздел Credentials.
- Нажмите Create Credentials → Service Account.
- Укажите имя, создайте, и на вкладке ключей добавьте новый ключ в формате JSON.
- Скачайте файл ключа и переименуйте его в credentials.json.
5. Создайте OAuth 2.0 Client IDs. 
В проекте:
- Загрузите файл credentials.json в директорию: app/storage/credentials.json

## Настройка ngrok:
1. Через vpn войдите на официальный сайт [ngrok](https://ngrok.com/) и создайте бесплатный аккаунт;
2. Запустите ngrok для перенаправления локального сервера командой: ngrok http 8000 (или другой используемый порт).
3. Через postman, выберите метод POST и вставьте строку: 

```
https://api.telegram.org/bot{token_бота}/setWebhook?url={ссылка_предоставляемая_ngrok_Forwarding}/telegram-webhook
```

## Настройка Google таблицы:

1. Создайте таблицу Google Sheets;
2. Добавьте в качестве редактора email сервисного аккаунта.

## Настройка проекта:
1. Переименуйте файл .env.example в .env;
2. Сгенерируйте ключ приложения командой: 

```
php artisan key:generate;
```

3. Введите свои данные в env:

```
- TELEGRAM_BOT_TOKEN="токен вашего бота (получаемый от botfather)"
- TELEGRAM_WEBHOOK_URL="(ссылка предоставляемая ngrok (Forwarding)/telegram-webhook)"
- TELEGRAM_USER_ADMIN_ID="ваш id в телеграмм"
- GOOGLE_APPLICATION_NAME="название вашей таблицы"
- GOOGLE_SHEET_ID="ID вашей страницы (получается из ссылки Пример: https://docs.google.com/spreadsheets/d/12345/editgid=232067112#gid=232067112 , где 12345 - GOOGLE_SHEET_ID)"
- GOOGLE_SERVICE_ACCOUNT_JSON_LOCATION=storage/credentials.json
- GOOGLE_CLIENT_ID="CLIENT_ID, получаемый при создании OAuth 2.0 Client ID"
- GOOGLE_CLIENT_SECRET="CLIENT_SECRET, получаемый при создании OAuth 2.0 Client ID"
- GOOGLE_SERVICE_ENABLED=true
```
4. Запустите seeder командой:

```
php artisan db:seed
```
5. Установите пакет telegram bot sdk:

```
composer require irazasyed/telegram-bot-sdk
```

## Запуск бота
Теперь тестовый проект на Laravel с Vue.js запущен и готов к использованию. Спасибо за внимание.
1. Запустите локальный сервер командой:

```
php artisan serve
```
2. Перейдите по ссылке ngrok под пунктом Web Interface, дополните её /inspect/http

3. Запустите бота

## Добавление задачи в планировщик

1. Используйте Windows Task Scheduler или Cron, в зависимости от системы;
2. В качестве задачи добавьте запуск PHP, указав аргумент reports:send;
3. Триггер - ежедневно, выполнять задачу раз в минуту.

## Заключение
Теперь тестовый проект c ботом запущен и готов к использованию. Спасибо за внимание.