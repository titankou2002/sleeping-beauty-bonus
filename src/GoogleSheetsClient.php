<?php
namespace App;

use Google\Client;
use Google\Service\Sheets;
use Google\Service\Sheets\ValueRange;

class GoogleSheetsClient
{
    private Sheets $service;
    private string $ssId;

    public function __construct(string $ssId = null)
    {
        $this->ssId = $ssId ?: SS_ID_MAIN;

        $client = new Client();
        $client->setAuthConfig(SERVICE_ACCOUNT_FILE);
        $client->addScope(Sheets::SPREADSHEETS);
        $this->service = new Sheets($client);
    }

    public function readSheet(string $sheetName): array
    {
        $range = "'{$sheetName}'!A:ZZ";
        $response = $this->service->spreadsheets_values->get($this->ssId, $range);
        return $response->getValues() ?: [];
    }

    public function writeRows(string $sheetName, int $startRow, array $rows): void
    {
        $range = "'{$sheetName}'!A{$startRow}";
        $body = new ValueRange(['values' => $rows]);
        $this->service->spreadsheets_values->update(
            $this->ssId, $range, $body,
            ['valueInputOption' => 'USER_ENTERED']
        );
    }

    public function appendRows(string $sheetName, array $rows): void
    {
        $range = "'{$sheetName}'!A:A";
        $body = new ValueRange(['values' => $rows]);
        $this->service->spreadsheets_values->append(
            $this->ssId, $range, $body,
            ['valueInputOption' => 'USER_ENTERED']
        );
    }

    public function getSheetLastRow(string $sheetName): int
    {
        $range = "'{$sheetName}'!A:A";
        $response = $this->service->spreadsheets_values->get($this->ssId, $range);
        $values = $response->getValues();
        return $values ? count($values) : 0;
    }

    public function getService(): Sheets
    {
        return $this->service;
    }
}
