<?php

namespace App\Http\Controllers;

use Google\Client;
use Google\Service\Sheets;

class GoogleSheetsController extends Controller
{
    public function fillSheet()
    {
        // Настройка клиента Google
        $client = new Client();
        $client->setApplicationName(env('GOOGLE_APPLICATION_NAME'));
        $client->setScopes(Sheets::SPREADSHEETS);
        $client->setAuthConfig(env('GOOGLE_SERVICE_ACCOUNT_JSON_LOCATION'));
        $client->setAccessType('offline');

        // Создание сервиса Sheets
        $service = new Sheets($client);

        // Получаем ID таблицы и название листа из .env
        $spreadsheetId = config('services.google.sheet_id');
        $sheetName = 'Demo'; // Укажите название листа

        // Проверяем, заданы ли ID таблицы и название листа
        if (empty($spreadsheetId)) {
            return response()->json([
                'error' => 'GOOGLE_SHEET_ID не задан в .env',
            ], 400);
        }

        // Тестовые данные для заполнения
        $data = [
            ['Имя', 'Возраст', 'Город'],
            ['Алексей', '25', 'Москва'],
            ['Мария', '30', 'Санкт-Петербург'],
            ['Иван', '22', 'Новосибирск'],
        ];

        // Добавляем данные в таблицу
        $range = $sheetName . '!A1'; // Укажите диапазон, куда добавлять данные
        $body = new \Google\Service\Sheets\ValueRange([
            'values' => $data
        ]);

        $result = $service->spreadsheets_values->append($spreadsheetId, $range, $body, ['valueInputOption' => 'RAW']);

        return response()->json([
            'message' => 'Данные успешно добавлены в таблицу!',
            'updates' => $result->getUpdates(),
        ]);
    }
}