<?php

namespace App\Console\Commands;

use App\Domain\Restaurant\Actions\RestaurantPeriod;
use App\Domain\Restaurant\Actions\SyncContificoRestaurantDocuments;
use Illuminate\Console\Command;

class SyncContificoRestaurantCommand extends Command
{
    protected $signature = 'frontier:sync-contifico-restaurant {--period=today : today, week or month}';

    protected $description = 'Sync restaurant documents from Contifico into the local Frontier cache.';

    public function handle(SyncContificoRestaurantDocuments $sync): int
    {
        $period = RestaurantPeriod::normalize((string) $this->option('period'));
        $result = $sync->handle($period);

        $this->info("Contifico restaurant sync completed for {$period}.");
        $this->line("Documents: {$result['documents']}");
        $this->line("Products: {$result['products']}");

        return self::SUCCESS;
    }
}
