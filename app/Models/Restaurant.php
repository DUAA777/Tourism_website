<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Restaurant extends Model
{
    use HasFactory;

    protected $fillable = [
        'restaurant_name',
        'image',
        'rating',
        'restaurant_type',
        'tags',
        'location',
        'description',
        'price_tier',
        'food_type',
        'phone_number',
        'opening_hours',
        'website',
        'directory_url',
        'vibe_tags',
        'occasion_tags',
        'search_text',
    ];

    protected $casts = [
        'rating' => 'decimal:1',
        'tags' => 'array',
        'vibe_tags',
        'occasion_tags',
        'search_text',
    ];

    public function getTagsArrayAttribute()
    {
        if (is_string($this->tags)) {
            return json_decode($this->tags, true) ?? [];
        }
        return $this->tags ?? [];
    }

    public function getSimilarRestaurants($limit = 3)
    {
        return self::where('id', '!=', $this->id)
            ->where(function($query) {
                $query->where('food_type', $this->food_type)
                    ->orWhere('restaurant_type', $this->restaurant_type)
                    ->orWhere('price_tier', $this->price_tier);
            })
            ->inRandomOrder()
            ->limit($limit)
            ->get();
    }
}