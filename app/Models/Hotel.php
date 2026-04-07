<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes; // Add this line
use Illuminate\Support\Str;

class Hotel extends Model
{
    use HasFactory, SoftDeletes; // Add SoftDeletes here

    protected $table = 'hotels';

    protected $primaryKey = 'id';

    public $timestamps = true;

    protected $fillable = [
        'hotel_name',
        'hotel_image',
        'hotel_url',
        'address',
        'distance_from_center',
        'nearby_landmark',
        'distance_from_beach',
        'rating_score',
        'review_text',
        'room_type',
        'bed_info',
        'price_per_night',
        'taxes_fees',
        'review_count',
        'stay_details',
        'description',
        'vibe_tags',
        'audience_tags',
        'search_text',
    ];

    protected $casts = [
        'rating_score' => 'decimal:2',
        'review_count' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime', // Add this for soft deletes
        'vibe_tags' => 'array',
        'audience_tags' => 'array',
    ];
    
    // Accessors
    public function getFormattedRatingAttribute()
    {
        return number_format($this->rating_score, 1);
    }

    public function getTotalPriceAttribute()
    {
        $price = preg_replace('/[^0-9]/', '', $this->price_per_night);
        $taxes = preg_replace('/[^0-9]/', '', $this->taxes_fees);
        
        return ($price + $taxes) . ' ' . $this->getCurrencySymbol();
    }

    public function getCurrencySymbol()
    {
        return '$'; // You can make this dynamic based on location
    }

    public function getShortDescriptionAttribute()
    {
        return Str::limit($this->description, 150);
    }

    public function getAmenitiesListAttribute()
    {
        $amenities = [];
        if ($this->distance_from_beach) $amenities[] = 'Near Beach';
        if ($this->nearby_landmark) $amenities[] = 'Near Landmarks';
        if ($this->distance_from_center) $amenities[] = 'City Center Access';
        
        return $amenities;
    }

    // Scopes
    public function scopeByRating($query, $minRating = 4.0)
    {
        return $query->where('rating_score', '>=', $minRating);
    }

    public function scopeByPriceRange($query, $min, $max)
    {
        return $query->whereRaw('CAST(REGEXP_REPLACE(price_per_night, "[^0-9]", "") AS UNSIGNED) BETWEEN ? AND ?', [$min, $max]);
    }
}