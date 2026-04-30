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

    /**
     * @return array<int, array<string, mixed>>
     */
    public function movements(?string $from = null, ?string $to = null): array
    {
        $url = config('services.inventory.google_sheets_url');

        if (! $url) {
            throw new RuntimeException('INVENTORY_GOOGLE_SHEETS_URL is not configured.');
        }

        $response = Http::acceptJson()
            ->timeout(30)
            ->retry(2, 500)
            ->get((string) $url, array_filter([
                'resource' => 'movements',
                'from' => $from,
                'to' => $to,
                'desde' => $from,
                'hasta' => $to,
            ]));

        $response->throw();

        $payload = $response->json();
        $rows = $payload['data']['movements']
            ?? $payload['data']['movimientos']
            ?? $payload['movements']
            ?? $payload['movimientos']
            ?? $payload['data']
            ?? [];

        return is_array($rows) ? array_values(array_filter($rows, 'is_array')) : [];
    }
}
