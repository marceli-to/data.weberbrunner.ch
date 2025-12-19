<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class RawData extends Model
{
    use SoftDeletes;
    protected $table = 'raw_data';

    protected $fillable = [
        'number',
        'title',
    ];

    public function meta(): HasMany
    {
        return $this->hasMany(RawDataMeta::class)->orderBy('position');
    }

    public function attributes(): HasMany
    {
        return $this->hasMany(RawDataAttribute::class)->orderBy('position');
    }
}
