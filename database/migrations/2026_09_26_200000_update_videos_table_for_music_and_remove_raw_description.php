<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('videos', function (Blueprint $table) {
            if (Schema::hasColumn('videos', 'raw_description')) {
                $table->dropColumn('raw_description');
            }
            $table->string('intro_music', 255)->nullable()->after('youtube_thumbnail_url');
            $table->string('outro_music', 255)->nullable()->after('intro_music');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('videos', function (Blueprint $table) {
            $table->text('raw_description')->nullable();
            $table->dropColumn(['intro_music', 'outro_music']);
        });
    }
};
