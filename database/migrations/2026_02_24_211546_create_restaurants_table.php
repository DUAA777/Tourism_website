<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::create('restaurants', function (Blueprint $table) {
            $table->id();

            $table->string('restaurant_name');
            $table->string('image')->nullable();
            $table->decimal('rating', 3, 1)->nullable();
            $table->string('restaurant_type')->nullable();
            $table->text('tags')->nullable();
            $table->string('location')->nullable();
            $table->text('description')->nullable();
            $table->string('price_tier')->nullable();
            $table->string('food_type')->nullable();
            $table->string('phone_number')->nullable();
            $table->text('opening_hours')->nullable();
            $table->string('website')->nullable();
            $table->string('directory_url')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('restaurants');
    }
};
