<?php

namespace App\Console\Commands;

use App\Models\Project;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class ExtractProjectNumbers extends Command
{
    protected $signature = 'projects:extract-numbers
                            {--dry-run : Preview changes without saving}
                            {--force : Update even if project already has a number}';

    protected $description = 'Extract project numbers from image filenames';

    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');

        if ($dryRun) {
            $this->info('DRY RUN - no changes will be saved');
        }

        $query = Project::with('images')->has('images');

        if (!$force) {
            $query->where(function ($q) {
                $q->whereNull('number')->orWhere('number', '');
            });
        }

        $projects = $query->get();

        $this->info("Found {$projects->count()} projects to process");

        $updated = 0;
        $skipped = 0;
        $skippedProjects = [];

        foreach ($projects as $project) {
            $number = $this->extractNumberFromImages($project->images);

            if ($number && $this->isValidNumber($number)) {
                $this->line("Project #{$project->id} \"{$project->title}\" → Number: {$number}");

                if (!$dryRun) {
                    $project->update(['number' => $number]);
                }
                $updated++;
            } elseif ($number) {
                $this->warn("Project #{$project->id} \"{$project->title}\" → Invalid number: {$number}");
                $skippedProjects[] = "Project #{$project->id} \"{$project->title}\" → Invalid number: {$number}";
                if (!$dryRun) {
                    $project->update(['number' => null]);
                }
                $skipped++;
            } else {
                $this->warn("Project #{$project->id} \"{$project->title}\" → No number found in images");
                $skippedProjects[] = "Project #{$project->id} \"{$project->title}\" → No number found in images";
                $skipped++;
            }
        }

        // Write skipped projects to file
        if (!$dryRun && count($skippedProjects) > 0) {
            $content = "Projects without numbers - " . now()->format('Y-m-d H:i:s') . "\n";
            $content .= str_repeat('=', 60) . "\n\n";
            $content .= implode("\n", $skippedProjects);

            Storage::put('projects-without-numbers.txt', $content);
            $this->newLine();
            $this->comment('Skipped projects written to storage/app/projects-without-numbers.txt');
        }

        $this->newLine();
        $this->info("Updated: {$updated}");
        $this->info("Skipped: {$skipped}");

        if ($dryRun && $updated > 0) {
            $this->newLine();
            $this->comment('Run without --dry-run to apply changes');
        }

        return Command::SUCCESS;
    }

    private function extractNumberFromImages($images): ?string
    {
        foreach ($images as $image) {
            $filename = $image->filename;

            // Check if filename starts with digits
            if (preg_match('/^(\d+)/', $filename, $matches)) {
                return $matches[1];
            }
        }

        return null;
    }

    private function isValidNumber(string $number): bool
    {
        $num = (int) $number;

        // Valid ranges: 1-999 or 1000-9999
        return ($num >= 1 && $num <= 999) || ($num >= 1000 && $num <= 9999);
    }
}
