<?php

namespace App\Console\Commands;

use App\Models\Jury;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ImportJury extends Command
{
    protected $signature = 'import:jury {--clear : Clear existing jury entries before import}';
    protected $description = 'Import jury entries from JSON file';

    public function handle()
    {
        $filePath = storage_path('app/public/import/jury.json');

        if (!File::exists($filePath)) {
            $this->error("Jury file not found: {$filePath}");
            return 1;
        }

        if ($this->option('clear')) {
            $this->info('Clearing existing jury entries...');
            Jury::query()->forceDelete();
        }

        $data = json_decode(File::get($filePath), true);

        if (!is_array($data)) {
            $this->error('Invalid JSON format');
            return 1;
        }

        $this->info('Importing jury entries from ' . count($data) . ' years...');

        $totalImported = 0;
        foreach ($data as $yearData) {
            $year = $yearData['year'];
            $entries = $yearData['entries'];

            $this->line("Processing year {$year} - " . count($entries) . ' entries');

            $position = 0;
            foreach ($entries as $entryTitle) {
                Jury::create([
                    'year' => $year,
                    'title' => $entryTitle,
                    'publish' => true,
                    'position' => $position++,
                ]);
                $totalImported++;
            }
        }

        $this->info("Import completed! {$totalImported} jury entries imported.");
        $this->info('Total in database: ' . Jury::count());
        return 0;
    }
}
