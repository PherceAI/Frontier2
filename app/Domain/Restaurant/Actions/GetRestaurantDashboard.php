<?php

namespace App\Domain\Restaurant\Actions;

use App\Domain\Restaurant\Models\ContificoDocument;
use App\Domain\Restaurant\Models\ContificoProduct;
use Illuminate\Support\Collection;

class GetRestaurantDashboard
{
    /**
     * @return array<string, mixed>
     */
    public function handle(string $period): array
    {
        $period = RestaurantPeriod::normalize($period);
        [$start, $end] = RestaurantPeriod::range($period);

        $documents = ContificoDocument::query()
            ->whereDate('fecha_emision', '>=', $start->toDateString())
            ->whereDate('fecha_emision', '<=', $end->toDateString())
            ->get();

        $sales = $documents
            ->where('tipo_registro', 'CLI')
            ->where('tipo_documento', 'FAC')
            ->reject(fn (ContificoDocument $document): bool => $this->isVoided($document));

        $purchases = $documents
            ->where('tipo_registro', 'PRO')
            ->where('tipo_documento', 'FAC')
            ->reject(fn (ContificoDocument $document): bool => $this->isVoided($document));

        $salesTotal = $sales->sum(fn (ContificoDocument $document): float => $this->netSalesTotal($document));
        $purchaseTotal = $purchases->sum(fn (ContificoDocument $document): float => (float) $document->total);
        $grossMargin = $salesTotal - $purchaseTotal;

        $lastSyncedDocument = $documents
            ->filter(fn (ContificoDocument $document): bool => $document->synced_at !== null)
            ->sortByDesc('synced_at')
            ->first();

        return [
            'period' => $period,
            'dateLabel' => now()->locale('es')->translatedFormat('l d \d\e F, Y'),
            'rangeLabel' => $this->rangeLabel($period),
            'lastSyncedAt' => $lastSyncedDocument?->synced_at?->toISOString(),
            'summary' => [
                'salesTotal' => $this->round($salesTotal),
                'salesInvoices' => $sales->count(),
                'averageTicket' => $this->round($sales->count() > 0 ? $salesTotal / $sales->count() : 0),
                'grossMargin' => $this->round($grossMargin),
                'grossMarginPercent' => $this->round($salesTotal > 0 ? ($grossMargin / $salesTotal) * 100 : 0),
                'purchaseTotal' => $this->round($purchaseTotal),
                'purchaseInvoices' => $purchases->count(),
            ],
            'topDishes' => $this->topDishes($sales),
            'waiterPerformance' => $this->waiterPerformance($sales),
            'paymentDistribution' => $this->paymentDistribution($sales),
            'documentStatus' => $this->documentStatus($documents),
            'supplierPurchases' => $this->supplierPurchases($purchases),
            'accountsPayable' => $this->accountsPayable(),
            'isEmpty' => $documents->isEmpty(),
        ];
    }

