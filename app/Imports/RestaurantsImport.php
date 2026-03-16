<?php

namespace App\Imports;

use App\Models\Restaurant;
use Maatwebsite\Excel\Concerns\ToModel;

class RestaurantsImport implements ToModel
{
    public function model(array $row)
    {
        if ($row[0] === 'restaurant_name') {
            return null;
        }

        return new Restaurant([
            'restaurant_name' => $row[0] ?? null,
            'image'           => $row[1] ?? null,
            'rating'          => $row[2] ?? null,
            'restaurant_type' => $row[3] ?? null,
            'tags'            => $row[4] ?? null,
            'location'        => $row[5] ?? null,
            'description'     => $row[6] ?? null,
            'price_tier'      => $row[7] ?? null,
            'food_type'       => $row[8] ?? null,
            'phone_number'    => $row[9] ?? null,
            'opening_hours'   => $row[10] ?? null,
            'website'         => $row[11] ?? null,
            'directory_url'   => $row[12] ?? null,
        ]);
    }
}