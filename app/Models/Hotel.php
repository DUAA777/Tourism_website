<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class Hotel extends Model
{
    use HasFactory, SoftDeletes;

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
        'deleted_at' => 'datetime',
        'vibe_tags' => 'array',
        'audience_tags' => 'array',
    ];

    /**
     * Returns a database-safe SQL expression for extracting a numeric hotel price.
     *
     * SQLite does not support REGEXP_REPLACE, while MySQL does.
     * This keeps the price filters working locally with SQLite and online with MySQL.
     */
    public static function numericPriceExpression(): string
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            return "CAST(
                REPLACE(
                    REPLACE(
                        REPLACE(
                            REPLACE(
                                REPLACE(price_per_night, '$', ''),
                            ',', ''),
                        'USD', ''),
                    'usd', ''),
                ' ', '')
            AS INTEGER)";
        }

        return "CAST(REGEXP_REPLACE(price_per_night, '[^0-9]', '') AS UNSIGNED)";
    }

    // Accessors
    public function getFormattedRatingAttribute()
    {
        return number_format((float) $this->rating_score, 1);
    }

    public function getTotalPriceAttribute()
    {
        $price = preg_replace('/[^0-9]/', '', (string) $this->price_per_night);
        $taxes = preg_replace('/[^0-9]/', '', (string) $this->taxes_fees);

        $price = $price !== '' ? (int) $price : 0;
        $taxes = $taxes !== '' ? (int) $taxes : 0;

        return ($price + $taxes) . ' ' . $this->getCurrencySymbol();
    }

    public function getCurrencySymbol()
    {
        return '$';
    }

    public function getShortDescriptionAttribute()
    {
        return Str::limit((string) $this->description, 150);
    }

    public function getAmenitiesListAttribute()
    {
        $amenities = [];

        if ($this->distance_from_beach) {
            $amenities[] = 'Near Beach';
        }

        if ($this->nearby_landmark) {
            $amenities[] = 'Near Landmarks';
        }

        if ($this->distance_from_center) {
            $amenities[] = 'City Center Access';
        }

        return $amenities;
    }

    // Scopes
    public function scopeByRating($query, $minRating = 4.0)
    {
        return $query->where('rating_score', '>=', $minRating);
    }

    public function scopeByPriceRange($query, $min, $max)
    {
        return $query->whereRaw(
            self::numericPriceExpression() . ' BETWEEN ? AND ?',
            [$min, $max]
        );
    }
}