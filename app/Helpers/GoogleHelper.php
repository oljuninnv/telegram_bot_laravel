<?php

namespace App\Helpers;

use Google\Client;
use Google\Service\Sheets;
use Google\Service\Sheets\ClearValuesRequest;
use Google\Service\Sheets\BatchUpdateSpreadsheetRequest;
use Google\Service\Sheets\ValueRange;

trait GoogleHelper
{
    private static $googleClient = null;

    protected function getGoogleClient(): Client
    {
        if (self::$googleClient === null) {
            self::$googleClient = new Client();
            self::$googleClient->setApplicationName(config('services.google.application_name'));
            self::$googleClient->setScopes(Sheets::SPREADSHEETS);
            self::$googleClient->setAuthConfig(config('services.google.service_account_json_location'));
            self::$googleClient->setAccessType('offline');
        }
        return self::$googleClient;
    }

    protected function getOrCreateSheet(Sheets $service, string $spreadsheetId, string $sheetName): void
    {
        if (!$this->checkIfSheetExists($service, $spreadsheetId, $sheetName)) {
            $batchUpdateRequest = new BatchUpdateSpreadsheetRequest([
                'requests' => [
                    'addSheet' => [
                        'properties' => [
                            'title' => $sheetName
                        ]
                    ]
                ]
            ]);
            $service->spreadsheets->batchUpdate($spreadsheetId, $batchUpdateRequest);
        } else {
            $range = $sheetName . '!A1:Z1000';
            $service->spreadsheets_values->clear(
                $spreadsheetId,
                $range,
                new ClearValuesRequest()
            );
        }
    }

    protected function fillGoogleSheet(Sheets $service, string $spreadsheetId, string $sheetName, array $data): void
    {
        $range = $sheetName . '!A1';
        $body = new ValueRange(['values' => $data]);
        $service->spreadsheets_values->append(
            $spreadsheetId,
            $range,
            $body,
            ['valueInputOption' => 'RAW']
        );
    }

    protected function checkIfSheetExists(Sheets $service, string $spreadsheetId, string $sheetName): bool
    {
        $spreadsheet = $service->spreadsheets->get($spreadsheetId, ['fields' => 'sheets.properties.title']);
        foreach ($spreadsheet->getSheets() as $sheet) {
            if ($sheet->getProperties()->getTitle() === $sheetName) {
                return true;
            }
        }
        return false;
    }
}