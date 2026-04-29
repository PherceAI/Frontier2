<?php

namespace App\Domain\Restaurant\Actions;

use App\Domain\Restaurant\Integrations\ContificoClient;
use App\Domain\Restaurant\Models\ContificoDocument;
use App\Domain\Restaurant\Models\ContificoProduct;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class SyncContificoRestaurantDocuments
{
    public function __construct(private readonly ContificoClient $client) {}

    /**
     * @return array{documents: int, products: int}
     */
    public function handle(string $period): array
    {
        [$start, $end] = RestaurantPeriod::range(RestaurantPeriod::normalize($period));
        $syncedDocuments = 0;
        $syncedProducts = 0;

        foreach (['CLI', 'PRO'] as $tipoRegistro) {
            $documents = $this->client->documents([
                'tipo_registro' => $tipoRegistro,
                'fecha_inicial' => $start->format('d/m/Y'),
                'fecha_final' => $end->format('d/m/Y'),
                'result_size' => 100,
            ]);

            $documents->each(function (array $document) use (&$syncedDocuments, &$syncedProducts): void {
                $this->upsertDocument($document);
                $syncedDocuments++;
                $syncedProducts += $this->syncProductsFromDetails(collect($document['detalles'] ?? []));
            });
        }

        $openPayables = $this->client->documents([
            'tipo_registro' => 'PRO',
            'estado' => 'P',
            'result_size' => 100,
        ]);

        $openPayables->each(function (array $document) use (&$syncedDocuments, &$syncedProducts): void {
            $this->upsertDocument($document);
            $syncedDocuments++;
            $syncedProducts += $this->syncProductsFromDetails(collect($document['detalles'] ?? []));
        });

        return ['documents' => $syncedDocuments, 'products' => $syncedProducts];
    }

    /**
     * @param  array<string, mixed>  $document
     */
    private function upsertDocument(array $document): void
    {
        ContificoDocument::updateOrCreate(
            ['external_id' => (string) $document['id']],
            [
                'tipo_registro' => (string) ($document['tipo_registro'] ?? ''),
                'tipo_documento' => $document['tipo_documento'] ?? $document['tipo'] ?? null,
                'documento' => $document['documento'] ?? null,
                'estado' => $document['estado'] ?? null,
                'anulado' => (bool) ($document['anulado'] ?? false),
                'fecha_emision' => $this->date($document['fecha_emision'] ?? null),
                'fecha_vencimiento' => $this->date($document['fecha_vencimiento'] ?? null),
                'total' => $this->money($document['total'] ?? 0),
                'saldo' => $this->money($document['saldo'] ?? 0),
                'servicio' => $this->money($document['servicio'] ?? 0),
                'vendedor_id' => $document['vendedor_id'] ?? null,
                'vendedor_nombre' => $this->personName(Arr::get($document, 'vendedor')),
                'persona_nombre' => $this->personName($document['cliente'] ?? $document['persona'] ?? null),
                'detalles' => $document['detalles'] ?? [],
                'cobros' => $document['cobros'] ?? [],
                'raw' => $document,
                'synced_at' => now(),
            ],
        );
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $details
     */
    private function syncProductsFromDetails(Collection $details): int
    {
        $synced = 0;

        $details
            ->filter(fn (array $detail): bool => filled($detail['producto_id'] ?? null) && filled($detail['producto_nombre'] ?? null))
            ->each(function (array $detail) use (&$synced): void {
                ContificoProduct::updateOrCreate(
                    ['external_id' => (string) $detail['producto_id']],
                    [
                        'nombre' => (string) $detail['producto_nombre'],
                        'raw' => ['source' => 'document_detail', ...$detail],
                        'synced_at' => now(),
                    ],
                );

                $synced++;
            });

        $details
            ->pluck('producto_id')
            ->filter()
            ->unique()
            ->each(function (string $productId) use (&$synced): void {
                if (ContificoProduct::where('external_id', $productId)->whereNotNull('nombre')->exists()) {
                    return;
                }

                $product = $this->client->product($productId);

                if (! $product) {
                    return;
                }

                ContificoProduct::updateOrCreate(
                    ['external_id' => $productId],
                    [
                        'nombre' => $product['nombre'] ?? $product['descripcion'] ?? $product['codigo'] ?? $productId,
                        'raw' => $product,
                        'synced_at' => now(),
                    ],
                );

                $synced++;
            });

        return $synced;
    }

    private function date(mixed $value): ?string
    {
        if (! $value) {
            return null;
        }

        return \DateTimeImmutable::createFromFormat('d/m/Y', (string) $value)?->format('Y-m-d');
    }

    private function money(mixed $value): float
    {
        return round((float) ($value ?? 0), 2);
    }

    private function personName(mixed $person): ?string
    {
        if (! is_array($person)) {
            return null;
        }

        return $person['razon_social'] ?? $person['nombre_comercial'] ?? $person['nombre'] ?? null;
    }
}
