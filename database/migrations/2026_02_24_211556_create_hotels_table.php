<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::create('hotels', function (Blueprint $table) {
            $table->id();
            $table->string('hotel_name');
            $table->string('hotel_image')->nullable();
            $table->string('hotel_url')->nullable();
            $table->string('address')->nullable();
            $table->string('distance_from_center')->nullable();
            $table->string('nearby_landmark')->nullable();
            $table->string('distance_from_beach')->nullable();
            $table->decimal('rating_score', 4, 2)->nullable();
            $table->text('review_text')->nullable();
            $table->string('room_type')->nullable();
            $table->string('bed_info')->nullable();
            $table->string('price_per_night')->nullable();
            $table->string('taxes_fees')->nullable();
            $table->integer('review_count')->nullable();
            $table->text('stay_details')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }


    public function down(): void
    {
        Schema::dropIfExists('hotels');
    }
};
