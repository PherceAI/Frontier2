<?php

namespace App\Console\Commands;

use App\Domain\Restaurant\Actions\ImportKitchenDailyStockFromDocx;
use Illuminate\Console\Command;

class ImportKitchenDailyStockCommand extends Command
{
    protected $signature = 'frontier:import-kitchen-daily-stock {path : Absolute or relative path to the STOCK DIARIO COCINA DOCX file}';

    protected $description = 'Import the kitchen daily stock catalog from the initial DOCX file.';

    public function handle(ImportKitchenDailyStockFromDocx $docxImport): int
    {
        $path = (string) $this->argument('path');
        $resolvedPath = realpath($path) ?: base_path($path);
        $extension = strtolower(pathinfo($resolvedPath, PATHINFO_EXTENSION));

        if ($extension !== 'docx') {
            $this->error('Kitchen daily stock import only supports the DOCX source document.');

            return self::FAILURE;
        }

        $summary = $docxImport->handle($resolvedPath);

        $this->info('Kitchen daily stock catalog imported.');
        $this->line('Items: '.$summary['items']);
        $this->line('Categories: '.$summary['categories']);

        return self::SUCCESS;
    }
}
