<?php

namespace App\Console\Commands;

use App\Models\RawData;
use App\Models\RawDataAttribute;
use App\Models\RawDataMeta;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ImportRawData extends Command
{
    protected $signature = 'import:raw-data';
    protected $description = 'Import raw project data from markdown files';

    public function handle(): int
    {
        $basePath = storage_path('app/public/import/raw-data/projects');

        $files = collect(File::files($basePath))
            ->filter(fn($file) => $file->getExtension() === 'md');

        if ($files->isEmpty()) {
            $this->info('No files to import.');
            return Command::SUCCESS;
        }

        $this->info("Found {$files->count()} files to import.");

        $imported = 0;
        $failed = 0;

        foreach ($files as $file) {
            $filename = $file->getFilename();

            try {
                $content = File::get($file->getPathname());
                $this->parseAndStore($content, $filename);
                $this->line("✓ Imported: {$filename}");
                $imported++;
            } catch (\Exception $e) {
                $this->error("✗ Failed: {$filename} - {$e->getMessage()}");
                $failed++;
            }
        }

        $this->newLine();
        $this->info("Import complete: {$imported} imported, {$failed} failed.");

        return $failed > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    private function parseAndStore(string $content, string $filename): void
    {
        $lines = array_map('trim', explode("\n", trim($content)));
        
        if (count($lines) < 2) {
            throw new \Exception('File must have at least number and title');
        }

        $number = array_shift($lines);
        $title = array_shift($lines);

        $rawData = RawData::create([
            'number' => $number,
            'title' => $title,
        ]);

        $metaPosition = 0;
        $attributePosition = 0;
        $currentGroup = null;
        $inAttributeSection = false;

        foreach ($lines as $line) {
            if (empty($line)) {
                continue;
            }

            // Check if this is a group header (line ending with colon or standalone word like "Fachplaner")
            if ($this->isGroupHeader($line)) {
                $inAttributeSection = true;
                $currentGroup = rtrim($line, ':');
                continue;
            }

            // Parse key:value pair
            if (str_contains($line, ':')) {
                $colonPos = strpos($line, ':');
                $label = trim(substr($line, 0, $colonPos));
                $value = trim(substr($line, $colonPos + 1));

                if ($inAttributeSection) {
                    RawDataAttribute::create([
                        'raw_data_id' => $rawData->id,
                        'group_key' => $currentGroup,
                        'label' => $label,
                        'value' => $value,
                        'position' => $attributePosition++,
                    ]);
                } else {
                    RawDataMeta::create([
                        'raw_data_id' => $rawData->id,
                        'label' => $label,
                        'value' => $value,
                        'position' => $metaPosition++,
                    ]);
                }
            } else {
                // Line without colon - treat as meta with empty label (e.g. subtitle)
                RawDataMeta::create([
                    'raw_data_id' => $rawData->id,
                    'label' => '',
                    'value' => $line,
                    'position' => $metaPosition++,
                ]);
            }
        }
    }

    private function isGroupHeader(string $line): bool
    {
        // Only "Fachplaner" or "Fachplaner:" triggers the attribute section
        $normalized = strtolower(rtrim($line, ':'));
        
        return in_array($normalized, ['fachplaner', 'fachplanung']);
    }
}
