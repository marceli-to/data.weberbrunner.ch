<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TeamMember extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'title',
        'email',
        'since',
        'role',
        'profile_url',
        'image',
        'publish',
        'position',
    ];

    protected $casts = [
        'since' => 'integer',
        'publish' => 'boolean',
        'position' => 'integer',
    ];
}
