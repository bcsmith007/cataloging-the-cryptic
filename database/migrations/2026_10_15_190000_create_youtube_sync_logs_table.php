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
        Schema::create('youtube_sync_logs', function (Blueprint $table) {
            $table->id();
            $table->string('status', 30)->default('success');
            $table->timestampTz('started_at');
            $table->timestampTz('finished_at')->nullable();
            $table->integer('duration_ms')->nullable();
            $table->integer('new_videos_count')->default(0);
            $table->integer('updated_views_count')->default(0);
            $table->integer('total_processed')->default(0);
            $table->string('message', 500)->nullable();
            $table->json('details')->nullable();
            $table->timestampsTz();

            $table->index('started_at');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('youtube_sync_logs');
    }
};
