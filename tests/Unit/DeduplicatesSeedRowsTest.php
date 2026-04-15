<?php

namespace Tests\Unit;

use Database\Seeders\Concerns\DeduplicatesSeedRows;
use PHPUnit\Framework\TestCase;

class DeduplicatesSeedRowsTest extends TestCase
{
    public function test_it_collapses_duplicate_rows_by_normalized_name_and_location(): void
    {
        $subject = $this->makeSubject();

        $rows = [
            [
                'hotel_name' => 'Aqualand Hotel & Resort',
                'address' => 'Byblos',
                'description' => 'Short description',
            ],
            [
                'hotel_name' => 'Aqualand Hotel Resort',
                'address' => 'Byblos;',
                'description' => 'A much longer and more complete description for the same hotel entry.',
                'price_per_night' => '$120',
            ],
        ];

        $result = $subject->deduplicate($rows, 'hotel_name', 'address', 'hotel');

        $this->assertCount(1, $result);
        $this->assertSame('Aqualand Hotel Resort', $result[0]['hotel_name']);
        $this->assertSame('$120', $result[0]['price_per_night']);
    }

    public function test_it_keeps_distinct_rows_when_same_name_has_different_locations(): void
    {
        $subject = $this->makeSubject();

        $rows = [
            [
                'restaurant_name' => 'Harbor House',
                'location' => 'Batroun',
            ],
            [
                'restaurant_name' => 'Harbor House',
                'location' => 'Byblos',
            ],
        ];

        $result = $subject->deduplicate($rows, 'restaurant_name', 'location', 'restaurant');

        $this->assertCount(2, $result);
    }

    public function test_it_removes_duplicate_tag_values_inside_rows(): void
    {
        $subject = $this->makeSubject();

        $rows = [
            [
                'restaurant_name' => 'Sea Deck',
                'location' => 'Beirut',
                'tags' => ['Sea View', 'sea view', ' Romantic ', 'Romantic'],
            ],
        ];

        $result = $subject->deduplicate($rows, 'restaurant_name', 'location', 'restaurant');

        $this->assertSame(['Sea View', 'Romantic'], $result[0]['tags']);
    }

    private function makeSubject(): object
    {
        return new class {
            use DeduplicatesSeedRows {
                deduplicateSeedRows as public deduplicate;
            }

            public $command = null;
        };
    }
}
