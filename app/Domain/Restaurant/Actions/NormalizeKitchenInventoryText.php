<?php

namespace App\Domain\Restaurant\Actions;

class NormalizeKitchenInventoryText
{
    public static function handle(?string $value): string
    {
        $value = mb_strtolower(trim((string) $value));
        $value = str($value)->ascii()->toString();

        return preg_replace('/\s+/', ' ', $value) ?: '';
    }
}
