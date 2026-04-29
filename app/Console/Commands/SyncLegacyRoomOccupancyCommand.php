<?php

namespace App\Console\Commands;

use App\Domain\Rooms\Actions\SyncLegacyRoomOccupancy;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class SyncLegacyRoomOccupancyCommand extends Command
{
    protected $signature = 'frontier:sync-legacy-room-occupancy {--date= : Date to sync in Y-m-d format. Defaults to today.}';

    protected $description = 'Sync today room occupancy from the legacy ERP Supabase table into Frontier.';

    public function handle(SyncLegacyRoomOccupancy $sync): int
    {
        $date = $this->option('date')
            ? Carbon::createFromFormat('Y-m-d', (string) $this->option('date'))
            : today();

        $result = $sync->handle($date);

        $this->info("Legacy room occupancy sync completed for {$result['date']}.");
        $this->line("Rows: {$result['rows']}");
        $this->line("Occupied: {$result['occupied']}");
        $this->line("Available: {$result['available']}");

        return self::SUCCESS;
    }
}
