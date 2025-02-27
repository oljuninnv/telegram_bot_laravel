<?php

namespace App\Helpers;

use Google\Client;
use Google\Service\Sheets;

trait GoogleHelper
{

    /**
     * Возвращает настроенный клиент Google.
     */
    protected function getGoogleClient(): Client
    {
        $client = new Client();
        $client->setApplicationName(env('GOOGLE_APPLICATION_NAME'));
        $client->setScopes(Sheets::SPREADSHEETS);
        $client->setAuthConfig(env('GOOGLE_SERVICE_ACCOUNT_JSON_LOCATION'));
        $client->setAccessType('offline');

        return $client;
    }

    /**
     * Создает новый лист в Google таблице.
     */
    protected function createGoogleSheet(Sheets $service, string $spreadsheetId, string $sheetName): void
    {
        $batchUpdateRequest = new \Google\Service\Sheets\BatchUpdateSpreadsheetRequest([
            'requests' => [
                'addSheet' => [
                    'properties' => [
                        'title' => $sheetName
                    ]
                ]
            ]
        ]);

        $service->spreadsheets->batchUpdate($spreadsheetId, $batchUpdateRequest);
    }

    /**
     * Заполняет лист данными.
     */
    protected function fillGoogleSheet(Sheets $service, string $spreadsheetId, string $sheetName, array $data): void
    {
        $range = $sheetName . '!A1';
        $body = new \Google\Service\Sheets\ValueRange(['values' => $data]);
        $service->spreadsheets_values->append($spreadsheetId, $range, $body, ['valueInputOption' => 'RAW']);
    }
}