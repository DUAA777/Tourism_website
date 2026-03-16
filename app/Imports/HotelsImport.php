<?php

namespace App\Imports;

use App\Models\Hotel;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

class HotelsImport implements ToCollection
{
    public function collection(Collection $rows)
    {
        foreach ($rows->skip(1) as $row) { // skip header row

            $hotelName = $row[0] ?? null;
            if (!$hotelName) continue;

            // "1,170 reviews" -> 1170
            $reviewCountRaw = $row[13] ?? null;
            $reviewCount = null;
            if ($reviewCountRaw !== null) {
                $digits = preg_replace('/[^\d]/', '', (string) $reviewCountRaw);
                $reviewCount = $digits !== '' ? (int) $digits : null;
            }

            Hotel::create([
                'hotel_name' => $hotelName,
                'hotel_image' => $row[1] ?? null,
                'hotel_url' => $row[2] ?? null,
                'address' => $row[3] ?? null,
                'distance_from_center' => $row[4] ?? null,
                'nearby_landmark' => $row[5] ?? null,
                'distance_from_beach' => $row[6] ?? null,
                'rating_score' => $row[7] ?? null,
                'review_text' => $row[8] ?? null,
                'room_type' => $row[9] ?? null,
                'bed_info' => $row[10] ?? null,
                'price_per_night' => $row[11] ?? null,
                'taxes_fees' => $row[12] ?? null,
                'review_count' => $reviewCount,
                'stay_details' => $row[14] ?? null,
                'description' => $row[15] ?? null,
            ]);
        }
    }
}