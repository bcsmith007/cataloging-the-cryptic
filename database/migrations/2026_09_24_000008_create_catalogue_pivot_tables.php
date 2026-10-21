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
        Schema::create('video_hosts', function (Blueprint $table) {
            $table->foreignId('video_id')->constrained('videos')->cascadeOnDelete();
            $table->foreignId('host_id')->constrained('hosts')->cascadeOnDelete();
            $table->primary(['video_id', 'host_id']);
        });

        Schema::create('video_puzzles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('video_id')->constrained('videos')->cascadeOnDelete();
            $table->foreignId('puzzle_id')->constrained('puzzles')->cascadeOnDelete();
            $table->smallInteger('order_in_video')->default(1);
            $table->integer('start_seconds')->nullable();
            $table->integer('solve_duration_seconds')->nullable();
            $table->unique(['video_id', 'puzzle_id']);
            $table->timestampsTz();
        });

        Schema::create('puzzle_setters', function (Blueprint $table) {
            $table->foreignId('puzzle_id')->constrained('puzzles')->cascadeOnDelete();
            $table->foreignId('setter_id')->constrained('setters')->cascadeOnDelete();
            $table->primary(['puzzle_id', 'setter_id']);
        });

        Schema::create('puzzle_constraints', function (Blueprint $table) {
            $table->foreignId('puzzle_id')->constrained('puzzles')->cascadeOnDelete();
            $table->foreignId('constraint_id')->constrained('constraints')->cascadeOnDelete();
            $table->primary(['puzzle_id', 'constraint_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('puzzle_constraints');
        Schema::dropIfExists('puzzle_setters');
        Schema::dropIfExists('video_puzzles');
        Schema::dropIfExists('video_hosts');
    }
};
