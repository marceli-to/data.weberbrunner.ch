<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectImage extends Model
{
    protected $fillable = [
        'project_id',
        'wp_id',
        'filename',
        'title',
        'alt',
        'caption',
        'description',
        'mime_type',
        'width',
        'height',
        'sizes',
        'is_featured',
        'is_content_block',
        'content_block_css',
        'content_block_caption',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'sizes' => 'array',
            'is_featured' => 'boolean',
            'is_content_block' => 'boolean',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function getUrl(string $size = 'large'): ?string
    {
        if ($this->sizes && isset($this->sizes[$size]['file'])) {
            return '/storage/images/' . $this->sizes[$size]['file'];
        }
        return '/storage/images/' . $this->filename;
    }
}
