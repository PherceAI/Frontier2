<?php

namespace App\Domain\Rooms\Actions;

use App\Domain\Rooms\Models\RoomOccupancySnapshot;
use App\Domain\Rooms\Support\HotelRoomCatalog;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class GetRoomsOverview
{
    public function __construct(private readonly HotelRoomCatalog $catalog) {}

    /**
     * @return array<string, mixed>
     */
    public function handle(?Carbon $date = null): array
    {
        $date ??= today();
        $catalogRooms = $this->catalog->rooms();
        $snapshots = RoomOccupancySnapshot::query()
            ->whereDate('occupancy_date', $date->toDateString())
            ->get()
            ->keyBy('room_number');

        $rooms = $catalogRooms
            ->map(function (array $room) use ($snapshots): array {
                /** @var RoomOccupancySnapshot|null $snapshot */
                $snapshot = $snapshots->get($room['number']);
                $isOccupied = (bool) ($snapshot?->is_occupied ?? false);

                return [
                    'number' => $room['number'],
                    'floor' => $room['floor'],
                    'type' => $room['type'],
                    'status' => $isOccupied ? 'occupied' : 'available',
                    'label' => $isOccupied ? 'Ocupada' : 'Libre',
                    'guestName' => $snapshot?->guest_name,
                    'companyName' => $snapshot?->company_name,
                    'reservationCode' => $snapshot?->reservation_code,
                    'checkInDate' => $snapshot?->check_in_date?->toDateString(),
                    'checkOutDate' => $snapshot?->check_out_date?->toDateString(),
                    'adults' => $snapshot?->adults,
                    'children' => $snapshot?->children,
                    'balance' => $snapshot?->balance ? (float) $snapshot->balance : null,
                    'syncedAt' => $snapshot?->synced_at?->toIso8601String(),
                ];
            })
            ->values();

        $occupied = $rooms->where('status', 'occupied')->count();
        $available = $rooms->where('status', 'available')->count();

        return [
            'date' => $date->toDateString(),
            'lastSyncedAt' => $snapshots
                ->pluck('synced_at')
                ->filter()
                ->sortDesc()
                ->first()?->toIso8601String(),
            'summary' => [
                'total' => $rooms->count(),
                'occupied' => $occupied,
                'available' => $available,
                'occupancyRate' => $rooms->count() > 0 ? round(($occupied / $rooms->count()) * 100, 1) : 0,
            ],
            'byType' => $this->byType($rooms),
            'byFloor' => $this->byFloor($rooms),
            'rooms' => $rooms,
        ];
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $rooms
     * @return array<int, array<string, mixed>>
     */
    private function byType(Collection $rooms): array
    {
        return $rooms
            ->groupBy('type')
            ->map(fn (Collection $items, string $type): array => [
                'type' => $type,
                'label' => match ($type) {
                    'standard' => 'Estandar',
                    'executive' => 'Ejecutiva',
                    'premium' => 'Premium',
                    default => ucfirst($type),
                },
                'total' => $items->count(),
                'occupied' => $items->where('status', 'occupied')->count(),
                'available' => $items->where('status', 'available')->count(),
            ])
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $rooms
     * @return array<int, array<string, mixed>>
     */
    private function byFloor(Collection $rooms): array
    {
        return $rooms
            ->groupBy('floor')
            ->map(fn (Collection $items, int $floor): array => [
                'floor' => $floor,
                'total' => $items->count(),
                'occupied' => $items->where('status', 'occupied')->count(),
                'available' => $items->where('status', 'available')->count(),
            ])
            ->sortBy('floor')
            ->values()
            ->all();
    }
}
