<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('restaurants', function (Blueprint $table) {
            $table->json('vibe_tags')->nullable()->after('description');
            $table->json('occasion_tags')->nullable()->after('vibe_tags');
            $table->text('search_text')->nullable()->after('occasion_tags');
        });
    }

    public function down(): void
    {
        Schema::table('restaurants', function (Blueprint $table) {
            $table->dropColumn(['vibe_tags', 'occasion_tags', 'search_text']);
        });
    }
};