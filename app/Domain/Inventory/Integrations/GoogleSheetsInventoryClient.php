<?php

namespace App\Domain\Inventory\Integrations;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class GoogleSheetsInventoryClient
{
    /**
     * @return array<string, mixed>
     */
    public function summary(): array
    {
        $url = config('services.inventory.google_sheets_url');

        if (! $url) {
            throw new RuntimeException('INVENTORY_GOOGLE_SHEETS_URL is not configured.');
        }

        $response = Http::acceptJson()
            ->timeout(30)
            ->retry(2, 500)
            ->get((string) $url);

        $response->throw();

        $payload = $response->json();

        if (($payload['status'] ?? null) !== 'success' || ! is_array($payload['data'] ?? null)) {
            throw new RuntimeException('Inventory Google Sheets API returned an unexpected payload.');
        }

        return $payload;
    }
}
