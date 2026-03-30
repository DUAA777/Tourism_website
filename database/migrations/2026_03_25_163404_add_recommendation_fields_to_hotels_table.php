<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hotels', function (Blueprint $table) {
            $table->json('vibe_tags')->nullable()->after('description');
            $table->json('audience_tags')->nullable()->after('vibe_tags');
            $table->text('search_text')->nullable()->after('audience_tags');
        });
    }

    public function down(): void
    {
        Schema::table('hotels', function (Blueprint $table) {
            $table->dropColumn(['vibe_tags', 'audience_tags', 'search_text']);
        });
    }
};