<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class ImportWordPressProjects extends Command
{
    protected $signature = 'import:wordpress-projects';
    protected $description = 'Import WordPress projects from database to JSON files';

    public function handle()
    {
        $this->info('Connecting to WordPress database...');

        // Test connection first
        try {
            $count = DB::select('SELECT COUNT(*) as count FROM weberbrunner_2017_posts WHERE post_type = ? AND post_status = ?', ['project', 'publish']);
            $this->info('Found ' . $count[0]->count . ' published projects');
        } catch (\Exception $e) {
            $this->error('Database connection failed: ' . $e->getMessage());
            return 1;
        }

        $this->info('Extracting project data...');
        $projects = $this->extractProjects();

        $this->info('Creating JSON files...');
        $this->createJsonFiles($projects);

        $this->info('Import completed successfully!');
        return 0;
    }

    private function extractProjects()
    {
        // Get all published projects
        $posts = DB::select('
            SELECT * FROM weberbrunner_2017_posts
            WHERE post_type = ? AND post_status = ?
            ORDER BY menu_order ASC
        ', ['project', 'publish']);

        $this->info('Processing ' . count($posts) . ' projects...');

        $projects = [];

        foreach ($posts as $post) {
            $projectId = $post->ID;

            // Get all post meta
            $meta = $this->getPostMeta($projectId);

            // Get project categories/taxonomies
            $categories = $this->getProjectCategories($projectId);

            // Parse ACF content blocks
            $contentBlocks = $this->parseContentBlocks($meta);

            // Get attachments
            $attachments = $this->getProjectAttachments($projectId);

            $project = [
                'id' => $projectId,
                'title' => $post->post_title,
                'slug' => $post->post_name,
                'content' => $post->post_content,
                'excerpt' => $post->post_excerpt,
                'date' => $post->post_date,
                'modified' => $post->post_modified,
                'status' => $post->post_status,
                'menu_order' => (int)$post->menu_order,
                'guid' => $post->guid,
                'meta' => $meta,
                'categories' => $categories,
                'content_blocks' => $contentBlocks,
                'attachments' => $attachments,
                'projektstatus' => $meta['projektstatus'] ?? null,
            ];

            $projects[] = $project;
        }

        return $projects;
    }

    private function getPostMeta($postId)
    {
        $metaRows = DB::select('
            SELECT meta_key, meta_value
            FROM weberbrunner_2017_postmeta
            WHERE post_id = ?
        ', [$postId]);

        $meta = [];
        foreach ($metaRows as $row) {
            $value = $row->meta_value;

            // Try to unserialize WordPress serialized data
            if ($this->isSerialized($value)) {
                $unserialized = @unserialize($value);
                if ($unserialized !== false) {
                    $value = $unserialized;
                }
            }

            $meta[$row->meta_key] = $value;
        }

        return $meta;
    }

    private function getProjectCategories($postId)
    {
        $categories = DB::select('
            SELECT t.name, t.slug, tt.taxonomy
            FROM weberbrunner_2017_term_relationships tr
            JOIN weberbrunner_2017_term_taxonomy tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
            JOIN weberbrunner_2017_terms t ON tt.term_id = t.term_id
            WHERE tr.object_id = ?
        ', [$postId]);

        $result = [];
        foreach ($categories as $cat) {
            $result[] = [
                'taxonomy' => $cat->taxonomy,
                'name' => $cat->name,
                'slug' => $cat->slug,
            ];
        }

        return $result;
    }

    private function parseContentBlocks($meta)
    {
        $contentBlocks = [];
        $blockIndex = 0;

        while (isset($meta["content_blocks_{$blockIndex}_type"])) {
            $block = [
                'type' => $meta["content_blocks_{$blockIndex}_type"],
                'image' => $meta["content_blocks_{$blockIndex}_image"] ?? null,
                'video' => $meta["content_blocks_{$blockIndex}_video"] ?? null,
                'text' => $meta["content_blocks_{$blockIndex}_text"] ?? null,
                'custom_css' => $meta["content_blocks_{$blockIndex}_custom_css"] ?? null,
            ];

            // Get image details if image ID exists
            if ($block['image']) {
                $imageDetails = $this->getAttachmentDetails($block['image']);
                if ($imageDetails) {
                    $block['image_details'] = $imageDetails;
                }
            }

            $contentBlocks[] = $block;
            $blockIndex++;
        }

        return $contentBlocks;
    }

    private function getProjectAttachments($projectId)
    {
        $attachments = DB::select('
            SELECT * FROM weberbrunner_2017_posts
            WHERE post_type = ? AND post_parent = ?
            ORDER BY menu_order ASC
        ', ['attachment', $projectId]);

        $result = [];
        foreach ($attachments as $attachment) {
            $attachmentMeta = $this->getPostMeta($attachment->ID);

            $result[] = [
                'id' => $attachment->ID,
                'title' => $attachment->post_title,
                'filename' => $attachmentMeta['_wp_attached_file'] ?? '',
                'alt' => $attachmentMeta['_wp_attachment_image_alt'] ?? '',
                'caption' => $attachment->post_excerpt,
                'description' => $attachment->post_content,
                'mime_type' => $attachment->post_mime_type,
                'date' => $attachment->post_date,
                'metadata' => $attachmentMeta['_wp_attachment_metadata'] ?? null,
            ];
        }

        return $result;
    }

    private function getAttachmentDetails($attachmentId)
    {
        $attachment = DB::select('
            SELECT * FROM weberbrunner_2017_posts
            WHERE ID = ? AND post_type = ?
        ', [$attachmentId, 'attachment']);

        if (empty($attachment)) {
            return null;
        }

        $attachment = $attachment[0];
        $attachmentMeta = $this->getPostMeta($attachmentId);

        return [
            'id' => $attachment->ID,
            'title' => $attachment->post_title,
            'alt' => $attachmentMeta['_wp_attachment_image_alt'] ?? '',
            'filename' => $attachmentMeta['_wp_attached_file'] ?? '',
            'mime_type' => $attachment->post_mime_type,
            'metadata' => $attachmentMeta['_wp_attachment_metadata'] ?? null,
        ];
    }

    private function createJsonFiles($projects)
    {
        $importDir = storage_path('app/public/import/projects');

        // Clear existing files
        if (File::exists($importDir)) {
            File::cleanDirectory($importDir);
        } else {
            File::makeDirectory($importDir, 0755, true);
        }

        foreach ($projects as $project) {
            $filename = $project['id'] . '-' . $project['slug'] . '.json';
            $filepath = $importDir . '/' . $filename;

            File::put($filepath, json_encode($project, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            $this->info("Created: {$filename}");
        }

        // Create summary file
        $summary = [
            'total_projects' => count($projects),
            'generated_at' => now()->toISOString(),
            'projects' => array_map(function($project) {
                return [
                    'id' => $project['id'],
                    'title' => $project['title'],
                    'slug' => $project['slug'],
                    'status' => $project['projektstatus'],
                    'categories' => array_column($project['categories'], 'name'),
                    'content_blocks_count' => count($project['content_blocks']),
                    'attachments_count' => count($project['attachments']),
                    'menu_order' => $project['menu_order'],
                ];
            }, $projects),
        ];

        File::put($importDir . '/summary.json', json_encode($summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $this->info("Created: summary.json");
    }

    private function isSerialized($data)
    {
        if (!is_string($data)) {
            return false;
        }

        $data = trim($data);
        if ('N;' === $data) {
            return true;
        }

        if (!preg_match('/^([adObis]):/', $data, $badions)) {
            return false;
        }

        switch ($badions[1]) {
            case 'a':
            case 'O':
            case 's':
                if (preg_match("/^{$badions[1]}:[0-9]+:/s", $data)) {
                    return true;
                }
                break;
            case 'b':
            case 'i':
            case 'd':
                if (preg_match("/^{$badions[1]}:[0-9.E+-]+;$/", $data)) {
                    return true;
                }
                break;
        }

        return false;
    }
}