    /**
     * @param  Collection<int, ContificoDocument>  $sales
     * @return array<int, array{name: string, amount: float, quantity: float, percent: float}>
     */
    private function topDishes(Collection $sales): array
    {
        $products = ContificoProduct::query()->pluck('nombre', 'external_id');
        $items = $sales
            ->flatMap(fn (ContificoDocument $document): array => $document->detalles ?? [])
            ->map(function (array $detail) use ($products): array {
                $productId = (string) ($detail['producto_id'] ?? 'otros');
                $quantity = (float) ($detail['cantidad'] ?? 1);
                $amount = $this->detailAmount($detail);

                return [
                    'key' => $productId,
                    'name' => $detail['producto_nombre']
                        ?? $detail['nombre']
                        ?? $this->manualName($detail)
                        ?? $products[$productId]
                        ?? $productId,
                    'quantity' => $quantity,
                    'amount' => $amount,
                ];
            })
            ->groupBy('key')
            ->map(fn (Collection $rows): array => [
                'name' => (string) $rows->first()['name'],
                'quantity' => $this->round($rows->sum('quantity')),
                'amount' => $this->round($rows->sum('amount')),
            ])
            ->sortByDesc('amount')
            ->values();

        $top = $items->take(5);
        $other = $items->slice(5);
        $total = max((float) $items->sum('amount'), 1);

        if ($other->isNotEmpty()) {
            $top->push([
                'name' => 'Otros',
                'quantity' => $this->round($other->sum('quantity')),
                'amount' => $this->round($other->sum('amount')),
            ]);
        }

        return $top
            ->map(fn (array $item): array => [
                ...$item,
                'percent' => $this->round(((float) $item['amount'] / $total) * 100),
            ])
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, ContificoDocument>  $sales
     * @return array<int, array{name: string, initials: string, amount: float}>
     */
    private function waiterPerformance(Collection $sales): array
    {
        return $sales
            ->groupBy(fn (ContificoDocument $document): string => $document->vendedor_id ?: ($document->vendedor_nombre ?: 'Sin vendedor'))
            ->map(function (Collection $documents): array {
                $name = $documents->first()->vendedor_nombre ?: 'Sin vendedor';

                return [
                    'name' => $name,
                    'initials' => collect(explode(' ', $name))
                        ->filter()
                        ->take(2)
                        ->map(fn (string $part): string => mb_substr($part, 0, 1))
                        ->implode('') ?: 'SV',
                    'amount' => $this->round($documents->sum(fn (ContificoDocument $document): float => $this->netSalesTotal($document))),
                ];
            })
            ->sortByDesc('amount')
            ->values()
            ->take(6)
            ->all();
    }

    /**
     * @param  Collection<int, ContificoDocument>  $sales
     * @return array<int, array{code: string, label: string, amount: float, percent: float}>
     */
    private function paymentDistribution(Collection $sales): array
    {
        $labels = [
            'TC' => 'Tarjeta de credito',
            'EF' => 'Efectivo',
            'TRA' => 'Transferencia',
            'CQ' => 'Cheque',
        ];

        $rows = $sales
            ->flatMap(fn (ContificoDocument $document): array => $document->cobros ?? [])
            ->map(fn (array $payment): array => [
                'code' => (string) ($payment['forma_cobro'] ?? 'OTR'),
                'amount' => max(0, (float) ($payment['monto'] ?? 0) - (float) ($payment['monto_propina'] ?? 0)),
            ])
            ->groupBy('code')
            ->map(fn (Collection $payments, string $code): array => [
                'code' => $code,
                'label' => $labels[$code] ?? 'Otros',
                'amount' => $this->round($payments->sum('amount')),
            ])
            ->sortByDesc('amount')
            ->values();

        $total = max((float) $rows->sum('amount'), 1);

        return $rows
            ->map(fn (array $row): array => [
                ...$row,
                'percent' => $this->round(((float) $row['amount'] / $total) * 100),
            ])
            ->all();
    }

    /**
     * @param  Collection<int, ContificoDocument>  $documents
     * @return array<string, array{count: int, amount: float}>
     */
    private function documentStatus(Collection $documents): array
    {
        $clientDocuments = $documents->where('tipo_registro', 'CLI');

        return [
            'charged' => [
                'count' => $clientDocuments->whereIn('estado', ['C', 'G'])->count(),
                'amount' => $this->round($clientDocuments->whereIn('estado', ['C', 'G'])->sum(fn (ContificoDocument $document): float => $this->netSalesTotal($document))),
            ],
            'pending' => [
                'count' => $clientDocuments->where('estado', 'P')->reject(fn (ContificoDocument $document): bool => $this->isVoided($document))->count(),
                'amount' => $this->round($clientDocuments->where('estado', 'P')->sum(fn (ContificoDocument $document): float => (float) $document->saldo)),
            ],
            'voided' => [
                'count' => $clientDocuments->filter(fn (ContificoDocument $document): bool => $this->isVoided($document))->count(),
                'amount' => $this->round($clientDocuments->filter(fn (ContificoDocument $document): bool => $this->isVoided($document))->sum('total')),
            ],
            'creditNotes' => [
                'count' => $clientDocuments->filter(fn (ContificoDocument $document): bool => in_array($document->tipo_documento, ['NCR', 'NC'], true))->count(),
                'amount' => $this->round($clientDocuments->filter(fn (ContificoDocument $document): bool => in_array($document->tipo_documento, ['NCR', 'NC'], true))->sum('total')),
            ],
        ];
    }

    /**
     * @param  Collection<int, ContificoDocument>  $purchases
     * @return array<string, mixed>
     */
    private function supplierPurchases(Collection $purchases): array
    {
        $soon = $purchases
            ->where('estado', 'P')
            ->filter(fn (ContificoDocument $document): bool => $document->fecha_vencimiento !== null)
            ->map(fn (ContificoDocument $document): array => [
                'supplier' => $document->persona_nombre ?: 'Proveedor',
                'document' => $document->documento ?: $document->external_id,
                'amount' => $this->round((float) ($document->saldo ?: $document->total)),
                'daysRemaining' => now()->startOfDay()->diffInDays($document->fecha_vencimiento, false),
            ])
            ->filter(fn (array $document): bool => $document['daysRemaining'] >= 0 && $document['daysRemaining'] < 3)
            ->sortBy('daysRemaining')
            ->values();

        return [
            'total' => $this->round($purchases->sum('total')),
            'count' => $purchases->count(),
            'dueSoonTotal' => $this->round($soon->sum('amount')),
            'dueSoonCount' => $soon->count(),
            'alerts' => $soon->take(3)->values()->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function accountsPayable(): array
    {
        $openPayables = ContificoDocument::query()
            ->where('tipo_registro', 'PRO')
            ->where(function ($query) {
                $query->where('estado', 'P')->orWhere('saldo', '>', 0);
            })
            ->where('anulado', false)
            ->get()
            ->filter(fn (ContificoDocument $document): bool => ! $this->isVoided($document));

        $today = now()->startOfDay();
        $nextWeek = now()->addDays(7)->endOfDay();

        $withDays = $openPayables
            ->map(function (ContificoDocument $document) use ($today): array {
                $dueDate = $document->fecha_vencimiento;

                return [
                    'supplier' => $document->persona_nombre ?: 'Proveedor',
                    'document' => $document->documento ?: $document->external_id,
                    'amount' => $this->round((float) ($document->saldo ?: $document->total)),
                    'dueDate' => $dueDate?->toDateString(),
                    'daysRemaining' => $dueDate ? $today->diffInDays($dueDate, false) : null,
                ];
            })
            ->sortBy(fn (array $document): int|float => $document['daysRemaining'] ?? 9999)
            ->values();

        return [
            'total' => $this->round($openPayables->sum(fn (ContificoDocument $document): float => (float) ($document->saldo ?: $document->total))),
            'count' => $openPayables->count(),
            'overdueTotal' => $this->round($openPayables
                ->filter(fn (ContificoDocument $document): bool => $document->fecha_vencimiento !== null && $document->fecha_vencimiento->lt($today))
                ->sum(fn (ContificoDocument $document): float => (float) ($document->saldo ?: $document->total))),
            'overdueCount' => $openPayables
                ->filter(fn (ContificoDocument $document): bool => $document->fecha_vencimiento !== null && $document->fecha_vencimiento->lt($today))
                ->count(),
            'dueTodayTotal' => $this->round($openPayables
                ->filter(fn (ContificoDocument $document): bool => $document->fecha_vencimiento !== null && $document->fecha_vencimiento->isSameDay($today))
                ->sum(fn (ContificoDocument $document): float => (float) ($document->saldo ?: $document->total))),
            'dueTodayCount' => $openPayables
                ->filter(fn (ContificoDocument $document): bool => $document->fecha_vencimiento !== null && $document->fecha_vencimiento->isSameDay($today))
                ->count(),
            'dueNext7Total' => $this->round($openPayables
                ->filter(fn (ContificoDocument $document): bool => $document->fecha_vencimiento !== null
                    && $document->fecha_vencimiento->greaterThan($today)
                    && $document->fecha_vencimiento->lessThanOrEqualTo($nextWeek))
                ->sum(fn (ContificoDocument $document): float => (float) ($document->saldo ?: $document->total))),
            'dueNext7Count' => $openPayables
                ->filter(fn (ContificoDocument $document): bool => $document->fecha_vencimiento !== null
                    && $document->fecha_vencimiento->greaterThan($today)
                    && $document->fecha_vencimiento->lessThanOrEqualTo($nextWeek))
                ->count(),
            'items' => $withDays->take(6)->all(),
        ];
    }

    private function netSalesTotal(ContificoDocument $document): float
    {
        $tips = collect($document->cobros ?? [])->sum(fn (array $payment): float => (float) ($payment['monto_propina'] ?? 0));

        return max(0, (float) $document->total - $tips);
    }

    /**
     * @param  array<string, mixed>  $detail
     */
    private function detailAmount(array $detail): float
    {
        $base = (float) ($detail['base_cero'] ?? 0)
            + (float) ($detail['base_gravable'] ?? 0)
            + (float) ($detail['base_no_gravable'] ?? 0);

        if ($base > 0) {
            return $this->round($base);
        }

        return $this->round((float) ($detail['precio'] ?? 0) * (float) ($detail['cantidad'] ?? 1));
    }

    private function isVoided(ContificoDocument $document): bool
    {
        return $document->anulado || $document->estado === 'A';
    }

    /**
     * @param  array<string, mixed>  $detail
     */
    private function manualName(array $detail): ?string
    {
        $name = trim((string) ($detail['nombre_manual'] ?? $detail['descripcion'] ?? ''));

        return $name !== '' ? $name : null;
    }

    private function rangeLabel(string $period): string
    {
        return match ($period) {
            'week' => 'Semana actual',
            'month' => 'Mes actual',
            default => 'Hoy',
        };
    }

    private function round(float|int $value): float
    {
        return round((float) $value, 2);
    }
}
