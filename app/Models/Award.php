<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Award extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'year',
        'title',
        'publish',
        'position',
    ];

    protected $casts = [
        'year' => 'integer',
        'publish' => 'boolean',
        'position' => 'integer',
    ];
}
