<?php

namespace App\Console\Commands;

use App\Models\Lecture;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ImportLectures extends Command
{
    protected $signature = 'import:lectures {--clear : Clear existing lecture entries before import}';
    protected $description = 'Import lecture entries from JSON file';

    public function handle()
    {
        $filePath = storage_path('app/public/import/lectures.json');

        if (!File::exists($filePath)) {
            $this->error("Lectures file not found: {$filePath}");
            return 1;
        }

        if ($this->option('clear')) {
            $this->info('Clearing existing lecture entries...');
            Lecture::query()->forceDelete();
        }

        $data = json_decode(File::get($filePath), true);

        if (!is_array($data)) {
            $this->error('Invalid JSON format');
            return 1;
        }

        $this->info('Importing lecture entries from ' . count($data) . ' years...');

        $totalImported = 0;
        foreach ($data as $yearData) {
            $year = $yearData['year'];
            $entries = $yearData['entries'];

            $this->line("Processing year {$year} - " . count($entries) . ' entries');

            $position = 0;
            foreach ($entries as $entryTitle) {
                Lecture::create([
                    'year' => $year,
                    'title' => $entryTitle,
                    'publish' => true,
                    'position' => $position++,
                ]);
                $totalImported++;
            }
        }

        $this->info("Import completed! {$totalImported} lecture entries imported.");
        $this->info('Total in database: ' . Lecture::count());
        return 0;
    }
}
