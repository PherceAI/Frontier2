<?php

namespace App\Domain\Shared\Data;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

class ModuleOverviewData extends Data
{
    /**
     * @param DataCollection<int, ModuleMetricData> $metrics
     * @param array<int, string> $nextSteps
     */
    public function __construct(
        public string $module,
        public string $title,
        public string $description,
        public string $status,
        public DataCollection $metrics,
        public array $nextSteps,
    ) {
    }
}
