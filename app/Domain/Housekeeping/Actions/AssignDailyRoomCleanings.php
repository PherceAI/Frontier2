<?php

namespace App\Domain\Housekeeping\Actions;

use App\Domain\Housekeeping\Models\HousekeepingTask;
use App\Domain\Housekeeping\Models\RoomCleaningSetting;
use App\Domain\Organization\Models\Area;
use App\Domain\Rooms\Models\Room;
use App\Domain\Rooms\Models\RoomOccupancySnapshot;
use App\Domain\Rooms\Models\RoomType;
use App\Domain\Rooms\Support\HotelRoomCatalog;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class AssignDailyRoomCleanings
{
    public function __construct(private readonly HotelRoomCatalog $catalog) {}

    /**
     * @return array{date: string, created: int, skipped: bool, reason: string|null}
     */
    public function handle(?Carbon $date = null, bool $force = false, ?User $assignedBy = null): array
    {
        $date = ($date ?? today('America/Guayaquil'))->copy()->startOfDay();
        $settings = RoomCleaningSetting::current();

        if (! $force && ! $settings->auto_assignment_enabled) {
            return $this->skipped($date, 'auto_assignment_disabled');
        }

        if (! $force && ! in_array((int) $date->dayOfWeekIso, $settings->working_days ?? [], true)) {
            return $this->skipped($date, 'non_working_day');
        }

        $rooms = $this->roomsByNumber();
        $employees = $this->eligibleEmployees();
        $candidates = $this->cleaningCandidates($date, $rooms);
        $assignments = $this->assignByFloor($candidates, $employees);
        $created = 0;

        foreach ($candidates as $candidate) {
            /** @var Room $room */
            $room = $candidate['room'];
            /** @var RoomOccupancySnapshot $snapshot */
            $snapshot = $candidate['snapshot'];

            $existingTask = HousekeepingTask::query()
                ->where('room_id', $room->id)
                ->whereDate('scheduled_date', $date->toDateString())
                ->where('cleaning_type', $candidate['cleaning_type'])
                ->first();

            if ($existingTask) {
                continue;
            }

            HousekeepingTask::create([
                'room_id' => $room->id,
                'scheduled_date' => $date->toDateString(),
                'cleaning_type' => $candidate['cleaning_type'],
                'occupancy_snapshot_id' => $snapshot->id,
                'assigned_to' => $assignments[$room->number] ?? null,
                'assigned_by' => $assignedBy?->id,
                'assignment_source' => $force ? HousekeepingTask::ASSIGNMENT_SOURCE_MANUAL : HousekeepingTask::ASSIGNMENT_SOURCE_AUTO,
                'type' => 'room_cleaning',
                'status' => HousekeepingTask::STATUS_PENDING,
                'generated_for_date' => $candidate['generated_for_date'],
                'scheduled_at' => $date->copy()->setTimeFromTimeString($settings->assignment_time),
                'notes' => null,
                'metadata' => [
                    'guest_name' => $snapshot->guest_name,
                    'company_name' => $snapshot->company_name,
                    'reservation_code' => $snapshot->reservation_code,
                    'check_in_date' => $snapshot->check_in_date?->toDateString(),
                    'check_out_date' => $snapshot->check_out_date?->toDateString(),
                    'generated_at' => now()->toISOString(),
                    'strategy' => $settings->assignment_strategy,
                ],
            ]);

            $created++;
        }

        return [
            'date' => $date->toDateString(),
            'created' => $created,
            'skipped' => false,
            'reason' => null,
        ];
    }

    /**
     * @return array{date: string, created: int, skipped: bool, reason: string}
     */
    private function skipped(Carbon $date, string $reason): array
    {
        return [
            'date' => $date->toDateString(),
            'created' => 0,
            'skipped' => true,
            'reason' => $reason,
        ];
    }

    /**
     * @return Collection<string, Room>
     */
    private function roomsByNumber(): Collection
    {
        $typeIds = collect(['standard', 'executive', 'premium'])
            ->mapWithKeys(fn (string $name): array => [
                $name => RoomType::firstOrCreate(['name' => $name], [
                    'capacity' => 2,
                    'base_rate' => 0,
                    'description' => ucfirst($name),
                ])->id,
            ]);

        $this->catalog->rooms()->each(function (array $catalogRoom) use ($typeIds): void {
            Room::firstOrCreate(['number' => $catalogRoom['number']], [
                'room_type_id' => $typeIds[$catalogRoom['type']],
                'floor' => $catalogRoom['floor'],
                'status' => 'available',
            ]);
        });

        return Room::query()->get()->keyBy('number');
    }

    /**
     * @return Collection<int, User>
     */
    private function eligibleEmployees(): Collection
    {
        $roomsArea = Area::query()->where('slug', 'rooms')->first();

        if (! $roomsArea) {
            return collect();
        }

        return $roomsArea->employees()
            ->wherePivot('is_active', true)
            ->where('users.operational_status', 'active')
            ->orderBy('users.name')
            ->get();
    }

    /**
     * @param  Collection<string, Room>  $rooms
     * @return Collection<int, array{room: Room, snapshot: RoomOccupancySnapshot, cleaning_type: string, generated_for_date: string}>
     */
    private function cleaningCandidates(Carbon $date, Collection $rooms): Collection
    {
        $checkoutDates = collect([$date->toDateString()]);

        if ((int) $date->dayOfWeekIso === 1) {
            $checkoutDates->push($date->copy()->subDay()->toDateString());
        }

        $checkoutCandidates = RoomOccupancySnapshot::query()
            ->where(function ($query) use ($checkoutDates) {
                $checkoutDates->each(fn (string $checkoutDate) => $query->orWhereDate('occupancy_date', $checkoutDate));
            })
            ->where(function ($query) use ($checkoutDates) {
                $checkoutDates->each(fn (string $checkoutDate) => $query->orWhereDate('check_out_date', $checkoutDate));
            })
            ->get()
            ->toBase()
            ->map(fn (RoomOccupancySnapshot $snapshot): ?array => $this->candidate(
                $snapshot,
                $rooms,
                HousekeepingTask::CLEANING_TYPE_CHECKOUT,
                $snapshot->check_out_date?->toDateString() ?? $date->toDateString(),
            ))
            ->filter();

        $checkoutRooms = $checkoutCandidates->pluck('room.number')->all();

        $stayCandidates = RoomOccupancySnapshot::query()
            ->whereDate('occupancy_date', $date->toDateString())
            ->where('is_occupied', true)
            ->whereDate('check_in_date', '<', $date->toDateString())
            ->whereDate('check_out_date', '>', $date->toDateString())
            ->get()
            ->toBase()
            ->filter(function (RoomOccupancySnapshot $snapshot) use ($date): bool {
                $daysSinceCheckIn = $snapshot->check_in_date
                    ? (int) $snapshot->check_in_date->diffInDays($date)
                    : 0;

                return $daysSinceCheckIn > 0 && $daysSinceCheckIn % 2 === 0;
            })
            ->reject(fn (RoomOccupancySnapshot $snapshot): bool => in_array($snapshot->room_number, $checkoutRooms, true))
            ->map(fn (RoomOccupancySnapshot $snapshot): ?array => $this->candidate(
                $snapshot,
                $rooms,
                HousekeepingTask::CLEANING_TYPE_STAY,
                $date->toDateString(),
            ))
            ->filter();

        return $checkoutCandidates
            ->merge($stayCandidates)
            ->sortBy(fn (array $candidate): string => str_pad((string) $candidate['room']->floor, 3, '0', STR_PAD_LEFT).'-'.$candidate['room']->number)
            ->values();
    }

    /**
     * @param  Collection<string, Room>  $rooms
     * @return array{room: Room, snapshot: RoomOccupancySnapshot, cleaning_type: string, generated_for_date: string}|null
     */
    private function candidate(RoomOccupancySnapshot $snapshot, Collection $rooms, string $type, string $generatedForDate): ?array
    {
        /** @var Room|null $room */
        $room = $rooms->get($snapshot->room_number);

        if (! $room) {
            return null;
        }

        return [
            'room' => $room,
            'snapshot' => $snapshot,
            'cleaning_type' => $type,
            'generated_for_date' => $generatedForDate,
        ];
    }

    /**
     * @param  Collection<int, array{room: Room, snapshot: RoomOccupancySnapshot, cleaning_type: string, generated_for_date: string}>  $candidates
     * @param  Collection<int, User>  $employees
     * @return array<string, int>
     */
    private function assignByFloor(Collection $candidates, Collection $employees): array
    {
        if ($employees->isEmpty()) {
            return [];
        }

        $loads = $employees->mapWithKeys(fn (User $employee): array => [$employee->id => 0])->all();
        $assignments = [];

        $candidates
            ->groupBy(fn (array $candidate): int => (int) $candidate['room']->floor)
            ->sortKeys()
            ->each(function (Collection $floorCandidates) use (&$loads, &$assignments): void {
                $employeeId = collect($loads)->sort()->keys()->first();

                foreach ($floorCandidates as $candidate) {
                    $assignments[$candidate['room']->number] = $employeeId;
                    $loads[$employeeId]++;
                }
            });

        return $assignments;
    }
}
