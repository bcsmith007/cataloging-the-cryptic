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
        Schema::create('videos', function (Blueprint $table) {
            $table->id();
            $table->string('youtube_video_id', 32)->nullable()->unique();
            $table->string('access_level', 20)->default('public');
            $table->string('patreon_post_url', 512)->nullable();
            $table->string('title', 255);
            $table->string('slug', 280)->unique();
            $table->timestampTz('published_at');
            $table->integer('duration_seconds')->default(0);
            $table->bigInteger('view_count')->default(0);
            $table->timestampTz('stats_synced_at')->nullable();
            $table->string('youtube_thumbnail_url', 512)->nullable();
            $table->text('raw_description')->nullable();
            $table->timestampsTz();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('videos');
    }
};
