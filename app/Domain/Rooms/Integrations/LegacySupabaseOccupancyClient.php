<?php

namespace App\Domain\Rooms\Integrations;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class LegacySupabaseOccupancyClient
{
    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function occupancyFor(Carbon $date): Collection
    {
        $table = (string) config('services.legacy_erp.occupancy_table', 'ocupacion');
        $columns = $this->columns($table);
        $query = DB::connection('supabase_legacy')->table($table);

        $arrival = $this->firstColumn($columns, ['fecha_llegada', 'llegada', 'check_in', 'checkin', 'arrival_date']);
        $departure = $this->firstColumn($columns, ['fecha_salida', 'salida', 'check_out', 'checkout', 'departure_date']);

        if ($arrival && $departure) {
            $query
                ->whereDate($arrival, '<=', $date->toDateString())
                ->whereDate($departure, '>=', $date->toDateString());
        } elseif ($dailyDate = $this->firstColumn($columns, ['fecha_ocupacion', 'fecha', 'date', 'dia', 'business_date', 'created_at', 'updated_at'])) {
            $query->whereDate($dailyDate, $date->toDateString());
        } else {
            $query->limit(1000);
        }

        return collect($query->get())
            ->map(fn (object $row): array => (array) $row)
            ->values();
    }

    /**
     * @return array<int, string>
     */
    private function columns(string $table): array
    {
        $schema = (string) config('database.connections.supabase_legacy.search_path', 'public');

        return DB::connection('supabase_legacy')
            ->table('information_schema.columns')
            ->where('table_schema', $schema)
            ->where('table_name', $table)
            ->orderBy('ordinal_position')
            ->pluck('column_name')
            ->map(fn (string $column): string => strtolower($column))
            ->all();
    }

    /**
     * @param  array<int, string>  $columns
     * @param  array<int, string>  $candidates
     */
    private function firstColumn(array $columns, array $candidates): ?string
    {
        foreach ($candidates as $candidate) {
            if (in_array($candidate, $columns, true)) {
                return $candidate;
            }
        }

        return null;
    }
}
