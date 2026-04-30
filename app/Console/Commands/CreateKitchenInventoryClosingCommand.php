<?php

namespace App\Console\Commands;

use App\Domain\Restaurant\Actions\CreateKitchenInventoryClosingTask;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class CreateKitchenInventoryClosingCommand extends Command
{
    protected $signature = 'frontier:create-kitchen-inventory-closing {--at=}';

    protected $description = 'Create or reuse the nightly kitchen inventory closing task.';

    public function handle(CreateKitchenInventoryClosingTask $createClosing): int
    {
        $at = $this->option('at')
            ? Carbon::parse((string) $this->option('at'), 'America/Guayaquil')
            : now('America/Guayaquil');

        $closing = $createClosing->handle($at);

        $this->info(sprintf(
            'Kitchen closing ready for %s with %d products.',
            $closing->operating_date->toDateString(),
            $closing->items()->count(),
        ));

        return self::SUCCESS;
    }
}
