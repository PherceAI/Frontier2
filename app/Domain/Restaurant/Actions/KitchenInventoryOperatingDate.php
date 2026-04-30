<?php

namespace App\Domain\Restaurant\Actions;

use Illuminate\Support\Carbon;

class KitchenInventoryOperatingDate
{
    public static function resolve(?Carbon $at = null): Carbon
    {
        $at ??= now('America/Guayaquil');
        $local = $at->copy()->timezone('America/Guayaquil');

        return $local->hour < 6
            ? $local->copy()->subDay()->startOfDay()
            : $local->copy()->startOfDay();
    }
}
