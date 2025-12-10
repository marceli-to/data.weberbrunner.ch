<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Project;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class ImportProjects extends Command
{
    protected $signature = 'import:projects {--fresh : Delete all existing data first}';
    protected $description = 'Import projects from JSON files';

    public function handle(): int
    {
        if ($this->option('fresh')) {
            $this->info('Clearing existing data...');
            Project::query()->forceDelete();
            Category::query()->forceDelete();
        }

        $files = Storage::disk('public')->files('import/projects');
        $jsonFiles = array_filter($files, fn($file) => str_ends_with($file, '.json'));

        if (empty($jsonFiles)) {
            $this->error('No JSON files found in storage/app/public/import/projects/');
            return Command::FAILURE;
        }

        $this->info('Found ' . count($jsonFiles) . ' JSON files to import.');
        $bar = $this->output->createProgressBar(count($jsonFiles));

        $imported = 0;
        $failed = 0;

        foreach ($jsonFiles as $file) {
            try {
                $content = Storage::disk('public')->get($file);
                $json = json_decode($content, true);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    $this->newLine();
                    $this->warn("Invalid JSON in file: {$file}");
                    $failed++;
                    $bar->advance();
                    continue;
                }

                $this->importProject($json);
                $imported++;
            } catch (\Exception $e) {
                $this->newLine();
                $this->warn("Error importing {$file}: " . $e->getMessage());
                $failed++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("Import completed! Imported: {$imported}, Failed: {$failed}");

        return Command::SUCCESS;
    }

    private function importProject(array $data): void
    {
        // Create or update project
        $project = Project::updateOrCreate(
            ['wp_id' => $data['id']],
            [
                'title' => $data['title'] ?? 'Untitled',
                'slug' => $data['slug'] ?? 'project-' . $data['id'],
                'year' => $data['meta']['year'] ?? null,
                'status' => $data['meta']['status'] ?? null,
                'steckbrief' => $data['meta']['steckbrief'] ?? null,
                'publish_status' => $data['status'] ?? 'publish',
                'menu_order' => $data['menu_order'] ?? 0,
                'color_text_dark' => $data['meta']['color_for_black_text'] ?? null,
                'color_text_light' => $data['meta']['color_for_white_text'] ?? null,
            ]
        );

        // Import categories
        $categoryIds = [];
        foreach ($data['categories'] ?? [] as $cat) {
            $category = Category::firstOrCreate(
                ['slug' => $cat['slug']],
                ['name' => $cat['name']]
            );
            $categoryIds[] = $category->id;
        }
        $project->categories()->sync($categoryIds);

        // Import attachments as images
        $project->images()->delete();
        $featuredImageWpId = $data['meta']['featured_image'] ?? null;

        foreach ($data['attachments'] ?? [] as $index => $attachment) {
            $project->images()->create([
                'wp_id' => $attachment['id'],
                'filename' => $attachment['filename'] ?? '',
                'title' => $attachment['title'] ?? null,
                'alt' => $attachment['alt'] ?? null,
                'caption' => $attachment['caption'] ?? null,
                'description' => $attachment['description'] ?? null,
                'mime_type' => $attachment['mime_type'] ?? null,
                'width' => $attachment['metadata']['width'] ?? null,
                'height' => $attachment['metadata']['height'] ?? null,
                'sizes' => $attachment['metadata']['sizes'] ?? null,
                'is_featured' => ($attachment['id'] == $featuredImageWpId),
                'position' => $index,
            ]);
        }

        // Import content blocks
        $project->texts()->delete();
        $textPosition = 0;

        foreach ($data['content_blocks'] ?? [] as $index => $block) {
            if (in_array($block['type'] ?? '', ['text', 'text_large'])) {
                $project->texts()->create([
                    'type' => $block['type'],
                    'text' => $block['text'] ?? null,
                    'custom_css' => $block['custom_css'] ?? null,
                    'position' => $textPosition++,
                ]);
            }

            // Mark images used in content blocks
            if (($block['type'] ?? '') === 'media' && !empty($block['image'])) {
                $project->images()
                    ->where('wp_id', $block['image'])
                    ->update([
                        'is_content_block' => true,
                        'content_block_css' => $block['custom_css'] ?? null,
                        'content_block_caption' => $block['text'] ?? null,
                        'position' => $index,
                    ]);
            }
        }

        // Import additional meta data
        $project->data()->delete();
        if (!empty($data['meta']['project_notes'])) {
            $project->data()->create(['key' => 'project_notes', 'value' => $data['meta']['project_notes']]);
        }
        if (!empty($data['meta']['related_projects'])) {
            $project->data()->create(['key' => 'related_projects', 'value' => json_encode($data['meta']['related_projects'])]);
        }
        if (!empty($data['meta']['has_featured_video'])) {
            $project->data()->create(['key' => 'has_featured_video', 'value' => $data['meta']['has_featured_video']]);
        }
    }
}
