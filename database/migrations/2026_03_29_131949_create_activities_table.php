<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('city')->index();
            $table->string('category')->nullable();
            $table->text('description')->nullable();
            $table->string('location')->nullable();
            $table->string('best_time')->nullable(); // morning, afternoon, evening, sunset, any
            $table->string('duration_estimate')->nullable(); // 1 hour, 2 hours, half day
            $table->string('price_type')->nullable(); // free, low, medium
            $table->json('vibe_tags')->nullable();
            $table->json('occasion_tags')->nullable();
            $table->text('search_text')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activities');
    }
};