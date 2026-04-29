<?php

namespace App\Domain\Rooms\Actions;

use App\Domain\Rooms\Integrations\LegacySupabaseOccupancyClient;
use App\Domain\Rooms\Models\RoomOccupancySnapshot;
use App\Domain\Rooms\Support\HotelRoomCatalog;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Throwable;

class SyncLegacyRoomOccupancy
{
    public function __construct(
        private readonly LegacySupabaseOccupancyClient $client,
        private readonly HotelRoomCatalog $catalog,
    ) {}

    /**
     * @return array{date: string, rows: int, occupied: int, available: int}
     */
    public function handle(?Carbon $date = null): array
    {
        $date ??= today();
        $rooms = $this->catalog->rooms()->keyBy('number');
        $rows = $this->client->occupancyFor($date);
        $synced = 0;

        $rows
            ->map(fn (array $row): array => $this->normalize($row, $date))
            ->filter(fn (array $row): bool => filled($row['room_number']) && $rooms->has($row['room_number']))
            ->unique('room_number')
            ->each(function (array $row) use ($rooms, $date, &$synced): void {
                $catalogRoom = $rooms->get($row['room_number']);

                RoomOccupancySnapshot::updateOrCreate(
                    [
                        'occupancy_date' => $date->toDateString(),
                        'room_number' => $row['room_number'],
                    ],
                    [
                        'room_type' => $catalogRoom['type'],
                        'floor' => $catalogRoom['floor'],
                        'status' => $row['status'],
                        'is_occupied' => $row['is_occupied'],
                        'guest_name' => $row['guest_name'],
                        'company_name' => $row['company_name'],
                        'reservation_code' => $row['reservation_code'],
                        'check_in_date' => $row['check_in_date'],
                        'check_out_date' => $row['check_out_date'],
                        'adults' => $row['adults'],
                        'children' => $row['children'],
                        'balance' => $row['balance'],
                        'raw' => $row['raw'],
                        'synced_at' => now(),
                    ],
                );

                $synced++;
            });

        $occupied = RoomOccupancySnapshot::query()
            ->whereDate('occupancy_date', $date->toDateString())
            ->where('is_occupied', true)
            ->count();

        return [
            'date' => $date->toDateString(),
            'rows' => $synced,
            'occupied' => $occupied,
            'available' => $rooms->count() - $occupied,
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function normalize(array $row, Carbon $date): array
    {
        $normalized = collect($row)
            ->mapWithKeys(fn (mixed $value, string $key): array => [Str::lower($key) => $value])
            ->all();

        $roomNumber = $this->string($normalized, ['habitacion', 'hab', 'room', 'room_number', 'numero', 'numero_habitacion', 'nro_habitacion']);
        $status = $this->string($normalized, ['estado_habitacion', 'estado', 'status', 'ocupacion']) ?? 'occupied';
        $guestName = $this->string($normalized, ['huesped', 'cliente', 'nombre_huesped', 'guest', 'guest_name', 'nombre']);
        $isOccupied = $this->isOccupied($status, $guestName);

        return [
            'room_number' => $roomNumber ? preg_replace('/\D+/', '', $roomNumber) : null,
            'status' => $status,
            'is_occupied' => $isOccupied,
            'guest_name' => $guestName,
            'company_name' => $this->string($normalized, ['empresa', 'company', 'company_name', 'cuenta']),
            'reservation_code' => $this->string($normalized, ['roi', 'reserva', 'reserva_id', 'reservation', 'reservation_code', 'codigo_reserva', 'folio']),
            'check_in_date' => $this->date($normalized, ['fecha_llegada', 'llegada', 'check_in', 'checkin', 'arrival_date'])?->toDateString() ?? $date->toDateString(),
            'check_out_date' => $this->date($normalized, ['fecha_salida', 'salida', 'check_out', 'checkout', 'departure_date'])?->toDateString(),
            'adults' => $this->integer($normalized, ['adultos', 'adults', 'pax_adultos']),
            'children' => $this->integer($normalized, ['ninos', 'niños', 'children', 'menores']),
            'balance' => $this->decimal($normalized, ['saldo', 'balance', 'deuda', 'total_pendiente']),
            'raw' => $row,
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  array<int, string>  $keys
     */
    private function string(array $row, array $keys): ?string
    {
        $value = Arr::first($keys, fn (string $key): bool => filled($row[$key] ?? null));

        return $value ? trim((string) $row[$value]) : null;
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  array<int, string>  $keys
     */
    private function integer(array $row, array $keys): ?int
    {
        $value = $this->string($row, $keys);

        return is_numeric($value) ? (int) $value : null;
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  array<int, string>  $keys
     */
    private function decimal(array $row, array $keys): ?float
    {
        $value = $this->string($row, $keys);

        return is_numeric($value) ? round((float) $value, 2) : null;
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  array<int, string>  $keys
     */
    private function date(array $row, array $keys): ?Carbon
    {
        $value = $this->string($row, $keys);

        if (! $value) {
            return null;
        }

        foreach (['Y-m-d', 'd/m/Y', 'm/d/Y', 'Y-m-d H:i:s'] as $format) {
            try {
                $date = Carbon::createFromFormat($format, $value);
            } catch (Throwable) {
                continue;
            }

            if ($date !== false) {
                return $date;
            }
        }

        return null;
    }

    private function isOccupied(string $status, ?string $guestName): bool
    {
        $normalized = Str::of($status)->lower()->ascii()->toString();

        if (Str::contains($normalized, ['libre', 'available', 'vacant', 'disponible'])) {
            return false;
        }

        if (Str::contains($normalized, ['ocup', 'occupied', 'house', 'in house', 'hosped'])) {
            return true;
        }

        return filled($guestName);
    }
}
