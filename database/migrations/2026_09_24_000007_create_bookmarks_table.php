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
        Schema::create('bookmarks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('video_id')->constrained('videos')->cascadeOnDelete();
            $table->foreignId('puzzle_id')->nullable()->constrained('puzzles')->nullOnDelete();
            $table->integer('timestamp_seconds');
            $table->string('label', 150);
            $table->string('tag', 50)->nullable();
            $table->text('notes')->nullable();
            $table->timestampsTz();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookmarks');
    }
};
