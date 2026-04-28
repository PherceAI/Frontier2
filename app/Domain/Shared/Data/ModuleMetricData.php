<?php

namespace App\Domain\Shared\Data;

use Spatie\LaravelData\Data;

class ModuleMetricData extends Data
{
    public function __construct(
        public string $label,
        public string $value,
        public string $trend,
    ) {
    }
}
