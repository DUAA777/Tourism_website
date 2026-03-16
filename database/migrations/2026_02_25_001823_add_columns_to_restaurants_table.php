<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('restaurants', function (Blueprint $table) {
            $table->string('restaurant_name')->nullable();
            $table->text('image')->nullable();
            $table->decimal('rating', 3, 1)->nullable();
            $table->string('restaurant_type')->nullable();
            $table->text('tags')->nullable();
            $table->string('location')->nullable();
            $table->text('description')->nullable();
            $table->string('price_tier')->nullable();
            $table->string('food_type')->nullable();
            $table->string('phone_number')->nullable();
            $table->text('opening_hours')->nullable();
            $table->text('website')->nullable();
            $table->text('directory_url')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('restaurants', function (Blueprint $table) {
            $table->dropColumn([
                'restaurant_name','image','rating','restaurant_type','tags','location',
                'description','price_tier','food_type','phone_number','opening_hours','website','directory_url'
            ]);
        });
    }
};