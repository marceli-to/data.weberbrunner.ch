<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'wp_id',
        'title',
        'slug',
        'year',
        'status',
        'steckbrief',
        'publish_status',
        'menu_order',
        'color_text_dark',
        'color_text_light',
    ];

    public function texts(): HasMany
    {
        return $this->hasMany(ProjectText::class)->orderBy('position');
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProjectImage::class)->orderBy('position');
    }

    public function featuredImage(): HasOne
    {
        return $this->hasOne(ProjectImage::class)->where('is_featured', true);
    }

    public function contentBlockImages(): HasMany
    {
        return $this->hasMany(ProjectImage::class)
            ->where('is_content_block', true)
            ->orderBy('position');
    }

    public function data(): HasMany
    {
        return $this->hasMany(ProjectData::class);
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class);
    }

    public function getDataValue(string $key): ?string
    {
        return $this->data->where('key', $key)->first()?->value;
    }
}
