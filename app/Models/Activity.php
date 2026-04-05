<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Activity extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'city',
        'category',
        'description',
        'location',
        'best_time',
        'duration_estimate',
        'price_type',
        'vibe_tags',
        'occasion_tags',
        'search_text',
    ];

    protected $casts = [
        'vibe_tags' => 'array',
        'occasion_tags' => 'array',
    ];
}