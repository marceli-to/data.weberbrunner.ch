<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RawDataAttribute extends Model
{
    protected $table = 'raw_data_attributes';

    protected $fillable = [
        'raw_data_id',
        'group_key',
        'label',
        'value',
        'position',
    ];

    public function rawData(): BelongsTo
    {
        return $this->belongsTo(RawData::class);
    }
}
