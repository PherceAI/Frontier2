<?php

namespace App\Console\Commands;

use App\Domain\Restaurant\Actions\ImportStandardRecipesFromCsv;
use Illuminate\Console\Command;

class ImportStandardRecipesCommand extends Command
{
    protected $signature = 'frontier:import-standard-recipes {path : Absolute or relative path to the standard recipes CSV}';

    protected $description = 'Import restaurant standard recipes from a CSV file.';

    public function handle(ImportStandardRecipesFromCsv $import): int
    {
        $path = (string) $this->argument('path');
        $resolvedPath = realpath($path) ?: base_path($path);

        $summary = $import->handle($resolvedPath);

        $this->info('Standard recipes imported.');
        $this->line('Recipes: '.$summary['recipes']);
        $this->line('Items: '.$summary['items']);

        return self::SUCCESS;
    }
}
