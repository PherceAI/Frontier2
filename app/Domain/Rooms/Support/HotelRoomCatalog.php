<?php

namespace App\Domain\Rooms\Support;

use Illuminate\Support\Collection;

class HotelRoomCatalog
{
    /**
     * @return Collection<int, array{number: string, floor: int, type: string}>
     */
    public function rooms(): Collection
    {
        return collect([
            ...$this->range(1, 101, 108, 'standard'),
            ...$this->range(2, 201, 208, 'standard'),
            ['number' => '225', 'floor' => 2, 'type' => 'standard'],
            ['number' => '226', 'floor' => 2, 'type' => 'standard'],
            ...$this->range(3, 301, 308, 'standard'),
            ...$this->range(4, 401, 408, 'executive'),
            ...$this->range(5, 501, 508, 'executive'),
            ...collect([601, 603, 604, 605, 606, 607, 608])->map(fn (int $number): array => [
                'number' => (string) $number,
                'floor' => 6,
                'type' => 'executive',
            ])->all(),
            ...$this->range(7, 701, 708, 'premium'),
            ...$this->range(8, 801, 808, 'premium'),
            ...$this->range(9, 901, 908, 'premium'),
            ...$this->range(10, 1001, 1008, 'premium'),
        ]);
    }

    /**
     * @return Collection<string, array{total: int, occupied: int, available: int}>
     */
    public function emptyTypeSummary(): Collection
    {
        return $this->rooms()
            ->groupBy('type')
            ->map(fn (Collection $rooms): array => [
                'total' => $rooms->count(),
                'occupied' => 0,
                'available' => $rooms->count(),
            ]);
    }

    /**
     * @return array<int, array{number: string, floor: int, type: string}>
     */
    private function range(int $floor, int $start, int $end, string $type): array
    {
        return collect(range($start, $end))
            ->map(fn (int $number): array => [
                'number' => (string) $number,
                'floor' => $floor,
                'type' => $type,
            ])
            ->all();
    }
}
