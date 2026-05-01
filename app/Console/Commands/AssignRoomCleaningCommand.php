<?php

namespace App\Console\Commands;

use App\Domain\Housekeeping\Actions\AssignDailyRoomCleanings;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class AssignRoomCleaningCommand extends Command
{
    protected $signature = 'frontier:assign-room-cleaning {--date= : Date to assign in Y-m-d format. Defaults to today.} {--force : Run even if automatic assignment is disabled or the date is not a working day.}';

    protected $description = 'Generate and assign daily room cleaning tasks from occupancy snapshots.';

    public function handle(AssignDailyRoomCleanings $assignCleanings): int
    {
        $date = $this->option('date')
            ? Carbon::createFromFormat('Y-m-d', (string) $this->option('date'), 'America/Guayaquil')
            : today('America/Guayaquil');

        $result = $assignCleanings->handle($date, (bool) $this->option('force'));

        if ($result['skipped']) {
            $this->warn("Room cleaning assignment skipped for {$result['date']}: {$result['reason']}.");

            return self::SUCCESS;
        }

        $this->info("Room cleaning assignment completed for {$result['date']}.");
        $this->line("Created: {$result['created']}");

        return self::SUCCESS;
    }
}
