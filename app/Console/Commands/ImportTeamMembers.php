<?php

namespace App\Console\Commands;

use App\Models\TeamMember;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ImportTeamMembers extends Command
{
    protected $signature = 'import:team {--clear : Clear existing team members before import}';
    protected $description = 'Import team members from JSON files';

    public function handle()
    {
        $importDir = storage_path('app/public/import/team');

        if (!File::exists($importDir)) {
            $this->error("Import directory not found: {$importDir}");
            $this->info('Run "php artisan parse:team" first to generate the JSON files.');
            return 1;
        }

        if ($this->option('clear')) {
            $this->info('Clearing existing team members...');
            TeamMember::query()->forceDelete();
        }

        $files = File::glob("{$importDir}/*.json");
        $files = array_filter($files, fn($f) => basename($f) !== 'summary.json');

        $this->info('Importing ' . count($files) . ' team members...');

        $position = 0;
        foreach ($files as $file) {
            $data = json_decode(File::get($file), true);

            if (empty($data['name'])) {
                $this->warn("Skipping {$file}: No name found");
                continue;
            }

            $slug = Str::slug($data['name']);

            // Check if member already exists
            $existing = TeamMember::where('slug', $slug)->first();
            if ($existing) {
                $this->line("Updating: {$data['name']}");
                $existing->update([
                    'name' => $data['name'],
                    'title' => $data['title'] ?? null,
                    'email' => $data['email'] ?? null,
                    'since' => $data['since'] ?? null,
                    'role' => $data['role'] ?? null,
                    'profile_url' => $data['profile_url'] ?? null,
                    'image' => $data['image'] ?? null,
                ]);
            } else {
                $this->line("Creating: {$data['name']}");
                TeamMember::create([
                    'name' => $data['name'],
                    'slug' => $slug,
                    'title' => $data['title'] ?? null,
                    'email' => $data['email'] ?? null,
                    'since' => $data['since'] ?? null,
                    'role' => $data['role'] ?? null,
                    'profile_url' => $data['profile_url'] ?? null,
                    'image' => $data['image'] ?? null,
                    'publish' => true,
                    'position' => $position++,
                ]);
            }
        }

        $this->info('Import completed! ' . TeamMember::count() . ' team members in database.');
        return 0;
    }
}
