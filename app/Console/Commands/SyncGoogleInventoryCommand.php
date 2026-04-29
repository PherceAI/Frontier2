<?php

namespace App\Console\Commands;

use App\Domain\Inventory\Actions\SyncGoogleInventorySnapshot;
use Illuminate\Console\Command;

class SyncGoogleInventoryCommand extends Command
{
    protected $signature = 'frontier:sync-google-inventory';

    protected $description = 'Sync inventory summary from the hotel Google Sheets Apps Script.';

    public function handle(SyncGoogleInventorySnapshot $sync): int
    {
        $snapshot = $sync->handle();

        $this->info('Google inventory sync completed.');
        $this->line('Generated at: '.$snapshot->generated_at?->toDateTimeString());
        $this->line('Products: '.$snapshot->total_products);
        $this->line('Inventory value: $'.number_format((float) $snapshot->inventory_value, 2));
        $this->line('Payables overdue: $'.number_format((float) $snapshot->payables_overdue, 2));

        return self::SUCCESS;
    }
}
