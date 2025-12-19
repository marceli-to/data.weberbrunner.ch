<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RawDataMeta extends Model
{
    protected $table = 'raw_data_meta';

    protected $fillable = [
        'raw_data_id',
        'label',
        'value',
        'position',
    ];

    public function rawData(): BelongsTo
    {
        return $this->belongsTo(RawData::class);
    }
}
