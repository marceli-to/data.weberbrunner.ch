<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ParseTeamFromHtml extends Command
{
    protected $signature = 'parse:team {--file=content.html : The HTML file to parse}';
    protected $description = 'Parse team members from HTML and export to individual JSON files';

    public function handle()
    {
        $filePath = base_path($this->option('file'));

        if (!File::exists($filePath)) {
            $this->error("File not found: {$filePath}");
            return 1;
        }

        $this->info('Reading HTML file...');
        $html = File::get($filePath);

        $this->info('Parsing team members...');
        $teamMembers = $this->parseTeamMembers($html);

        if (empty($teamMembers)) {
            $this->error('No team members found in the HTML file.');
            return 1;
        }

        $this->info("Found {$this->count($teamMembers)} team members.");

        $this->info('Creating JSON files...');
        $this->createJsonFiles($teamMembers);

        $this->info('Done!');
        return 0;
    }

    private function count($array)
    {
        return count($array);
    }

    private function parseTeamMembers(string $html): array
    {
        $teamMembers = [];

        // Split by content-block-wrap to get individual blocks
        $parts = preg_split('/<div class="content-block-wrap">/', $html);

        foreach ($parts as $part) {
            // Check if this block has both an image srcset and a caption
            if (!preg_match('/data-srcset="([^"]+)"/', $part, $srcsetMatch)) {
                continue;
            }

            if (!preg_match('/<div[^>]*class="content-block__caption"[^>]*>\s*<p>(.*?)<\/p>/s', $part, $captionMatch)) {
                continue;
            }

            $srcset = $srcsetMatch[1];
            $captionHtml = $captionMatch[1];

            // Skip if this doesn't look like a team member (should have name and typically email or title)
            if (!$this->looksLikeTeamMember($captionHtml)) {
                continue;
            }

            $teamMember = $this->parseTeamMemberData($srcset, $captionHtml);
            if ($teamMember) {
                $teamMembers[] = $teamMember;
            }
        }

        return $teamMembers;
    }

    private function looksLikeTeamMember(string $captionHtml): bool
    {
        // Team members typically have:
        // - An email address OR
        // - "Mitarbeit seit" text OR
        // - A title like "dipl." or "M.Sc." or "B.A." etc.
        return preg_match('/@weberbrunner\.(ch|de)|Mitarbeit seit|Zusammenarbeit seit|dipl\.|M\.?\s?Sc|B\.?\s?A\.|M\.?\s?A\.|Lernend|Praktikant|Inhaber|Geschäftsleitung/i', $captionHtml);
    }

    private function parseTeamMemberData(string $srcset, string $captionHtml): ?array
    {
        // Get the largest image (prefer one close to 1130w, or the largest available)
        $image = $this->getBestImage($srcset);

        // Parse the caption HTML
        $data = $this->parseCaptionHtml($captionHtml);

        if (empty($data['name'])) {
            return null;
        }

        return [
            'name' => $data['name'],
            'title' => $data['title'],
            'email' => $data['email'],
            'since' => $data['since'],
            'role' => $data['role'],
            'profile_url' => $data['profile_url'],
            'image' => $image,
        ];
    }

    private function getBestImage(string $srcset): ?string
    {
        // Parse srcset to get all images with their widths
        preg_match_all('/(\S+)\s+(\d+)w/', $srcset, $matches, PREG_SET_ORDER);

        if (empty($matches)) {
            return null;
        }

        $images = [];
        foreach ($matches as $match) {
            $images[] = [
                'url' => $match[1],
                'width' => (int) $match[2],
            ];
        }

        // Sort by width descending
        usort($images, fn($a, $b) => $b['width'] - $a['width']);

        // Look for image with 1130w first
        foreach ($images as $img) {
            if ($img['width'] === 1130) {
                return $img['url'];
            }
        }

        // Otherwise return the largest image
        return $images[0]['url'] ?? null;
    }

    private function parseCaptionHtml(string $html): array
    {
        $data = [
            'name' => null,
            'title' => null,
            'email' => null,
            'since' => null,
            'role' => null,
            'profile_url' => null,
        ];

        // Clean up HTML - convert <br> to newlines for easier parsing
        $text = preg_replace('/<br\s*\/?>/i', "\n", $html);

        // Extract profile URL if present
        if (preg_match('/<a[^>]*href="(https:\/\/weberbrunner\.eu\/profile\/[^"]+)"[^>]*>([^<]+)<\/a>/', $html, $profileMatch)) {
            $data['profile_url'] = $profileMatch[1];
            // Replace the link with just the name in text
            $text = str_replace($profileMatch[0], $profileMatch[2], $text);
        }

        // Extract email
        if (preg_match('/<a[^>]*href="mailto:([^"]+)"[^>]*>[^<]*<\/a>/', $html, $emailMatch)) {
            $data['email'] = $emailMatch[1];
        }

        // Remove all remaining HTML tags
        $text = strip_tags($text);

        // Split by newlines
        $lines = array_map('trim', explode("\n", $text));
        $lines = array_filter($lines);
        $lines = array_values($lines);

        if (empty($lines)) {
            return $data;
        }

        // First line typically contains name and title
        $firstLine = $lines[0];

        // Parse name and title from first line
        // Format is usually: "Name, Title" or just "Name"
        if (preg_match('/^([^,]+),\s*(.+)$/', $firstLine, $nameMatch)) {
            $data['name'] = trim($nameMatch[1]);
            $data['title'] = trim($nameMatch[2]);
        } else {
            $data['name'] = trim($firstLine);
        }

        // Process remaining lines
        foreach ($lines as $i => $line) {
            if ($i === 0) continue;

            // Check for "Mitarbeit seit" or "Zusammenarbeit seit"
            if (preg_match('/(Mitarbeit|Zusammenarbeit)\s+seit\s+(\d{4})/', $line, $sinceMatch)) {
                $data['since'] = (int) $sinceMatch[2];
                continue;
            }

            // Check for role keywords
            if (preg_match('/Inhaber|Geschäftsleitung|Mitglied der Geschäftsleitung/i', $line)) {
                $data['role'] = trim($line);
                continue;
            }

            // If title is empty and this looks like a title continuation (university, etc.)
            if ($data['title'] && preg_match('/^(TU|ETH|FH|Uni|RWTH|Hochschule)/i', $line)) {
                $data['title'] .= ' ' . trim($line);
            }
        }

        return $data;
    }

    private function createJsonFiles(array $teamMembers): void
    {
        $outputDir = storage_path('app/public/import/team');

        // Create directory if it doesn't exist
        if (!File::exists($outputDir)) {
            File::makeDirectory($outputDir, 0755, true);
        } else {
            // Clear existing files
            File::cleanDirectory($outputDir);
        }

        foreach ($teamMembers as $member) {
            $slug = Str::slug($member['name']);
            $filename = "{$slug}.json";
            $filepath = "{$outputDir}/{$filename}";

            File::put($filepath, json_encode($member, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

            $this->info("Created: {$filename}");
        }

        // Create summary file
        $summary = [
            'total_members' => count($teamMembers),
            'generated_at' => now()->toISOString(),
            'members' => array_map(fn($m) => [
                'name' => $m['name'],
                'slug' => Str::slug($m['name']),
                'email' => $m['email'],
                'since' => $m['since'],
            ], $teamMembers),
        ];

        File::put("{$outputDir}/summary.json", json_encode($summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $this->info("Created: summary.json");
    }
}
