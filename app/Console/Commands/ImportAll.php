<?php

namespace App\Console\Commands;

use App\Models\Award;
use App\Models\Category;
use App\Models\Jury;
use App\Models\Lecture;
use App\Models\Project;
use App\Models\RawData;
use App\Models\TeamMember;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Process;

class ImportAll extends Command
{
    protected $signature = 'import:all {--fresh : Clear all import tables before importing} {--no-backup : Skip database backup}';
    protected $description = 'Run all import commands in the correct order';

    public function handle(): int
    {
        $this->info('Starting full import...');
        $this->newLine();

        // Create backup before clearing (unless --no-backup is set)
        if ($this->option('fresh') && !$this->option('no-backup')) {
            $this->info('Creating database backup...');
            if (!$this->createDatabaseBackup()) {
                if (!$this->confirm('Backup failed. Continue anyway?')) {
                    return Command::FAILURE;
                }
            }
            $this->newLine();
        }

        // Optionally clear all import tables
        if ($this->option('fresh')) {
            $this->warn('Clearing import tables...');
            $this->clearImportTables();
            $this->newLine();
        }

        $commands = [
            ['command' => 'import:projects', 'options' => [], 'description' => 'Importing projects...'],
            ['command' => 'projects:extract-numbers', 'options' => [], 'description' => 'Extracting project numbers from images...'],
            ['command' => 'import:team', 'options' => [], 'description' => 'Importing team members...'],
            ['command' => 'import:awards', 'options' => [], 'description' => 'Importing awards...'],
            ['command' => 'import:jury', 'options' => [], 'description' => 'Importing jury entries...'],
            ['command' => 'import:lectures', 'options' => [], 'description' => 'Importing lectures...'],
            ['command' => 'import:raw-data', 'options' => [], 'description' => 'Importing raw data...'],
        ];

        foreach ($commands as $cmd) {
            $this->info($cmd['description']);
            $exitCode = $this->call($cmd['command'], $cmd['options']);
            
            if ($exitCode !== 0) {
                $this->error("Command {$cmd['command']} failed with exit code {$exitCode}");
                return Command::FAILURE;
            }
            
            $this->newLine();
        }

        $this->info('✓ All imports completed successfully!');
        
        return Command::SUCCESS;
    }

    private function clearImportTables(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        // Projects and related tables
        DB::table('project_attributes')->truncate();
        DB::table('project_data')->truncate();
        DB::table('project_images')->truncate();
        DB::table('project_texts')->truncate();
        DB::table('category_project')->truncate();
        Project::query()->forceDelete();
        Category::query()->forceDelete();

        // Other entities
        TeamMember::query()->forceDelete();
        Award::query()->forceDelete();
        Jury::query()->forceDelete();
        Lecture::query()->forceDelete();

        // Raw data
        DB::table('raw_data_attributes')->truncate();
        DB::table('raw_data_meta')->truncate();
        RawData::query()->forceDelete();

        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $this->line('  ✓ Cleared: projects, categories, team_members, awards, jury, lectures, raw_data');
    }

    private function createDatabaseBackup(): bool
    {
        $database = config('database.connections.mysql.database');
        $username = config('database.connections.mysql.username');
        $password = config('database.connections.mysql.password');
        $socket = config('database.connections.mysql.unix_socket');

        $backupDir = storage_path('app/backups');
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        $filename = "backup_" . date('Y-m-d_His') . ".sql";
        $filepath = "{$backupDir}/{$filename}";

        // Use socket if available (MAMP), otherwise use host/port
        if ($socket) {
            $command = sprintf(
                'mysqldump --socket=%s -u%s -p%s %s > %s',
                escapeshellarg($socket),
                escapeshellarg($username),
                escapeshellarg($password),
                escapeshellarg($database),
                escapeshellarg($filepath)
            );
        } else {
            $host = config('database.connections.mysql.host');
            $port = config('database.connections.mysql.port', 3306);
            $command = sprintf(
                'mysqldump -h%s -P%s -u%s -p%s %s > %s',
                escapeshellarg($host),
                escapeshellarg($port),
                escapeshellarg($username),
                escapeshellarg($password),
                escapeshellarg($database),
                escapeshellarg($filepath)
            );
        }

        $result = Process::run($command);

        if ($result->successful() && file_exists($filepath) && filesize($filepath) > 0) {
            $this->line("  ✓ Backup saved to: storage/app/backups/{$filename}");
            return true;
        }

        $this->error("  ✗ Backup failed");
        if ($result->errorOutput()) {
            $this->error("  " . $result->errorOutput());
        }
        return false;
    }
}
