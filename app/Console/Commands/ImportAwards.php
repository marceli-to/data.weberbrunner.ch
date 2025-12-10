<?php

namespace App\Console\Commands;

use App\Models\Award;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ImportAwards extends Command
{
    protected $signature = 'import:awards {--clear : Clear existing awards before import}';
    protected $description = 'Import awards from JSON file';

    public function handle()
    {
        $filePath = storage_path('app/public/import/awards.json');

        if (!File::exists($filePath)) {
            $this->error("Awards file not found: {$filePath}");
            return 1;
        }

        if ($this->option('clear')) {
            $this->info('Clearing existing awards...');
            Award::query()->forceDelete();
        }

        $data = json_decode(File::get($filePath), true);

        if (!is_array($data)) {
            $this->error('Invalid JSON format');
            return 1;
        }

        $this->info('Importing awards from ' . count($data) . ' years...');

        $totalImported = 0;
        foreach ($data as $yearData) {
            $year = $yearData['year'];
            $awards = $yearData['awards'];

            $this->line("Processing year {$year} - " . count($awards) . ' awards');

            $position = 0;
            foreach ($awards as $awardTitle) {
                Award::create([
                    'year' => $year,
                    'title' => $awardTitle,
                    'publish' => true,
                    'position' => $position++,
                ]);
                $totalImported++;
            }
        }

        $this->info("Import completed! {$totalImported} awards imported.");
        $this->info('Total in database: ' . Award::count());
        return 0;
    }
}
