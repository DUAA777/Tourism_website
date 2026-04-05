<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        $columns = [
            'restaurant_name' => fn (Blueprint $table) => $table->string('restaurant_name')->nullable(),
            'image' => fn (Blueprint $table) => $table->text('image')->nullable(),
            'rating' => fn (Blueprint $table) => $table->decimal('rating', 3, 1)->nullable(),
            'restaurant_type' => fn (Blueprint $table) => $table->string('restaurant_type')->nullable(),
            'tags' => fn (Blueprint $table) => $table->text('tags')->nullable(),
            'location' => fn (Blueprint $table) => $table->string('location')->nullable(),
            'description' => fn (Blueprint $table) => $table->text('description')->nullable(),
            'price_tier' => fn (Blueprint $table) => $table->string('price_tier')->nullable(),
            'food_type' => fn (Blueprint $table) => $table->string('food_type')->nullable(),
            'phone_number' => fn (Blueprint $table) => $table->string('phone_number')->nullable(),
            'opening_hours' => fn (Blueprint $table) => $table->text('opening_hours')->nullable(),
            'website' => fn (Blueprint $table) => $table->text('website')->nullable(),
            'directory_url' => fn (Blueprint $table) => $table->text('directory_url')->nullable(),
        ];

        foreach ($columns as $column => $definition) {
            if (Schema::hasColumn('restaurants', $column)) {
                continue;
            }

            Schema::table('restaurants', function (Blueprint $table) use ($definition) {
                $definition($table);
            });
        }
    }

    public function down(): void
    {
        $columns = [
            'restaurant_name', 'image', 'rating', 'restaurant_type', 'tags', 'location',
            'description', 'price_tier', 'food_type', 'phone_number', 'opening_hours', 'website', 'directory_url',
        ];

        foreach ($columns as $column) {
            if (!Schema::hasColumn('restaurants', $column)) {
                continue;
            }

            Schema::table('restaurants', function (Blueprint $table) use ($column) {
                $table->dropColumn($column);
            });
        }
    }
};
