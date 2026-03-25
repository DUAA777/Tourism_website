<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Hotel extends Model
{
    use HasFactory;

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
    ];

    protected $casts = [
        'rating_score' => 'decimal:2',
        'review_count' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}