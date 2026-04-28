<?php

namespace App\Domain\Restaurant\Actions;

use Carbon\CarbonImmutable;
use Illuminate\Support\Carbon;

class RestaurantPeriod
{
    /**
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    public static function range(string $period): array
    {
        $today = CarbonImmutable::instance(Carbon::today());

        return match ($period) {
            'week' => [$today->startOfWeek(), $today->endOfWeek()],
            'month' => [$today->startOfMonth(), $today->endOfMonth()],
            default => [$today, $today],
        };
    }

    public static function normalize(?string $period): string
    {
        return in_array($period, ['today', 'week', 'month'], true) ? $period : 'today';
    }
}
