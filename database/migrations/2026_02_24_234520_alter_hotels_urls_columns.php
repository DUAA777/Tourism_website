<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('hotels', function (Blueprint $table) {
            $table->text('hotel_url')->nullable()->change();
            $table->text('hotel_image')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Schema::table('hotels', function (Blueprint $table) {
        //     $table->string('hotel_url', 255)->nullable()->change();
        //     $table->string('hotel_image', 255)->nullable()->change();
        // });
    }
};