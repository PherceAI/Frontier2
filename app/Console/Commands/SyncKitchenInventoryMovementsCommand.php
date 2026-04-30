<?php

namespace App\Console\Commands;

use App\Domain\Restaurant\Actions\SyncKitchenInventoryMovements;
use Illuminate\Console\Command;

class SyncKitchenInventoryMovementsCommand extends Command
{
    protected $signature = 'frontier:sync-kitchen-inventory-movements {--from=} {--to=}';

    protected $description = 'Sync detailed kitchen inventory movements from Google Sheets.';

    public function handle(SyncKitchenInventoryMovements $sync): int
    {
        $from = (string) ($this->option('from') ?: now('America/Guayaquil')->toDateString());
        $to = (string) ($this->option('to') ?: $from);
        $movements = $sync->handle($from, $to);

        $this->info(sprintf('Synced %d kitchen inventory movements.', $movements->count()));

        return self::SUCCESS;
    }
}
